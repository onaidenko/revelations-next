<?php
/**
 * Plugin Name: REVELATIONS Editorial Art Scanner
 * Description: Dry-run RSS scoring rules for Art candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

function revelations_editorial_art_profile(): array {
    return function_exists( 'revelations_editorial_get_scanner_profile' )
        ? revelations_editorial_get_scanner_profile( 'art' )
        : array();
}

function revelations_editorial_art_sources(): array {
    $profile = revelations_editorial_art_profile();
    return is_array( $profile['active_sources'] ?? null ) ? array_values( $profile['active_sources'] ) : array();
}

function revelations_editorial_art_disabled_sources(): array {
    $profile = revelations_editorial_art_profile();
    return is_array( $profile['disabled_sources'] ?? null ) ? array_values( $profile['disabled_sources'] ) : array();
}

function revelations_editorial_art_keywords(): array {
    $profile = revelations_editorial_art_profile();
    $keywords = is_array( $profile['keywords'] ?? null ) ? $profile['keywords'] : array();
    foreach ( array( 'relevance', 'implementation', 'speculative', 'impact', 'avoid' ) as $group ) {
        if ( ! is_array( $keywords[ $group ] ?? null ) ) $keywords[ $group ] = array();
    }
    return $keywords;
}

/** @param string[] $keywords */
function revelations_editorial_art_keyword_matches( string $text, array $keywords ): array {
    $matches = array();
    foreach ( $keywords as $keyword ) {
        $keyword = trim( mb_strtolower( (string) $keyword, 'UTF-8' ) );
        if ( '' !== $keyword && 1 === preg_match( '~(?<![\p{L}\p{N}])' . preg_quote( $keyword, '~' ) . '(?![\p{L}\p{N}])~iu', $text ) ) $matches[] = $keyword;
    }
    return array_values( array_unique( $matches ) );
}

function revelations_editorial_art_relevance_gate( array $story ): array {
    $raw_title = (string) ( $story['title'] ?? '' );
    $raw_text = trim( $raw_title . ' ' . (string) ( $story['summary'] ?? '' ) );
    $title = mb_strtolower( $raw_title, 'UTF-8' );
    $text = mb_strtolower( $raw_text, 'UTF-8' );
    $keywords = revelations_editorial_art_keywords();
    $art_matches = revelations_editorial_art_keyword_matches( $text, $keywords['relevance'] );
    $action_matches = revelations_editorial_art_keyword_matches( $text, $keywords['implementation'] );
    $development_matches = array_values( array_unique( array_merge( $action_matches, revelations_editorial_art_keyword_matches( $text, array( 'new work', 'new project', 'new series', 'artistic practice', 'retrospective', 'survey', 'acquisition', 'art market', 'auction', 'public commission', 'permanent collection', 'institutional debut', 'first major' ) ) ) ) );
    $listing_matches = revelations_editorial_art_keyword_matches( $text, array( 'event calendar', 'what to see', "what's on", 'tickets', 'register now', 'opening hours', 'this week in' ) );
    $promotional_matches = revelations_editorial_art_keyword_matches( $text, array( 'press release', 'sponsored', 'partner content', 'shop now', 'for sale now' ) );
    $design_matches = revelations_editorial_art_keyword_matches( $text, array( 'interior design', 'home decor', 'furniture', 'product design', 'lighting collection' ) );
    $celebrity_matches = revelations_editorial_art_keyword_matches( $text, array( 'celebrity', 'red carpet', 'reality star', 'influencer' ) );
    $generic_tech_matches = revelations_editorial_art_keyword_matches( $text, array( 'image generator', 'image model', 'ai model', 'text-to-image', 'image tool', 'nft', 'crypto' ) );

    $strong_subject_matches = revelations_editorial_art_keyword_matches( $text, array( 'artwork', 'art work', 'painting', 'sculpture', 'photography', 'visual art', 'contemporary art', 'exhibition', 'museum', 'biennale', 'installation', 'public art', 'art fair', 'art market', 'auction', 'immersive art', 'digital art', 'new media art', 'generative art', 'computational art', 'ai art', 'ai-created art', 'ai-generated art', 'ai-assisted art', 'acquisition' ) );
    $named_artist_project = 1 === preg_match( '/(?<![\\p{L}])\\p{Lu}[\\p{L}\\p{M}\\\'-]+(?:\\s+\\p{Lu}[\\p{L}\\p{M}\\\'-]+){1,2}(?![\\p{L}])/u', $raw_text ) && false !== stripos( $raw_text, 'artist' ) && array() !== $development_matches;
    $reject = static function ( string $code, string $reason ) use ( $art_matches, $action_matches ): array {
        return array( 'qualified' => false, 'rejection_code' => $code, 'reason' => $reason, 'art_matches' => $art_matches, 'action_matches' => $action_matches, 'gate_branch' => 'art' );
    };
    if ( array() !== $listing_matches ) return $reject( 'generic_event_listing', 'Generic event listings are not Art discovery candidates.' );
    if ( array() !== $promotional_matches ) return $reject( 'promotional_only', 'Promotional material is not an Art discovery candidate.' );
    if ( array() !== $design_matches && array() === $strong_subject_matches && ! $named_artist_project ) return $reject( 'design_not_art', 'Design is present without a central art subject.' );
    if ( array() !== $celebrity_matches && array() === $strong_subject_matches && ! $named_artist_project ) return $reject( 'celebrity_art_incidental', 'Art is incidental to a celebrity or lifestyle story.' );
    if ( array() !== $generic_tech_matches && array() === $strong_subject_matches && ! $named_artist_project ) return $reject( 'generic_tech_not_art', 'Generic image technology is not an Art development.' );
    if ( array() === $strong_subject_matches && ! $named_artist_project ) return $reject( array() === $art_matches ? 'no_art_signal' : 'art_not_central', 'No meaningful visual-art subject is present.' );
    if ( array() === $development_matches ) return $reject( 'art_not_central', 'The Art subject lacks a substantive development or action.' );

    return array( 'qualified' => true, 'rejection_code' => '', 'reason' => 'Meaningful Art subject and substantive development are present; ranking remains required.', 'art_matches' => $art_matches, 'action_matches' => $development_matches, 'gate_branch' => 'art' );
}

function revelations_editorial_art_freshness( int $published_timestamp ): array {
    if ( $published_timestamp <= 0 ) return array( 'score' => 5.0, 'age_hours' => null );
    $age_hours = max( 0, ( time() - $published_timestamp ) / HOUR_IN_SECONDS );
    $score = $age_hours <= 24 ? 10.0 : ( $age_hours <= 72 ? 8.0 : ( $age_hours <= 168 ? 6.0 : ( $age_hours <= 336 ? 3.0 : 1.0 ) ) );
    return array( 'score' => $score, 'age_hours' => round( $age_hours, 1 ) );
}

function revelations_editorial_score_art_story( array $story ): ?array {
    $text = mb_strtolower( (string) ( $story['title'] ?? '' ) . ' ' . (string) ( $story['summary'] ?? '' ), 'UTF-8' );
    $keywords = revelations_editorial_art_keywords();
    $art_matches = revelations_editorial_art_keyword_matches( $text, $keywords['relevance'] );
    $action_matches = revelations_editorial_art_keyword_matches( $text, $keywords['implementation'] );
    $impact_matches = revelations_editorial_art_keyword_matches( $text, $keywords['impact'] );
    $speculative_matches = revelations_editorial_art_keyword_matches( $text, $keywords['speculative'] );
    $freshness = revelations_editorial_art_freshness( absint( $story['published_timestamp'] ?? 0 ) );
    $relevance_score = min( 10.0, count( $art_matches ) * 2.0 );
    $implementation_score = min( 10.0, max( 0.0, count( $action_matches ) * 2.0 - count( $speculative_matches ) ) );
    $impact_score = min( 10.0, count( $impact_matches ) * 2.5 );
    $fit_score = count( $art_matches ) >= 3 ? 8.0 : 6.0;
    $total_score = ( $freshness['score'] + $relevance_score * 1.5 + $implementation_score + $impact_score + $fit_score ) / 5.5;

    return array(
        'freshness_score' => round( $freshness['score'], 1 ), 'implementation_score' => round( $implementation_score, 1 ), 'relevance_score' => round( $relevance_score, 1 ), 'impact_score' => round( $impact_score, 1 ), 'fit_score' => round( $fit_score, 1 ), 'total_score' => round( $total_score, 1 ), 'age_hours' => $freshness['age_hours'], 'qualified' => true, 'rejection_code' => '', 'editorial_track' => 'art_development',
        'scoring_reason' => 'Art signals: ' . implode( ', ', array_slice( $art_matches, 0, 4 ) ) . ( array() !== $action_matches ? '. Development signals: ' . implode( ', ', array_slice( $action_matches, 0, 3 ) ) : '' ),
        'section_signals' => array_values( array_unique( array_merge( $art_matches, $action_matches, $impact_matches ) ) ),
    );
}

function revelations_editorial_art_scan_dry_run( int $qualified_limit = 5 ): array {
    if ( ! function_exists( 'revelations_editorial_scanner_run_dry_run' ) ) {
        return array( 'mode' => 'dry-run', 'section' => 'art', 'errors' => array( 'Generic scanner engine is unavailable.' ), 'sources_checked' => array(), 'sources_failed' => array_column( revelations_editorial_art_sources(), 'name' ), 'ai_gate_filtered' => 0, 'relevance_filtered' => 0, 'relevance_scope' => 'art_relevance_gate', 'rejection_counts' => array( 'global_ai_gate' => array(), 'art_relevance_gate' => array(), 'section' => array() ), 'rejection_samples' => array( 'global_ai_gate' => array(), 'art_relevance_gate' => array(), 'section' => array() ), 'qualified_candidates' => array(), 'candidates_created' => 0, 'run_logs_created' => 0 );
    }

    return revelations_editorial_scanner_run_dry_run( 'art', revelations_editorial_art_sources(), revelations_editorial_art_disabled_sources(), 'revelations_editorial_score_art_story', array(), $qualified_limit, 'revelations_editorial_art_relevance_gate', 'art_relevance_gate', 'Art relevance gate' );
}

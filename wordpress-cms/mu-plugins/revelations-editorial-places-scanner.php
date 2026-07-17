<?php
/**
 * Plugin Name: REVELATIONS Editorial Places Scanner
 * Description: Dry-run RSS scoring rules for Places candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the configured Places scanner profile.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_places_profile(): array {
    return function_exists(
        'revelations_editorial_get_scanner_profile'
    )
        ? revelations_editorial_get_scanner_profile(
            'places'
        )
        : array();
}

/**
 * Return active Places RSS sources.
 *
 * @return array<int, array{name:string,url:string}>
 */
function revelations_editorial_places_sources(): array {
    $profile =
        revelations_editorial_places_profile();

    return isset( $profile['active_sources'] ) &&
        is_array( $profile['active_sources'] )
            ? $profile['active_sources']
            : array();
}

/**
 * Return disabled Places sources.
 *
 * @return array<int, array{name:string,reason:string}>
 */
function revelations_editorial_places_disabled_sources(): array {
    $profile =
        revelations_editorial_places_profile();

    return isset( $profile['disabled_sources'] ) &&
        is_array( $profile['disabled_sources'] )
            ? $profile['disabled_sources']
            : array();
}

/**
 * Return normalized Places keyword groups.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_places_keywords(): array {
    $profile =
        revelations_editorial_places_profile();

    $keywords = isset( $profile['keywords'] ) &&
        is_array( $profile['keywords'] )
            ? $profile['keywords']
            : array();

    $groups = array(
        'relevance',
        'implementation',
        'speculative',
        'impact',
        'avoid',
    );

    $result = array();

    foreach ( $groups as $group ) {
        $result[ $group ] =
            isset( $keywords[ $group ] ) &&
            is_array( $keywords[ $group ] )
                ? $keywords[ $group ]
                : array();
    }

    return $result;
}

/**
 * Return Places scoring thresholds.
 *
 * @return array<string, array<string, float>>
 */
function revelations_editorial_places_thresholds(): array {
    $profile =
        revelations_editorial_places_profile();

    return isset( $profile['thresholds'] ) &&
        is_array( $profile['thresholds'] )
            ? $profile['thresholds']
            : array();
}

/**
 * Find whole-word and whole-phrase matches.
 *
 * @param string[] $keywords Keywords.
 * @return string[]
 */
function revelations_editorial_places_keyword_matches(
    string $text,
    array $keywords
): array {
    $matches = array();

    foreach ( $keywords as $keyword ) {
        $keyword = trim(
            mb_strtolower(
                (string) $keyword,
                'UTF-8'
            )
        );

        if ( '' === $keyword ) {
            continue;
        }

        $pattern =
            '~(?<![\p{L}\p{N}])' .
            preg_quote( $keyword, '~' ) .
            '(?![\p{L}\p{N}])~iu';

        if (
            1 === preg_match(
                $pattern,
                $text
            )
        ) {
            $matches[] =
                $keyword;
        }
    }

    return array_values(
        array_unique( $matches )
    );
}

/**
 * Calculate freshness.
 *
 * @return array{score:float,age_hours:float|null}
 */
function revelations_editorial_places_freshness(
    int $published_timestamp
): array {
    if ( $published_timestamp <= 0 ) {
        return array(
            'score' =>
                5.0,

            'age_hours' =>
                null,
        );
    }

    $age_hours = max(
        0,
        (
            time() -
            $published_timestamp
        ) / HOUR_IN_SECONDS
    );

    if ( $age_hours <= 24 ) {
        $score = 10.0;
    } elseif ( $age_hours <= 72 ) {
        $score = 8.0;
    } elseif ( $age_hours <= 168 ) {
        $score = 6.0;
    } elseif ( $age_hours <= 336 ) {
        $score = 3.0;
    } else {
        $score = 1.0;
    }

    return array(
        'score' =>
            $score,

        'age_hours' =>
            round(
                $age_hours,
                1
            ),
    );
}

/**
 * Score one Places story.
 *
 * Returning null removes the story from consideration.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>|null
 */
function revelations_editorial_score_places_story(
    array $story
): ?array {
    $keywords =
        revelations_editorial_places_keywords();

    $thresholds =
        revelations_editorial_places_thresholds();

    $title = (string) (
        $story['title'] ?? ''
    );

    $text = mb_strtolower(
        $title .
        ' ' .
        (string) (
            $story['summary'] ?? ''
        ),
        'UTF-8'
    );

    $avoid_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['avoid']
        );

    if ( array() !== $avoid_matches ) {
        return null;
    }

    $relevance_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['relevance']
        );

    if ( array() === $relevance_matches ) {
        return null;
    }

    $implementation_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['implementation']
        );

    $speculative_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['speculative']
        );

    $impact_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['impact']
        );

    $relevance_score = min(
        10,
        count( $relevance_matches ) * 2.5
    );

    $implementation_score = min(
        10,
        max(
            0,
            count( $implementation_matches ) * 2 -
            count( $speculative_matches ) * 1.5
        )
    );

    $impact_score = min(
        10,
        count( $impact_matches ) * 2
    );

    $freshness =
        revelations_editorial_places_freshness(
            absint(
                $story['published_timestamp'] ?? 0
            )
        );

    $freshness_score =
        (float) $freshness['score'];

    if (
        $relevance_score >= 5 &&
        $implementation_score >= 2
    ) {
        $fit_score = 9.0;
    } elseif (
        $relevance_score >= 5 &&
        $impact_score >= 2
    ) {
        $fit_score = 7.0;
    } elseif ( $relevance_score >= 2.5 ) {
        $fit_score = 5.0;
    } else {
        $fit_score = 3.0;
    }

    $total_score = (
        $freshness_score +
        $relevance_score * 2 +
        $implementation_score * 1.5 +
        $impact_score * 1.2 +
        $fit_score
    ) / 6.7;

    $places_thresholds = isset(
        $thresholds['places_signal']
    ) && is_array(
        $thresholds['places_signal']
    )
        ? $thresholds['places_signal']
        : array();

    $base_qualified =
        $total_score >= (float) (
            $places_thresholds['total_score']
            ?? 4.6
        ) &&
        $relevance_score >= (float) (
            $places_thresholds['relevance_score']
            ?? 2.5
        ) &&
        $freshness_score >= (float) (
            $places_thresholds['freshness_score']
            ?? 3.0
        );

    $significance_qualified =
        $implementation_score >= (float) (
            $places_thresholds[
                'implementation_score'
            ] ?? 2.0
        ) ||
        $impact_score >= (float) (
            $places_thresholds[
                'impact_score'
            ] ?? 4.0
        ) ||
        $relevance_score >= (float) (
            $places_thresholds[
                'strong_relevance_score'
            ] ?? 7.5
        );

    $qualified =
        $base_qualified &&
        $significance_qualified;

    $reasons = array();

    if ( array() !== $relevance_matches ) {
        $reasons[] =
            'Place signals: ' .
            implode(
                ', ',
                array_slice(
                    $relevance_matches,
                    0,
                    4
                )
            );
    }

    if ( array() !== $implementation_matches ) {
        $reasons[] =
            'Confirmed development: ' .
            implode(
                ', ',
                array_slice(
                    $implementation_matches,
                    0,
                    3
                )
            );
    }

    if ( array() !== $impact_matches ) {
        $reasons[] =
            'Impact: ' .
            implode(
                ', ',
                array_slice(
                    $impact_matches,
                    0,
                    3
                )
            );
    }

    if ( array() !== $speculative_matches ) {
        $reasons[] =
            'Speculative signals: ' .
            count(
                $speculative_matches
            );
    }

    return array(
        'freshness_score' =>
            round(
                $freshness_score,
                1
            ),

        'implementation_score' =>
            round(
                $implementation_score,
                1
            ),

        'relevance_score' =>
            round(
                $relevance_score,
                1
            ),

        'impact_score' =>
            round(
                $impact_score,
                1
            ),

        'fit_score' =>
            round(
                $fit_score,
                1
            ),

        'total_score' =>
            round(
                $total_score,
                1
            ),

        'age_hours' =>
            $freshness['age_hours'],

        'qualified' =>
            $qualified,

        'editorial_track' =>
            $qualified
                ? 'places_signal'
                : null,

        'scoring_reason' =>
            array() !== $reasons
                ? implode(
                    '. ',
                    $reasons
                )
                : 'General Places relevance.',
    );
}

/**
 * Run Places scanning without creating records.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_places_scan_dry_run(
    int $qualified_limit = 5
): array {
    if (
        ! function_exists(
            'revelations_editorial_scanner_run_dry_run'
        )
    ) {
        return array(
            'mode' =>
                'dry-run',

            'section' =>
                'places',

            'errors' => array(
                'Generic scanner engine is unavailable.',
            ),

            'sources_checked' =>
                array(),

            'sources_failed' =>
                array_column(
                    revelations_editorial_places_sources(),
                    'name'
                ),

            'qualified_candidates' =>
                array(),

            'candidates_created' =>
                0,

            'run_logs_created' =>
                0,
        );
    }

    return revelations_editorial_scanner_run_dry_run(
        'places',
        revelations_editorial_places_sources(),
        revelations_editorial_places_disabled_sources(),
        'revelations_editorial_score_places_story',
        revelations_editorial_places_thresholds(),
        $qualified_limit,
        20
    );
}

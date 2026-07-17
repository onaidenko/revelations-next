<?php
/**
 * Plugin Name: REVELATIONS Editorial News Scanner
 * Description: Configurable scoring profile and dry-run wrapper for News.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load the current News scanner profile.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_news_profile(): array {
    if (
        ! function_exists(
            'revelations_editorial_get_scanner_profile'
        )
    ) {
        return array();
    }

    return revelations_editorial_get_scanner_profile(
        'news'
    );
}

/**
 * Return configured News RSS sources.
 *
 * @return array<int, array{name:string,url:string}>
 */
function revelations_editorial_news_sources(): array {
    $profile =
        revelations_editorial_news_profile();

    return isset( $profile['active_sources'] ) &&
        is_array( $profile['active_sources'] )
            ? array_values(
                $profile['active_sources']
            )
            : array();
}

/**
 * Return disabled News RSS sources.
 *
 * @return array<int, array{name:string,reason:string}>
 */
function revelations_editorial_news_disabled_sources(): array {
    $profile =
        revelations_editorial_news_profile();

    return isset( $profile['disabled_sources'] ) &&
        is_array( $profile['disabled_sources'] )
            ? array_values(
                $profile['disabled_sources']
            )
            : array();
}

/**
 * Return configured News keyword groups.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_news_keywords(): array {
    $profile =
        revelations_editorial_news_profile();

    $keywords = isset( $profile['keywords'] ) &&
        is_array( $profile['keywords'] )
            ? $profile['keywords']
            : array();

    foreach (
        array(
            'relevance',
            'implementation',
            'speculative',
            'impact',
            'product_launch',
            'avoid',
        ) as $group
    ) {
        if (
            ! isset( $keywords[ $group ] ) ||
            ! is_array( $keywords[ $group ] )
        ) {
            $keywords[ $group ] =
                array();
        }
    }

    return $keywords;
}

/**
 * Return configured News scoring thresholds.
 *
 * @return array<string, array<string, float>>
 */
function revelations_editorial_news_thresholds(): array {
    $profile =
        revelations_editorial_news_profile();

    return isset( $profile['thresholds'] ) &&
        is_array( $profile['thresholds'] )
            ? $profile['thresholds']
            : array(
                'news_signal' => array(
                    'total_score'     => 4.5,
                    'relevance_score' => 2.0,
                    'freshness_score' => 4.0,
                ),
            );
}

/**
 * Find whole-word or whole-phrase matches.
 *
 * @param string[] $keywords Keywords.
 * @return string[]
 */
function revelations_editorial_news_keyword_matches(
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
 * Score one News story.
 *
 * Returning null means that the story was removed by a hard
 * filter or does not have enough editorial relevance.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>|null
 */
function revelations_editorial_score_news_story(
    array $story
): ?array {
    $keywords =
        revelations_editorial_news_keywords();

    $thresholds =
        revelations_editorial_news_thresholds();

    $text = mb_strtolower(
        (string) (
            $story['title'] ?? ''
        ) .
        ' ' .
        (string) (
            $story['summary'] ?? ''
        ),
        'UTF-8'
    );

    $avoid_matches =
        revelations_editorial_news_keyword_matches(
            $text,
            $keywords['avoid']
        );

    if ( array() !== $avoid_matches ) {
        return revelations_editorial_scanner_rejection(
            revelations_editorial_scanner_avoid_rejection_code(
                $avoid_matches
            ),
            'News avoid-list rule matched.'
        );
    }

    $published_timestamp = absint(
        $story['published_timestamp'] ?? 0
    );

    $freshness_score =
        5.0;

    $age_hours =
        null;

    if ( $published_timestamp > 0 ) {
        $age_hours = max(
            0,
            (
                time() -
                $published_timestamp
            ) / HOUR_IN_SECONDS
        );

        if ( $age_hours <= 24 ) {
            $freshness_score = 10.0;
        } elseif ( $age_hours <= 48 ) {
            $freshness_score = 8.0;
        } elseif ( $age_hours <= 72 ) {
            $freshness_score = 6.0;
        } elseif ( $age_hours <= 168 ) {
            $freshness_score = 3.0;
        } else {
            $freshness_score = 1.0;
        }
    }

    $implementation_matches =
        revelations_editorial_news_keyword_matches(
            $text,
            $keywords['implementation']
        );

    $speculative_matches =
        revelations_editorial_news_keyword_matches(
            $text,
            $keywords['speculative']
        );

    $implementation_score = min(
        10,
        max(
            0,
            count(
                $implementation_matches
            ) * 2
            -
            count(
                $speculative_matches
            ) * 1.5
        )
    );

    $relevance_matches =
        revelations_editorial_news_keyword_matches(
            $text,
            $keywords['relevance']
        );

    $relevance_score = min(
        10,
        count(
            $relevance_matches
        ) * 2
    );

    if ( $relevance_score < 2 ) {
        return revelations_editorial_scanner_rejection(
            'insufficient_section_signal',
            'News relevance signal is below the required minimum.',
            array(
                'relevance_score' =>
                    round( $relevance_score, 1 ),
            )
        );
    }

    $impact_matches =
        revelations_editorial_news_keyword_matches(
            $text,
            $keywords['impact']
        );

    $impact_score = min(
        10,
        count(
            $impact_matches
        ) * 2.5
    );

    if (
        $relevance_score >= 4 &&
        $implementation_score >= 2
    ) {
        $fit_score = 8.0;
    } else {
        $fit_score = 5.0;
    }

    $total_score = (
        $freshness_score
        + $implementation_score * 1.5
        + $relevance_score * 1.5
        + $impact_score
        + $fit_score
    ) / 5.5;

    $news_thresholds = isset(
        $thresholds['news_signal']
    ) && is_array(
        $thresholds['news_signal']
    )
        ? $thresholds['news_signal']
        : array();

    $base_qualified =
        $total_score >= (float) (
            $news_thresholds['total_score']
            ?? 4.5
        ) &&
        $relevance_score >= (float) (
            $news_thresholds['relevance_score']
            ?? 2.0
        ) &&
        $freshness_score >= (float) (
            $news_thresholds['freshness_score']
            ?? 4.0
        );

    $significance_qualified =
        $relevance_score >= (float) (
            $news_thresholds[
                'significance_relevance_score'
            ] ?? 6.0
        ) ||
        $impact_score >= (float) (
            $news_thresholds[
                'significance_impact_score'
            ] ?? 2.5
        );

    $qualified =
        $base_qualified &&
        $significance_qualified;

    $reasons = array();

    if ( array() !== $implementation_matches ) {
        $reasons[] =
            'Confirmed action: ' .
            implode(
                ', ',
                array_slice(
                    $implementation_matches,
                    0,
                    3
                )
            );
    }

    if ( array() !== $relevance_matches ) {
        $reasons[] =
            'Relevance matches: ' .
            count(
                $relevance_matches
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
            null === $age_hours
                ? null
                : round(
                    $age_hours,
                    1
                ),

        'qualified' =>
            $qualified,

        'rejection_code' =>
            $qualified
                ? ''
                : (
                    ! $base_qualified
                        ? 'below_threshold'
                        : 'insufficient_significance'
                ),

        'editorial_track' =>
            $qualified
                ? 'news_signal'
                : null,

        'scoring_reason' =>
            array() !== $reasons
                ? implode(
                    '. ',
                    $reasons
                )
                : 'General News relevance.',
    );
}

/**
 * Run the News scanner without saving anything.
 *
 * This function does not itself check whether the UI scanner
 * is enabled. The future preview handler will enforce that.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_news_scan_dry_run(
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
                'news',

            'errors' => array(
                'Generic scanner engine is unavailable.',
            ),

            'sources_checked' =>
                array(),

            'sources_failed' =>
                array_column(
                    revelations_editorial_news_sources(),
                    'name'
                ),

            'ai_gate_filtered' =>
                0,

            'rejection_counts' => array(
                'global_ai_gate' => array(),
                'section' => array(),
            ),

            'rejection_samples' => array(
                'global_ai_gate' => array(),
                'section' => array(),
            ),

            'below_threshold_scores' =>
                array(),

            'qualified_candidates' =>
                array(),

            'candidates_created' =>
                0,

            'run_logs_created' =>
                0,
        );
    }

    return revelations_editorial_scanner_run_dry_run(
        'news',
        revelations_editorial_news_sources(),
        revelations_editorial_news_disabled_sources(),
        'revelations_editorial_score_news_story',
        revelations_editorial_news_thresholds(),
        $qualified_limit,
        20
    );
}

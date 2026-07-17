<?php
/**
 * Plugin Name: REVELATIONS Editorial People Scanner
 * Description: Dry-run RSS scoring rules for People candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the configured People scanner profile.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_people_profile(): array {
    return function_exists(
        'revelations_editorial_get_scanner_profile'
    )
        ? revelations_editorial_get_scanner_profile(
            'people'
        )
        : array();
}

/**
 * Return active People RSS sources.
 *
 * @return array<int, array{name:string,url:string}>
 */
function revelations_editorial_people_sources(): array {
    $profile =
        revelations_editorial_people_profile();

    return isset( $profile['active_sources'] ) &&
        is_array( $profile['active_sources'] )
            ? $profile['active_sources']
            : array();
}

/**
 * Return disabled People RSS sources.
 *
 * @return array<int, array{name:string,reason:string}>
 */
function revelations_editorial_people_disabled_sources(): array {
    $profile =
        revelations_editorial_people_profile();

    return isset( $profile['disabled_sources'] ) &&
        is_array( $profile['disabled_sources'] )
            ? $profile['disabled_sources']
            : array();
}

/**
 * Return normalized People keyword groups.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_people_keywords(): array {
    $profile =
        revelations_editorial_people_profile();

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
 * Return People scoring thresholds.
 *
 * @return array<string, array<string, float>>
 */
function revelations_editorial_people_thresholds(): array {
    $profile =
        revelations_editorial_people_profile();

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
function revelations_editorial_people_keyword_matches(
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

        $right_boundary =
            'won' === $keyword
                ? '(?![’\']t)(?![\p{L}\p{N}])'
                : '(?![\p{L}\p{N}])';

        $pattern =
            '~(?<![\p{L}\p{N}])' .
            preg_quote( $keyword, '~' ) .
            $right_boundary .
            '~iu';

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
 * Detect a likely personal name in a title.
 */
function revelations_editorial_people_has_name_signal(
    string $title
): bool {
    $pattern =
        '~(?<![\p{L}\p{N}])' .
        '(\p{Lu}[\p{L}\p{M}.\'’\-]{1,})' .
        '\s+' .
        '(\p{Lu}[\p{L}\p{M}.\'’\-]{1,})' .
        '(?![\p{L}\p{N}])~u';

    preg_match_all(
        $pattern,
        $title,
        $matches,
        PREG_SET_ORDER
    );

    if ( array() === $matches ) {
        return false;
    }

    $stopwords = array(
        'ai',
        'applied',
        'artificial',
        'attention',
        'business',
        'company',
        'computing',
        'fast',
        'five',
        'former',
        'four',
        'health',
        'indian',
        'inside',
        'intelligence',
        'ive',
        'labs',
        'leadership',
        'new',
        'one',
        'open',
        'research',
        'source',
        'startup',
        'techcrunch',
        'the',
        'this',
        'three',
        'two',
        'watched',
        'why',
    );

    foreach ( $matches as $match ) {
        $first = mb_strtolower(
            str_replace(
                array( "'", '’' ),
                '',
                (string) ( $match[1] ?? '' )
            ),
            'UTF-8'
        );

        $second = mb_strtolower(
            str_replace(
                array( "'", '’' ),
                '',
                (string) ( $match[2] ?? '' )
            ),
            'UTF-8'
        );

        if (
            in_array( $first, $stopwords, true ) ||
            in_array( $second, $stopwords, true )
        ) {
            continue;
        }

        if (
            mb_strlen( $first, 'UTF-8' ) < 2 ||
            mb_strlen( $second, 'UTF-8' ) < 2
        ) {
            continue;
        }

        return true;
    }

    return false;
}

/**
 * Calculate story freshness.
 *
 * @return array{score:float,age_hours:float|null}
 */
function revelations_editorial_people_freshness(
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
 * Score one People story.
 *
 * Returning null removes the story from consideration.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>|null
 */
function revelations_editorial_score_people_story(
    array $story
): ?array {
    $keywords =
        revelations_editorial_people_keywords();

    $thresholds =
        revelations_editorial_people_thresholds();

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

    $title_text = mb_strtolower(
        $title,
        'UTF-8'
    );

    $avoid_matches =
        revelations_editorial_people_keyword_matches(
            $text,
            $keywords['avoid']
        );

    if ( array() !== $avoid_matches ) {
        return null;
    }

    $relevance_matches =
        revelations_editorial_people_keyword_matches(
            $text,
            $keywords['relevance']
        );

    $implementation_matches =
        revelations_editorial_people_keyword_matches(
            $text,
            $keywords['implementation']
        );

    $speculative_matches =
        revelations_editorial_people_keyword_matches(
            $text,
            $keywords['speculative']
        );

    $impact_matches =
        revelations_editorial_people_keyword_matches(
            $text,
            $keywords['impact']
        );

    $title_action_matches =
        revelations_editorial_people_keyword_matches(
            $title_text,
            $keywords['implementation']
        );

    $name_signal =
        revelations_editorial_people_has_name_signal(
            $title
        );

    $has_explicit_people_signal =
        array() !== $relevance_matches;

    $has_named_action_signal =
        $name_signal &&
        array() !== $title_action_matches;

    /*
     * A story may omit a title such as CEO or founder when the
     * headline still contains a plausible personal name and a
     * confirmed action. Company names alone are not sufficient.
     */
    if (
        ! $has_explicit_people_signal &&
        ! $has_named_action_signal
    ) {
        return null;
    }

    $relevance_score = min(
        10,
        count( $relevance_matches ) * 2 +
        ( $name_signal ? 2 : 0 ) +
        (
            ! $has_explicit_people_signal &&
            $has_named_action_signal
                ? 2
                : 0
        )
    );

    $implementation_score = min(
        10,
        max(
            0,
            count( $implementation_matches ) * 2 -
            count( $speculative_matches ) * 1.5
        )
    );

    $compact_money_signal =
        1 === preg_match(
            '~[$€£]\s*\d+(?:\.\d+)?\s*[mb]' .
            '(?![\p{L}\p{N}])~iu',
            $text
        );

    $impact_score = min(
        10,
        count( $impact_matches ) * 2.5 +
        ( $compact_money_signal ? 5.0 : 0 )
    );

    $freshness =
        revelations_editorial_people_freshness(
            absint(
                $story['published_timestamp'] ?? 0
            )
        );

    $freshness_score =
        (float) $freshness['score'];

    if (
        $relevance_score >= 4 &&
        (
            $implementation_score >= 2 ||
            $impact_score >= 2.5
        )
    ) {
        $fit_score = 9.0;
    } elseif ( $relevance_score >= 4 ) {
        $fit_score = 6.0;
    } else {
        $fit_score = 4.0;
    }

    $total_score = (
        $freshness_score +
        $relevance_score * 2 +
        $implementation_score * 1.5 +
        $impact_score * 1.2 +
        $fit_score
    ) / 6.7;

    $people_thresholds = isset(
        $thresholds['people_signal']
    ) && is_array(
        $thresholds['people_signal']
    )
        ? $thresholds['people_signal']
        : array();

    $base_qualified =
        $total_score >= (float) (
            $people_thresholds['total_score']
            ?? 4.8
        ) &&
        $relevance_score >= (float) (
            $people_thresholds['relevance_score']
            ?? 2.0
        ) &&
        $freshness_score >= (float) (
            $people_thresholds['freshness_score']
            ?? 3.0
        );

    $significance_qualified =
        $implementation_score >= (float) (
            $people_thresholds[
                'implementation_score'
            ] ?? 2.0
        ) ||
        $impact_score >= (float) (
            $people_thresholds[
                'impact_score'
            ] ?? 2.5
        ) ||
        $relevance_score >= (float) (
            $people_thresholds[
                'strong_relevance_score'
            ] ?? 6.0
        );

    $qualified =
        $base_qualified &&
        $significance_qualified;

    $reasons = array();

    if ( $name_signal ) {
        $reasons[] =
            'Personal name detected';
    }

    if ( array() !== $relevance_matches ) {
        $reasons[] =
            'People signals: ' .
            implode(
                ', ',
                array_slice(
                    $relevance_matches,
                    0,
                    3
                )
            );
    }

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
                ? 'people_signal'
                : null,

        'scoring_reason' =>
            array() !== $reasons
                ? implode(
                    '. ',
                    $reasons
                )
                : 'General People relevance.',
    );
}

/**
 * Run People scanning without creating records.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_people_scan_dry_run(
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
                'people',

            'errors' => array(
                'Generic scanner engine is unavailable.',
            ),

            'sources_checked' =>
                array(),

            'sources_failed' =>
                array_column(
                    revelations_editorial_people_sources(),
                    'name'
                ),

            'ai_gate_filtered' =>
                0,

            'qualified_candidates' =>
                array(),

            'candidates_created' =>
                0,

            'run_logs_created' =>
                0,
        );
    }

    return revelations_editorial_scanner_run_dry_run(
        'people',
        revelations_editorial_people_sources(),
        revelations_editorial_people_disabled_sources(),
        'revelations_editorial_score_people_story',
        revelations_editorial_people_thresholds(),
        $qualified_limit,
        20
    );
}

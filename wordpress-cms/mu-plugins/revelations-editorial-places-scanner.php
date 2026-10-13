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
 * Recognize a physical deployment environment without treating a passing
 * location mention as a Place. A site must be paired with future-mobility
 * deployment semantics already recognized by the shared gate.
 *
 * @param array<string, mixed> $story
 * @return string[]
 */
function revelations_editorial_places_physical_deployment_matches(
    array $story,
    string $text
): array {
    $actions = isset( $story['scanner_global_action_signals'] ) &&
        is_array( $story['scanner_global_action_signals'] )
            ? $story['scanner_global_action_signals']
            : array();
    $environment_matches = revelations_editorial_places_keyword_matches(
        $text,
        array(
            'road',
            'highway',
            'corridor',
            'lane',
            'route',
            'transit network',
            'freight route',
            'urban mobility',
            'port',
            'logistics hub',
            'logistics facility',
            'public infrastructure',
        )
    );
    $municipal_matches = revelations_editorial_places_keyword_matches(
        $text,
        array( 'city', 'municipality' )
    );
    $has_operations_action = array() !== array_intersect(
        $actions,
        array(
            'deploy',
            'operations',
            'trial_or_pilot',
            'rollout',
        )
    );
    $has_environment_action = $has_operations_action ||
        array() !== array_intersect(
            $actions,
            array( 'launch' )
        );
    $has_autonomous_mobility = 1 === preg_match(
        '~(?<![\p{L}\p{N}])(?:autonomous|driverless|self-driving)' .
        '(?![\p{L}\p{N}])~iu',
        $text
    );
    $has_operational_text = 1 === preg_match(
        '~(?<![\p{L}\p{N}])(?:deploy(?:ed|ment|s|ing)?|' .
        'operat(?:e|es|ed|ing|ion|ions)|runs?|service|' .
        'trial|pilot|rollout)(?![\p{L}\p{N}])~iu',
        $text
    );
    $raw_text = (string) ( $story['title'] ?? '' ) . ' ' .
        (string) ( $story['summary'] ?? '' );
    $has_named_site = 1 === preg_match(
        '~(?<![\p{L}\p{N}])(?:in|on|along|across|between)\s+' .
        '(?:the\s+)?(?:[\p{Lu}][\p{L}\p{M}.’\'\-]{1,}|[A-Z]-\d{1,4})' .
        '(?![\p{L}\p{N}])~u',
        $raw_text
    );
    $has_named_route_site = 1 === preg_match(
        '~(?<![\p{L}\p{N}])(?:' .
        '(?:I|US|SR)-\d{1,4}|' .
        '(?:Interstate|Highway|Route|State Route)\s+\d{1,4}|' .
        '(?:[\p{Lu}][\p{L}\p{M}.’\'\-]{1,}\s+){0,2}' .
        '(?:highway|route|corridor|lane)' .
        ')(?![\p{L}\p{N}])~u',
        $raw_text
    );

    if (
        ( array() !== $environment_matches && $has_environment_action ) ||
        (
            array() !== $municipal_matches &&
            $has_operations_action &&
            $has_operational_text
        ) ||
        ( $has_named_site && $has_operations_action && $has_operational_text ) ||
        (
            $has_named_route_site &&
            $has_autonomous_mobility &&
            $has_environment_action
        )
    ) {
        return array( 'physical deployment environment' );
    }

    return array();
}

/**
 * Add semantic implementation signals only after the physical deployment
 * predicate has established that the action is about a Place. This keeps a
 * generic company launch or deal from becoming Places implementation.
 *
 * @param array<string, mixed> $story
 * @param string[] $physical_deployment_matches
 * @return string[]
 */
function revelations_editorial_places_semantic_implementation_matches(
    array $story,
    array $physical_deployment_matches,
    string $text
): array {
    if ( array() === $physical_deployment_matches ) {
        return array();
    }

    $actions = isset( $story['scanner_global_action_signals'] ) &&
        is_array( $story['scanner_global_action_signals'] )
            ? $story['scanner_global_action_signals']
            : array();
    $matches = array();

    if ( array() !== array_intersect( $actions, array( 'deploy', 'operations' ) ) ) {
        $matches[] = 'confirmed physical operations';
    }

    // The global gate normalizes this action label, while the Places keyword
    // list historically only carried inflected forms (launches/launched).
    if (
        in_array( 'launch', $actions, true ) &&
        1 === preg_match(
            '~(?<![\p{L}\p{N}])launch(?![\p{L}\p{N}])~iu',
            $text
        )
    ) {
        $matches[] = 'launch';
    }

    return $matches;
}

/**
 * Require an established physical use, not merely a launch announcement or
 * an intended route. The shared action labels are retained as semantics, but
 * Places owns the categorical distinction between use and a future plan.
 *
 * @param array<string, mixed> $story Story record.
 * @param string[] $physical_deployment_matches Confirmed physical context.
 * @param string[] $speculative_matches Plan/speculation matches.
 */
function revelations_editorial_places_has_established_use(
    array $story,
    array $physical_deployment_matches,
    array $speculative_matches,
    string $text
): bool {
    if (
        array() === $physical_deployment_matches ||
        array() !== $speculative_matches
    ) {
        return false;
    }

    $actions = isset( $story['scanner_global_action_signals'] ) &&
        is_array( $story['scanner_global_action_signals'] )
            ? $story['scanner_global_action_signals']
            : array();

    if ( in_array( 'operations', $actions, true ) ) {
        return true;
    }

    if (
        in_array( 'deploy', $actions, true ) &&
        1 === preg_match(
            '~(?<![\p{L}\p{N}])(?:deployed|deployment\s+(?:is\s+)?(?:underway|active|live))(?![\p{L}\p{N}])~iu',
            $text
        )
    ) {
        return true;
    }

    return 1 === preg_match(
        '~(?<![\p{L}\p{N}])(?:operates?|operating|operational|in\s+service|daily\s+(?:driverless|autonomous)\s+(?:runs?|service))(?![\p{L}\p{N}])~iu',
        $text
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
        return revelations_editorial_scanner_rejection(
            revelations_editorial_scanner_avoid_rejection_code(
                $avoid_matches
            ),
            'Places avoid-list rule matched.'
        );
    }

    $relevance_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['relevance']
        );
    $physical_deployment_matches =
        revelations_editorial_places_physical_deployment_matches(
            $story,
            $text
        );
    $relevance_matches = array_values(
        array_unique(
            array_merge(
                $relevance_matches,
                $physical_deployment_matches
            )
        )
    );

    if ( array() === $relevance_matches ) {
        return revelations_editorial_scanner_rejection(
            'place_not_central',
            'No central physical-place signal was found.'
        );
    }

    $speculative_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['speculative']
        );

    if (
        ! revelations_editorial_places_has_established_use(
            $story,
            $physical_deployment_matches,
            $speculative_matches,
            $text
        )
    ) {
        return revelations_editorial_scanner_rejection(
            'planned_not_implemented',
            'Physical deployment is not established as active use or operation.',
            array(
                'section_signals' => $relevance_matches,
            )
        );
    }

    $implementation_matches =
        revelations_editorial_places_keyword_matches(
            $text,
            $keywords['implementation']
        );
    $implementation_matches = array_values(
        array_unique(
            array_merge(
                $implementation_matches,
                revelations_editorial_places_semantic_implementation_matches(
                    $story,
                    $physical_deployment_matches,
                    $text
                )
            )
        )
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

    /* Identity is complete above; preserve the existing total for ranking. */
    $qualified = true;

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

        'rejection_code' => '',

        'editorial_track' => 'places_signal',

        'scoring_reason' =>
            array() !== $reasons
                ? implode(
                    '. ',
                    $reasons
                )
                : 'General Places relevance.',

        'section_signals' => array_values(
            array_unique(
                array_merge(
                    $relevance_matches,
                    $implementation_matches,
                    $impact_matches
                )
            )
        ),
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
        'places',
        revelations_editorial_places_sources(),
        revelations_editorial_places_disabled_sources(),
        'revelations_editorial_score_places_story',
        revelations_editorial_places_thresholds(),
        $qualified_limit
    );
}

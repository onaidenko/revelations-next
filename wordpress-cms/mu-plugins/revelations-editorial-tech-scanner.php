<?php
/**
 * Plugin Name: REVELATIONS Editorial Tech Scanner
 * Description: Dry-run RSS scoring for the Tech editorial section.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currently active Tech RSS sources.
 *
 * Sources confirmed as healthy on the server.
 *
 * @return array<int, array{name:string,url:string}>
 */
function revelations_editorial_tech_sources(): array {
    if (
        function_exists(
            'revelations_editorial_get_scanner_profile'
        )
    ) {
        $profile =
            revelations_editorial_get_scanner_profile(
                'tech'
            );

        if (
            array_key_exists(
                'active_sources',
                $profile
            ) &&
            is_array(
                $profile['active_sources']
            )
        ) {
            return array_values(
                $profile['active_sources']
            );
        }
    }

    return array(
        array(
            'name' => 'TechCrunch AI',
            'url'  =>
                'https://techcrunch.com/category/artificial-intelligence/feed/',
        ),

        array(
            'name' => 'The Robot Report',
            'url'  =>
                'https://www.therobotreport.com/feed/',
        ),

        array(
            'name' => 'MIT Technology Review',
            'url'  =>
                'https://www.technologyreview.com/feed/',
        ),
    );
}

/**
 * Sources intentionally disabled after server testing.
 *
 * @return array<int, array{name:string,reason:string}>
 */
function revelations_editorial_tech_disabled_sources(): array {
    if (
        function_exists(
            'revelations_editorial_get_scanner_profile'
        )
    ) {
        $profile =
            revelations_editorial_get_scanner_profile(
                'tech'
            );

        if (
            array_key_exists(
                'disabled_sources',
                $profile
            ) &&
            is_array(
                $profile['disabled_sources']
            )
        ) {
            return array_values(
                $profile['disabled_sources']
            );
        }
    }

    return array(
        array(
            'name'   => 'Healthcare IT News',
            'reason' => 'RSS endpoint returns HTTP 403.',
        ),

        array(
            'name'   => 'VentureBeat AI',
            'reason' =>
                'RSS feed is stale; newest item was dated 2026-05-19.',
        ),
    );
}

/**
 * Scoring configuration copied from the previous Editorial Desk.
 *
 * @return array<string, array<int, string>>
 */
function revelations_editorial_tech_keywords(): array {
    if (
        function_exists(
            'revelations_editorial_get_scanner_profile'
        )
    ) {
        $profile =
            revelations_editorial_get_scanner_profile(
                'tech'
            );

        if (
            isset( $profile['keywords'] ) &&
            is_array( $profile['keywords'] )
        ) {
            return $profile['keywords'];
        }
    }

    return array(
        'relevance' => array(
            'ai',
            'artificial intelligence',
            'robot',
            'robotics',
            'automation',
            'medical ai',
            'surgical',
            'diagnostics',
            'drug discovery',
            'factory',
            'warehouse',
            'autonomous',
            'fintech',
            'infrastructure',
            'cybersecurity',
            'machine learning',
            'neural',
            'industrial ai',
            'physical ai',
        ),

        'implementation' => array(
            'deployed',
            'used',
            'launched',
            'piloted',
            'approved',
            'operated',
            'performed',
            'installed',
            'live',
            'commercial',
            'hospital',
            'patient',
            'factory',
            'warehouse',
            'robot',
            'autonomous',
            'surgery',
            'diagnostic',
            'clinical',
            'manufacturing',
            'logistics',
            'agriculture',
            'trial',
            'fda',
            'cleared',
            'certified',
            'production',
        ),

        'speculative' => array(
            'plans to',
            'could',
            'may ',
            'aims to',
            'intends to',
            'forecast',
            'opinion',
            'predicts',
            'might',
            'hopes to',
            'expects to',
            'will eventually',
        ),

        'impact' => array(
            'patient',
            'surgery',
            'cancer',
            'harvest',
            'warehouse',
            'factory',
            'diagnostic',
            'performs',
            'detects',
            'autonomous robot',
            'first time',
            'breakthrough',
            'approval',
        ),

        'product_launch' => array(
            'launches',
            'launched',
            'rolls out',
            'rolled out',
            'opens',
            'opened',
            'public beta',
            'available now',
            'available to everyone',
            'released',
            'releases',
            'debut',
            'introducing',
        ),

        'avoid' => array(
            'funding',
            'raises',
            'valuation',
            'ipo',
            'crypto',
            'bitcoin',
            'nft',
            'metaverse',
            'opinion:',
            'commentary:',
            'editorial:',
            'review:',
            'acquires',
            'acquisition',
            'merger',
            'merges',
            'patent',
            'rebrands',
            'startup alley',
            'apply to',
        ),
    );
}

/**
 * Load configurable Tech scoring thresholds.
 *
 * @return array<string, array<string, float>>
 */
function revelations_editorial_tech_thresholds(): array {
    if (
        function_exists(
            'revelations_editorial_get_scanner_profile'
        )
    ) {
        $profile =
            revelations_editorial_get_scanner_profile(
                'tech'
            );

        if (
            isset( $profile['thresholds'] ) &&
            is_array( $profile['thresholds'] )
        ) {
            return $profile['thresholds'];
        }
    }

    return array(
        'applied_technology' => array(
            'total_score'          => 4.0,
            'relevance_score'      => 4.0,
            'implementation_score' => 2.0,
        ),

        'high_impact_technology' => array(
            'total_score'          => 4.3,
            'relevance_score'      => 2.0,
            'implementation_score' => 2.0,
            'impact_score'         => 2.5,
        ),

        'public_product_launch' => array(
            'total_score'     => 3.2,
            'freshness_score' => 8.0,
            'relevance_score' => 2.0,
        ),
    );
}

/**
 * Return matching keywords.
 *
 * @param string[] $keywords Keywords to search for.
 * @return string[]
 */
function revelations_editorial_keyword_matches(
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
            '~(?<![\\p{L}\\p{N}])' .
            preg_quote( $keyword, '~' ) .
            '(?![\\p{L}\\p{N}])~iu';

        if ( 1 === preg_match( $pattern, $text ) ) {
            $matches[] = $keyword;
        }
    }

    return array_values(
        array_unique( $matches )
    );
}

/**
 * Score one Tech story.
 *
 * Returning null means the story was rejected by a hard filter
 * or had insufficient relevance.
 *
 * @param array<string, mixed> $story Story record.
 * @return array<string, mixed>|null
 */
function revelations_editorial_score_tech_story(
    array $story
): ?array {
    $keywords =
        revelations_editorial_tech_keywords();

    $thresholds =
        revelations_editorial_tech_thresholds();

    $text = mb_strtolower(
        (string) ( $story['title'] ?? '' ) .
        ' ' .
        (string) ( $story['summary'] ?? '' ),
        'UTF-8'
    );

    $avoid_matches =
        revelations_editorial_keyword_matches(
            $text,
            $keywords['avoid']
        );

    if ( $avoid_matches !== array() ) {
        return null;
    }

    $published_timestamp = absint(
        $story['published_timestamp'] ?? 0
    );

    $freshness_score = 5.0;
    $age_hours       = null;

    if ( $published_timestamp > 0 ) {
        $age_hours = max(
            0,
            ( time() - $published_timestamp ) / HOUR_IN_SECONDS
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
        revelations_editorial_keyword_matches(
            $text,
            $keywords['implementation']
        );

    $speculative_matches =
        revelations_editorial_keyword_matches(
            $text,
            $keywords['speculative']
        );

    $implementation_score = min(
        10,
        max(
            0,
            count( $implementation_matches ) * 2
            - count( $speculative_matches ) * 1.5
        )
    );

    $relevance_matches =
        revelations_editorial_keyword_matches(
            $text,
            $keywords['relevance']
        );

    $relevance_score = min(
        10,
        count( $relevance_matches ) * 2
    );

    if ( $relevance_score < 2 ) {
        return null;
    }

    $impact_matches =
        revelations_editorial_keyword_matches(
            $text,
            $keywords['impact']
        );

    $impact_score = min(
        10,
        count( $impact_matches ) * 2.5
    );

    if (
        $relevance_score >= 4 &&
        $implementation_score >= 2
    ) {
        $fit_score = 8;
    } elseif ( $relevance_score >= 2 ) {
        $fit_score = 5;
    } else {
        $fit_score = 2;
    }

    $total_score = (
        $freshness_score
        + $implementation_score * 1.5
        + $relevance_score * 1.5
        + $impact_score
        + $fit_score
    ) / 5.5;

    $product_launch_matches =
        revelations_editorial_keyword_matches(
            $text,
            $keywords['product_launch']
        );

    $applied_thresholds = isset(
        $thresholds['applied_technology']
    ) && is_array(
        $thresholds['applied_technology']
    )
        ? $thresholds['applied_technology']
        : array();

    $impact_thresholds = isset(
        $thresholds['high_impact_technology']
    ) && is_array(
        $thresholds['high_impact_technology']
    )
        ? $thresholds['high_impact_technology']
        : array();

    $launch_thresholds = isset(
        $thresholds['public_product_launch']
    ) && is_array(
        $thresholds['public_product_launch']
    )
        ? $thresholds['public_product_launch']
        : array();

    $editorial_track = null;

    if (
        $total_score >= (float) (
            $applied_thresholds['total_score']
            ?? 4.0
        ) &&
        $relevance_score >= (float) (
            $applied_thresholds['relevance_score']
            ?? 4.0
        ) &&
        $implementation_score >= (float) (
            $applied_thresholds[
                'implementation_score'
            ] ?? 2.0
        )
    ) {
        $editorial_track = 'applied_technology';
    } elseif (
        $total_score >= (float) (
            $impact_thresholds['total_score']
            ?? 4.3
        ) &&
        $relevance_score >= (float) (
            $impact_thresholds['relevance_score']
            ?? 2.0
        ) &&
        $implementation_score >= (float) (
            $impact_thresholds[
                'implementation_score'
            ] ?? 2.0
        ) &&
        $impact_score >= (float) (
            $impact_thresholds['impact_score']
            ?? 2.5
        )
    ) {
        $editorial_track = 'high_impact_technology';
    } elseif (
        $total_score >= (float) (
            $launch_thresholds['total_score']
            ?? 3.2
        ) &&
        $freshness_score >= (float) (
            $launch_thresholds['freshness_score']
            ?? 8.0
        ) &&
        $relevance_score >= (float) (
            $launch_thresholds['relevance_score']
            ?? 2.0
        ) &&
        $product_launch_matches !== array()
    ) {
        $editorial_track = 'public_product_launch';
    }

    $qualified = null !== $editorial_track;

    $reasons = array();

    if ( $implementation_matches !== array() ) {
        $reasons[] =
            'Implementation: ' .
            implode(
                ', ',
                array_slice(
                    $implementation_matches,
                    0,
                    3
                )
            );
    }

    if ( $relevance_matches !== array() ) {
        $reasons[] =
            'Relevance matches: ' .
            count( $relevance_matches );
    }

    if ( $impact_matches !== array() ) {
        $reasons[] =
            'Impact: ' .
            implode(
                ', ',
                array_slice(
                    $impact_matches,
                    0,
                    2
                )
            );
    }

    if ( $product_launch_matches !== array() ) {
        $reasons[] =
            'Launch signal: ' .
            implode(
                ', ',
                array_slice(
                    $product_launch_matches,
                    0,
                    2
                )
            );
    }

    return array(
        'freshness_score' =>
            round( $freshness_score, 1 ),

        'implementation_score' =>
            round( $implementation_score, 1 ),

        'relevance_score' =>
            round( $relevance_score, 1 ),

        'impact_score' =>
            round( $impact_score, 1 ),

        'fit_score' =>
            round( $fit_score, 1 ),

        'total_score' =>
            round( $total_score, 1 ),

        'age_hours' =>
            null === $age_hours
                ? null
                : round( $age_hours, 1 ),

        'qualified' => $qualified,

        'editorial_track' =>
            $editorial_track,

        'scoring_reason' =>
            $reasons !== array()
                ? implode( '. ', $reasons )
                : 'General Tech relevance.',
    );
}

/**
 * Normalize feed text.
 */
function revelations_editorial_clean_feed_text(
    string $value,
    int $limit
): string {
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $value = wp_strip_all_tags(
        $value,
        true
    );

    $value = preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    $value = is_string( $value )
        ? trim( $value )
        : '';

    return mb_substr(
        $value,
        0,
        $limit
    );
}

/**
 * Load stored duplicate keys without modifying records.
 *
 * @return array<string, true>
 */
function revelations_editorial_existing_candidate_keys(): array {
    global $wpdb;

    $values = $wpdb->get_col(
        "
        SELECT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p
            ON p.ID = pm.post_id
        WHERE p.post_type = 'rev_candidate'
          AND p.post_status NOT IN (
              'trash',
              'auto-draft'
          )
          AND pm.meta_key = '_rev_duplicate_key'
          AND pm.meta_value <> ''
        "
    );

    $keys = array();

    foreach ( $values as $value ) {
        $keys[ (string) $value ] = true;
    }

    return $keys;
}

/**
 * Run the Tech scanner without writing anything.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_tech_scan_dry_run(
    int $qualified_limit = 10
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
                'tech',

            'thresholds' =>
                revelations_editorial_tech_thresholds(),

            'active_sources' =>
                array_column(
                    revelations_editorial_tech_sources(),
                    'name'
                ),

            'disabled_sources' =>
                revelations_editorial_tech_disabled_sources(),

            'source_results' =>
                array(),

            'sources_checked' =>
                array(),

            'sources_failed' =>
                array_column(
                    revelations_editorial_tech_sources(),
                    'name'
                ),

            'errors' => array(
                'Generic scanner engine is unavailable.',
            ),

            'total_feed_items' =>
                0,

            'duplicates_removed' =>
                0,

            'invalid_removed' =>
                0,

            'ai_gate_filtered' =>
                0,

            'hard_filtered' =>
                0,

            'scored_stories' =>
                0,

            'qualified_stories' =>
                0,

            'top_scored' =>
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
        'tech',
        revelations_editorial_tech_sources(),
        revelations_editorial_tech_disabled_sources(),
        'revelations_editorial_score_tech_story',
        revelations_editorial_tech_thresholds(),
        $qualified_limit,
        20
    );
}

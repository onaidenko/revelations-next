<?php
/**
 * Plugin Name: REVELATIONS Editorial Scanner Engine
 * Description: Shared dry-run RSS engine for editorial candidate scanners.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize text received from an RSS feed.
 */
function revelations_editorial_scanner_clean_feed_text(
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
        max( 1, $limit )
    );
}

/**
 * Load duplicate keys already stored as editorial candidates.
 *
 * Duplicate detection remains global across sections to preserve
 * the current Tech scanner behaviour.
 *
 * @return array<string, true>
 */
function revelations_editorial_scanner_existing_candidate_keys(): array {
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
 * Convert one scored story into the common preview structure.
 *
 * @param array<string, mixed> $story Story record.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_format_story(
    array $story
): array {
    return array(
        'title' =>
            (string) (
                $story['title'] ?? ''
            ),

        'source' =>
            (string) (
                $story['source_name'] ?? ''
            ),

        'url' =>
            (string) (
                $story['source_url'] ?? ''
            ),

        'published_at' =>
            (string) (
                $story['published_at'] ?? ''
            ),

        'age_hours' =>
            $story['age_hours'] ?? null,

        'summary' =>
            (string) (
                $story['summary'] ?? ''
            ),

        'duplicate_key' =>
            (string) (
                $story['duplicate_key'] ?? ''
            ),

        'scores' => array(
            'total' =>
                (float) (
                    $story['total_score'] ?? 0
                ),

            'freshness' =>
                (float) (
                    $story['freshness_score'] ?? 0
                ),

            'implementation' =>
                (float) (
                    $story['implementation_score'] ?? 0
                ),

            'relevance' =>
                (float) (
                    $story['relevance_score'] ?? 0
                ),

            'impact' =>
                (float) (
                    $story['impact_score'] ?? 0
                ),

            'fit' =>
                (float) (
                    $story['fit_score'] ?? 0
                ),
        ),

        'qualified' =>
            true === (
                $story['qualified'] ?? false
            ),

        'editorial_track' =>
            sanitize_key(
                (string) (
                    $story['editorial_track'] ?? ''
                )
            ),

        'reason' =>
            (string) (
                $story['scoring_reason'] ?? ''
            ),
    );
}

/**
 * Run one section scanner without writing candidates or logs.
 *
 * The scoring callback must return either:
 * - a common score array; or
 * - null when the story should be filtered out.
 *
 * @param array<int, array{name:string,url:string}> $sources
 * @param array<int, array{name:string,reason:string}> $disabled_sources
 * @param callable(array<string,mixed>): ?array<string,mixed> $score_story
 * @param array<string, mixed> $thresholds
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_run_dry_run(
    string $section,
    array $sources,
    array $disabled_sources,
    callable $score_story,
    array $thresholds,
    int $qualified_limit = 10,
    int $items_per_source = 20
): array {
    require_once ABSPATH . WPINC . '/feed.php';

    $section = sanitize_key(
        $section
    );

    $qualified_limit = max(
        1,
        min( 20, $qualified_limit )
    );

    $items_per_source = max(
        1,
        min( 50, $items_per_source )
    );

    $seen_keys =
        revelations_editorial_scanner_existing_candidate_keys();

    $stories            = array();
    $source_results     = array();
    $sources_checked    = array();
    $sources_failed     = array();
    $errors             = array();
    $duplicates_removed = 0;
    $invalid_removed    = 0;
    $hard_filtered      = 0;

    foreach ( $sources as $source ) {
        if ( ! is_array( $source ) ) {
            $invalid_removed++;
            continue;
        }

        $source_name = sanitize_text_field(
            (string) (
                $source['name'] ?? ''
            )
        );

        $feed_url = esc_url_raw(
            (string) (
                $source['url'] ?? ''
            )
        );

        if (
            '' === $source_name ||
            '' === $feed_url
        ) {
            $invalid_removed++;
            continue;
        }

        $started = microtime( true );

        $feed = fetch_feed(
            $feed_url
        );

        $duration_ms = (int) round(
            ( microtime( true ) - $started ) * 1000
        );

        if ( is_wp_error( $feed ) ) {
            $sources_failed[] =
                $source_name;

            $errors[] =
                $source_name .
                ': ' .
                $feed->get_error_message();

            $source_results[] = array(
                'source' =>
                    $source_name,

                'success' =>
                    false,

                'items' =>
                    0,

                'duration_ms' =>
                    $duration_ms,

                'error' =>
                    $feed->get_error_message(),
            );

            continue;
        }

        $sources_checked[] =
            $source_name;

        $quantity = (int) $feed->get_item_quantity(
            $items_per_source
        );

        $source_results[] = array(
            'source' =>
                $source_name,

            'success' =>
                true,

            'items' =>
                $quantity,

            'duration_ms' =>
                $duration_ms,
        );

        $items = $feed->get_items(
            0,
            $quantity
        );

        foreach ( $items as $item ) {
            $title =
                revelations_editorial_scanner_clean_feed_text(
                    (string) $item->get_title(),
                    300
                );

            $source_url = esc_url_raw(
                (string) $item->get_permalink()
            );

            if (
                '' === $title ||
                '' === $source_url
            ) {
                $invalid_removed++;
                continue;
            }

            $summary_source =
                (string) $item->get_description();

            if ( '' === $summary_source ) {
                $summary_source =
                    (string) $item->get_content();
            }

            $summary =
                revelations_editorial_scanner_clean_feed_text(
                    $summary_source,
                    600
                );

            $published_timestamp = absint(
                $item->get_date( 'U' )
            );

            $published_at =
                $published_timestamp > 0
                    ? gmdate(
                        'c',
                        $published_timestamp
                    )
                    : '';

            $duplicate_key = function_exists(
                'revelations_editorial_duplicate_key'
            )
                ? revelations_editorial_duplicate_key(
                    $source_url,
                    $title
                )
                : md5(
                    strtolower( $source_url )
                );

            if (
                isset(
                    $seen_keys[ $duplicate_key ]
                )
            ) {
                $duplicates_removed++;
                continue;
            }

            $seen_keys[ $duplicate_key ] =
                true;

            $story = array(
                'title' =>
                    $title,

                'source_url' =>
                    $source_url,

                'source_name' =>
                    $source_name,

                'published_at' =>
                    $published_at,

                'published_timestamp' =>
                    $published_timestamp,

                'summary' =>
                    $summary,

                'duplicate_key' =>
                    $duplicate_key,
            );

            $scores =
                call_user_func(
                    $score_story,
                    $story
                );

            if ( null === $scores ) {
                $hard_filtered++;
                continue;
            }

            $stories[] = array_merge(
                $story,
                $scores
            );
        }
    }

    usort(
        $stories,
        static fn (
            array $left,
            array $right
        ): int =>
            (float) (
                $right['total_score'] ?? 0
            )
            <=>
            (float) (
                $left['total_score'] ?? 0
            )
    );

    $qualified = array_values(
        array_filter(
            $stories,
            static fn ( array $story ): bool =>
                true === (
                    $story['qualified'] ?? false
                )
        )
    );

    return array(
        'mode' =>
            'dry-run',

        'section' =>
            $section,

        'thresholds' =>
            $thresholds,

        'active_sources' =>
            array_values(
                array_filter(
                    array_map(
                        static fn ( $source ): string =>
                            is_array( $source )
                                ? sanitize_text_field(
                                    (string) (
                                        $source['name'] ?? ''
                                    )
                                )
                                : '',
                        $sources
                    )
                )
            ),

        'disabled_sources' =>
            $disabled_sources,

        'source_results' =>
            $source_results,

        'sources_checked' =>
            $sources_checked,

        'sources_failed' =>
            $sources_failed,

        'errors' =>
            $errors,

        'total_feed_items' =>
            array_sum(
                array_map(
                    static fn ( array $source ): int =>
                        (int) (
                            $source['items'] ?? 0
                        ),
                    $source_results
                )
            ),

        'duplicates_removed' =>
            $duplicates_removed,

        'invalid_removed' =>
            $invalid_removed,

        'hard_filtered' =>
            $hard_filtered,

        'scored_stories' =>
            count( $stories ),

        'qualified_stories' =>
            count( $qualified ),

        'top_scored' =>
            array_map(
                'revelations_editorial_scanner_format_story',
                array_slice(
                    $stories,
                    0,
                    15
                )
            ),

        'qualified_candidates' =>
            array_map(
                'revelations_editorial_scanner_format_story',
                array_slice(
                    $qualified,
                    0,
                    $qualified_limit
                )
            ),

        'candidates_created' =>
            0,

        'run_logs_created' =>
            0,
    );
}

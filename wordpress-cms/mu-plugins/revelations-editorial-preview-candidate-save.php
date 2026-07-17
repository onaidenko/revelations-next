<?php
/**
 * Plugin Name: REVELATIONS Editorial Preview Candidate Save
 * Description: Shared backend for saving candidates from editorial previews.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check whether a candidate with this duplicate key exists.
 */
function revelations_editorial_preview_candidate_exists(
    string $duplicate_key
): int {
    if ( '' === $duplicate_key ) {
        return 0;
    }

    $ids = get_posts(
        array(
            'post_type'      => 'rev_candidate',
            'post_status'    => array(
                'publish',
                'draft',
                'pending',
                'private',
            ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_rev_duplicate_key',
            'meta_value'     => $duplicate_key,
        )
    );

    return isset( $ids[0] )
        ? absint( $ids[0] )
        : 0;
}

/**
 * Save one candidate from a temporary editorial preview.
 */
add_action(
    'admin_post_revelations_save_preview_candidate',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to save candidates.',
                    'revelations'
                )
            );
        }

        $section = sanitize_key(
            wp_unslash(
                $_POST['preview_section'] ?? ''
            )
        );

        if (
            ! function_exists(
                'revelations_editorial_preview_section_is_registered'
            ) ||
            ! revelations_editorial_preview_section_is_registered(
                $section
            )
        ) {
            wp_die(
                esc_html__(
                    'The editorial preview section is invalid.',
                    'revelations'
                )
            );
        }

        $candidate_index = absint(
            $_POST['candidate_index'] ?? -1
        );

        check_admin_referer(
            'revelations_save_preview_candidate_' .
            $section .
            '_' .
            $candidate_index
        );

        $redirect = static function (
            array $args
        ) use ( $section ): void {
            wp_safe_redirect(
                add_query_arg(
                    array_merge(
                        array(
                            'page' =>
                                'revelations-editorial-desk',

                            'view' =>
                                'candidates',

                            'candidate_section' =>
                                $section,
                        ),
                        $args
                    ),
                    admin_url( 'admin.php' )
                )
            );

            exit;
        };

        $preview = get_transient(
            revelations_editorial_preview_key(
                $section
            )
        );

        if (
            ! is_array( $preview ) ||
            $section !== sanitize_key(
                (string) (
                    $preview['section'] ?? ''
                )
            ) ||
            ! isset(
                $preview['qualified_candidates']
            ) ||
            ! is_array(
                $preview['qualified_candidates']
            ) ||
            ! isset(
                $preview['qualified_candidates'][
                    $candidate_index
                ]
            )
        ) {
            $redirect(
                array(
                    'candidate_error' => 'expired',
                )
            );
        }

        $candidate =
            $preview['qualified_candidates'][
                $candidate_index
            ];

        $title = sanitize_text_field(
            (string) (
                $candidate['title'] ?? ''
            )
        );

        $source_url = esc_url_raw(
            (string) (
                $candidate['url'] ?? ''
            )
        );

        $source_name = sanitize_text_field(
            (string) (
                $candidate['source'] ?? ''
            )
        );

        if (
            '' === $title ||
            '' === $source_url
        ) {
            $redirect(
                array(
                    'candidate_error' => 'invalid',
                )
            );
        }

        $duplicate_key = sanitize_text_field(
            (string) (
                $candidate['duplicate_key'] ?? ''
            )
        );

        if (
            '' === $duplicate_key &&
            function_exists(
                'revelations_editorial_duplicate_key'
            )
        ) {
            $duplicate_key =
                revelations_editorial_duplicate_key(
                    $source_url,
                    $title
                );
        }

        $existing_id =
            revelations_editorial_preview_candidate_exists(
                $duplicate_key
            );

        if ( $existing_id > 0 ) {
            $redirect(
                array(
                    'candidate_duplicate' =>
                        $existing_id,
                )
            );
        }

        $candidate_id = wp_insert_post(
            array(
                'post_type'   => 'rev_candidate',
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_author' => get_current_user_id(),
            ),
            true
        );

        if ( is_wp_error( $candidate_id ) ) {
            $redirect(
                array(
                    'candidate_error' => 'save',
                )
            );
        }

        $candidate_id = absint( $candidate_id );

        $summary = sanitize_textarea_field(
            (string) (
                $candidate['summary'] ?? ''
            )
        );

        $scores = is_array(
            $candidate['scores'] ?? null
        )
            ? $candidate['scores']
            : array();

        $meta = array(
            '_rev_source_url' =>
                $source_url,

            '_rev_source_name' =>
                $source_name,

            '_rev_published_at' =>
                sanitize_text_field(
                    (string) (
                        $candidate['published_at'] ?? ''
                    )
                ),

            '_rev_fetched_at' =>
                gmdate( 'c' ),

            '_rev_summary' =>
                $summary,

            '_rev_raw_excerpt' =>
                mb_substr(
                    $summary,
                    0,
                    300
                ),

            '_rev_section' =>
                $section,

            '_rev_freshness_score' =>
                (float) (
                    $scores['freshness'] ?? 0
                ),

            '_rev_relevance_score' =>
                (float) (
                    $scores['relevance'] ?? 0
                ),

            '_rev_implementation_score' =>
                (float) (
                    $scores['implementation'] ?? 0
                ),

            '_rev_hype_score' =>
                (float) (
                    $scores['impact'] ?? 0
                ),

            '_rev_fit_score' =>
                (float) (
                    $scores['fit'] ?? 0
                ),

            '_rev_total_score' =>
                (float) (
                    $scores['total'] ?? 0
                ),

            '_rev_scoring_reason' =>
                sanitize_text_field(
                    (string) (
                        $candidate['reason'] ?? ''
                    )
                ),

            '_rev_editorial_track' =>
                sanitize_key(
                    (string) (
                        $candidate[
                            'editorial_track'
                        ] ?? ''
                    )
                ),

            '_rev_duplicate_key' =>
                $duplicate_key,

            '_rev_run_id' =>
                '',

            '_rev_status' =>
                'selected',

            '_rev_error_message' =>
                '',
        );

        foreach ( $meta as $key => $value ) {
            update_post_meta(
                $candidate_id,
                $key,
                $value
            );
        }

        $redirect(
            array(
                'candidate_saved' =>
                    $candidate_id,
            )
        );
    }
);

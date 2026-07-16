<?php
/**
 * Plugin Name: REVELATIONS Editorial Tech Preview
 * Description: Admin-only dry-run preview of qualified Tech candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * User-specific transient key.
 */
function revelations_editorial_tech_preview_key(): string {
    if (
        function_exists(
            'revelations_editorial_preview_key'
        )
    ) {
        return revelations_editorial_preview_key(
            'tech'
        );
    }

    return 'rev_tech_preview_' .
        get_current_user_id();
}

/**
 * Load the current Tech preview runtime configuration.
 *
 * @return array{enabled:bool,preview_limit:int}
 */
function revelations_editorial_tech_preview_runtime_config(): array {
    if (
        function_exists(
            'revelations_editorial_preview_runtime_config'
        )
    ) {
        return revelations_editorial_preview_runtime_config(
            'tech'
        );
    }

    return array(
        'enabled' =>
            true,

        'preview_limit' =>
            10,
    );
}

/**
 * Record one Tech RSS preview scan privately.
 *
 * The scan remains dry-run: no candidates or articles are created.
 *
 * @param array<string, mixed> $result Scanner result.
 * @return int|WP_Error
 */
function revelations_editorial_log_tech_preview_scan(
    array $result,
    int $duration_ms
) {
    if (
        function_exists(
            'revelations_editorial_log_preview_scan'
        )
    ) {
        return revelations_editorial_log_preview_scan(
            'tech',
            $result,
            $duration_ms
        );
    }

    return new WP_Error(
        'preview_engine_unavailable',
        'Editorial preview engine is unavailable.'
    );
}

/**
 * Run a dry Tech scan, store its temporary preview
 * and record private scan statistics.
 */
add_action(
    'admin_post_revelations_preview_tech_scan',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to run editorial scans.',
                    'revelations'
                )
            );
        }

        check_admin_referer(
            'revelations_preview_tech_scan'
        );

        if (
            ! function_exists(
                'revelations_editorial_generate_preview'
            )
        ) {
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' =>
                            'revelations-editorial-desk',

                        'view' =>
                            'candidates',

                        'scan_section' =>
                            'tech',

                        'scan_error' =>
                            'scanner',
                    ),
                    admin_url( 'admin.php' )
                )
            );

            exit;
        }

        $result =
            revelations_editorial_generate_preview(
                'tech'
            );

        if ( is_wp_error( $result ) ) {
            $error_code =
                $result->get_error_code();

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' =>
                            'revelations-editorial-desk',

                        'view' =>
                            'candidates',

                        'scan_section' =>
                            'tech',

                        'scan_error' =>
                            'scanner_disabled' === $error_code
                                ? 'disabled'
                                : 'scanner',
                    ),
                    admin_url( 'admin.php' )
                )
            );

            exit;
        }

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' =>
                        'revelations-editorial-desk',

                    'view' =>
                        'candidates',

                    'scan_section' =>
                        'tech',

                    'scan_ready' =>
                        1,
                ),
                admin_url( 'admin.php' )
            )
        );

        exit;
    }
);

/**
 * Render the Tech dry-run control and latest preview.
 */
function revelations_editorial_render_tech_preview(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $runtime =
        revelations_editorial_tech_preview_runtime_config();

    $scanner_enabled =
        $runtime['enabled'];

    $preview_limit =
        $runtime['preview_limit'];

    $preview = get_transient(
        revelations_editorial_tech_preview_key()
    );

    $scan_section = sanitize_key(
        wp_unslash(
            $_GET['scan_section'] ?? ''
        )
    );

    $candidate_section = sanitize_key(
        wp_unslash(
            $_GET['candidate_section'] ?? ''
        )
    );

    if (
        'tech' === $candidate_section &&
        isset( $_GET['candidate_saved'] )
    ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Candidate saved successfully.
            </p>
        </div>
        <?php
    }

    if (
        'tech' === $candidate_section &&
        isset( $_GET['candidate_duplicate'] )
    ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                This candidate is already stored.
            </p>
        </div>
        <?php
    }

    $candidate_error = 'tech' === $candidate_section
        ? sanitize_key(
            wp_unslash(
                $_GET['candidate_error'] ?? ''
            )
        )
        : '';

    if ( '' !== $candidate_error ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                Candidate could not be saved.
                The preview may have expired.
            </p>
        </div>
        <?php
    }

    if (
        'tech' === $scan_section &&
        isset( $_GET['scan_ready'] )
    ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Tech scan completed in dry-run mode.
                No candidates or articles were created. A private run log was recorded.
            </p>
        </div>
        <?php
    }

    $scan_error = 'tech' === $scan_section
        ? sanitize_key(
            wp_unslash(
                $_GET['scan_error'] ?? ''
            )
        )
        : '';

    if (
        in_array(
            $scan_error,
            array(
                'scanner',
                'disabled',
            ),
            true
        )
    ) {
        $scan_error_message =
            'disabled' === $scan_error
                ? 'The Tech scanner is disabled in Settings.'
                : 'The Tech scanner is not available.';
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo esc_html(
                    $scan_error_message
                ); ?>
            </p>
        </div>
        <?php
    }
    ?>
    <section
        class="
            revelations-desk__section
            revelations-tech-preview
        "
        style="margin-top:20px"
    >
        <div class="revelations-tech-preview__header">
            <div>
                <h2>Tech RSS scan</h2>

                <p class="revelations-desk__section-description">
                    Preview qualified stories from active Tech
                    sources. This scan does not create candidates
                    or articles; only private scan statistics are saved.

                    <?php if ( $scanner_enabled ) : ?>
                        Current preview limit:
                        <strong>
                            <?php echo esc_html(
                                (string) $preview_limit
                            ); ?>
                        </strong>.
                    <?php else : ?>
                        <strong>
                            Scanner is currently disabled in Settings.
                        </strong>
                    <?php endif; ?>
                </p>
            </div>

            <form
                method="post"
                action="<?php echo esc_url(
                    admin_url( 'admin-post.php' )
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="revelations_preview_tech_scan"
                >

                <?php wp_nonce_field(
                    'revelations_preview_tech_scan'
                ); ?>

                <?php submit_button(
                    'Preview Tech scan',
                    'primary',
                    'submit',
                    false,
                    $scanner_enabled
                        ? ''
                        : 'disabled="disabled" aria-disabled="true"'
                ); ?>
            </form>
        </div>

        <?php if ( ! is_array( $preview ) ) : ?>
            <p>
                No preview has been generated in this session.
            </p>
        <?php else : ?>
            <?php
            $qualified = isset(
                $preview['qualified_candidates']
            ) && is_array(
                $preview['qualified_candidates']
            )
                ? $preview['qualified_candidates']
                : array();

            $active_sources = isset(
                $preview['active_sources']
            ) && is_array(
                $preview['active_sources']
            )
                ? $preview['active_sources']
                : array();
            ?>

            <div class="revelations-tech-preview__summary">
                <span>
                    <strong>
                        <?php echo esc_html(
                            (string) absint(
                                $preview['total_feed_items'] ?? 0
                            )
                        ); ?>
                    </strong>
                    feed items
                </span>

                <span>
                    <strong>
                        <?php echo esc_html(
                            (string) absint(
                                $preview['qualified_stories'] ?? 0
                            )
                        ); ?>
                    </strong>
                    qualified · showing
                    <strong>
                        <?php echo esc_html(
                            (string) count( $qualified )
                        ); ?>
                    </strong>
                </span>

                <span>
                    <strong>
                        <?php echo esc_html(
                            (string) count( $active_sources )
                        ); ?>
                    </strong>
                    active sources
                </span>

                <span>
                    <strong>0</strong>
                    records created
                </span>
            </div>

            <?php if ( $qualified === array() ) : ?>
                <p>
                    No stories passed the current editorial rules.
                </p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Source</th>
                            <th>Editorial track</th>
                            <th>Scores</th>
                            <th>Age</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (
                            $qualified as $candidate_index => $item
                        ) : ?>
                            <?php
                            $scores = is_array(
                                $item['scores'] ?? null
                            )
                                ? $item['scores']
                                : array();

                            $track = (string) (
                                $item['editorial_track'] ?? ''
                            );
                            ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $item['title'] ?? ''
                                            )
                                        ); ?>
                                    </strong>

                                    <div
                                        class="
                                            revelations-candidate-meta
                                        "
                                    >
                                        <?php echo esc_html(
                                            (string) (
                                                $item['reason'] ?? ''
                                            )
                                        ); ?>
                                    </div>
                                </td>

                                <td>
                                    <a
                                        href="<?php echo esc_url(
                                            (string) (
                                                $item['url'] ?? ''
                                            )
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <?php echo esc_html(
                                            (string) (
                                                $item['source'] ?? ''
                                            )
                                        ); ?>
                                    </a>
                                </td>

                                <td>
                                    <span
                                        class="
                                            revelations-preview-track
                                        "
                                    >
                                        <?php echo esc_html(
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $track
                                                )
                                            )
                                        ); ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $scores['total'] ?? 0
                                            )
                                        ); ?>
                                    </strong>

                                    <div
                                        class="
                                            revelations-candidate-meta
                                        "
                                    >
                                        R <?php echo esc_html(
                                            (string) (
                                                $scores[
                                                    'relevance'
                                                ] ?? 0
                                            )
                                        ); ?>

                                        · I <?php echo esc_html(
                                            (string) (
                                                $scores[
                                                    'implementation'
                                                ] ?? 0
                                            )
                                        ); ?>

                                        · F <?php echo esc_html(
                                            (string) (
                                                $scores[
                                                    'freshness'
                                                ] ?? 0
                                            )
                                        ); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        number_format(
                                            (float) (
                                                $item['age_hours'] ?? 0
                                            ),
                                            1
                                        ) . ' h'
                                    ); ?>
                                </td>

                                <td>
                                    <?php
                                    $duplicate_key =
                                        sanitize_text_field(
                                            (string) (
                                                $item[
                                                    'duplicate_key'
                                                ] ?? ''
                                            )
                                        );

                                    $stored_candidate_id =
                                        revelations_editorial_preview_candidate_exists(
                                            $duplicate_key
                                        );
                                    ?>

                                    <?php if (
                                        $stored_candidate_id > 0
                                    ) : ?>
                                        <span
                                            class="
                                                revelations-preview-saved
                                            "
                                        >
                                            Saved
                                        </span>
                                    <?php else : ?>
                                        <form
                                            method="post"
                                            action="<?php
                                                echo esc_url(
                                                    admin_url(
                                                        'admin-post.php'
                                                    )
                                                );
                                            ?>"
                                        >
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="
revelations_save_preview_candidate"
                                            >

                                            <input
                                                type="hidden"
                                                name="preview_section"
                                                value="tech"
                                            >

                                            <input
                                                type="hidden"
                                                name="candidate_index"
                                                value="<?php
                                                    echo esc_attr(
                                                        (string)
                                                        $candidate_index
                                                    );
                                                ?>"
                                            >

                                            <?php wp_nonce_field(
                                                'revelations_save_preview_candidate_' .
                                                'tech_' .
                                                $candidate_index
                                            ); ?>

                                            <?php submit_button(
                                                'Save candidate',
                                                'secondary small',
                                                'submit',
                                                false
                                            ); ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p class="description" style="margin-top:12px">
                Preview expires automatically after 15 minutes.
                Candidates are saved only when you click
                “Save candidate”.
            </p>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Preview-specific styling.
 */
add_action(
    'admin_enqueue_scripts',
    static function ( string $hook_suffix ): void {
        if (
            'toplevel_page_revelations-editorial-desk'
            !== $hook_suffix
        ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );

        wp_add_inline_style(
            'dashicons',
            '
            .revelations-tech-preview__header {
                display:flex;
                align-items:flex-start;
                justify-content:space-between;
                gap:24px;
            }

            .revelations-tech-preview__summary {
                display:flex;
                flex-wrap:wrap;
                gap:10px 24px;
                margin:18px 0;
                padding:14px 16px;
                background:#f6f7f7;
                border-radius:6px;
            }

            .revelations-preview-saved {
                display:inline-flex;
                padding:4px 8px;
                border-radius:999px;
                background:#e8f5e9;
                color:#1b5e20;
                font-size:11px;
                font-weight:600;
            }

            .revelations-preview-track {
                display:inline-flex;
                padding:4px 8px;
                border-radius:999px;
                background:#e7f3ff;
                color:#0a4b78;
                font-size:11px;
                font-weight:600;
            }

            @media (max-width:782px) {
                .revelations-tech-preview__header {
                    display:block;
                }
            }
            '
        );
    }
);

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
 * Save one candidate from the temporary Tech preview.
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

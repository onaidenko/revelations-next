<?php
/**
 * Plugin Name: REVELATIONS Editorial Run Logs
 * Description: Private run history for Editorial Desk.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Allowed run statuses.
 *
 * @return string[]
 */
function revelations_editorial_run_statuses(): array {
    return array(
        'running',
        'completed',
        'partial_success',
        'failed',
    );
}

/**
 * Create a private editorial run log.
 *
 * @param array<string, mixed> $data Run data.
 * @return int|WP_Error
 */
function revelations_editorial_create_run_log(
    array $data
) {
    $defaults = array(
        'run_date'           => gmdate( 'c' ),
        'status'             => 'running',
        'section'            => 'news',
        'sources_checked'    => array(),
        'sources_failed'     => array(),
        'errors'             => array(),
        'total_fetched'      => 0,
        'duplicates_removed' => 0,
        'candidates_selected'=> 0,
        'drafts_requested'   => 0,
        'drafts_generated'   => 0,
        'drafts_failed'      => 0,
        'duration_ms'        => 0,
        'event_type'         => 'editorial_run',
        'draft_id'           => 0,
        'candidate_id'       => 0,
        'ai_model'           => '',
        'ai_input_tokens'    => 0,
        'ai_output_tokens'   => 0,
        'ai_generation'      => 0,
        'ai_regenerated'     => false,
        'error_code'         => '',
    );

    $data = wp_parse_args( $data, $defaults );

    $section = function_exists(
        'revelations_editorial_sanitize_section'
    )
        ? revelations_editorial_sanitize_section(
            $data['section']
        )
        : 'news';

    $status = sanitize_key(
        (string) $data['status']
    );

    if (
        ! in_array(
            $status,
            revelations_editorial_run_statuses(),
            true
        )
    ) {
        $status = 'running';
    }

    $event_type = sanitize_key(
        (string) $data['event_type']
    );

    if (
        ! in_array(
            $event_type,
            array(
                'editorial_run',
                'rss_scan',
                'ai_generation',
            ),
            true
        )
    ) {
        $event_type = 'editorial_run';
    }

    $event_label = match ( $event_type ) {
        'rss_scan' =>
            'RSS scan',

        'ai_generation' =>
            'AI generation',

        default =>
            'Editorial run',
    };

    $post_id = wp_insert_post(
        array(
            'post_type'   => 'rev_run_log',
            'post_status' => 'publish',

            'post_title'  => sprintf(
                '%s — %s — %s',
                $event_label,
                strtoupper( $section ),
                sanitize_text_field(
                    (string) $data['run_date']
                )
            ),

            'post_author' => get_current_user_id(),
        ),
        true
    );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    $sanitize_list = static function (
        mixed $values
    ): array {
        if ( ! is_array( $values ) ) {
            return array();
        }

        return array_values(
            array_filter(
                array_map(
                    static fn ( mixed $value ): string =>
                        sanitize_text_field(
                            (string) $value
                        ),
                    $values
                )
            )
        );
    };

    $meta = array(
        '_rev_run_date' =>
            sanitize_text_field(
                (string) $data['run_date']
            ),

        '_rev_run_status' => $status,
        '_rev_section'    => $section,

        '_rev_sources_checked' =>
            wp_json_encode(
                $sanitize_list(
                    $data['sources_checked']
                ),
                JSON_UNESCAPED_SLASHES
            ),

        '_rev_sources_failed' =>
            wp_json_encode(
                $sanitize_list(
                    $data['sources_failed']
                ),
                JSON_UNESCAPED_SLASHES
            ),

        '_rev_errors' =>
            wp_json_encode(
                $sanitize_list(
                    $data['errors']
                ),
                JSON_UNESCAPED_SLASHES
            ),

        '_rev_total_fetched' =>
            absint( $data['total_fetched'] ),

        '_rev_duplicates_removed' =>
            absint( $data['duplicates_removed'] ),

        '_rev_candidates_selected' =>
            absint( $data['candidates_selected'] ),

        '_rev_drafts_requested' =>
            absint( $data['drafts_requested'] ),

        '_rev_drafts_generated' =>
            absint( $data['drafts_generated'] ),

        '_rev_drafts_failed' =>
            absint( $data['drafts_failed'] ),

        '_rev_duration_ms' =>
            absint( $data['duration_ms'] ),

        '_rev_event_type' =>
            $event_type,

        '_rev_draft_id' =>
            absint( $data['draft_id'] ),

        '_rev_candidate_id' =>
            absint( $data['candidate_id'] ),

        '_rev_ai_model' =>
            sanitize_text_field(
                (string) $data['ai_model']
            ),

        '_rev_ai_input_tokens' =>
            absint( $data['ai_input_tokens'] ),

        '_rev_ai_output_tokens' =>
            absint( $data['ai_output_tokens'] ),

        '_rev_ai_generation' =>
            absint( $data['ai_generation'] ),

        '_rev_ai_regenerated' =>
            ! empty( $data['ai_regenerated'] )
                ? 1
                : 0,

        '_rev_error_code' =>
            sanitize_key(
                (string) $data['error_code']
            ),
    );

    foreach ( $meta as $key => $value ) {
        update_post_meta(
            (int) $post_id,
            $key,
            $value
        );
    }

    return (int) $post_id;
}

/**
 * Record one completed or failed AI-generation attempt.
 *
 * Logging is intentionally non-blocking for the generation workflow.
 *
 * @param array<string, mixed> $result Successful generation data.
 * @return int|WP_Error
 */
function revelations_editorial_log_ai_generation(
    int $draft_id,
    string $status,
    int $duration_ms,
    array $result = array(),
    ?WP_Error $error = null
) {
    $candidate_id = absint(
        get_post_meta(
            $draft_id,
            '_revelations_editorial_candidate_id',
            true
        )
    );

    $section = sanitize_key(
        (string) (
            $result['section']
            ?? get_post_meta(
                $draft_id,
                '_revelations_ai_section_suggestion',
                true
            )
        )
    );

    if ( '' === $section ) {
        $category_slugs = wp_get_post_categories(
            $draft_id,
            array(
                'fields' => 'slugs',
            )
        );

        $section = sanitize_key(
            (string) (
                $category_slugs[0] ?? 'news'
            )
        );
    }

    if (
        function_exists(
            'revelations_editorial_sanitize_section'
        )
    ) {
        $section =
            revelations_editorial_sanitize_section(
                $section
            );
    }

    $model = sanitize_text_field(
        (string) (
            $result['model'] ?? ''
        )
    );

    if (
        '' === $model &&
        defined(
            'REVELATIONS_OPENAI_MODEL'
        )
    ) {
        $model = sanitize_text_field(
            (string) REVELATIONS_OPENAI_MODEL
        );
    }

    if ( '' === $model ) {
        $model = sanitize_text_field(
            (string) get_post_meta(
                $draft_id,
                '_revelations_ai_model',
                true
            )
        );
    }

    $current_generation = absint(
        get_post_meta(
            $draft_id,
            '_revelations_ai_generation_number',
            true
        )
    );

    $completed =
        'completed' === $status;

    $generation_number = $completed
        ? max(
            1,
            $current_generation
        )
        : $current_generation + 1;

    $regenerated = $completed
        ? ! empty(
            $result['regenerated']
        )
        : $current_generation > 0;

    $error_code = '';

    if ( $error instanceof WP_Error ) {
        $error_code = sanitize_key(
            (string) $error->get_error_code()
        );
    }

    if (
        '' === $error_code &&
        'failed' === $status
    ) {
        $error_code =
            'ai_generation_failed';
    }

    return revelations_editorial_create_run_log(
        array(
            'run_date' =>
                gmdate( 'c' ),

            'event_type' =>
                'ai_generation',

            'status' =>
                $completed
                    ? 'completed'
                    : 'failed',

            'section' =>
                $section,

            'sources_checked' =>
                array(),

            'sources_failed' =>
                array(),

            /*
             * Store only the safe error code.
             * Do not store prompts, article text,
             * API responses or the API key.
             */
            'errors' =>
                '' !== $error_code
                    ? array( $error_code )
                    : array(),

            'total_fetched' =>
                0,

            'duplicates_removed' =>
                0,

            'candidates_selected' =>
                $candidate_id > 0
                    ? 1
                    : 0,

            'drafts_requested' =>
                1,

            'drafts_generated' =>
                $completed
                    ? 1
                    : 0,

            'drafts_failed' =>
                $completed
                    ? 0
                    : 1,

            'duration_ms' =>
                max(
                    0,
                    $duration_ms
                ),

            'draft_id' =>
                $draft_id,

            'candidate_id' =>
                $candidate_id,

            'ai_model' =>
                $model,

            'ai_input_tokens' =>
                absint(
                    $result['input_tokens']
                    ?? 0
                ),

            'ai_output_tokens' =>
                absint(
                    $result['output_tokens']
                    ?? 0
                ),

            'ai_generation' =>
                $generation_number,

            'ai_regenerated' =>
                $regenerated,

            'error_code' =>
                $error_code,
        )
    );
}

/**
 * Get recent run logs.
 *
 * @return WP_Post[]
 */
function revelations_editorial_recent_run_logs(): array {
    return get_posts(
        array(
            'post_type'      => 'rev_run_log',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
}

/**
 * Decode a stored JSON list.
 *
 * @return string[]
 */
function revelations_editorial_decode_log_list(
    int $run_id,
    string $meta_key
): array {
    $decoded = json_decode(
        (string) get_post_meta(
            $run_id,
            $meta_key,
            true
        ),
        true
    );

    return is_array( $decoded )
        ? array_values( $decoded )
        : array();
}

/**
 * Render the Run Logs tab.
 */
function revelations_editorial_render_run_logs(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $logs = revelations_editorial_recent_run_logs();
    ?>
    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <h2>Run Logs</h2>

        <p class="revelations-desk__section-description">
            RSS scans, source failures, candidate counts and
            AI-generation results are recorded privately.
        </p>

        <?php if ( $logs === array() ) : ?>
            <p>No editorial runs have been recorded.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Run</th>
                        <th>Status</th>
                        <th>Fetched</th>
                        <th>Duplicates</th>
                        <th>Candidates</th>
                        <th>Drafts</th>
                        <th>Sources</th>
                        <th>AI</th>
                        <th>Duration</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <?php
                        $run_id = (int) $log->ID;

                        $section = (string) get_post_meta(
                            $run_id,
                            '_rev_section',
                            true
                        );

                        $status = (string) get_post_meta(
                            $run_id,
                            '_rev_run_status',
                            true
                        );

                        $sources_checked =
                            revelations_editorial_decode_log_list(
                                $run_id,
                                '_rev_sources_checked'
                            );

                        $sources_failed =
                            revelations_editorial_decode_log_list(
                                $run_id,
                                '_rev_sources_failed'
                            );

                        $errors =
                            revelations_editorial_decode_log_list(
                                $run_id,
                                '_rev_errors'
                            );

                        $duration_ms = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_duration_ms',
                                true
                            )
                        );

                        $event_type = (string) get_post_meta(
                            $run_id,
                            '_rev_event_type',
                            true
                        );

                        if ( '' === $event_type ) {
                            $event_type = 'editorial_run';
                        }

                        $draft_id = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_draft_id',
                                true
                            )
                        );

                        $candidate_id = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_candidate_id',
                                true
                            )
                        );

                        $ai_model = (string) get_post_meta(
                            $run_id,
                            '_rev_ai_model',
                            true
                        );

                        $ai_input_tokens = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_ai_input_tokens',
                                true
                            )
                        );

                        $ai_output_tokens = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_ai_output_tokens',
                                true
                            )
                        );

                        $ai_generation = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_ai_generation',
                                true
                            )
                        );

                        $ai_regenerated = 1 === absint(
                            get_post_meta(
                                $run_id,
                                '_rev_ai_regenerated',
                                true
                            )
                        );

                        $error_code = (string) get_post_meta(
                            $run_id,
                            '_rev_error_code',
                            true
                        );

                        $drafts_requested = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_drafts_requested',
                                true
                            )
                        );

                        $drafts_generated = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_drafts_generated',
                                true
                            )
                        );

                        $drafts_failed = absint(
                            get_post_meta(
                                $run_id,
                                '_rev_drafts_failed',
                                true
                            )
                        );
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        ucfirst( $section )
                                    ); ?>
                                </strong>

                                <div class="revelations-candidate-meta">
                                    <?php echo esc_html(
                                        get_date_from_gmt(
                                            $log->post_date_gmt,
                                            'M j, Y H:i:s'
                                        )
                                    ); ?>

                                    · ID <?php echo esc_html(
                                        (string) $run_id
                                    ); ?>
                                </div>

                                <div class="revelations-candidate-meta">
                                    <?php echo esc_html(
                                        match ( $event_type ) {
                                            'ai_generation' =>
                                                'AI generation',

                                            'rss_scan' =>
                                                'RSS scan',

                                            default =>
                                                'Editorial run',
                                        }
                                    ); ?>

                                    <?php if ( $draft_id > 0 ) : ?>
                                        · Draft <?php echo esc_html(
                                            (string) $draft_id
                                        ); ?>
                                    <?php endif; ?>

                                    <?php if ( $candidate_id > 0 ) : ?>
                                        · Candidate <?php echo esc_html(
                                            (string) $candidate_id
                                        ); ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <span
                                    class="
                                        revelations-status
                                        revelations-run-status
                                        revelations-run-status--<?php
                                            echo esc_attr(
                                                sanitize_html_class(
                                                    $status
                                                )
                                            );
                                        ?>
                                    "
                                >
                                    <?php echo esc_html(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $status
                                        )
                                    ); ?>
                                </span>

                                <?php if ( $errors !== array() ) : ?>
                                    <div class="revelations-candidate-meta">
                                        <?php echo esc_html(
                                            count( $errors ) .
                                            ' error(s)'
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    (string) absint(
                                        get_post_meta(
                                            $run_id,
                                            '_rev_total_fetched',
                                            true
                                        )
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    (string) absint(
                                        get_post_meta(
                                            $run_id,
                                            '_rev_duplicates_removed',
                                            true
                                        )
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    (string) absint(
                                        get_post_meta(
                                            $run_id,
                                            '_rev_candidates_selected',
                                            true
                                        )
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    sprintf(
                                        '%d/%d',
                                        $drafts_generated,
                                        $drafts_requested
                                    )
                                ); ?>

                                <?php if ( $drafts_failed > 0 ) : ?>
                                    <div class="revelations-candidate-meta">
                                        <?php echo esc_html(
                                            $drafts_failed .
                                            ' failed'
                                        ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    'ai_generation' === $event_type
                                ) : ?>
                                    —
                                <?php else : ?>
                                    <?php echo esc_html(
                                        count( $sources_checked ) .
                                        ' OK'
                                    ); ?>

                                    <?php if (
                                        $sources_failed !== array()
                                    ) : ?>
                                        <div class="revelations-candidate-meta">
                                            <?php echo esc_html(
                                                count( $sources_failed ) .
                                                ' failed'
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    'ai_generation' === $event_type
                                ) : ?>
                                    <strong>
                                        <?php echo esc_html(
                                            '' !== $ai_model
                                                ? $ai_model
                                                : 'Unknown model'
                                        ); ?>
                                    </strong>

                                    <div class="revelations-candidate-meta">
                                        Generation <?php echo esc_html(
                                            (string) $ai_generation
                                        ); ?>

                                        <?php if ( $ai_regenerated ) : ?>
                                            · regeneration
                                        <?php endif; ?>
                                    </div>

                                    <div class="revelations-candidate-meta">
                                        <?php echo esc_html(
                                            sprintf(
                                                '%d in / %d out',
                                                $ai_input_tokens,
                                                $ai_output_tokens
                                            )
                                        ); ?>
                                    </div>

                                    <?php if ( '' !== $error_code ) : ?>
                                        <div class="revelations-candidate-meta">
                                            Error: <?php echo esc_html(
                                                $error_code
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    number_format(
                                        $duration_ms / 1000,
                                        2
                                    ) . ' s'
                                ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Run-log status styling.
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
            .revelations-run-status--running {
                background:#e7f3ff;
                color:#0a4b78;
            }

            .revelations-run-status--completed {
                background:#e8f5e9;
                color:#1b5e20;
            }

            .revelations-run-status--partial_success {
                background:#fff4ce;
                color:#6a4b00;
            }

            .revelations-run-status--failed {
                background:#fce8e6;
                color:#8a2424;
            }
            '
        );
    }
);

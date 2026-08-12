<?php
/**
 * Plugin Name: REVELATIONS Editorial Unspoken Preview
 * Description: Admin-only preview for strict Unspoken RSS candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle an explicitly requested Unspoken preview.
 */
add_action(
    'admin_post_revelations_preview_unspoken_scan',
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
            'revelations_preview_unspoken_scan'
        );

        $result = function_exists(
            'revelations_editorial_generate_preview'
        )
            ? revelations_editorial_generate_preview(
                'unspoken'
            )
            : new WP_Error(
                'scanner_unavailable',
                'Editorial preview runner is unavailable.'
            );

        $args = array(
            'page' =>
                'revelations-editorial-desk',
            'view' =>
                'candidates',
            'scan_section' =>
                'unspoken',
        );

        if ( is_wp_error( $result ) ) {
            $args['scan_error'] =
                'scanner_disabled' ===
                $result->get_error_code()
                    ? 'disabled'
                    : 'scanner';
        } else {
            $args['scan_ready'] = 1;
        }

        wp_safe_redirect(
            add_query_arg(
                $args,
                admin_url( 'admin.php' )
            )
        );

        exit;
    }
);

/**
 * Render the current Unspoken preview and reputational safeguards.
 */
function revelations_editorial_render_unspoken_preview(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $runtime = function_exists(
        'revelations_editorial_preview_runtime_config'
    )
        ? revelations_editorial_preview_runtime_config(
            'unspoken'
        )
        : array(
            'enabled' => false,
            'preview_limit' => 5,
        );

    $scanner_enabled =
        ! empty( $runtime['enabled'] );
    $preview_limit = max(
        1,
        absint(
            $runtime['preview_limit'] ?? 5
        )
    );

    $preview_key = function_exists(
        'revelations_editorial_preview_key'
    )
        ? revelations_editorial_preview_key(
            'unspoken'
        )
        : '';

    $preview = '' !== $preview_key
        ? get_transient( $preview_key )
        : false;

    $scan_section = sanitize_key(
        (string) (
            $_GET['scan_section'] ?? ''
        )
    );
    $candidate_section = sanitize_key(
        (string) (
            $_GET['candidate_section'] ?? ''
        )
    );

    $qualified = is_array( $preview ) &&
        isset( $preview['qualified_candidates'] ) &&
        is_array( $preview['qualified_candidates'] )
            ? $preview['qualified_candidates']
            : array();

    $active_sources = is_array( $preview ) &&
        isset( $preview['active_sources'] ) &&
        is_array( $preview['active_sources'] )
            ? $preview['active_sources']
            : array();
    ?>

    <section
        class="revelations-desk__section revelations-tech-preview"
        style="margin-top:20px"
    >
        <div class="revelations-tech-preview__header">
            <div>
                <h2>Unspoken RSS scan</h2>

                <p class="revelations-desk__section-description">
                    Preview AI and future-tech stories with overlooked
                    consequences, limitations, hidden layers and
                    second-order effects. Results are leads for strict
                    human and reputational review, not findings of
                    truth. No candidate is created automatically.

                    <?php if ( $scanner_enabled ) : ?>
                        Current preview limit:
                        <strong>
                            <?php echo esc_html(
                                (string) $preview_limit
                            ); ?>
                        </strong>.
                    <?php else : ?>
                        <strong>
                            Scanner is disabled by default in Settings.
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
                    value="revelations_preview_unspoken_scan"
                >

                <?php wp_nonce_field(
                    'revelations_preview_unspoken_scan'
                ); ?>

                <?php submit_button(
                    'Preview Unspoken scan',
                    'primary',
                    'submit',
                    false,
                    $scanner_enabled
                        ? ''
                        : 'disabled="disabled" aria-disabled="true"'
                ); ?>
            </form>
        </div>

        <?php if (
            'unspoken' === $scan_section &&
            isset( $_GET['scan_ready'] ) &&
            is_array( $preview )
        ) : ?>
            <div class="notice notice-success inline">
                <p>Unspoken preview completed.</p>
            </div>
        <?php elseif (
            'unspoken' === $scan_section &&
            'disabled' === (
                $_GET['scan_error'] ?? ''
            )
        ) : ?>
            <div class="notice notice-warning inline">
                <p>
                    Unspoken scanner is disabled in Settings.
                </p>
            </div>
        <?php elseif (
            'unspoken' === $scan_section &&
            isset( $_GET['scan_error'] )
        ) : ?>
            <div class="notice notice-error inline">
                <p>
                    Unspoken preview could not be completed.
                    Review the source diagnostics below.
                </p>
            </div>
        <?php endif; ?>

        <?php if (
            'unspoken' === $candidate_section &&
            isset( $_GET['candidate_saved'] )
        ) : ?>
            <div class="notice notice-success inline">
                <p>Unspoken candidate saved for editorial review.</p>
            </div>
        <?php elseif (
            'unspoken' === $candidate_section &&
            isset( $_GET['candidate_duplicate'] )
        ) : ?>
            <div class="notice notice-warning inline">
                <p>This story already exists as a candidate.</p>
            </div>
        <?php elseif (
            'unspoken' === $candidate_section &&
            isset( $_GET['candidate_error'] )
        ) : ?>
            <div class="notice notice-error inline">
                <p>The Unspoken candidate could not be saved.</p>
            </div>
        <?php endif; ?>

        <?php if ( ! is_array( $preview ) ) : ?>
            <p>
                No Unspoken preview has been generated in this session.
            </p>
        <?php else : ?>
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
                    qualified - showing
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
                    records created automatically
                </span>
            </div>

            <p class="description" style="margin-top:12px">
                Global relevance filtered:
                <strong>
                    <?php echo esc_html(
                        (string) absint(
                            $preview['ai_gate_filtered'] ?? 0
                        )
                    ); ?>
                </strong>.
            </p>

            <?php
            $source_results = isset(
                $preview['source_results']
            ) && is_array(
                $preview['source_results']
            )
                ? $preview['source_results']
                : array();
            ?>

            <?php if ( array() !== $source_results ) : ?>
                <details style="margin:12px 0">
                    <summary>Source diagnostics</summary>
                    <ul>
                        <?php foreach (
                            $source_results as $source_result
                        ) : ?>
                            <li>
                                <?php echo esc_html(
                                    (string) (
                                        $source_result['source']
                                        ?? 'Unknown source'
                                    )
                                ); ?>:
                                <?php echo esc_html(
                                    ! empty(
                                        $source_result['success']
                                    )
                                        ? 'available'
                                        : 'skipped safely - ' .
                                            (string) (
                                                $source_result['error']
                                                ?? 'feed error'
                                            )
                                ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>

            <?php if ( array() === $qualified ) : ?>
                <p>
                    No stories passed the current Unspoken editorial rules.
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
                        $scores = is_array( $item['scores'] ?? null )
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
                                <div class="revelations-candidate-meta">
                                    <?php echo esc_html(
                                        (string) (
                                            $item['reason'] ?? ''
                                        )
                                    ); ?>
                                </div>
                                <div class="revelations-candidate-meta">
                                    Evidence type:
                                    <?php echo esc_html(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) (
                                                $item['evidence_type']
                                                ?? 'unspecified'
                                            )
                                        )
                                    ); ?>
                                </div>
                                <?php if ( '' !== ( $item['secondary_section'] ?? '' ) ) : ?>
                                    <div class="revelations-candidate-meta">
                                        Secondary section advisory:
                                        <?php echo esc_html(
                                            ucfirst(
                                                (string) $item[
                                                    'secondary_section'
                                                ]
                                            )
                                        ); ?>.
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $item['single_source_allegation'] ) ) : ?>
                                    <div class="revelations-candidate-meta">
                                        Single-source allegation - requires
                                        reputational review.
                                    </div>
                                <?php endif; ?>
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
                                <span class="revelations-preview-track">
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
                                <div class="revelations-candidate-meta">
                                    R <?php echo esc_html(
                                        (string) (
                                            $scores['relevance'] ?? 0
                                        )
                                    ); ?>
                                    · I <?php echo esc_html(
                                        (string) (
                                            $scores['implementation'] ?? 0
                                        )
                                    ); ?>
                                    · F <?php echo esc_html(
                                        (string) (
                                            $scores['freshness'] ?? 0
                                        )
                                    ); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $age_hours = $item['age_hours'] ?? null;
                                echo esc_html(
                                    null === $age_hours
                                        ? '-'
                                        : number_format(
                                            (float) $age_hours,
                                            1
                                        ) . ' h'
                                );
                                ?>
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
                                function_exists(
                                    'revelations_editorial_preview_candidate_exists'
                                )
                                    ? revelations_editorial_preview_candidate_exists(
                                        $duplicate_key
                                    )
                                    : 0;
                            ?>

                            <?php if (
                                $stored_candidate_id > 0
                            ) : ?>
                                <span class="revelations-preview-saved">
                                    Saved
                                </span>
                            <?php else : ?>
                                <form
                                    method="post"
                                    action="<?php echo esc_url(
                                        admin_url(
                                            'admin-post.php'
                                        )
                                    ); ?>"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="revelations_save_preview_candidate"
                                    >

                                    <input
                                        type="hidden"
                                        name="preview_section"
                                        value="unspoken"
                                    >

                                    <input
                                        type="hidden"
                                        name="candidate_index"
                                        value="<?php echo esc_attr(
                                            (string) $candidate_index
                                        ); ?>"
                                    >

                                    <?php wp_nonce_field(
                                        'revelations_save_preview_candidate_' .
                                        'unspoken_' .
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

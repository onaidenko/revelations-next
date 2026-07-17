<?php
/**
 * Plugin Name: REVELATIONS Editorial People Preview
 * Description: Admin-only People RSS preview control.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle the People preview request.
 *
 * The shared runner blocks the request while People is disabled.
 */
add_action(
    'admin_post_revelations_preview_people_scan',
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
            'revelations_preview_people_scan'
        );

        if (
            ! function_exists(
                'revelations_editorial_generate_preview'
            )
        ) {
            $result = new WP_Error(
                'scanner_unavailable',
                'Editorial preview runner is unavailable.'
            );
        } else {
            $result =
                revelations_editorial_generate_preview(
                    'people'
                );
        }

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' =>
                            'revelations-editorial-desk',

                        'view' =>
                            'candidates',

                        'scan_section' =>
                            'people',

                        'scan_error' =>
                            'scanner_disabled' ===
                            $result->get_error_code()
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
                        'people',

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
 * Render the People preview card.
 */
function revelations_editorial_render_people_preview(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $runtime = function_exists(
        'revelations_editorial_preview_runtime_config'
    )
        ? revelations_editorial_preview_runtime_config(
            'people'
        )
        : array(
            'enabled' =>
                false,

            'preview_limit' =>
                5,
        );

    $scanner_enabled =
        ! empty( $runtime['enabled'] );

    $preview_limit = max(
        1,
        min(
            20,
            absint(
                $runtime['preview_limit'] ?? 5
            )
        )
    );

    $preview_key = function_exists(
        'revelations_editorial_preview_key'
    )
        ? revelations_editorial_preview_key(
            'people'
        )
        : '';

    $preview = '' !== $preview_key
        ? get_transient(
            $preview_key
        )
        : false;

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
        'people' === $candidate_section &&
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
        'people' === $candidate_section &&
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

    $candidate_error = 'people' === $candidate_section
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
        'people' === $scan_section &&
        isset( $_GET['scan_ready'] )
    ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                People scan completed in dry-run mode.
                No candidates or articles were created.
                A private run log was recorded.
            </p>
        </div>
        <?php
    }

    $scan_error = 'people' === $scan_section
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
                'disabled',
                'scanner',
            ),
            true
        )
    ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo esc_html(
                    'disabled' === $scan_error
                        ? 'The People scanner is disabled in Settings.'
                        : 'The People scanner is not available.'
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
                <h2>People RSS scan</h2>

                <p class="revelations-desk__section-description">
                    Preview qualified stories from active People
                    sources. The scan does not automatically create
                    candidates, drafts or articles.

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
                    value="revelations_preview_people_scan"
                >

                <?php wp_nonce_field(
                    'revelations_preview_people_scan'
                ); ?>

                <?php submit_button(
                    'Preview People scan',
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
                No People preview has been generated in this session.
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
                    records created automatically
                </span>
            </div>

            <?php if ( array() === $qualified ) : ?>
                <p>
                    No stories passed the current People editorial rules.
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

                                        · P <?php echo esc_html(
                                            (string) (
                                                $scores[
                                                    'impact'
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
                                    <?php
                                    $age_hours =
                                        $item['age_hours'] ?? null;

                                    echo esc_html(
                                        null === $age_hours
                                            ? '—'
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
                                                value="revelations_save_preview_candidate"
                                            >

                                            <input
                                                type="hidden"
                                                name="preview_section"
                                                value="people"
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
                                                'people_' .
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

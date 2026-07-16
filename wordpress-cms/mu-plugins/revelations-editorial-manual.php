<?php
/**
 * Plugin Name: REVELATIONS Editorial Manual Candidates
 * Description: Private manual candidate intake for Editorial Desk.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate a stable duplicate key from a source URL.
 */
function revelations_editorial_duplicate_key(
    string $source_url,
    string $title = ''
): string {
    $parts = wp_parse_url( $source_url );

    if (
        is_array( $parts ) &&
        ! empty( $parts['host'] )
    ) {
        $host = strtolower(
            preg_replace(
                '/^www\./i',
                '',
                (string) $parts['host']
            )
        );

        $path = isset( $parts['path'] )
            ? strtolower(
                untrailingslashit(
                    (string) $parts['path']
                )
            )
            : '';

        $query_string = '';

        if ( ! empty( $parts['query'] ) ) {
            parse_str(
                (string) $parts['query'],
                $query
            );

            foreach ( array_keys( $query ) as $key ) {
                if (
                    str_starts_with(
                        strtolower( (string) $key ),
                        'utm_'
                    ) ||
                    in_array(
                        strtolower( (string) $key ),
                        array(
                            'fbclid',
                            'gclid',
                            'mc_cid',
                            'mc_eid',
                        ),
                        true
                    )
                ) {
                    unset( $query[ $key ] );
                }
            }

            if ( $query !== array() ) {
                ksort( $query );

                $query_string = '?' . http_build_query(
                    $query
                );
            }
        }

        return substr(
            $host . $path . $query_string,
            0,
            500
        );
    }

    return sanitize_title( $title );
}

/**
 * Redirect back to Editorial Desk.
 */
function revelations_editorial_manual_redirect(
    array $args
): never {
    wp_safe_redirect(
        add_query_arg(
            $args,
            admin_url(
                'admin.php?page=revelations-editorial-desk'
            )
        )
    );

    exit;
}

/**
 * Save a manually entered editorial candidate.
 */
add_action(
    'admin_post_revelations_add_editorial_candidate',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to add candidates.',
                    'revelations'
                )
            );
        }

        check_admin_referer(
            'revelations_add_editorial_candidate'
        );

        $title = sanitize_text_field(
            wp_unslash(
                $_POST['candidate_title'] ?? ''
            )
        );

        $source_url = esc_url_raw(
            wp_unslash(
                $_POST['source_url'] ?? ''
            )
        );

        $source_name = sanitize_text_field(
            wp_unslash(
                $_POST['source_name'] ?? ''
            )
        );

        $summary = sanitize_textarea_field(
            wp_unslash(
                $_POST['summary'] ?? ''
            )
        );

        $section = function_exists(
            'revelations_editorial_sanitize_section'
        )
            ? revelations_editorial_sanitize_section(
                $_POST['section'] ?? 'news'
            )
            : 'news';

        if (
            $title === '' ||
            $source_url === '' ||
            ! wp_http_validate_url( $source_url )
        ) {
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'invalid',
                )
            );
        }

        if ( $source_name === '' ) {
            $host = wp_parse_url(
                $source_url,
                PHP_URL_HOST
            );

            $source_name = is_string( $host )
                ? preg_replace(
                    '/^www\./i',
                    '',
                    $host
                )
                : 'Manual source';
        }

        $duplicate_key =
            revelations_editorial_duplicate_key(
                $source_url,
                $title
            );

        $existing = get_posts(
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

                'meta_query' => array(
                    array(
                        'key'   => '_rev_duplicate_key',
                        'value' => $duplicate_key,
                    ),
                ),
            )
        );

        if ( $existing !== array() ) {
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'duplicate',
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
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'save',
                )
            );
        }

        $meta = array(
            '_rev_source_url'     => $source_url,
            '_rev_source_name'    => $source_name,
            '_rev_fetched_at'     => gmdate( 'c' ),
            '_rev_summary'        => $summary,
            '_rev_raw_excerpt'    => $summary,
            '_rev_section'        => $section,
            '_rev_duplicate_key'  => $duplicate_key,
            '_rev_status'         => 'selected',
            '_rev_error_message'  => '',
            '_rev_scoring_reason' => 'Added manually by an editor.',
        );

        foreach ( $meta as $key => $value ) {
            update_post_meta(
                (int) $candidate_id,
                $key,
                $value
            );
        }

        revelations_editorial_manual_redirect(
            array(
                'candidate_added' => (int) $candidate_id,
            )
        );
    }
);

/**
 * Return a validated private candidate.
 */
function revelations_editorial_candidate_for_action(
    int $candidate_id
): ?WP_Post {
    $candidate = get_post( $candidate_id );

    if (
        ! $candidate ||
        'rev_candidate' !== $candidate->post_type
    ) {
        return null;
    }

    return $candidate;
}

/**
 * Reject an editorial candidate.
 */
add_action(
    'admin_post_revelations_reject_editorial_candidate',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to reject candidates.',
                    'revelations'
                )
            );
        }

        $candidate_id = absint(
            $_POST['candidate_id'] ?? 0
        );

        check_admin_referer(
            'revelations_reject_editorial_candidate_' .
            $candidate_id
        );

        $candidate =
            revelations_editorial_candidate_for_action(
                $candidate_id
            );

        if ( ! $candidate ) {
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'not_found',
                )
            );
        }

        update_post_meta(
            $candidate_id,
            '_rev_status',
            'rejected'
        );

        update_post_meta(
            $candidate_id,
            '_rev_error_message',
            ''
        );

        revelations_editorial_manual_redirect(
            array(
                'candidate_action' => 'rejected',
            )
        );
    }
);

/**
 * Permanently delete an editorial candidate.
 */
add_action(
    'admin_post_revelations_delete_editorial_candidate',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to delete candidates.',
                    'revelations'
                )
            );
        }

        $candidate_id = absint(
            $_POST['candidate_id'] ?? 0
        );

        check_admin_referer(
            'revelations_delete_editorial_candidate_' .
            $candidate_id
        );

        $candidate =
            revelations_editorial_candidate_for_action(
                $candidate_id
            );

        if ( ! $candidate ) {
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'not_found',
                )
            );
        }

        $deleted = wp_delete_post(
            $candidate_id,
            true
        );

        if ( ! $deleted ) {
            revelations_editorial_manual_redirect(
                array(
                    'candidate_error' => 'delete',
                )
            );
        }

        revelations_editorial_manual_redirect(
            array(
                'candidate_action' => 'deleted',
            )
        );
    }
);

/**
 * Load recent private candidates.
 *
 * @return WP_Post[]
 */
function revelations_editorial_recent_candidates(): array {
    return get_posts(
        array(
            'post_type'      => 'rev_candidate',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
}

/**
 * Add styles to the Editorial Desk only.
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
            .revelations-desk__workspace {
                display: grid;
                grid-template-columns:
                    minmax(300px, 420px)
                    minmax(0, 1fr);
                gap: 20px;
                margin-top: 20px;
                align-items: start;
            }

            .revelations-desk__section {
                padding: 22px;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                background: #fff;
            }

            .revelations-desk__section h2 {
                margin: 0 0 6px;
            }

            .revelations-desk__section-description {
                margin: 0 0 20px;
                color: #646970;
            }

            .revelations-desk__field {
                margin-bottom: 16px;
            }

            .revelations-desk__field label {
                display: block;
                margin-bottom: 6px;
                font-weight: 600;
            }

            .revelations-desk__field input,
            .revelations-desk__field select,
            .revelations-desk__field textarea {
                width: 100%;
                max-width: none;
            }

            .revelations-desk__field textarea {
                min-height: 120px;
            }

            .revelations-candidate-title {
                font-weight: 600;
            }

            .revelations-candidate-meta {
                margin-top: 4px;
                color: #646970;
                font-size: 12px;
            }

            .revelations-status {
                display: inline-flex;
                padding: 3px 8px;
                border-radius: 999px;
                background: #e8f5e9;
                color: #1b5e20;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .revelations-candidate-actions {
                white-space: nowrap;
            }

            .revelations-candidate-actions form {
                display: inline-block;
                margin: 0 5px 4px 0;
            }

            .revelations-delete-button {
                border-color: #b32d2e !important;
                color: #b32d2e !important;
            }

            .revelations-delete-button:hover {
                border-color: #8a2424 !important;
                color: #8a2424 !important;
            }

            @media (max-width: 960px) {
                .revelations-desk__workspace {
                    grid-template-columns: 1fr;
                }
            }
            '
        );
    }
);

/**
 * Render manual intake and candidate list.
 */
function revelations_editorial_render_manual_candidates(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $candidates = revelations_editorial_recent_candidates();

    $candidate_error = sanitize_key(
        wp_unslash(
            $_GET['candidate_error'] ?? ''
        )
    );

    $candidate_action = sanitize_key(
        wp_unslash(
            $_GET['candidate_action'] ?? ''
        )
    );

    if ( isset( $_GET['candidate_added'] ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Editorial candidate saved successfully.
            </p>
        </div>
        <?php
    }

    if (
        in_array(
            $candidate_action,
            array(
                'rejected',
                'deleted',
            ),
            true
        )
    ) {
        $message = 'rejected' === $candidate_action
            ? 'Editorial candidate rejected.'
            : 'Editorial candidate deleted permanently.';
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
    }

    if ( $candidate_error !== '' ) {
        $message = match ( $candidate_error ) {
            'duplicate' =>
                'This source URL is already stored as a candidate.',

            'invalid' =>
                'Enter a title and a valid public source URL.',

            'not_found' =>
                'The requested candidate no longer exists.',

            'delete' =>
                'The candidate could not be deleted.',

            default =>
                'The candidate could not be saved.',
        };
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
    }
    ?>
    <div class="revelations-desk__workspace">
        <section class="revelations-desk__section">
            <h2>Add candidate manually</h2>

            <p class="revelations-desk__section-description">
                Save a source for later AI drafting.
                Nothing will be published automatically.
            </p>

            <form
                method="post"
                action="<?php echo esc_url(
                    admin_url( 'admin-post.php' )
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="revelations_add_editorial_candidate"
                >

                <?php wp_nonce_field(
                    'revelations_add_editorial_candidate'
                ); ?>

                <div class="revelations-desk__field">
                    <label for="candidate-title">
                        Source headline
                    </label>

                    <input
                        id="candidate-title"
                        name="candidate_title"
                        type="text"
                        required
                    >
                </div>

                <div class="revelations-desk__field">
                    <label for="candidate-url">
                        Source URL
                    </label>

                    <input
                        id="candidate-url"
                        name="source_url"
                        type="url"
                        placeholder="https://..."
                        required
                    >
                </div>

                <div class="revelations-desk__field">
                    <label for="candidate-source">
                        Source name
                    </label>

                    <input
                        id="candidate-source"
                        name="source_name"
                        type="text"
                        placeholder="The Verge"
                    >
                </div>

                <div class="revelations-desk__field">
                    <label for="candidate-section">
                        REVELATIONS section
                    </label>

                    <select
                        id="candidate-section"
                        name="section"
                    >
                        <?php
                        foreach (
                            revelations_editorial_sections()
                            as $section
                        ) :
                            ?>
                            <option
                                value="<?php echo esc_attr(
                                    $section
                                ); ?>"
                            >
                                <?php echo esc_html(
                                    ucfirst( $section )
                                ); ?>
                            </option>
                            <?php
                        endforeach;
                        ?>
                    </select>
                </div>

                <div class="revelations-desk__field">
                    <label for="candidate-summary">
                        Source summary or notes
                    </label>

                    <textarea
                        id="candidate-summary"
                        name="summary"
                        placeholder="Add the facts or context the AI must use."
                    ></textarea>
                </div>

                <?php submit_button(
                    'Save candidate',
                    'primary',
                    'submit',
                    false
                ); ?>
            </form>
        </section>

        <section class="revelations-desk__section">
            <h2>Recent candidates</h2>

            <p class="revelations-desk__section-description">
                The 20 most recently stored editorial sources.
            </p>

            <?php if ( $candidates === array() ) : ?>
                <p>No candidates have been added yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ( $candidates as $candidate ) : ?>
                            <?php
                            $candidate_id = (int) $candidate->ID;

                            $source_url = (string) get_post_meta(
                                $candidate_id,
                                '_rev_source_url',
                                true
                            );

                            $source_name = (string) get_post_meta(
                                $candidate_id,
                                '_rev_source_name',
                                true
                            );

                            $section = (string) get_post_meta(
                                $candidate_id,
                                '_rev_section',
                                true
                            );

                            $status = (string) get_post_meta(
                                $candidate_id,
                                '_rev_status',
                                true
                            );
                            ?>
                            <tr>
                                <td>
                                    <div class="revelations-candidate-title">
                                        <?php echo esc_html(
                                            $candidate->post_title
                                        ); ?>
                                    </div>

                                    <div class="revelations-candidate-meta">
                                        <?php if ( $source_url !== '' ) : ?>
                                            <a
                                                href="<?php echo esc_url(
                                                    $source_url
                                                ); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <?php echo esc_html(
                                                    $source_name !== ''
                                                        ? $source_name
                                                        : $source_url
                                                ); ?>
                                            </a>
                                        <?php endif; ?>

                                        · ID <?php echo esc_html(
                                            (string) $candidate_id
                                        ); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        ucfirst( $section )
                                    ); ?>
                                </td>

                                <td>
                                    <span class="revelations-status">
                                        <?php echo esc_html(
                                            $status
                                        ); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        get_date_from_gmt(
                                            $candidate->post_date_gmt,
                                            'M j, Y H:i'
                                        )
                                    ); ?>
                                </td>

                                <td class="revelations-candidate-actions">
                                    <?php if ( 'rejected' !== $status ) : ?>
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
                                                value="revelations_reject_editorial_candidate"
                                            >

                                            <input
                                                type="hidden"
                                                name="candidate_id"
                                                value="<?php echo esc_attr(
                                                    (string) $candidate_id
                                                ); ?>"
                                            >

                                            <?php wp_nonce_field(
                                                'revelations_reject_editorial_candidate_' .
                                                $candidate_id
                                            ); ?>

                                            <button
                                                type="submit"
                                                class="button button-small"
                                            >
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form
                                        method="post"
                                        action="<?php echo esc_url(
                                            admin_url(
                                                'admin-post.php'
                                            )
                                        ); ?>"
                                        onsubmit="return confirm(
                                            'Delete this candidate permanently?'
                                        );"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="revelations_delete_editorial_candidate"
                                        >

                                        <input
                                            type="hidden"
                                            name="candidate_id"
                                            value="<?php echo esc_attr(
                                                (string) $candidate_id
                                            ); ?>"
                                        >

                                        <?php wp_nonce_field(
                                            'revelations_delete_editorial_candidate_' .
                                            $candidate_id
                                        ); ?>

                                        <button
                                            type="submit"
                                            class="
                                                button
                                                button-small
                                                revelations-delete-button
                                            "
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
    <?php
}

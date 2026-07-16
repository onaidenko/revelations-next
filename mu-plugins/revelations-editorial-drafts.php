<?php
/**
 * Plugin Name: REVELATIONS Editorial Drafts
 * Description: Creates private WordPress source drafts from selected candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Redirect to the AI Drafts tab.
 *
 * @param array<string, scalar> $args Query arguments.
 */
function revelations_editorial_drafts_redirect(
    array $args = array()
): void {
    wp_safe_redirect(
        add_query_arg(
            array_merge(
                array(
                    'page' => 'revelations-editorial-desk',
                    'view' => 'drafts',
                ),
                $args
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Return the linked WordPress draft ID when it still exists.
 */
function revelations_editorial_candidate_draft_id(
    int $candidate_id
): int {
    $draft_id = absint(
        get_post_meta(
            $candidate_id,
            '_rev_wordpress_draft_id',
            true
        )
    );

    if (
        $draft_id > 0 &&
        'post' === get_post_type( $draft_id ) &&
        false !== get_post_status( $draft_id )
    ) {
        return $draft_id;
    }

    if ( $draft_id > 0 ) {
        delete_post_meta(
            $candidate_id,
            '_rev_wordpress_draft_id'
        );
    }

    return 0;
}

/**
 * Create a non-AI WordPress source draft.
 */
add_action(
    'admin_post_revelations_create_source_draft',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to create editorial drafts.',
                    'revelations'
                )
            );
        }

        $candidate_id = absint(
            $_POST['candidate_id'] ?? 0
        );

        check_admin_referer(
            'revelations_create_source_draft_' .
            $candidate_id
        );

        if (
            $candidate_id < 1 ||
            'rev_candidate' !== get_post_type(
                $candidate_id
            )
        ) {
            revelations_editorial_drafts_redirect(
                array(
                    'draft_error' => 'candidate',
                )
            );
        }

        $existing_draft_id =
            revelations_editorial_candidate_draft_id(
                $candidate_id
            );

        if ( $existing_draft_id > 0 ) {
            revelations_editorial_drafts_redirect(
                array(
                    'draft_existing' =>
                        $existing_draft_id,
                )
            );
        }

        $candidate_status = sanitize_key(
            (string) get_post_meta(
                $candidate_id,
                '_rev_status',
                true
            )
        );

        if (
            ! in_array(
                $candidate_status,
                array(
                    'selected',
                    'failed',
                ),
                true
            )
        ) {
            revelations_editorial_drafts_redirect(
                array(
                    'draft_error' => 'status',
                )
            );
        }

        $title = sanitize_text_field(
            get_the_title( $candidate_id )
        );

        $summary = sanitize_textarea_field(
            (string) get_post_meta(
                $candidate_id,
                '_rev_summary',
                true
            )
        );

        $source_url = esc_url_raw(
            (string) get_post_meta(
                $candidate_id,
                '_rev_source_url',
                true
            )
        );

        $source_name = sanitize_text_field(
            (string) get_post_meta(
                $candidate_id,
                '_rev_source_name',
                true
            )
        );

        $section = sanitize_key(
            (string) get_post_meta(
                $candidate_id,
                '_rev_section',
                true
            )
        );

        $editorial_track = sanitize_key(
            (string) get_post_meta(
                $candidate_id,
                '_rev_editorial_track',
                true
            )
        );

        if (
            '' === $title ||
            '' === $summary ||
            '' === $source_url
        ) {
            update_post_meta(
                $candidate_id,
                '_rev_status',
                'failed'
            );

            update_post_meta(
                $candidate_id,
                '_rev_error_message',
                'Candidate source data is incomplete.'
            );

            revelations_editorial_drafts_redirect(
                array(
                    'draft_error' => 'data',
                )
            );
        }

        if ( '' === $source_name ) {
            $source_name = 'Original source';
        }

        $content =
            "<!-- wp:paragraph -->\n" .
            '<p>' .
            esc_html( $summary ) .
            "</p>\n" .
            "<!-- /wp:paragraph -->\n\n" .

            "<!-- wp:paragraph -->\n" .
            '<p><em>Source: ' .
            '<a href="' .
            esc_url( $source_url ) .
            '" target="_blank" rel="noreferrer noopener">' .
            esc_html( $source_name ) .
            '</a></em></p>' .
            "\n<!-- /wp:paragraph -->";

        $category_ids = array();

        if ( '' !== $section ) {
            $category = get_category_by_slug(
                $section
            );

            if ( $category instanceof WP_Term ) {
                $category_ids[] =
                    (int) $category->term_id;
            }
        }

        $draft_id = wp_insert_post(
            array(
                'post_type'     => 'post',
                'post_status'   => 'draft',
                'post_title'    => $title,
                'post_content'  => $content,
                'post_excerpt'  => $summary,
                'post_author'   =>
                    get_current_user_id(),
                'post_category' =>
                    $category_ids,
            ),
            true
        );

        if ( is_wp_error( $draft_id ) ) {
            update_post_meta(
                $candidate_id,
                '_rev_status',
                'failed'
            );

            update_post_meta(
                $candidate_id,
                '_rev_error_message',
                $draft_id->get_error_message()
            );

            revelations_editorial_drafts_redirect(
                array(
                    'draft_error' => 'insert',
                )
            );
        }

        $draft_id = absint( $draft_id );

        $draft_meta = array(
            '_revelations_editorial_candidate_id' =>
                $candidate_id,

            '_revelations_source_url' =>
                $source_url,

            '_revelations_source_name' =>
                $source_name,

            '_revelations_editorial_track' =>
                $editorial_track,

            '_revelations_draft_kind' =>
                'source_scaffold',
        );

        foreach ( $draft_meta as $key => $value ) {
            update_post_meta(
                $draft_id,
                $key,
                $value
            );
        }

        update_post_meta(
            $candidate_id,
            '_rev_wordpress_draft_id',
            $draft_id
        );

        update_post_meta(
            $candidate_id,
            '_rev_status',
            'processed'
        );

        update_post_meta(
            $candidate_id,
            '_rev_error_message',
            ''
        );

        revelations_editorial_drafts_redirect(
            array(
                'draft_created' => $draft_id,
            )
        );
    }
);

/**
 * Get selected candidates that do not yet have a draft.
 *
 * @return WP_Post[]
 */
function revelations_editorial_candidates_ready_for_draft(): array {
    return get_posts(
        array(
            'post_type'      => 'rev_candidate',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_key'       => '_rev_status',
            'meta_value'     => 'selected',
        )
    );
}

/**
 * Get WordPress drafts created by Editorial Desk.
 *
 * @return WP_Post[]
 */
function revelations_editorial_source_drafts(): array {
    return get_posts(
        array(
            'post_type'      => 'post',
            'post_status'    => array(
                'draft',
                'pending',
                'private',
            ),
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     =>
                        '_revelations_editorial_candidate_id',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );
}

/**
 * Render the AI Drafts tab.
 */
function revelations_editorial_render_drafts(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if (
        function_exists(
            'revelations_editorial_render_ai_generation_notices'
        )
    ) {
        revelations_editorial_render_ai_generation_notices();
    }

    if (
        function_exists(
            'revelations_editorial_render_source_snapshot_notices'
        )
    ) {
        revelations_editorial_render_source_snapshot_notices();
    }

    if (
        function_exists(
            'revelations_editorial_render_ai_connection_test'
        )
    ) {
        revelations_editorial_render_ai_connection_test();
    }

    if (
        function_exists(
            'revelations_editorial_render_review_notices'
        )
    ) {
        revelations_editorial_render_review_notices();
    }

    $ready_candidates =
        revelations_editorial_candidates_ready_for_draft();

    $drafts =
        revelations_editorial_source_drafts();

    if ( isset( $_GET['draft_created'] ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                WordPress source draft created successfully.
                No AI request was made.
            </p>
        </div>
        <?php
    }

    if ( isset( $_GET['draft_existing'] ) ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                This candidate already has a WordPress draft.
            </p>
        </div>
        <?php
    }

    $draft_error = sanitize_key(
        wp_unslash(
            $_GET['draft_error'] ?? ''
        )
    );

    if ( '' !== $draft_error ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                The source draft could not be created.
            </p>
        </div>
        <?php
    }
    ?>
    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <h2>Selected candidates</h2>

        <p class="revelations-desk__section-description">
            Create an ordinary WordPress source draft. After the source text is fetched, AI can generate the editorial article.
        </p>

        <?php if ( $ready_candidates === array() ) : ?>
            <p>No selected candidates are waiting for a draft.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Section</th>
                        <th>Editorial track</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $ready_candidates as $candidate
                    ) : ?>
                        <?php
                        $candidate_id =
                            (int) $candidate->ID;

                        $section = sanitize_key(
                            (string) get_post_meta(
                                $candidate_id,
                                '_rev_section',
                                true
                            )
                        );

                        $track = sanitize_key(
                            (string) get_post_meta(
                                $candidate_id,
                                '_rev_editorial_track',
                                true
                            )
                        );
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        get_the_title(
                                            $candidate_id
                                        )
                                    ); ?>
                                </strong>

                                <div
                                    class="
                                        revelations-candidate-meta
                                    "
                                >
                                    Candidate ID
                                    <?php echo esc_html(
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
                                <?php echo esc_html(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $track
                                        )
                                    )
                                ); ?>
                            </td>

                            <td>
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
                                        value="revelations_create_source_draft"
                                    >

                                    <input
                                        type="hidden"
                                        name="candidate_id"
                                        value="<?php echo esc_attr(
                                            (string) $candidate_id
                                        ); ?>"
                                    >

                                    <?php wp_nonce_field(
                                        'revelations_create_source_draft_' .
                                        $candidate_id
                                    ); ?>

                                    <?php submit_button(
                                        'Create source draft',
                                        'primary small',
                                        'submit',
                                        false
                                    ); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <h2>Editorial drafts</h2>

        <p class="revelations-desk__section-description">
            WordPress drafts linked to Editorial Desk candidates.
        </p>

        <?php if ( $drafts === array() ) : ?>
            <p>No Editorial Desk drafts have been created.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Draft</th>
                        <th>Section</th>
                        <th>Kind</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ( $drafts as $draft ) : ?>
                        <?php
                        $draft_id = (int) $draft->ID;

                        $candidate_id = absint(
                            get_post_meta(
                                $draft_id,
                                '_revelations_editorial_candidate_id',
                                true
                            )
                        );

                        $categories =
                            wp_get_post_categories(
                                $draft_id,
                                array(
                                    'fields' => 'names',
                                )
                            );

                        $edit_link =
                            get_edit_post_link(
                                $draft_id,
                                ''
                            );

                        if ( ! is_string( $edit_link ) ) {
                            $edit_link = admin_url(
                                'post.php?post=' .
                                $draft_id .
                                '&action=edit'
                            );
                        }
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        get_the_title(
                                            $draft_id
                                        )
                                    ); ?>
                                </strong>

                                <div
                                    class="
                                        revelations-candidate-meta
                                    "
                                >
                                    Draft ID
                                    <?php echo esc_html(
                                        (string) $draft_id
                                    ); ?>

                                    · Candidate ID
                                    <?php echo esc_html(
                                        (string) $candidate_id
                                    ); ?>
                                </div>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $categories !== array()
                                        ? implode(
                                            ', ',
                                            $categories
                                        )
                                        : '—'
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $draft_kind = sanitize_key(
                                    (string) get_post_meta(
                                        $draft_id,
                                        '_revelations_draft_kind',
                                        true
                                    )
                                );

                                echo esc_html(
                                    'ai_generated' === $draft_kind
                                        ? 'AI generated'
                                        : 'Source scaffold'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                $created_timestamp =
                                    get_post_timestamp(
                                        $draft
                                    );

                                echo esc_html(
                                    false !== $created_timestamp
                                    && $created_timestamp > 0
                                        ? wp_date(
                                            'M j, Y H:i',
                                            $created_timestamp
                                        )
                                        : '—'
                                );
                                ?>
                            </td>

                            <td>
                                <a
                                    class="button button-secondary"
                                    href="<?php echo esc_url(
                                        $edit_link
                                    ); ?>"
                                >
                                    Open in editor
                                </a>

                                <?php
                                if (
                                    function_exists(
                                        'revelations_editorial_render_source_snapshot_action'
                                    )
                                ) {
                                    revelations_editorial_render_source_snapshot_action(
                                        $candidate_id,
                                        $draft_id
                                    );
                                }

                                if (
                                    function_exists(
                                        'revelations_editorial_render_ai_readiness_action'
                                    )
                                ) {
                                    revelations_editorial_render_ai_readiness_action(
                                        $draft_id
                                    );
                                }

                                if (
                                    function_exists(
                                        'revelations_editorial_render_review_action'
                                    )
                                ) {
                                    revelations_editorial_render_review_action(
                                        $draft_id
                                    );
                                }

                                if (
                                    function_exists(
                                        'revelations_editorial_render_ai_versions_action'
                                    )
                                ) {
                                    revelations_editorial_render_ai_versions_action(
                                        $draft_id
                                    );
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php
}

<?php
/**
 * Plugin Name: REVELATIONS Editorial Review
 * Description: Tracks human editorial review of AI-generated WordPress drafts.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Count words in the current WordPress draft.
 */
function revelations_editorial_current_draft_word_count(
    int $draft_id
): int {
    $content = (string) get_post_field(
        'post_content',
        $draft_id
    );

    $plain_text = trim(
        wp_strip_all_tags(
            do_blocks( $content ),
            true
        )
    );

    preg_match_all(
        "/[\p{L}\p{N}]+(?:[’'\-][\p{L}\p{N}]+)*/u",
        $plain_text,
        $matches
    );

    return count(
        $matches[0] ?? array()
    );
}

/**
 * Hash everything covered by editorial review.
 */
function revelations_editorial_review_content_hash(
    int $draft_id
): string {
    $post = get_post( $draft_id );

    if ( ! $post instanceof WP_Post ) {
        return '';
    }

    $reviewed_data = array(
        'title' =>
            $post->post_title,

        'content' =>
            $post->post_content,

        'excerpt' =>
            $post->post_excerpt,

        'categories' =>
            wp_get_post_categories(
                $draft_id
            ),

        'seo_title' =>
            get_post_meta(
                $draft_id,
                'revelations_seo_title',
                true
            ),

        'seo_description' =>
            get_post_meta(
                $draft_id,
                'revelations_seo_description',
                true
            ),

        'revelation' => get_post_meta( $draft_id, 'revelations_revelation', true ),
        'source_note' => get_post_meta( $draft_id, 'revelations_source_note', true ),
        'editorial_note' => get_post_meta( $draft_id, 'revelations_editorial_note', true ),
        'disclosure' => get_post_meta( $draft_id, 'revelations_disclosure', true ),
        'public_sources' => get_post_meta( $draft_id, 'revelations_public_sources', true ),

        'ai_review_metadata' =>
            function_exists(
                'revelations_editorial_ai_review_metadata_for_draft'
            )
                ? revelations_editorial_ai_review_metadata_for_draft(
                    $draft_id
                )
                : array(),
    );

    return hash(
        'sha256',
        wp_json_encode(
            $reviewed_data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
    );
}

/** @return array<string,string> */
function revelations_editorial_review_field_hashes( int $draft_id ): array {
    $post = get_post( $draft_id );
    if ( ! $post instanceof WP_Post ) return array();
    $tags = array_map( 'absint', wp_get_post_tags( $draft_id, array( 'fields' => 'ids' ) ) ); sort( $tags );
    $categories = function_exists( 'revelations_editorial_category_contract_normalize_ids' )
        ? revelations_editorial_category_contract_normalize_ids( wp_get_post_categories( $draft_id ) )
        : array_values( array_unique( array_map( 'absint', wp_get_post_categories( $draft_id ) ) ) );
    $values = array(
        'title' => $post->post_title, 'content' => $post->post_content, 'excerpt' => $post->post_excerpt,
        'category' => 1 === count( $categories ) ? $categories[0] : $categories,
        'tags' => $tags,
        'seo_title' => (string) get_post_meta( $draft_id, 'revelations_seo_title', true ),
        'seo_description' => (string) get_post_meta( $draft_id, 'revelations_seo_description', true ),
        'displayed_author' => (string) get_post_meta( $draft_id, 'revelations_author', true ),
        'revelation' => (string) get_post_meta( $draft_id, 'revelations_revelation', true ),
        'source_note' => (string) get_post_meta( $draft_id, 'revelations_source_note', true ),
        'editorial_note' => (string) get_post_meta( $draft_id, 'revelations_editorial_note', true ),
        'disclosure' => (string) get_post_meta( $draft_id, 'revelations_disclosure', true ),
        'public_sources' => (string) get_post_meta( $draft_id, 'revelations_public_sources', true ),
        'ai_review_metadata' => function_exists( 'revelations_editorial_ai_review_metadata_for_draft' ) ? revelations_editorial_ai_review_metadata_for_draft( $draft_id ) : array(),
    );
    $hashes = array(); foreach ( $values as $key => $value ) $hashes[ $key ] = hash( 'sha256', wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
    return $hashes;
}

/** @return array<string,string> */
function revelations_editorial_review_stored_field_hashes( int $draft_id ): array {
    $raw = get_post_meta( $draft_id, '_revelations_editorial_review_field_hashes', true );
    $value = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
    if ( ! is_array( $value ) ) return array();
    $result = array(); foreach ( revelations_editorial_review_field_hashes( $draft_id ) as $key => $_ ) if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) ) $result[ $key ] = $value[ $key ];
    return $result;
}

/**
 * Return the current review state.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_review_status(
    int $draft_id
): array {
    $stored_status = sanitize_key(
        (string) get_post_meta(
            $draft_id,
            '_revelations_editorial_review_status',
            true
        )
    );

    $stored_hash = sanitize_text_field(
        (string) get_post_meta(
            $draft_id,
            '_revelations_editorial_review_hash',
            true
        )
    );

    $current_hash =
        revelations_editorial_review_content_hash(
            $draft_id
        );

    $was_reviewed =
        'reviewed' === $stored_status &&
        '' !== $stored_hash;

    $field_hashes = revelations_editorial_review_stored_field_hashes( $draft_id );
    $fields_current = array() === $field_hashes || $field_hashes === revelations_editorial_review_field_hashes( $draft_id );
    $is_current =
        $was_reviewed &&
        '' !== $current_hash &&
        hash_equals(
            $stored_hash,
            $current_hash
        ) && $fields_current;

    return array(
        'status' =>
            $is_current
                ? 'reviewed'
                : (
                    $was_reviewed
                        ? 'outdated'
                        : 'required'
                ),

        'was_reviewed' =>
            $was_reviewed,

        'is_current' =>
            $is_current,

        'reviewed_at' =>
            sanitize_text_field(
                (string) get_post_meta(
                    $draft_id,
                    '_revelations_editorial_reviewed_at',
                    true
                )
            ),

        'reviewed_by' =>
            absint(
                get_post_meta(
                    $draft_id,
                    '_revelations_editorial_reviewed_by',
                    true
                )
            ),

        'current_word_count' =>
            revelations_editorial_current_draft_word_count(
                $draft_id
            ),

        'stored_hash' =>
            $stored_hash,

        'current_hash' =>
            $current_hash,
        'field_hashes' => $field_hashes,
    );
}

/**
 * Redirect back to AI Drafts.
 *
 * @param array<string, scalar> $args Query arguments.
 */
function revelations_editorial_review_redirect(
    array $args
): void {
    wp_safe_redirect(
        add_query_arg(
            array_merge(
                array(
                    'page' =>
                        'revelations-editorial-desk',

                    'view' =>
                        'drafts',
                ),
                $args
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Mark the current version as editorially reviewed.
 */
add_action(
    'admin_post_revelations_mark_editorial_reviewed',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to review drafts.',
                    'revelations'
                )
            );
        }

        $draft_id = absint(
            $_POST['draft_id'] ?? 0
        );

        check_admin_referer(
            'revelations_mark_editorial_reviewed_' .
            $draft_id
        );

        $post = get_post( $draft_id );

        if (
            ! $post instanceof WP_Post ||
            'post' !== $post->post_type ||
            ! in_array(
                $post->post_status,
                array(
                    'draft',
                    'pending',
                    'private',
                ),
                true
            )
        ) {
            revelations_editorial_review_redirect(
                array(
                    'review_error' => 'draft',
                )
            );
        }

        $candidate_id = absint(
            get_post_meta(
                $draft_id,
                '_revelations_editorial_candidate_id',
                true
            )
        );

        if (
            $candidate_id < 1 ||
            'rev_candidate' !== get_post_type(
                $candidate_id
            )
        ) {
            revelations_editorial_review_redirect(
                array(
                    'review_error' => 'candidate',
                )
            );
        }

        $category_check = function_exists( 'revelations_editorial_category_contract_validate' )
            ? revelations_editorial_category_contract_validate( wp_get_post_categories( $draft_id ) ) : array( 'code' => '' );
        if ( '' !== (string) $category_check['code'] ) revelations_editorial_review_redirect( array( 'review_error' => 'category' ) );
        $content_hash =
            revelations_editorial_review_content_hash(
                $draft_id
            );

        if ( '' === $content_hash ) {
            revelations_editorial_review_redirect(
                array(
                    'review_error' => 'hash',
                )
            );
        }

        $meta = array(
            '_revelations_editorial_review_status' =>
                'reviewed',

            '_revelations_editorial_reviewed_at' =>
                gmdate( 'c' ),

            '_revelations_editorial_reviewed_by' =>
                get_current_user_id(),

            '_revelations_editorial_review_hash' =>
                $content_hash,

            '_revelations_editorial_review_field_hashes' =>
                wp_json_encode( revelations_editorial_review_field_hashes( $draft_id ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            '_revelations_editorial_review_changed_fields' => '',

            '_revelations_editorial_review_word_count' =>
                revelations_editorial_current_draft_word_count(
                    $draft_id
                ),
        );

        foreach ( $meta as $key => $value ) {
            update_post_meta(
                $draft_id,
                $key,
                $value
            );
        }

        revelations_editorial_review_redirect(
            array(
                'review_saved' => $draft_id,
            )
        );
    }
);

/**
 * Render review notices.
 */
function revelations_editorial_render_review_notices(): void {
    if ( isset( $_GET['review_saved'] ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                The current draft version was marked as
                editorially reviewed.
            </p>
        </div>
        <?php
    }

    if ( isset( $_GET['review_error'] ) ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                The editorial review status could not be saved.
            </p>
        </div>
        <?php
    }
}

/**
 * Render review status for one draft.
 */
function revelations_editorial_render_review_action(
    int $draft_id
): void {
    $review =
        revelations_editorial_review_status(
            $draft_id
        );

    $status = (string) $review['status'];

    $reviewed_user = null;

    if ( $review['reviewed_by'] > 0 ) {
        $reviewed_user = get_userdata(
            (int) $review['reviewed_by']
        );
    }
    ?>
    <div class="revelations-editorial-review">
        <?php if ( 'reviewed' === $status ) : ?>
            <span class="revelations-review-badge revelations-review-badge--reviewed">
                Editorially reviewed
            </span>

            <div class="revelations-review-details">
                <?php
                $review_details = array(
                    absint(
                        $review['current_word_count']
                    ) . ' current words',
                );

                if (
                    $reviewed_user instanceof WP_User
                ) {
                    $review_details[] =
                        'reviewed by ' .
                        $reviewed_user->display_name;
                }

                echo esc_html(
                    implode(
                        ' · ',
                        $review_details
                    )
                );
                ?>
            </div>

        <?php else : ?>
            <span
                class="revelations-review-badge <?php
                    echo esc_attr(
                        'outdated' === $status
                            ? 'revelations-review-badge--outdated'
                            : 'revelations-review-badge--required'
                    );
                ?>"
            >
                <?php echo esc_html(
                    'outdated' === $status
                        ? 'Review outdated'
                        : 'Editorial review required'
                ); ?>
            </span>

            <div class="revelations-review-details">
                <?php echo esc_html(
                    absint(
                        $review['current_word_count']
                    ) .
                    ' current words'
                ); ?>

                <?php if ( 'outdated' === $status ) : ?>
                    · The draft changed after its last review.
                <?php endif; ?>
            </div>

            <form
                method="post"
                class="revelations-review-form"
                action="<?php echo esc_url(
                    admin_url( 'admin-post.php' )
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="revelations_mark_editorial_reviewed"
                >

                <input
                    type="hidden"
                    name="draft_id"
                    value="<?php echo esc_attr(
                        (string) $draft_id
                    ); ?>"
                >

                <?php wp_nonce_field(
                    'revelations_mark_editorial_reviewed_' .
                    $draft_id
                ); ?>

                <button
                    type="submit"
                    class="button button-secondary"
                >
                    <?php echo esc_html(
                        'outdated' === $status
                            ? 'Mark reviewed again'
                            : 'Mark as reviewed'
                    ); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Review UI styling.
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
            .revelations-editorial-review {
                margin-top:10px;
                padding-top:10px;
                border-top:1px solid #dcdcde;
            }

            .revelations-review-badge {
                display:inline-flex;
                padding:4px 8px;
                border-radius:999px;
                font-size:11px;
                font-weight:600;
            }

            .revelations-review-badge--reviewed {
                background:#e8f5e9;
                color:#1b5e20;
            }

            .revelations-review-badge--required {
                background:#fff4ce;
                color:#6a4b00;
            }

            .revelations-review-badge--outdated {
                background:#fce8e6;
                color:#8a2424;
            }

            .revelations-review-details {
                margin-top:4px;
                color:#646970;
                font-size:11px;
                line-height:1.35;
            }

            .revelations-review-form {
                margin-top:7px;
            }
            '
        );
    }
);

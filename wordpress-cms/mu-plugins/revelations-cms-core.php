<?php
/**
 * Plugin Name: REVELATIONS CMS Core
 * Description: Article fields, REST schema and editor interface for REVELATIONS.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Return the five optional, editor-approved public enrichment meta keys. */
function revelations_enrichment_meta_keys(): array {
    return array(
        'revelations_revelation',
        'revelations_source_note',
        'revelations_editorial_note',
        'revelations_disclosure',
        'revelations_public_sources',
    );
}

/** Normalize one optional public-text value. */
function revelations_enrichment_sanitize_text( mixed $value ): string {
    return sanitize_textarea_field( (string) $value );
}

/** Count Unicode words without changing editorial punctuation. */
function revelations_enrichment_word_count( string $value ): int {
    preg_match_all( "/[\\p{L}\\p{N}]+(?:[’'\\-][\\p{L}\\p{N}]+)*/u", $value, $matches );
    return count( $matches[0] ?? array() );
}

/** Revelation remains plain text and must never be silently truncated. */
function revelations_enrichment_sanitize_revelation( mixed $value ): string {
    return revelations_enrichment_sanitize_text( $value );
}

function revelations_enrichment_revelation_is_valid( mixed $value ): bool {
    return revelations_enrichment_word_count(
        revelations_enrichment_sanitize_revelation( $value )
    ) <= 180;
}

/**
 * Decode, validate and canonically encode the ordered public-source list.
 * Malformed entries are rejected as an empty list; private source metadata is
 * neither accepted nor used as a fallback.
 */
function revelations_enrichment_sanitize_public_sources( mixed $value ): string {
    $items = is_string( $value ) ? json_decode( $value, true ) : $value;
    if ( ! is_array( $items ) ) return '[]';
    $result = array();
    foreach ( $items as $item ) {
        if ( ! is_array( $item ) ) return '[]';
        $label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
        $url = esc_url_raw( (string) ( $item['url'] ?? '' ), array( 'http', 'https' ) );
        if ( '' === $label || '' === $url || ! preg_match( '#^https?://#i', $url ) ) return '[]';
        $result[] = array( 'label' => $label, 'url' => $url );
    }
    return (string) wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/** Return safe public sources only from the explicitly approved public field. */
function revelations_enrichment_public_sources( int $post_id ): array {
    $decoded = json_decode( (string) get_post_meta( $post_id, 'revelations_public_sources', true ), true );
    if ( ! is_array( $decoded ) ) return array();
    $sanitized = revelations_enrichment_sanitize_public_sources( $decoded );
    return '[]' === $sanitized ? array() : (array) json_decode( $sanitized, true );
}

/**
 * Rename standard WordPress posts to Articles in the admin interface.
 */
add_filter(
    'post_type_labels_post',
    static function ( $labels ) {
        $labels->name               = 'Articles';
        $labels->singular_name      = 'Article';
        $labels->menu_name          = 'Articles';
        $labels->name_admin_bar     = 'Article';
        $labels->add_new            = 'Add Article';
        $labels->add_new_item       = 'Add New Article';
        $labels->edit_item          = 'Edit Article';
        $labels->new_item           = 'New Article';
        $labels->view_item          = 'View Article';
        $labels->search_items       = 'Search Articles';
        $labels->not_found          = 'No articles found';
        $labels->not_found_in_trash = 'No articles found in Trash';

        return $labels;
    }
);

/**
 * Register REVELATIONS article metadata.
 */
add_action(
    'init',
    static function () {
        add_post_type_support( 'post', 'custom-fields' );

        $fields = array(
            'revelations_author' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'revelations_seo_title' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'revelations_seo_description' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'revelations_youtube_url' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'revelations_featured' => array(
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
            'revelations_is_gated' => array(
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
            'revelations_legacy_id' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'revelations_revelation' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'revelations_enrichment_sanitize_revelation',
            ),
            'revelations_source_note' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'revelations_enrichment_sanitize_text',
            ),
            'revelations_editorial_note' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'revelations_enrichment_sanitize_text',
            ),
            'revelations_disclosure' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'revelations_enrichment_sanitize_text',
            ),
            'revelations_public_sources' => array(
                'type'              => 'string',
                'default'           => '[]',
                'sanitize_callback' => 'revelations_enrichment_sanitize_public_sources',
            ),
        );

        foreach ( $fields as $key => $field ) {
            register_post_meta(
                'post',
                $key,
                array(
                    'single'            => true,
                    'type'              => $field['type'],
                    'default'           => $field['default'],
                    'sanitize_callback' => $field['sanitize_callback'],
                    'auth_callback'     => static function () {
                        return current_user_can( 'edit_posts' );
                    },
                    'show_in_rest'      => array(
                        'schema' => array(
                            'type'    => $field['type'],
                            'default' => $field['default'],
                            'context' => array( 'view', 'edit' ),
                        ),
                    ),
                )
            );
        }
    }
);

/* Gutenberg saves use REST: reject an over-limit value rather than truncating it. */
add_filter(
    'rest_pre_insert_post',
    static function ( $prepared_post, WP_REST_Request $request ) {
        if ( is_wp_error( $prepared_post ) || ! $request->has_param( 'meta' ) ) return $prepared_post;
        $meta = $request->get_param( 'meta' );
        if ( ! is_array( $meta ) || ! array_key_exists( 'revelations_revelation', $meta ) ) return $prepared_post;
        if ( revelations_enrichment_revelation_is_valid( $meta['revelations_revelation'] ) ) return $prepared_post;
        return new WP_Error( 'revelations_revelation_too_long', 'THE REVELATION must not exceed 180 words.', array( 'status' => 400 ) );
    },
    90,
    2
);

/**
 * Build data used by the Gutenberg publication-readiness panel.
 *
 * @return array<string, mixed>
 */
function revelations_cms_editor_data(
    int $post_id
): array {
    $data = array(
        'editorialReviewRequired' => false,
        'reviewIsCurrent'         => false,
        'reviewStatus'            => 'not_required',
        'reviewedSnapshot'        => null,
        'editorialCategories'     => function_exists( 'revelations_editorial_category_contract_allowed_terms' ) ? revelations_editorial_category_contract_allowed_terms() : array(),
    );

    if (
        $post_id < 1 ||
        'post' !== get_post_type(
            $post_id
        )
    ) {
        return $data;
    }

    $review_required =
        function_exists(
            'revelations_editorial_publish_gate_applies'
        ) &&
        revelations_editorial_publish_gate_applies(
            $post_id
        );

    $data['editorialReviewRequired'] =
        $review_required;

    if ( ! $review_required ) {
        return $data;
    }

    $data['reviewStatus'] = 'required';

    if (
        ! function_exists(
            'revelations_editorial_review_status'
        )
    ) {
        $data['reviewStatus'] = 'unavailable';

        return $data;
    }

    $review =
        revelations_editorial_review_status(
            $post_id
        );

    $data['reviewStatus'] =
        sanitize_key(
            (string) (
                $review['status'] ??
                'required'
            )
        );

    $data['reviewIsCurrent'] =
        ! empty(
            $review['is_current']
        ) &&
        'reviewed' ===
        $data['reviewStatus'];

    if (
        ! $data['reviewIsCurrent']
    ) {
        return $data;
    }

    $post = get_post(
        $post_id
    );

    if ( ! $post instanceof WP_Post ) {
        $data['reviewIsCurrent'] = false;

        return $data;
    }

    $categories = array_map(
        'absint',
        wp_get_post_categories(
            $post_id
        )
    );

    sort( $categories );

    $data['reviewedSnapshot'] = array(
        'title' =>
            $post->post_title,

        'content' =>
            $post->post_content,

        'excerpt' =>
            $post->post_excerpt,

        'categories' =>
            $categories,

        'tags' => array_map( 'absint', wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) ) ),

        'displayedAuthor' => (string) get_post_meta( $post_id, 'revelations_author', true ),

        'seoTitle' =>
            (string) get_post_meta(
                $post_id,
                'revelations_seo_title',
                true
            ),

        'seoDescription' =>
            (string) get_post_meta(
                $post_id,
                'revelations_seo_description',
                true
            ),
        'revelation' => (string) get_post_meta( $post_id, 'revelations_revelation', true ),
        'sourceNote' => (string) get_post_meta( $post_id, 'revelations_source_note', true ),
        'editorialNote' => (string) get_post_meta( $post_id, 'revelations_editorial_note', true ),
        'disclosure' => (string) get_post_meta( $post_id, 'revelations_disclosure', true ),
        'publicSources' => (string) get_post_meta( $post_id, 'revelations_public_sources', true ),
        'authorProfileIds' => (string) get_post_meta( $post_id, '_revelations_author_profile_ids', true ),
    );
    $data['authorProfiles'] = array_map( static function ( WP_Post $author ): array { return array( 'id' => $author->ID, 'name' => $author->post_title ); }, get_posts( array( 'post_type'=>'rev_author', 'post_status'=>array('publish','draft','private'), 'posts_per_page'=>100, 'orderby'=>'title', 'order'=>'ASC' ) ) );

    return $data;
}

/**
 * Load the custom Gutenberg article panel.
 */
add_action(
    'enqueue_block_editor_assets',
    static function () {
        $screen = get_current_screen();

        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }

        $script_path = __DIR__ . '/revelations-cms-editor.js';

        wp_enqueue_script(
            'revelations-cms-editor',
            plugin_dir_url( __FILE__ ) . 'revelations-cms-editor.js',
            array(
                'wp-components',
                'wp-core-data',
                'wp-data',
                'wp-edit-post',
                'wp-editor',
                'wp-element',
                'wp-plugins',
            ),
            (string) filemtime( $script_path ),
            true
        );

        $post_id = isset( $_GET['post'] )
            ? absint(
                wp_unslash(
                    (string) $_GET['post']
                )
            )
            : 0;

        if ( $post_id < 1 ) {
            global $post;

            if ( $post instanceof WP_Post ) {
                $post_id = (int) $post->ID;
            }
        }

        $editor_data =
            revelations_cms_editor_data(
                $post_id
            );

        wp_add_inline_script(
            'revelations-cms-editor',
            'window.revelationsCmsEditorData = ' .
            wp_json_encode(
                $editor_data,
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) .
            ';',
            'before'
        );
    }
);

/**
 * REVELATIONS does not use WordPress comments.
 */
add_action(
    'admin_menu',
    static function () {
        remove_menu_page( 'edit-comments.php' );
    }
);

add_action(
    'admin_bar_menu',
    static function ( $admin_bar ) {
        $admin_bar->remove_node( 'comments' );
    },
    999
);

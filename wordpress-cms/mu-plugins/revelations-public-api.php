<?php
/**
 * Plugin Name: REVELATIONS Public API
 * Description: Read-only API for the REVELATIONS Next.js frontend.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Convert one published WordPress article to the public API structure.
 */
function revelations_api_prepare_article( WP_Post $post ): array {
    $post_id = $post->ID;

    $categories = wp_get_post_terms(
        $post_id,
        'category',
        array(
            'fields' => 'all',
        )
    );

    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }

    $category_data = array_map(
        static function ( WP_Term $term ): array {
            return array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        },
        $categories
    );

    $tags = wp_get_post_terms(
        $post_id,
        'post_tag',
        array(
            'fields' => 'all',
        )
    );

    if ( is_wp_error( $tags ) ) {
        $tags = array();
    }

    $tag_data = array_map(
        static function ( WP_Term $term ): array {
            return array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        },
        $tags
    );

    $image = null;
    $image_id = get_post_thumbnail_id( $post_id );

    if ( $image_id ) {
        $source = wp_get_attachment_image_src(
            $image_id,
            'full'
        );

        if ( $source ) {
            $image = array(
                'id'     => $image_id,
                'url'    => $source[0],
                'width'  => (int) $source[1],
                'height' => (int) $source[2],
                'alt'    => get_post_meta(
                    $image_id,
                    '_wp_attachment_image_alt',
                    true
                ),
            );
        }
    }

    $is_gated = rest_sanitize_boolean(
        get_post_meta(
            $post_id,
            'revelations_is_gated',
            true
        )
    );

    $raw_content = get_post_field(
        'post_content',
        $post_id,
        'raw'
    );

    $editorial_taxonomy = function_exists( 'revelations_editorial_taxonomy_public_data' )
        ? revelations_editorial_taxonomy_public_data( $post_id )
        : array(
            'primary_topic'         => null,
            'topics'                => array(),
            'series'                => null,
            'locations'             => array(),
            'public_topic_eligible' => true,
            'taxonomy_status'       => 'proposed',
            'manual_related'        => array(),
        );
    $author_profiles = array();
    if ( function_exists( 'revelations_author_relation' ) ) {
        foreach ( revelations_author_relation( get_post_meta( $post_id, '_revelations_author_profile_ids', true ) ) as $author_id ) {
            $author = get_post( $author_id );
            if ( $author instanceof WP_Post && function_exists( 'revelations_author_public_data' ) ) {
                $count = new WP_Query( array( 'post_type'=>'post', 'post_status'=>'publish', 'meta_key'=>'_revelations_author_profile_ids', 'meta_value'=>(string) $author_id, 'compare'=>'LIKE', 'posts_per_page'=>1, 'fields'=>'ids' ) );
                $data = revelations_author_article_data( $author, (int) $count->found_posts ); if ( $data ) $author_profiles[] = $data;
            }
        }
    }

    return array(
        'id' => $post_id,

        'legacy_id' => get_post_meta(
            $post_id,
            'revelations_legacy_id',
            true
        ),

        'slug'   => $post->post_name,
        'status' => $post->post_status,
        'title'  => get_the_title( $post ),

        'excerpt' => get_the_excerpt( $post ),

        /*
         * Gated articles expose their metadata and excerpt,
         * but not the full content.
         */
        'content' => $is_gated
            ? null
            : apply_filters(
                'the_content',
                $raw_content
            ),

        'displayed_author' => get_post_meta(
            $post_id,
            'revelations_author',
            true
        ),
        'author_profiles' => $author_profiles,

        /* Only explicit public editorial enrichment is serialized here. */
        'revelation' => ( $value = trim( (string) get_post_meta( $post_id, 'revelations_revelation', true ) ) ) !== ''
            && function_exists( 'revelations_enrichment_word_count' )
            && revelations_enrichment_word_count( $value ) <= 180 ? $value : null,
        'source_note' => ( $value = trim( (string) get_post_meta( $post_id, 'revelations_source_note', true ) ) ) !== '' ? $value : null,
        'editorial_note' => ( $value = trim( (string) get_post_meta( $post_id, 'revelations_editorial_note', true ) ) ) !== '' ? $value : null,
        'disclosure' => ( $value = trim( (string) get_post_meta( $post_id, 'revelations_disclosure', true ) ) ) !== '' ? $value : null,
        'public_sources' => function_exists( 'revelations_enrichment_public_sources' )
            ? revelations_enrichment_public_sources( $post_id ) : array(),

        'section' => isset( $category_data[0] )
            ? $category_data[0]
            : null,

        'categories' => $category_data,
        'tags'       => $tag_data,

        'primary_topic'         => $editorial_taxonomy['primary_topic'],
        'topics'                => $editorial_taxonomy['topics'],
        'series'                => $editorial_taxonomy['series'],
        'locations'             => $editorial_taxonomy['locations'],
        'public_topic_eligible' => $editorial_taxonomy['public_topic_eligible'],
        'taxonomy_status'       => $editorial_taxonomy['taxonomy_status'],
        'manual_related'        => $editorial_taxonomy['manual_related'],

        'cover_image' => $image,

        'featured' => rest_sanitize_boolean(
            get_post_meta(
                $post_id,
                'revelations_featured',
                true
            )
        ),

        'is_gated' => $is_gated,

        'youtube_url' => get_post_meta(
            $post_id,
            'revelations_youtube_url',
            true
        ),

        'seo_title' => get_post_meta(
            $post_id,
            'revelations_seo_title',
            true
        ),

        'seo_description' => get_post_meta(
            $post_id,
            'revelations_seo_description',
            true
        ),

        'publication_date' => get_post_time(
            DATE_ATOM,
            true,
            $post
        ),

        'modified_date' => get_post_modified_time(
            DATE_ATOM,
            true,
            $post
        ),

        'link' => get_permalink( $post ),
    );
}

function revelations_api_authors( WP_REST_Request $request ): WP_REST_Response {
    $authors = get_posts( array( 'post_type'=>'rev_author', 'post_status'=>'publish', 'posts_per_page'=>100, 'orderby'=>'title', 'order'=>'ASC' ) ); $items=array();
    foreach ( $authors as $author ) { $count=(new WP_Query(array('post_type'=>'post','post_status'=>'publish','meta_key'=>'_revelations_author_profile_ids','meta_value'=>(string) $author->ID,'compare'=>'LIKE','posts_per_page'=>1,'fields'=>'ids')))->found_posts; $data=function_exists('revelations_author_public_data')?revelations_author_public_data($author,(int)$count):null; if($data)$items[]=$data; }
    return new WP_REST_Response( array( 'items'=>$items ), 200 );
}
function revelations_api_author( WP_REST_Request $request ) {
    $author = get_page_by_path( sanitize_title( $request->get_param('slug') ), OBJECT, 'rev_author' ); if ( ! $author instanceof WP_Post ) return new WP_Error('revelations_author_not_found','Author not found.',array('status'=>404));
    $articles = get_posts( array( 'post_type'=>'post','post_status'=>'publish','meta_key'=>'_revelations_author_profile_ids','meta_value'=>(string) $author->ID,'compare'=>'LIKE','posts_per_page'=>100,'orderby'=>'date','order'=>'DESC' ) );
    $data=function_exists('revelations_author_public_data')?revelations_author_public_data($author,count($articles)):null; if(!$data)return new WP_Error('revelations_author_not_found','Author not found.',array('status'=>404));
    return new WP_REST_Response( array( 'author'=>$data,'articles'=>array_map('revelations_api_prepare_article',$articles) ),200 );
}

/**
 * Public health check.
 */
function revelations_api_health(): WP_REST_Response {
    return new WP_REST_Response(
        array(
            'status'  => 'ok',
            'service' => 'revelations-cms-api',
            'version' => '1.0.0',
        ),
        200
    );
}

/**
 * Public list of published articles.
 */
function revelations_api_articles(
    WP_REST_Request $request
): WP_REST_Response {
    $page = max(
        1,
        (int) $request->get_param( 'page' )
    );

    $per_page = min(
        100,
        max(
            1,
            (int) $request->get_param( 'per_page' )
        )
    );

    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $section = $request->get_param( 'section' );

    if ( $section ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_title( $section ),
            ),
        );
    }

    $search = $request->get_param( 'search' );

    if ( $search ) {
        $query_args['s'] = sanitize_text_field(
            $search
        );
    }

    if (
        rest_sanitize_boolean(
            $request->get_param( 'featured' )
        )
    ) {
        $query_args['meta_query'] = array(
            array(
                'key'     => 'revelations_featured',
                'value'   => '1',
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $query_args );

    $items = array_map(
        'revelations_api_prepare_article',
        $query->posts
    );

    return new WP_REST_Response(
        array(
            'items' => $items,

            'pagination' => array(
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
            ),
        ),
        200
    );
}

/**
 * Public single published article by slug.
 */
function revelations_api_single_article(
    WP_REST_Request $request
) {
    $query = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'name'           => sanitize_title(
                $request->get_param( 'slug' )
            ),
            'posts_per_page' => 1,
        )
    );

    if ( empty( $query->posts ) ) {
        return new WP_Error(
            'revelations_article_not_found',
            'Article not found.',
            array(
                'status' => 404,
            )
        );
    }

    return new WP_REST_Response(
        revelations_api_prepare_article(
            $query->posts[0]
        ),
        200
    );
}

/**
 * Public list of article sections.
 */
function revelations_api_sections(): WP_REST_Response {
    $terms = get_terms(
        array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $terms ) ) {
        return new WP_REST_Response(
            array(),
            200
        );
    }

    $items = array_map(
        static function ( WP_Term $term ): array {
            return array(
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $term->count,
            );
        },
        $terms
    );

    return new WP_REST_Response(
        $items,
        200
    );
}

/**
 * Register public read-only routes.
 */
add_action(
    'rest_api_init',
    static function (): void {
        register_rest_route(
            'revelations/v1',
            '/health',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'revelations_api_health',
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route( 'revelations/v1', '/authors', array( 'methods'=>WP_REST_Server::READABLE, 'callback'=>'revelations_api_authors', 'permission_callback'=>'__return_true' ) );
        register_rest_route( 'revelations/v1', '/authors/(?P<slug>[a-z0-9-]+)', array( 'methods'=>WP_REST_Server::READABLE, 'callback'=>'revelations_api_author', 'permission_callback'=>'__return_true' ) );

        register_rest_route(
            'revelations/v1',
            '/articles',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'revelations_api_articles',
                'permission_callback' => '__return_true',

                'args' => array(
                    'page' => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),

                    'per_page' => array(
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),

                    'section' => array(
                        'default'           => '',
                        'sanitize_callback' =>
                            'sanitize_text_field',
                    ),

                    'search' => array(
                        'default'           => '',
                        'sanitize_callback' =>
                            'sanitize_text_field',
                    ),

                    'featured' => array(
                        'default'           => false,
                        'sanitize_callback' =>
                            'rest_sanitize_boolean',
                    ),
                ),
            )
        );

        register_rest_route(
            'revelations/v1',
            '/articles/(?P<slug>[a-z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            =>
                    'revelations_api_single_article',
                'permission_callback' => '__return_true',

                'args' => array(
                    'slug' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_title',
                    ),
                ),
            )
        );

        register_rest_route(
            'revelations/v1',
            '/sections',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'revelations_api_sections',
                'permission_callback' => '__return_true',
            )
        );
    }
);

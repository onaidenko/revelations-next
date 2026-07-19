<?php
/** Plugin Name: REVELATIONS Frontend Revalidation */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

function revelations_frontend_revalidation_slug( mixed $value ): ?string {
    $value = (string) $value;

    return preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value )
        ? $value
        : null;
}

function revelations_frontend_revalidation_section( int $id ): ?string {
    $terms = wp_get_post_categories(
        $id,
        array( 'fields' => 'slugs' )
    );
    $allowed = function_exists( 'revelations_editorial_sections' )
        ? revelations_editorial_sections()
        : array();
    $terms = array_values(
        array_intersect(
            is_array( $terms ) ? $terms : array(),
            $allowed
        )
    );

    return 1 === count( $terms ) ? $terms[0] : null;
}

function revelations_frontend_revalidation_term_slugs(
    int $id,
    string $taxonomy
): array {
    $terms = wp_get_post_terms(
        $id,
        $taxonomy,
        array( 'fields' => 'slugs' )
    );

    if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
        return array();
    }

    $slugs = array_values(
        array_unique(
            array_filter(
                array_map(
                    'revelations_frontend_revalidation_slug',
                    $terms
                )
            )
        )
    );

    sort( $slugs, SORT_STRING );

    return $slugs;
}

function revelations_frontend_revalidation_taxonomy_paths(
    int $id
): array {
    $paths = array();

    $topic_eligible =
        '0' !== (string) get_post_meta(
            $id,
            '_revelations_public_topic_eligible',
            true
        ) &&
        'needs-editorial-review' !== (string) get_post_meta(
            $id,
            '_revelations_taxonomy_status',
            true
        );

    $groups = array(
        'series'    => revelations_frontend_revalidation_term_slugs(
            $id,
            'revelations_series'
        ),
        'locations' => revelations_frontend_revalidation_term_slugs(
            $id,
            'revelations_location'
        ),
        'tags'      => revelations_frontend_revalidation_term_slugs(
            $id,
            'post_tag'
        ),
    );

    if ( $topic_eligible ) {
        $groups = array_merge(
            array(
                'topics' => revelations_frontend_revalidation_term_slugs(
                    $id,
                    'revelations_topic'
                ),
            ),
            $groups
        );
    }

    foreach ( $groups as $prefix => $slugs ) {
        foreach ( $slugs as $slug ) {
            $paths[] = '/' . $prefix . '/' . $slug;
        }
    }

    $paths = array_values( array_unique( $paths ) );
    sort( $paths, SORT_STRING );

    return array_slice( $paths, 0, 128 );
}

function revelations_frontend_revalidation_snapshot(
    int $id
): ?array {
    global $revelations_frontend_revalidation_snapshots;

    $snapshot =
        $revelations_frontend_revalidation_snapshots[ $id ] ??
        null;

    unset(
        $revelations_frontend_revalidation_snapshots[ $id ]
    );

    return $snapshot;
}

function revelations_frontend_revalidation_store_snapshot(
    int $id
): void {
    global $revelations_frontend_revalidation_snapshots;

    $post = get_post( $id );

    if ( $post instanceof WP_Post ) {
        $revelations_frontend_revalidation_snapshots[ $id ] =
            array(
                'status'         => $post->post_status,
                'slug'           => revelations_frontend_revalidation_slug(
                    $post->post_name
                ),
                'section'        => revelations_frontend_revalidation_section(
                    $id
                ),
                'taxonomy_paths' => revelations_frontend_revalidation_taxonomy_paths(
                    $id
                ),
            );
    }
}

function revelations_frontend_revalidation_validate_config(
    mixed $url,
    mixed $secret
): ?array {
    if ( ! is_string( $url ) || ! is_string( $secret ) ) {
        return null;
    }

    $url = trim( $url );
    $secret = trim( $secret );
    $parts = filter_var( $url, FILTER_VALIDATE_URL )
        ? wp_parse_url( $url )
        : false;

    return '' !== $url &&
        '' !== $secret &&
        is_array( $parts ) &&
        'https' === strtolower(
            (string) ( $parts['scheme'] ?? '' )
        ) &&
        ! empty( $parts['host'] ) &&
        ! isset( $parts['user'] ) &&
        ! isset( $parts['pass'] ) &&
        ! isset( $parts['fragment'] )
            ? array(
                'url'    => $url,
                'secret' => $secret,
            )
            : null;
}

function revelations_frontend_revalidation_config(): ?array {
    return revelations_frontend_revalidation_validate_config(
        defined( 'REVELATIONS_REVALIDATION_URL' )
            ? constant( 'REVELATIONS_REVALIDATION_URL' )
            : null,
        defined( 'REVELATIONS_REVALIDATION_SECRET' )
            ? constant( 'REVELATIONS_REVALIDATION_SECRET' )
            : null
    );
}

function revelations_frontend_revalidation_send(
    array $payload
): bool {
    static $sent = array();

    $config = revelations_frontend_revalidation_config();

    if ( ! $config ) {
        return false;
    }

    $fingerprint_data = array(
        $payload['post_id'],
        $payload['action'],
        $payload['old_status'],
        $payload['new_status'],
        $payload['old_slug'],
        $payload['new_slug'],
        $payload['old_section'],
        $payload['new_section'],
        $payload['old_taxonomy_paths'] ?? array(),
        $payload['new_taxonomy_paths'] ?? array(),
    );
    $encoded_fingerprint_data = wp_json_encode(
        $fingerprint_data
    );

    if ( ! is_string( $encoded_fingerprint_data ) ) {
        return false;
    }

    $fingerprint = sha1( $encoded_fingerprint_data );

    if ( isset( $sent[ $fingerprint ] ) ) {
        return false;
    }

    $raw_body = wp_json_encode( $payload );

    if ( ! is_string( $raw_body ) ) {
        return false;
    }

    $timestamp = (string) time();
    $sent[ $fingerprint ] = true;

    try {
        $result = wp_remote_post(
            $config['url'],
            array(
                'body'               => $raw_body,
                'data_format'        => 'body',
                'timeout'            => 3,
                'redirection'        => 0,
                'blocking'           => true,
                'sslverify'          => true,
                'reject_unsafe_urls' => true,
                'headers'            => array(
                    'Content-Type'             => 'application/json',
                    'X-Revelations-Timestamp' => $timestamp,
                    'X-Revelations-Signature' => 'sha256=' .
                        hash_hmac(
                            'sha256',
                            $timestamp . '.' . $raw_body,
                            $config['secret']
                        ),
                ),
            )
        );

        return ! is_wp_error( $result ) &&
            (int) wp_remote_retrieve_response_code( $result ) >= 200 &&
            (int) wp_remote_retrieve_response_code( $result ) < 300;
    } catch ( Throwable ) {
        return false;
    }
}

function revelations_frontend_revalidation_event(
    int $id,
    WP_Post $post,
    string $old_status,
    ?string $old_slug,
    ?string $old_section,
    array $old_taxonomy_paths,
    string $action
): void {
    $new_public =
        'publish' === $post->post_status &&
        'delete' !== $action;

    revelations_frontend_revalidation_send(
        array(
            'version'              => 1,
            'event_id'             => 'wp-' .
                $id .
                '-' .
                $action .
                '-' .
                wp_generate_uuid4(),
            'post_id'              => $id,
            'post_type'            => 'post',
            'action'               => $action,
            'old_status'           => $old_status,
            'new_status'           => 'delete' === $action
                ? 'delete'
                : $post->post_status,
            'old_slug'             => 'publish' === $old_status
                ? $old_slug
                : null,
            'new_slug'             => $new_public
                ? revelations_frontend_revalidation_slug(
                    $post->post_name
                )
                : null,
            'old_section'          => 'publish' === $old_status
                ? $old_section
                : null,
            'new_section'          => $new_public
                ? revelations_frontend_revalidation_section(
                    $id
                )
                : null,
            'old_taxonomy_paths'   => 'publish' === $old_status
                ? $old_taxonomy_paths
                : array(),
            'new_taxonomy_paths'   => $new_public
                ? revelations_frontend_revalidation_taxonomy_paths(
                    $id
                )
                : array(),
            'occurred_at'          => gmdate(
                'Y-m-d\TH:i:s\Z'
            ),
        )
    );
}

add_action(
    'pre_post_update',
    static function ( int $id, array $data ): void {
        revelations_frontend_revalidation_store_snapshot(
            $id
        );
    },
    10,
    2
);

add_action(
    'wp_after_insert_post',
    static function (
        int $id,
        WP_Post $post,
        bool $update,
        ?WP_Post $before
    ): void {
        if (
            'post' !== $post->post_type ||
            wp_is_post_revision( $id ) ||
            wp_is_post_autosave( $id ) ||
            'auto-draft' === $post->post_status
        ) {
            return;
        }

        $old = revelations_frontend_revalidation_snapshot(
            $id
        ) ?: array(
            'status'         => $before?->post_status ?? '',
            'slug'           => revelations_frontend_revalidation_slug(
                $before?->post_name ?? ''
            ),
            'section'        => null,
            'taxonomy_paths' => array(),
        );

        $action =
            'publish' === $post->post_status &&
            'publish' !== $old['status']
                ? 'publish'
                : (
                    'publish' === $post->post_status &&
                    'publish' === $old['status']
                        ? 'update'
                        : (
                            'publish' === $old['status']
                                ? 'unpublish'
                                : ''
                        )
                );

        if ( '' !== $action ) {
            revelations_frontend_revalidation_event(
                $id,
                $post,
                $old['status'],
                $old['slug'],
                $old['section'],
                $old['taxonomy_paths'],
                $action
            );
        }
    },
    20,
    4
);

add_action(
    'before_delete_post',
    static function (
        int $id,
        WP_Post $post
    ): void {
        if (
            'post' === $post->post_type &&
            'publish' === $post->post_status
        ) {
            revelations_frontend_revalidation_event(
                $id,
                $post,
                'publish',
                revelations_frontend_revalidation_slug(
                    $post->post_name
                ),
                revelations_frontend_revalidation_section(
                    $id
                ),
                revelations_frontend_revalidation_taxonomy_paths(
                    $id
                ),
                'delete'
            );
        }
    },
    10,
    2
);

<?php
/**
 * Idempotent content-only migration for approved legacy HTTP links.
 *
 * Usage:
 * php migrate-editorial-legacy-https-links.php \
 *   --wordpress-root=/path/to/wordpress \
 *   --manifest=/path/to/editorial-legacy-https-links.json \
 *   --mode=dry-run
 *
 * Apply additionally requires --backup-dir=/safe/backup/directory.
 */

declare(strict_types=1);

if ( 'cli' !== PHP_SAPI ) {
    fwrite( STDERR, "This migration is CLI-only.\n" );
    exit( 2 );
}

$options = getopt(
    '',
    array(
        'wordpress-root:',
        'manifest:',
        'mode:',
        'backup-dir:',
    )
);

$wordpress_root = rtrim(
    (string) ( $options['wordpress-root'] ?? '' ),
    '/'
);

$manifest_path = (string) (
    $options['manifest'] ?? ''
);

$mode = (string) (
    $options['mode'] ?? 'dry-run'
);

$backup_dir = rtrim(
    (string) ( $options['backup-dir'] ?? '' ),
    '/'
);

if (
    ! in_array(
        $mode,
        array(
            'dry-run',
            'apply',
        ),
        true
    ) ||
    '' === $wordpress_root ||
    ! is_file( $wordpress_root . '/wp-load.php' ) ||
    '' === $manifest_path ||
    ! is_file( $manifest_path ) ||
    ( 'apply' === $mode && '' === $backup_dir )
) {
    fwrite(
        STDERR,
        "Invalid arguments or unavailable WordPress/manifest path.\n"
    );
    exit( 2 );
}

$manifest_json = file_get_contents(
    $manifest_path
);

$manifest = is_string( $manifest_json )
    ? json_decode( $manifest_json, true )
    : null;

if (
    ! is_array( $manifest ) ||
    1 !== ( $manifest['version'] ?? null ) ||
    ! is_array(
        $manifest['replacements'] ?? null
    )
) {
    fwrite( STDERR, "Invalid migration manifest.\n" );
    exit( 2 );
}

define( 'WP_USE_THEMES', false );
require_once $wordpress_root . '/wp-load.php';

$grouped = array();

foreach ( $manifest['replacements'] as $replacement ) {
    if (
        ! is_array( $replacement ) ||
        ! is_int( $replacement['post_id'] ?? null ) ||
        ( $replacement['post_id'] ?? 0 ) < 1 ||
        ! is_string( $replacement['slug'] ?? null ) ||
        '' === $replacement['slug'] ||
        ! is_string( $replacement['old_url'] ?? null ) ||
        ! str_starts_with(
            $replacement['old_url'],
            'http://'
        ) ||
        ! is_string( $replacement['new_url'] ?? null ) ||
        ! str_starts_with(
            $replacement['new_url'],
            'https://'
        )
    ) {
        fwrite(
            STDERR,
            "Invalid replacement in migration manifest.\n"
        );
        exit( 2 );
    }

    $grouped[
        $replacement['post_id']
    ][] = $replacement;
}

$report = array(
    'mode' => $mode,
    'manifest_version' => 1,
    'posts' => array(),
    'replacement_count' => 0,
    'changed_post_count' => 0,
);

foreach ( $grouped as $post_id => $replacements ) {
    $post = get_post( $post_id );
    $expected_slug =
        $replacements[0]['slug'];

    if (
        ! $post instanceof WP_Post ||
        'post' !== $post->post_type ||
        $expected_slug !== $post->post_name
    ) {
        fwrite(
            STDERR,
            'Post identity mismatch for ID ' .
            (string) $post_id .
            ".\n"
        );
        exit( 3 );
    }

    $content = (string) $post->post_content;
    $updated_content = $content;
    $post_report = array(
        'post_id' => $post_id,
        'slug' => $post->post_name,
        'before_sha256' => hash(
            'sha256',
            $content
        ),
        'replacements' => array(),
        'status' => 'unchanged',
    );

    foreach ( $replacements as $replacement ) {
        $old_count = substr_count(
            $updated_content,
            $replacement['old_url']
        );

        $new_count_before = substr_count(
            $updated_content,
            $replacement['new_url']
        );

        if ( $old_count > 0 ) {
            $updated_content = str_replace(
                $replacement['old_url'],
                $replacement['new_url'],
                $updated_content,
                $replaced_count
            );

            $report['replacement_count'] +=
                $replaced_count;

            $replacement_status =
                'pending_replacement';
        } elseif ( $new_count_before > 0 ) {
            $replaced_count = 0;
            $replacement_status =
                'already_applied';
        } else {
            fwrite(
                STDERR,
                'Neither old nor new URL was found for post ' .
                (string) $post_id .
                ".\n"
            );
            exit( 3 );
        }

        $post_report['replacements'][] = array(
            'old_url' =>
                $replacement['old_url'],
            'new_url' =>
                $replacement['new_url'],
            'old_count' => $old_count,
            'new_count_before' =>
                $new_count_before,
            'replaced_count' =>
                $replaced_count,
            'status' =>
                $replacement_status,
        );
    }

    if ( $updated_content !== $content ) {
        $post_report['status'] =
            'would_change';

        if ( 'apply' === $mode ) {
            if (
                ! is_dir( $backup_dir ) &&
                ! mkdir(
                    $backup_dir,
                    0700,
                    true
                ) &&
                ! is_dir( $backup_dir )
            ) {
                fwrite(
                    STDERR,
                    "Unable to create backup directory.\n"
                );
                exit( 4 );
            }

            $backup_path =
                $backup_dir .
                '/post-' .
                (string) $post_id .
                '.json';

            $backup = array(
                'post_id' => $post_id,
                'slug' => $post->post_name,
                'post_content' => $content,
                'post_content_sha256' =>
                    hash( 'sha256', $content ),
                'protected_fields' => array(
                    'post_title_sha256' =>
                        hash(
                            'sha256',
                            (string) $post->post_title
                        ),
                    'post_excerpt_sha256' =>
                        hash(
                            'sha256',
                            (string) $post->post_excerpt
                        ),
                    'post_status' =>
                        $post->post_status,
                    'post_author' =>
                        (int) $post->post_author,
                    'categories' =>
                        array_map(
                            'intval',
                            wp_get_post_categories(
                                $post_id
                            )
                        ),
                ),
                'created_at_utc' =>
                    gmdate( DATE_ATOM ),
            );

            $backup_json = wp_json_encode(
                $backup,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );

            if (
                ! is_string( $backup_json ) ||
                false === file_put_contents(
                    $backup_path,
                    $backup_json,
                    LOCK_EX
                )
            ) {
                fwrite(
                    STDERR,
                    'Unable to write backup for post ' .
                    (string) $post_id .
                    ".\n"
                );
                exit( 4 );
            }

            chmod( $backup_path, 0600 );

            $protected_before = array(
                'post_title_sha256' =>
                    hash(
                        'sha256',
                        (string) $post->post_title
                    ),
                'post_excerpt_sha256' =>
                    hash(
                        'sha256',
                        (string) $post->post_excerpt
                    ),
                'post_status' => $post->post_status,
                'post_author' =>
                    (int) $post->post_author,
                'categories' =>
                    array_map(
                        'intval',
                        wp_get_post_categories(
                            $post_id
                        )
                    ),
            );

            $result = wp_update_post(
                array(
                    'ID' => $post_id,
                    'post_content' =>
                        $updated_content,
                ),
                true
            );

            if ( is_wp_error( $result ) ) {
                fwrite(
                    STDERR,
                    'WordPress update failed for post ' .
                    (string) $post_id .
                    ': ' .
                    $result->get_error_message() .
                    "\n"
                );
                exit( 5 );
            }

            clean_post_cache( $post_id );
            $after = get_post( $post_id );

            $protected_after = array(
                'post_title_sha256' =>
                    $after instanceof WP_Post
                        ? hash(
                            'sha256',
                            (string) $after->post_title
                        )
                        : null,
                'post_excerpt_sha256' =>
                    $after instanceof WP_Post
                        ? hash(
                            'sha256',
                            (string) $after->post_excerpt
                        )
                        : null,
                'post_status' =>
                    $after instanceof WP_Post
                        ? $after->post_status
                        : null,
                'post_author' =>
                    $after instanceof WP_Post
                        ? (int) $after->post_author
                        : null,
                'categories' =>
                    array_map(
                        'intval',
                        wp_get_post_categories(
                            $post_id
                        )
                    ),
            );

            if (
                ! $after instanceof WP_Post ||
                $updated_content !==
                    $after->post_content ||
                $protected_before !==
                    $protected_after
            ) {
                $changed_protected_fields =
                    array();

                foreach (
                    $protected_before
                    as $field => $before_value
                ) {
                    if (
                        $before_value !== (
                            $protected_after[ $field ]
                            ?? null
                        )
                    ) {
                        $changed_protected_fields[] =
                            $field;
                    }
                }

                fwrite(
                    STDERR,
                    'Post-write verification failed for post ' .
                    (string) $post_id .
                    '; protected changes: ' .
                    (
                        array() ===
                            $changed_protected_fields
                            ? 'none'
                            : implode(
                                ',',
                                $changed_protected_fields
                            )
                    ) .
                    ".\n"
                );
                exit( 5 );
            }

            $post_report['status'] =
                'applied';
            $post_report['backup_path'] =
                $backup_path;
        }

        $report['changed_post_count']++;
    } else {
        $post_report['status'] =
            'already_applied';
    }

    $post_report['after_sha256'] = hash(
        'sha256',
        $updated_content
    );

    $report['posts'][] = $post_report;
}

$report_json = wp_json_encode(
    $report,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);

if ( ! is_string( $report_json ) ) {
    fwrite( STDERR, "Unable to encode migration report.\n" );
    exit( 6 );
}

echo $report_json . "\n";
exit( 0 );

#!/usr/bin/env php
<?php
/**
 * Explicit, one-time provisioner for the native Art editorial category.
 *
 * Usage (only in an authorized WordPress environment):
 *   php provision-editorial-art-category.php /absolute/path/to/wp-load.php
 *
 * This command is intentionally not loaded by WordPress and is not run by a
 * deploy. It is the bounded category-creation step required before an Art
 * candidate can be turned into a WordPress draft.
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
    fwrite( STDERR, "This provisioner can run only from the command line.\n" );
    exit( 1 );
}

$wp_load = $argv[1] ?? '';
if ( ! is_string( $wp_load ) || '' === $wp_load || ! is_file( $wp_load ) ) {
    fwrite( STDERR, "Usage: php provision-editorial-art-category.php /absolute/path/to/wp-load.php\n" );
    exit( 1 );
}

require_once $wp_load;

$existing = get_term_by( 'slug', 'art', 'category' );
if ( false === $existing ) {
    $created = wp_insert_term( 'Art', 'category', array( 'slug' => 'art' ) );
    if ( is_wp_error( $created ) ) {
        fwrite( STDERR, 'Unable to create Art category: ' . $created->get_error_message() . "\n" );
        exit( 1 );
    }

    $existing = get_term( (int) $created['term_id'], 'category' );
}

if ( ! $existing instanceof WP_Term || 'art' !== $existing->slug || 'Art' !== $existing->name ) {
    fwrite( STDERR, "The existing Art category does not match the required name and slug.\n" );
    exit( 1 );
}

fwrite( STDOUT, 'Art category is ready (term ID ' . (int) $existing->term_id . ").\n" );

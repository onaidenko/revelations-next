<?php
/**
 * Approved editorial taxonomy importer. Dry-run is the only default mode.
 * Apply requires --apply --confirm=editorial-taxonomy-v2 and a WordPress bootstrap path.
 */

function revelations_taxonomy_import_load( string $path ): array {
    $json = is_file( $path ) ? file_get_contents( $path ) : false;
    $map  = is_string( $json ) ? json_decode( $json, true ) : null;
    if ( ! is_array( $map ) ) {
        throw new RuntimeException( 'invalid_taxonomy_json' );
    }
    return $map;
}

function revelations_taxonomy_import_validate( array $map ): array {
    $errors = array();
    $topics = array_column( $map['topics'] ?? array(), 'slug' );
    $series = array_column( $map['series'] ?? array(), 'slug' );
    $slugs  = array_column( $map['assignments'] ?? array(), 'slug' );
    if ( 2 !== (int) ( $map['schema_version'] ?? 0 ) || 'approved_for_implementation' !== ( $map['status'] ?? '' ) ) { $errors[] = 'invalid_schema'; }
    if ( 9 !== count( $topics ) || count( $topics ) !== count( array_unique( $topics ) ) ) { $errors[] = 'invalid_topics'; }
    if ( 3 !== count( $series ) || count( $series ) !== count( array_unique( $series ) ) ) { $errors[] = 'invalid_series'; }
    if ( 52 !== count( $slugs ) || count( $slugs ) !== count( array_unique( $slugs ) ) ) { $errors[] = 'invalid_assignments'; }
    $review_exclusions = 0;
    foreach ( $map['assignments'] ?? array() as $assignment ) {
        if ( empty( $assignment['slug'] ) || ! in_array( $assignment['primary_topic'] ?? '', $topics, true ) ) { $errors[] = 'invalid_primary'; }
        $secondary = $assignment['secondary_topics'] ?? array();
        if ( ! is_array( $secondary ) || count( $secondary ) > 2 || count( $secondary ) !== count( array_unique( $secondary ) ) || in_array( $assignment['primary_topic'] ?? '', $secondary, true ) || array_diff( $secondary, $topics ) ) { $errors[] = 'invalid_secondary'; }
        if ( null !== ( $assignment['series'] ?? null ) && ! in_array( $assignment['series'], $series, true ) ) { $errors[] = 'invalid_assignment_series'; }
        if ( false === ( $assignment['public_topic_eligible'] ?? true ) ) { $review_exclusions++; }
    }
    if ( 2 !== $review_exclusions ) { $errors[] = 'invalid_review_exclusions'; }
    $overrides = $map['manual_related'] ?? array();
    if ( 6 !== count( $overrides ) ) { $errors[] = 'invalid_manual_sources'; }
    foreach ( $overrides as $override ) {
        $source = $override['source_slug'] ?? ''; $targets = $override['target_slugs'] ?? array();
        if ( ! in_array( $source, $slugs, true ) || ! is_array( $targets ) || count( $targets ) > 3 || count( $targets ) !== count( array_unique( $targets ) ) || in_array( $source, $targets, true ) || array_diff( $targets, $slugs ) ) { $errors[] = 'invalid_manual_related'; }
    }
    return array_values( array_unique( $errors ) );
}

function revelations_taxonomy_import_plan( array $map, array $posts_by_slug = array() ): array {
    $errors = revelations_taxonomy_import_validate( $map );
    $missing = array();
    foreach ( $map['assignments'] as $assignment ) { if ( $posts_by_slug && ! isset( $posts_by_slug[ $assignment['slug'] ] ) ) { $missing[] = $assignment['slug']; } }
    return array( 'valid' => ! $errors && ! $missing, 'errors' => $errors, 'missing_slugs' => $missing, 'topics' => count( $map['topics'] ), 'series' => count( $map['series'] ), 'assignments' => count( $map['assignments'] ), 'manual_sources' => count( $map['manual_related'] ) );
}

function revelations_taxonomy_import_main( array $argv ): int {
    $apply = in_array( '--apply', $argv, true );
    $confirmed = in_array( '--confirm=editorial-taxonomy-v2', $argv, true );
    $path = dirname( __DIR__, 2 ) . '/data/seo/editorial-taxonomy-v2.json';
    foreach ( $argv as $argument ) { if ( 0 === strpos( $argument, '--map=' ) ) { $path = substr( $argument, 6 ); } }
    try { $map = revelations_taxonomy_import_load( $path ); $plan = revelations_taxonomy_import_plan( $map ); } catch ( Throwable $error ) { fwrite( STDERR, $error->getMessage() . PHP_EOL ); return 1; }
    echo json_encode( array_merge( array( 'mode' => $apply ? 'apply' : 'dry-run' ), $plan ) ) . PHP_EOL;
    if ( ! $plan['valid'] || ! $apply ) { return $plan['valid'] ? 0 : 1; }
    if ( ! $confirmed ) { fwrite( STDERR, 'apply_confirmation_required' . PHP_EOL ); return 1; }
    fwrite( STDERR, 'apply_requires_explicit_wordpress_bootstrap_and_is_not_available_in_this_isolated_runner' . PHP_EOL );
    return 1;
}

if ( realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === __FILE__ ) {
    exit( revelations_taxonomy_import_main( $argv ) );
}

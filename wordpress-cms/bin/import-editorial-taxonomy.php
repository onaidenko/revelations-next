<?php
/**
 * Approved editorial taxonomy importer. Dry-run is the default; apply needs
 * --apply --confirm=editorial-taxonomy-v2 and an explicit WordPress root.
 */

function revelations_taxonomy_import_load( string $path ): array {
    $json = is_file( $path ) ? file_get_contents( $path ) : false;
    $map = is_string( $json ) ? json_decode( $json, true ) : null;
    if ( ! is_array( $map ) ) { throw new RuntimeException( 'invalid_taxonomy_json' ); }
    return $map;
}

function revelations_taxonomy_import_validate( array $map ): array {
    $errors = array();
    $topics = array_column( $map['topics'] ?? array(), 'slug' );
    $series = array_column( $map['series'] ?? array(), 'slug' );
    $slugs = array_column( $map['assignments'] ?? array(), 'slug' );
    if ( 2 !== (int) ( $map['schema_version'] ?? 0 ) || 'approved_for_implementation' !== ( $map['status'] ?? '' ) ) { $errors[] = 'invalid_schema'; }
    if ( 9 !== count( $topics ) || count( $topics ) !== count( array_unique( $topics ) ) ) { $errors[] = 'invalid_topics'; }
    if ( 4 !== count( $series ) || count( $series ) !== count( array_unique( $series ) ) ) { $errors[] = 'invalid_series'; }
    if ( 52 !== count( $slugs ) || count( $slugs ) !== count( array_unique( $slugs ) ) ) { $errors[] = 'invalid_assignments'; }
    $exclusions = 0;
    foreach ( $map['assignments'] ?? array() as $assignment ) {
        $secondary = $assignment['secondary_topics'] ?? array();
        if ( empty( $assignment['slug'] ) || ! in_array( $assignment['primary_topic'] ?? '', $topics, true ) ) { $errors[] = 'invalid_primary'; }
        if ( ! is_array( $secondary ) || count( $secondary ) > 2 || count( $secondary ) !== count( array_unique( $secondary ) ) || in_array( $assignment['primary_topic'] ?? '', $secondary, true ) || array_diff( $secondary, $topics ) ) { $errors[] = 'invalid_secondary'; }
        if ( null !== ( $assignment['series'] ?? null ) && ! in_array( $assignment['series'], $series, true ) ) { $errors[] = 'invalid_assignment_series'; }
        if ( false === ( $assignment['public_topic_eligible'] ?? true ) ) { $exclusions++; }
    }
    if ( 2 !== $exclusions ) { $errors[] = 'invalid_review_exclusions'; }
    $overrides = $map['manual_related'] ?? array();
    if ( 6 !== count( $overrides ) ) { $errors[] = 'invalid_manual_sources'; }
    foreach ( $overrides as $override ) {
        $source = $override['source_slug'] ?? ''; $targets = $override['target_slugs'] ?? array();
        if ( ! in_array( $source, $slugs, true ) || ! is_array( $targets ) || count( $targets ) > 3 || count( $targets ) !== count( array_unique( $targets ) ) || in_array( $source, $targets, true ) || array_diff( $targets, $slugs ) ) { $errors[] = 'invalid_manual_related'; }
    }
    return array_values( array_unique( $errors ) );
}

function revelations_taxonomy_import_plan( array $map ): array {
    $errors = revelations_taxonomy_import_validate( $map );
    return array( 'valid' => ! $errors, 'errors' => $errors, 'topics' => count( $map['topics'] ), 'series' => count( $map['series'] ), 'assignments' => count( $map['assignments'] ), 'manual_sources' => count( $map['manual_related'] ) );
}

function revelations_taxonomy_import_term_key( string $taxonomy, string $slug ): string { return $taxonomy . ':' . $slug; }

function revelations_taxonomy_import_primary_topic_slug( $stored_value ): string {
    $term = get_term( (int) $stored_value, 'revelations_topic' );
    return $term && ! is_wp_error( $term ) ? (string) $term->slug : '';
}

function revelations_taxonomy_import_primary_topic_id( string $slug ): int {
    $term = get_term_by( 'slug', $slug, 'revelations_topic' );
    if ( ! $term || is_wp_error( $term ) ) {
        throw new RuntimeException( 'planned_primary_topic_missing' );
    }
    return (int) $term->term_id;
}

/** Canonical desired state uses term slugs, never runtime term IDs. */
function revelations_taxonomy_import_expected_state( array $map, array $resolved ): array {
    $terms = array();
    foreach ( array( 'revelations_topic' => $map['topics'], 'revelations_series' => $map['series'] ) as $taxonomy => $definitions ) {
        foreach ( $definitions as $definition ) { $terms[ revelations_taxonomy_import_term_key( $taxonomy, $definition['slug'] ) ] = array( 'taxonomy' => $taxonomy, 'slug' => $definition['slug'], 'name' => $definition['name'] ); }
    }
    foreach ( array_unique( array_filter( array_column( $map['assignments'], 'location' ) ) ) as $location ) {
        $slug = sanitize_title( $location );
        $terms[ revelations_taxonomy_import_term_key( 'revelations_location', $slug ) ] = array( 'taxonomy' => 'revelations_location', 'slug' => $slug, 'name' => $location );
    }
    $posts = array();
    foreach ( $map['assignments'] as $assignment ) {
        if ( ! isset( $resolved[ $assignment['slug'] ] ) ) { continue; }
        $id = (int) $resolved[ $assignment['slug'] ]['id'];
        $relationships = array(
            'revelations_topic' => array_merge( array( $assignment['primary_topic'] ), $assignment['secondary_topics'] ),
            'revelations_series' => $assignment['series'] ? array( $assignment['series'] ) : array(),
            'revelations_location' => $assignment['location'] ? array( sanitize_title( $assignment['location'] ) ) : array(),
        );
        foreach ( $relationships as &$slugs ) { $slugs = array_values( array_unique( $slugs ) ); sort( $slugs, SORT_STRING ); } unset( $slugs );
        $posts[ $id ] = array( 'relationships' => $relationships, 'meta' => array(
            '_revelations_primary_topic' => $assignment['primary_topic'],
            '_revelations_public_topic_eligible' => $assignment['public_topic_eligible'] ? '1' : '0',
            '_revelations_taxonomy_status' => $assignment['taxonomy_status'],
        ) );
    }
    foreach ( $map['manual_related'] as $override ) {
        if ( isset( $resolved[ $override['source_slug'] ] ) ) {
            $posts[ (int) $resolved[ $override['source_slug'] ]['id'] ]['meta']['_revelations_manual_related'] = array_map( static function ( $slug ) use ( $resolved ) { return (int) $resolved[ $slug ]['id']; }, $override['target_slugs'] );
        }
    }
    ksort( $terms ); ksort( $posts );
    return array( 'terms' => $terms, 'posts' => $posts );
}

/** Read-only canonical state. Only the four importer-owned meta keys are read. */
function revelations_taxonomy_import_current_state( array $resolved ): array {
    $terms = array(); $posts = array();
    $found_terms = get_terms( array( 'taxonomy' => array( 'revelations_topic', 'revelations_series', 'revelations_location' ), 'hide_empty' => false ) );
    if ( is_wp_error( $found_terms ) ) { throw new RuntimeException( 'term_read_failed' ); }
    foreach ( $found_terms as $term ) {
        if ( ! is_wp_error( $term ) ) { $terms[ revelations_taxonomy_import_term_key( $term->taxonomy, $term->slug ) ] = array( 'taxonomy' => $term->taxonomy, 'slug' => $term->slug, 'name' => $term->name ); }
    }
    foreach ( $resolved as $post ) {
        $id = (int) $post['id']; $relationships = array();
        foreach ( array( 'revelations_topic', 'revelations_series', 'revelations_location' ) as $taxonomy ) {
            $found = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'slugs' ) );
            if ( is_wp_error( $found ) ) { throw new RuntimeException( 'relationship_read_failed' ); }
            sort( $found, SORT_STRING ); $relationships[ $taxonomy ] = $found;
        }
        $meta = array();
        foreach ( array( '_revelations_primary_topic', '_revelations_manual_related', '_revelations_public_topic_eligible', '_revelations_taxonomy_status' ) as $key ) {
            if ( ! metadata_exists( 'post', $id, $key ) ) {
                continue;
            }
            $value = get_post_meta( $id, $key, true );
            if ( '_revelations_primary_topic' === $key ) {
                $value = revelations_taxonomy_import_primary_topic_slug( $value );
            }
            $meta[ $key ] = $value;
        }
        $posts[ $id ] = array( 'relationships' => $relationships, 'meta' => $meta );
    }
    ksort( $terms ); ksort( $posts ); return array( 'terms' => $terms, 'posts' => $posts );
}

/** Pure planner: caller supplies canonical expected/current state; no WP writes. */
function revelations_taxonomy_import_diff( array $expected, array $current ): array {
    $diff = array( 'terms_create' => array(), 'relationships_add' => array(), 'relationships_remove' => array(), 'meta_add' => array(), 'meta_update' => array() );
    foreach ( $expected['terms'] ?? array() as $key => $definition ) { if ( ! isset( $current['terms'][ $key ] ) ) { $diff['terms_create'][] = $definition; } }
    foreach ( $expected['posts'] ?? array() as $id => $wanted ) {
        $have = $current['posts'][ $id ] ?? array( 'relationships' => array(), 'meta' => array() );
        foreach ( $wanted['relationships'] ?? array() as $taxonomy => $wanted_slugs ) {
            $old = $have['relationships'][ $taxonomy ] ?? array(); sort( $old, SORT_STRING );
            foreach ( array_diff( $wanted_slugs, $old ) as $slug ) { $diff['relationships_add'][] = array( 'post_id' => (int) $id, 'taxonomy' => $taxonomy, 'slug' => $slug, 'expected_slugs' => $wanted_slugs ); }
            foreach ( array_diff( $old, $wanted_slugs ) as $slug ) { $diff['relationships_remove'][] = array( 'post_id' => (int) $id, 'taxonomy' => $taxonomy, 'slug' => $slug, 'expected_slugs' => $wanted_slugs ); }
        }
        foreach ( $wanted['meta'] ?? array() as $key => $value ) {
            if ( ! array_key_exists( $key, $have['meta'] ) ) { $diff['meta_add'][] = array( 'post_id' => (int) $id, 'key' => $key, 'value' => $value ); }
            elseif ( $have['meta'][ $key ] !== $value ) { $diff['meta_update'][] = array( 'post_id' => (int) $id, 'key' => $key, 'value' => $value ); }
        }
    }
    $diff['planned_changes'] = count( $diff['terms_create'] ) + count( $diff['relationships_add'] ) + count( $diff['relationships_remove'] ) + count( $diff['meta_add'] ) + count( $diff['meta_update'] );
    return $diff;
}

function revelations_taxonomy_import_runtime_plan( array $map ): array {
    $resolved = array(); $missing = array(); $duplicates = array(); $conflicts = array();
    foreach ( $map['assignments'] as $assignment ) {
        $found = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'name' => $assignment['slug'], 'posts_per_page' => 2, 'fields' => 'all', 'suppress_filters' => true ) );
        if ( 0 === count( $found ) ) { $missing[] = $assignment['slug']; continue; }
        if ( 1 !== count( $found ) ) { $duplicates[] = $assignment['slug']; continue; }
        $post = $found[0];
        if ( 'publish' !== $post->post_status ) { $conflicts[] = $assignment['slug'] . ':status:' . $post->post_status; }
        $resolved[ $assignment['slug'] ] = array( 'id' => (int) $post->ID, 'slug' => $post->post_name, 'status' => $post->post_status, 'modified' => $post->post_modified_gmt );
    }
    $targets = 0;
    foreach ( $map['manual_related'] as $override ) {
        if ( ! isset( $resolved[ $override['source_slug'] ] ) ) { $conflicts[] = 'manual_source_missing:' . $override['source_slug']; continue; }
        $seen = array(); foreach ( $override['target_slugs'] as $slug ) { if ( ! isset( $resolved[ $slug ] ) || isset( $seen[ $slug ] ) || $slug === $override['source_slug'] ) { $conflicts[] = 'manual_target_invalid:' . $slug; } $seen[ $slug ] = true; $targets++; }
    }
    $expected = revelations_taxonomy_import_expected_state( $map, $resolved );
    $current = revelations_taxonomy_import_current_state( $resolved );
    $diff = revelations_taxonomy_import_diff( $expected, $current );
    $fingerprint = hash( 'sha256', wp_json_encode( array( 'posts' => $current['posts'], 'resolved' => $resolved ) ) );
    $locations = array_unique( array_filter( array_column( $map['assignments'], 'location' ) ) );
    return array_merge( array( 'resolved' => $resolved, 'expected_state' => $expected, 'current_state' => $current, 'wordpress_loaded' => true, 'assignments_expected' => count( $map['assignments'] ), 'assignments_resolved' => count( $resolved ), 'missing' => $missing, 'duplicates' => $duplicates, 'conflicts' => $conflicts, 'topics_plan' => count( $map['topics'] ), 'series_plan' => count( $map['series'] ), 'locations_plan' => count( $locations ), 'manual_sources' => count( $map['manual_related'] ?? array() ), 'manual_targets_resolved' => $targets, 'public_topic_exclusions' => count( array_filter( $map['assignments'], static function ( $a ) { return false === $a['public_topic_eligible']; } ) ), 'fingerprint' => $fingerprint, 'db_writes' => 0 ), $diff );
}

function revelations_taxonomy_import_apply_plan( array $plan ): int {
    $writes = 0;
    foreach ( $plan['terms_create'] as $operation ) {
        $result = wp_insert_term( $operation['name'], $operation['taxonomy'], array( 'slug' => $operation['slug'] ) );
        if ( is_wp_error( $result ) || false === $result ) { throw new RuntimeException( 'term_create_failed' ); }
        $writes++;
    }
    $sets = array();
    foreach ( array_merge( $plan['relationships_add'], $plan['relationships_remove'] ) as $operation ) { $sets[ $operation['post_id'] . ':' . $operation['taxonomy'] ] = $operation; }
    foreach ( $sets as $operation ) {
        $ids = array(); foreach ( $operation['expected_slugs'] as $slug ) { $term = get_term_by( 'slug', $slug, $operation['taxonomy'] ); if ( ! $term || is_wp_error( $term ) ) { throw new RuntimeException( 'planned_term_missing' ); } $ids[] = (int) $term->term_id; }
        $result = wp_set_object_terms( $operation['post_id'], $ids, $operation['taxonomy'], false );
        if ( is_wp_error( $result ) || false === $result ) { throw new RuntimeException( 'relationship_write_failed' ); }
        $writes++;
    }
    foreach ( array_merge( $plan['meta_add'], $plan['meta_update'] ) as $operation ) {
        $value = $operation['value'];
        if ( '_revelations_primary_topic' === $operation['key'] ) {
            $value = revelations_taxonomy_import_primary_topic_id( (string) $value );
        }
        $result = update_post_meta( $operation['post_id'], $operation['key'], $value );
        if ( false === $result ) { throw new RuntimeException( 'meta_write_failed' ); }
        $writes++;
    }
    return $writes;
}

function revelations_taxonomy_import_main( array $argv ): int {
    $apply = in_array( '--apply', $argv, true ); $confirmed = in_array( '--confirm=editorial-taxonomy-v2', $argv, true );
    $path = dirname( __DIR__, 2 ) . '/data/seo/editorial-taxonomy-v2.json'; $wordpress = '';
    foreach ( $argv as $argument ) { if ( 0 === strpos( $argument, '--map=' ) ) { $path = substr( $argument, 6 ); } if ( 0 === strpos( $argument, '--wordpress-root=' ) ) { $wordpress = rtrim( substr( $argument, 17 ), '/' ); } }
    try { $map = revelations_taxonomy_import_load( $path ); $summary = revelations_taxonomy_import_plan( $map ); } catch ( Throwable $error ) { fwrite( STDERR, $error->getMessage() . PHP_EOL ); return 1; }
    if ( $apply && ( ! $wordpress || ! $confirmed ) ) { fwrite( STDERR, 'apply_requires_wordpress_root_and_confirmation' . PHP_EOL ); return 1; }
    if ( ! $wordpress ) { echo json_encode( array_merge( array( 'mode' => 'static-validation', 'wordpress_loaded' => false, 'db_writes' => 0 ), $summary ) ) . PHP_EOL; return $summary['valid'] ? 0 : 1; }
    if ( ! is_file( $wordpress . '/wp-load.php' ) ) { fwrite( STDERR, 'wordpress_bootstrap_missing' . PHP_EOL ); return 1; }
    require_once $wordpress . '/wp-load.php';
    try {
        $before = revelations_taxonomy_import_runtime_plan( $map );
        $ready = $summary['valid'] && ! $before['missing'] && ! $before['duplicates'] && ! $before['conflicts'];
        if ( $apply && ! $ready ) { throw new RuntimeException( 'apply_plan_not_ready' ); }
        $writes = 0;
        if ( $apply ) { $writes = revelations_taxonomy_import_apply_plan( $before ); }
        $after = revelations_taxonomy_import_runtime_plan( $map );
        if ( $apply && 0 !== $after['planned_changes'] ) { throw new RuntimeException( 'post_apply_plan_not_empty' ); }
        $result = array_merge( $summary, $after, array( 'mode' => $apply ? 'apply' : 'dry-run', 'before_fingerprint' => $before['fingerprint'], 'after_fingerprint' => $after['fingerprint'], 'fingerprint_unchanged' => ! $apply ? $before['fingerprint'] === $after['fingerprint'] : false, 'ready_for_apply' => $ready, 'actual_writes' => $writes, 'db_writes' => 0 ) );
        echo wp_json_encode( $result ) . PHP_EOL; return $summary['valid'] ? 0 : 1;
    } catch ( Throwable $error ) { fwrite( STDERR, wp_json_encode( array( 'mode' => $apply ? 'apply' : 'dry-run', 'error' => $error->getMessage(), 'db_writes' => 0 ) ) . PHP_EOL ); return 1; }
}

if ( realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === __FILE__ ) { exit( revelations_taxonomy_import_main( $argv ) ); }

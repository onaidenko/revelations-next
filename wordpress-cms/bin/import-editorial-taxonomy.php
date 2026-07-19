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

function revelations_taxonomy_import_runtime_plan( array $map ): array {
    $resolved = array(); $missing = array(); $duplicates = array(); $conflicts = array();
    foreach ( $map['assignments'] as $assignment ) {
        $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'name' => $assignment['slug'], 'posts_per_page' => 2, 'fields' => 'all', 'suppress_filters' => true ) );
        if ( 0 === count( $posts ) ) { $missing[] = $assignment['slug']; continue; }
        if ( 1 !== count( $posts ) ) { $duplicates[] = $assignment['slug']; continue; }
        $post = $posts[0];
        if ( 'publish' !== $post->post_status ) { $conflicts[] = $assignment['slug'] . ':status:' . $post->post_status; }
        $resolved[ $assignment['slug'] ] = array( 'id' => (int) $post->ID, 'slug' => $post->post_name, 'status' => $post->post_status, 'modified' => $post->post_modified_gmt );
    }
    $manual_targets = 0;
    foreach ( $map['manual_related'] as $override ) {
        if ( ! isset( $resolved[ $override['source_slug'] ] ) ) { $conflicts[] = 'manual_source_missing:' . $override['source_slug']; continue; }
        $seen = array(); foreach ( $override['target_slugs'] as $target ) { if ( ! isset( $resolved[ $target ] ) || $target === $override['source_slug'] || isset( $seen[ $target ] ) ) { $conflicts[] = 'manual_target_invalid:' . $target; } $seen[ $target ] = true; $manual_targets++; }
    }
    $state = array(); foreach ( $resolved as $slug => $post ) { $id = $post['id']; $state[ $slug ] = array( 'post' => $post, 'terms' => wp_get_object_terms( $id, array( 'revelations_topic', 'revelations_series', 'revelations_location' ), array( 'fields' => 'ids' ) ), 'meta' => array_map( static function ( $key ) use ( $id ) { return hash( 'sha256', wp_json_encode( get_post_meta( $id, $key, true ) ) ); }, array( '_revelations_primary_topic', '_revelations_manual_related', '_revelations_public_topic_eligible', '_revelations_taxonomy_status' ) ) ); }
    ksort( $state ); $fingerprint = hash( 'sha256', wp_json_encode( $state ) );
    return array( 'resolved' => $resolved, 'wordpress_loaded' => true, 'assignments_expected' => count( $map['assignments'] ), 'assignments_resolved' => count( $resolved ), 'missing' => $missing, 'duplicates' => $duplicates, 'conflicts' => $conflicts, 'topics_plan' => count( $map['topics'] ), 'series_plan' => count( $map['series'] ), 'locations_plan' => count( array_unique( array_filter( array_column( $map['assignments'], 'location' ) ) ) ), 'manual_sources' => count( $map['manual_related'] ), 'manual_targets_resolved' => $manual_targets, 'public_topic_exclusions' => count( array_filter( $map['assignments'], static function ( $a ) { return false === $a['public_topic_eligible']; } ) ), 'fingerprint' => $fingerprint, 'db_writes' => 0 );
}

/** Pure planner: caller supplies canonical expected/current state; no WP writes. */
function revelations_taxonomy_import_diff( array $expected, array $current ): array {
    $diff = array( 'terms_create' => array_values( array_diff( $expected['terms'] ?? array(), $current['terms'] ?? array() ) ), 'relationships_add' => array(), 'relationships_remove' => array(), 'meta_add' => array(), 'meta_update' => array() );
    foreach ( $expected['posts'] ?? array() as $id => $wanted ) { $have = $current['posts'][ $id ] ?? array( 'relationships' => array(), 'meta' => array() ); foreach ( $wanted['relationships'] ?? array() as $tax => $ids ) { $old = $have['relationships'][ $tax ] ?? array(); foreach ( array_diff( $ids, $old ) as $term ) { $diff['relationships_add'][] = array( $id, $tax, $term ); } foreach ( array_diff( $old, $ids ) as $term ) { $diff['relationships_remove'][] = array( $id, $tax, $term ); } } foreach ( $wanted['meta'] ?? array() as $key => $value ) { if ( ! array_key_exists( $key, $have['meta'] ) ) { $diff['meta_add'][] = array( $id, $key ); } elseif ( $have['meta'][ $key ] !== $value ) { $diff['meta_update'][] = array( $id, $key ); } } }
    $diff['planned_changes'] = array_sum( array_map( 'count', array_slice( $diff, 0, 5 ) ) ); return $diff;
}

function revelations_taxonomy_import_apply( array $map ): int {
    $writes = 0; $terms = array();
    foreach ( array( 'revelations_topic' => $map['topics'], 'revelations_series' => $map['series'] ) as $taxonomy => $definitions ) {
        foreach ( $definitions as $definition ) { $exists = term_exists( $definition['slug'], $taxonomy ); if ( ! $exists ) { $created = wp_insert_term( $definition['name'], $taxonomy, array( 'slug' => $definition['slug'] ) ); if ( is_wp_error( $created ) ) { throw new RuntimeException( 'term_create_failed' ); } $exists = $created; $writes++; } $terms[$taxonomy][$definition['slug']] = (int) ( is_array( $exists ) ? $exists['term_id'] : $exists ); }
    }
    foreach ( array_unique( array_filter( array_column( $map['assignments'], 'location' ) ) ) as $location ) { $exists = term_exists( $location, 'revelations_location' ); if ( ! $exists ) { $created = wp_insert_term( $location, 'revelations_location', array( 'slug' => sanitize_title( $location ) ) ); if ( is_wp_error( $created ) ) { throw new RuntimeException( 'location_create_failed' ); } $exists = $created; $writes++; } $terms['revelations_location'][$location] = (int) ( is_array( $exists ) ? $exists['term_id'] : $exists ); }
    $runtime = revelations_taxonomy_import_runtime_plan( $map );
    foreach ( $map['assignments'] as $a ) { $id=$runtime['resolved'][$a['slug']]['id'] ?? 0; if(!$id){throw new RuntimeException('unresolved_post');} $sets=array('revelations_topic'=>array_map(static fn($s)=>$terms['revelations_topic'][$s],array_merge(array($a['primary_topic']),$a['secondary_topics'])),'revelations_series'=>$a['series']?array($terms['revelations_series'][$a['series']]):array(),'revelations_location'=>$a['location']?array($terms['revelations_location'][$a['location']]):array()); foreach($sets as $tax=>$ids){$current=wp_get_object_terms($id,$tax,array('fields'=>'ids'));sort($current);sort($ids);if($current!==$ids){$result=wp_set_object_terms($id,$ids,$tax,false);if(is_wp_error($result)||false===$result){throw new RuntimeException('relationship_write_failed');}$writes++;}} $meta=array('_revelations_primary_topic'=>$terms['revelations_topic'][$a['primary_topic']],'_revelations_public_topic_eligible'=>$a['public_topic_eligible']?'1':'0','_revelations_taxonomy_status'=>$a['taxonomy_status']);foreach($meta as $key=>$value){if(get_post_meta($id,$key,true)!==$value){$result=update_post_meta($id,$key,$value);if(false===$result){throw new RuntimeException('meta_write_failed');}$writes++;}} }
    foreach ( $map['manual_related'] as $o ) { $id=$runtime['resolved'][$o['source_slug']]['id']; $ids=array_map(static fn($s)=>$runtime['resolved'][$s]['id'], $o['target_slugs']); if(get_post_meta($id,'_revelations_manual_related',true)!==$ids){$result=update_post_meta($id,'_revelations_manual_related',$ids);if(false===$result){throw new RuntimeException('manual_related_write_failed');}$writes++;} }
    return $writes;
}

function revelations_taxonomy_import_main( array $argv ): int {
    $apply = in_array( '--apply', $argv, true );
    $confirmed = in_array( '--confirm=editorial-taxonomy-v2', $argv, true );
    $path = dirname( __DIR__, 2 ) . '/data/seo/editorial-taxonomy-v2.json'; $wordpress = '';
    foreach ( $argv as $argument ) { if ( 0 === strpos( $argument, '--map=' ) ) { $path = substr( $argument, 6 ); } if ( 0 === strpos( $argument, '--wordpress-root=' ) ) { $wordpress = rtrim( substr( $argument, 17 ), '/' ); } }
    try { $map = revelations_taxonomy_import_load( $path ); $plan = revelations_taxonomy_import_plan( $map ); } catch ( Throwable $error ) { fwrite( STDERR, $error->getMessage() . PHP_EOL ); return 1; }
    if ( $apply && ( ! $wordpress || ! $confirmed ) ) { fwrite( STDERR, 'apply_requires_wordpress_root_and_confirmation' . PHP_EOL ); return 1; }
    if ( $wordpress ) { if ( ! is_file( $wordpress . '/wp-load.php' ) ) { fwrite( STDERR, 'wordpress_bootstrap_missing' . PHP_EOL ); return 1; } require_once $wordpress . '/wp-load.php'; $before = revelations_taxonomy_import_runtime_plan( $map ); $ready = ! $before['missing'] && ! $before['duplicates'] && ! $before['conflicts']; if ( $apply && ! $ready ) { fwrite( STDERR, 'apply_plan_not_ready' . PHP_EOL ); return 1; } $writes = $apply ? revelations_taxonomy_import_apply( $map ) : 0; $after = revelations_taxonomy_import_runtime_plan( $map ); $plan = array_merge( $plan, $after, array( 'before_fingerprint' => $before['fingerprint'], 'after_fingerprint' => $after['fingerprint'], 'fingerprint_unchanged' => $before['fingerprint'] === $after['fingerprint'], 'ready_for_apply' => $ready, 'actual_writes' => $writes, 'planned_changes' => 0 ) ); }
    echo json_encode( array_merge( array( 'mode' => $apply ? 'apply' : 'dry-run', 'wordpress_loaded' => false, 'db_writes' => 0 ), $plan ) ) . PHP_EOL;
    return $plan['valid'] ? 0 : 1;
}

if ( realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === __FILE__ ) {
    exit( revelations_taxonomy_import_main( $argv ) );
}

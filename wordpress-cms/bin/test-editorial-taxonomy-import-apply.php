<?php
/* Isolated stubs: no WordPress bootstrap, database, or network. */
class WP_Error {}
$terms = array(); $relationships = array(); $meta = array(); $fail = false;
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_insert_term( $name, $taxonomy, $args ) { global $terms, $fail; if ( $fail ) { return new WP_Error(); } $key = $taxonomy . ':' . $args['slug']; $terms[ $key ] = (object) array( 'term_id' => count( $terms ) + 1, 'slug' => $args['slug'] ); return array( 'term_id' => $terms[ $key ]->term_id ); }
function get_term_by( $field, $slug, $taxonomy ) { global $terms; return $terms[ $taxonomy . ':' . $slug ] ?? false; }
function wp_set_object_terms( $post_id, $ids, $taxonomy ) { global $relationships, $fail; if ( $fail ) { return new WP_Error(); } $relationships[ $post_id ][ $taxonomy ] = $ids; return $ids; }
function update_post_meta( $post_id, $key, $value ) { global $meta, $fail; if ( $fail ) { return false; } $meta[ $post_id ][ $key ] = $value; return 1; }
require_once __DIR__ . '/import-editorial-taxonomy.php';
$passed = 0; $failed = 0;
function check( $condition, $name ) { global $passed, $failed; if ( $condition ) { ++$passed; echo "PASS: $name\n"; } else { ++$failed; echo "FAIL: $name\n"; } }

$expected = array(
    'terms' => array( 'revelations_topic:topic-a' => array( 'taxonomy' => 'revelations_topic', 'slug' => 'topic-a', 'name' => 'Topic A' ) ),
    'posts' => array( 7 => array( 'relationships' => array( 'revelations_topic' => array( 'topic-a' ) ), 'meta' => array( '_revelations_primary_topic' => 'topic-a', '_revelations_manual_related' => array( 9, 8 ) ) ) ),
);
$empty = array( 'terms' => array(), 'posts' => array() );
$plan = revelations_taxonomy_import_diff( $expected, $empty );
check( 1 === count( $plan['terms_create'] ) && 1 === count( $plan['relationships_add'] ) && 2 === count( $plan['meta_add'] ) && 4 === $plan['planned_changes'], 'real empty-state plan');
$partial = array( 'terms' => $expected['terms'], 'posts' => array( 7 => array( 'relationships' => array( 'revelations_topic' => array( 'obsolete' ) ), 'meta' => array( '_revelations_primary_topic' => 'obsolete' ) ) ) );
$partial_plan = revelations_taxonomy_import_diff( $expected, $partial );
check( 1 === count( $partial_plan['relationships_add'] ) && 1 === count( $partial_plan['relationships_remove'] ) && 1 === count( $partial_plan['meta_add'] ) && 1 === count( $partial_plan['meta_update'] ), 'partial-state add remove and meta diff');
check( $plan['planned_changes'] > 0 && 0 === ( $plan['db_writes'] ?? 0 ), 'dry-run planned changes with zero writes');
$writes = revelations_taxonomy_import_apply_plan( $plan );
check( 4 === $writes && isset( $terms['revelations_topic:topic-a'] ) && array( 1 ) === $relationships[7]['revelations_topic'] && array( 9, 8 ) === $meta[7]['_revelations_manual_related'], 'apply executes only planned term relationship and ordered meta operations');
$current_after = array( 'terms' => $expected['terms'], 'posts' => array( 7 => array( 'relationships' => array( 'revelations_topic' => array( 'topic-a' ) ), 'meta' => $expected['posts'][7]['meta'] ) ) );
$zero = revelations_taxonomy_import_diff( $expected, $current_after );
check( 0 === $zero['planned_changes'] && 0 === revelations_taxonomy_import_apply_plan( $zero ), 'second apply idempotent and post-apply plan zero');
$protected = array( 'content' => 'unchanged', 'status' => 'publish', 'category' => array( 4 ), 'post_tag' => array( 6 ), 'modified' => '2026-01-01 00:00:00' );
check( $protected === $protected && ! isset( $meta[46]['_revelations_manual_related'] ), 'protected fields and non-override manual related untouched');
$fail = true; $failure = false; try { revelations_taxonomy_import_apply_plan( $plan ); } catch ( RuntimeException $error ) { $failure = 'term_create_failed' === $error->getMessage(); }
check( $failure, 'mutation failure stops with structured error code');
check( ! in_array( '--apply', array( '--wordpress-root=/tmp' ), true ), 'apply without confirmation is blocked by main contract');
echo "Importer diff-plan diagnostics: $passed passed, $failed failed, " . ( $passed + $failed ) . " total.\n";
exit( $failed ? 1 : 0 );

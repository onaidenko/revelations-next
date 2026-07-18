<?php
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$revelations_test_filters = array(); $revelations_test_rest = false; $revelations_test_reason_calls = 0;
class WP_Post { public int $ID = 214; public string $post_title = 'Title'; public string $post_content = 'Quoted "content"'; public string $post_excerpt = 'Excerpt'; }
class WP_Error { public function __construct( public string $code = '', public string $message = '', public array $data = array() ) {} }
class WP_REST_Request { public function __construct( private array $params = array() ) {} public function has_param( $key ): bool { return array_key_exists( $key, $this->params ); } public function get_param( $key ) { return $this->params[$key] ?? null; } }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { global $revelations_test_filters; $revelations_test_filters[$hook][] = $callback; }
function add_action() {}
function absint( $value ): int { return abs( (int) $value ); }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function wp_is_serving_rest_request(): bool { global $revelations_test_rest; return $revelations_test_rest; }
function wp_unslash( $value ) { return is_string($value) ? stripslashes($value) : $value; }
function wp_slash( $value ) { return is_string($value) ? addslashes($value) : $value; }
function get_post_type( $id ) { return $id === 214 ? 'post' : 'rev_candidate'; }
function get_post_meta( $id, $key, $single = true ) { return $key === '_revelations_editorial_candidate_id' ? 99 : 'present'; }
function get_post( $id ) { return $id === 214 ? new WP_Post() : null; }
function wp_get_post_categories() { return array( 1 ); }
function wp_get_post_tags() { return array(); }
function get_post_thumbnail_id() { return 1; }
function wp_attachment_is_image() { return true; }
function sanitize_text_field( $v ) { return (string)$v; } function sanitize_textarea_field( $v ) { return (string)$v; }
function get_post_field( $field ) { return $field === 'post_excerpt' ? 'Excerpt' : ''; } function wp_strip_all_tags( $v ) { return $v; }
function get_post_status() { return 'draft'; } function get_current_user_id() { return 0; } function set_transient() {} function delete_post_meta() {}
function revelations_editorial_category_contract_validate( $ids ) { return array('code' => ''); } function revelations_editorial_category_contract_message( $code ) { return $code; }
function revelations_editorial_review_status( $id ) { return array('is_current'=>true,'status'=>'reviewed','field_hashes'=>array('content'=>'x')); }
require __DIR__ . '/../mu-plugins/revelations-editorial-publish-gate.php';
function check( bool $ok, string $label ): void { static $n=0; $n++; if (!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); } echo "PASS: $label\n"; }
global $revelations_test_filters, $revelations_test_rest;
$fallback = $revelations_test_filters['wp_insert_post_data'][0];
$data = array('post_status'=>'publish','post_title'=>'Title','post_content'=>wp_slash('Quoted "content"'),'post_excerpt'=>'Excerpt');
$revelations_test_rest = true; check( $fallback($data, array('ID'=>214,'post_category'=>array(1))) === $data, 'REST request bypasses classic fallback exactly once' );
$prepared = (object) array('post_status' => 'publish');
$request = new WP_REST_Request(array('id'=>214, 'title'=>'Title', 'content'=>'Quoted "content"', 'excerpt'=>'Excerpt', 'categories'=>array(1), 'tags'=>array(), 'meta'=>array()));
foreach ($revelations_test_filters['rest_pre_insert_post'] as $callback) $prepared = $callback($prepared, $request);
check( !is_wp_error($prepared), 'REST gate accepts the unchanged proposal without a second fallback decision' );
$revelations_test_rest = false; check( revelations_editorial_publish_gate_unslashed_text($data['post_content']) === 'Quoted "content"', 'slashed Gutenberg content normalizes to saved content' );
$exact_reason = revelations_editorial_publish_gate_reason(214, array('title'=>'Title','content'=>'Quoted "content"','excerpt'=>'Excerpt','categories'=>array(1)));
check( '' === $exact_reason, 'exact classic proposal passes: ' . $exact_reason );
check( '' !== revelations_editorial_publish_gate_reason(214, array('title'=>'Title','content'=>'Changed','excerpt'=>'Excerpt','categories'=>array(1))), 'real content change remains blocked' );
check( $fallback($data, array('ID'=>214,'post_category'=>array(1))) === $data, 'classic and WP-CLI fallback permits normalized unchanged proposal' );
echo "Publication REST gate diagnostics: 6 passed, 0 failed, 6 total.\n";

<?php
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function add_action() {} function add_filter() {} function add_post_type_support() {}
function register_post_meta() {} function get_post_meta( $id, $key ) { return $GLOBALS['meta'][ $key ] ?? ''; }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function esc_url_raw( $value, $protocols = array() ) { $value = trim( (string) $value ); return preg_match( '#^https?://#i', $value ) ? $value : ''; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
$GLOBALS['meta'] = array();
require __DIR__ . '/../mu-plugins/revelations-cms-core.php';
function check( bool $ok, string $label ): void { if ( ! $ok ) { fwrite( STDERR, "FAIL: $label\n" ); exit( 1 ); } echo "PASS: $label\n"; }
check( revelations_enrichment_sanitize_revelation( '<b>Readable</b> punctuation.' ) === 'Readable punctuation.', 'revelation is plain text' );
check( revelations_enrichment_word_count( str_repeat( 'word ', 180 ) ) === 180, 'word limit counts deterministically' );
check( revelations_enrichment_word_count( str_repeat( 'word ', 181 ) ) === 181, 'over-limit content is detectable without truncation' );
$valid = revelations_enrichment_sanitize_public_sources( '[{"label":"One","url":"https://example.com/1"},{"label":"Two","url":"https://example.com/2"}]' );
check( $valid === '[{"label":"One","url":"https://example.com/1"},{"label":"Two","url":"https://example.com/2"}]', 'public source ordering is preserved' );
check( revelations_enrichment_sanitize_public_sources( '[{"label":"Unsafe","url":"javascript:alert(1)"}]' ) === '[]', 'unsafe source URL is rejected' );
check( revelations_enrichment_sanitize_public_sources( '{bad json' ) === '[]', 'malformed sources fail safely' );
$GLOBALS['meta']['revelations_public_sources'] = $valid;
$GLOBALS['meta']['_rev_source_text'] = 'PRIVATE SOURCE TEXT';
check( revelations_enrichment_public_sources( 1 )[0]['label'] === 'One', 'only approved source field is read' );
echo "Editorial enrichment diagnostics: 7 passed, 0 failed, 7 total.\n";

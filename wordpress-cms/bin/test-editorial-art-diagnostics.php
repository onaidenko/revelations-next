<?php
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function sanitize_text_field( mixed $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( mixed $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ?? ''; }
function absint( mixed $value ): int { return abs( (int) $value ); }
function esc_url_raw( mixed $value ): string { return (string) $value; }
function add_action( string $hook, callable $callback ): void {}
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-logs.php';
$safe = revelations_editorial_sanitize_scan_diagnostics( array(
    'rejection_counts' => array( 'art_relevance_gate' => array( 'generic_event_listing' => 2 ) ),
    'closest_rejected' => array( array( 'title' => 'Calendar', 'rejection_code' => 'generic_event_listing', 'gate_branch' => 'art' ) ),
    'fetched_items' => array(
        array( 'source' => 'Art Feed', 'title' => 'Calendar', 'canonical_url' => 'https://example.test/a', 'stage' => 'relevance_rejected', 'relevance_scope' => 'art_relevance_gate', 'relevance_rejection_code' => 'generic_event_listing' ),
        array( 'source' => 'News Feed', 'title' => 'News', 'canonical_url' => 'https://example.test/n', 'stage' => 'global_rejected', 'global_rejection_code' => 'insufficient_ai_context' ),
    ),
) );
$ok = 2 === ( $safe['rejection_counts']['art_relevance_gate']['generic_event_listing'] ?? 0 )
    && 'generic_event_listing' === ( $safe['closest_rejected'][0]['rejection_code'] ?? '' )
    && 'art_relevance_gate' === ( $safe['fetched_items'][0]['relevance_scope'] ?? '' )
    && 'generic_event_listing' === ( $safe['fetched_items'][0]['relevance_rejection_code'] ?? '' )
    && ! array_key_exists( 'relevance_scope', $safe['fetched_items'][1] ?? array() )
    && false !== json_encode( $safe );
echo $ok ? "Art diagnostics: passed\n" : "Art diagnostics: failed\n";
exit( $ok ? 0 : 1 );

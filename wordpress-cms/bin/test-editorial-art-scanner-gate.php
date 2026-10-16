<?php
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
function sanitize_key( mixed $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ?? ''; }
function sanitize_text_field( mixed $value ): string { return trim( strip_tags( (string) $value ) ); }
function absint( mixed $value ): int { return abs( (int) $value ); }
function add_action( string $hook, callable $callback ): void {}
function get_option( string $key, mixed $default = false ): mixed { return $default; }
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-scanner-settings.php';
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-scanner-engine.php';
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-art-scanner.php';
$cases = array(
    array( 'Artist Maya Lin unveils a new public installation', 'The artist presents a new project for a museum.', true ),
    array( 'Museum acquires a contemporary painting', 'The acquisition enters the permanent collection.', true ),
    array( 'What to see this week', 'Event calendar, tickets and opening hours.', false ),
    array( 'Celebrity visits gallery', 'A red-carpet lifestyle story.', false ),
    array( 'New image model launches', 'An AI image generator product update.', false ),
    array( 'Interior design awards', 'Furniture and home decor trends.', false ),
);
$ok = true;
foreach ( $cases as list( $title, $summary, $expected ) ) {
    $result = revelations_editorial_scanner_apply_relevance_gate( array( 'title' => $title, 'summary' => $summary ), 'art', 'revelations_editorial_art_relevance_gate' );
    $ok = $ok && $expected === $result['qualified'];
}
echo $ok ? "Art scanner gate: passed\n" : "Art scanner gate: failed\n";
exit( $ok ? 0 : 1 );

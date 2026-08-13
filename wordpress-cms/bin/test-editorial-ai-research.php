<?php
/* Focused, network-free regression diagnostic for the evidence-first research layer. */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function esc_url_raw( string $value ): string { return $value; }
function sanitize_text_field( string $value ): string { return trim( $value ); }
function wp_parse_url( string $url, int $component = -1 ) { return parse_url( $url, $component ); }

require dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-ai-research.php';

$book = revelations_editorial_ai_classify_lead_source( 'https://www.wired.com/story/example/', 'WIRED', 'Excerpted from The Vanishing Earth. Copyright 2026.' );
$government = revelations_editorial_ai_classify_lead_source( 'https://www.nist.gov/news/example', 'NIST', 'Official update.' );
if ( 'book_excerpt' !== $book['source_type'] || 'excerpt_licensed_syndicated' !== $book['access'] || empty( $book['requires_independent_corroboration'] ) ) { fwrite( STDERR, "Book-excerpt routing regression.\n" ); exit( 1 ); }
if ( 'official_government' !== $government['source_type'] || 'primary_authoritative' !== $government['reliability'] ) { fwrite( STDERR, "Official-source routing regression.\n" ); exit( 1 ); }
$schema = revelations_editorial_ai_research_schema();
if ( ! in_array( 'book_excerpt', revelations_editorial_ai_research_allowed_source_types(), true ) || ! isset( $schema['properties']['sources'] ) ) { fwrite( STDERR, "Research schema regression.\n" ); exit( 1 ); }
$secondary = array( 'host' => 'secondary.example', 'role' => 'secondary' ); $other = array( 'host' => 'other.example', 'role' => 'secondary' ); $primary = array( 'host' => 'paper.example', 'role' => 'primary' );
$reject = revelations_editorial_ai_evaluate_pillar_dominance( array( array( 'importance' => 'central', 'sources' => array( $secondary ) ), array( 'importance' => 'supporting', 'sources' => array( $secondary ) ), array( 'importance' => 'supporting', 'sources' => array( $secondary ) ), array( 'importance' => 'supporting', 'sources' => array( $secondary ) ), array( 'importance' => 'supporting', 'sources' => array( $other ) ) ) );
if ( empty( $reject['hard_failure'] ) || 'insufficient_independent_evidence' !== $reject['status'] ) { fwrite( STDERR, "Secondary-backbone rejection regression.\n" ); exit( 1 ); }
$primary_allowed = revelations_editorial_ai_evaluate_pillar_dominance( array( array( 'importance' => 'central', 'sources' => array( $primary ) ), array( 'importance' => 'supporting', 'sources' => array( $primary ) ), array( 'importance' => 'supporting', 'sources' => array( $primary ) ), array( 'importance' => 'supporting', 'sources' => array( $primary ) ), array( 'importance' => 'supporting', 'sources' => array( $other ) ) ) );
if ( ! empty( $primary_allowed['hard_failure'] ) || empty( $primary_allowed['primary_source_dominance_note'] ) ) { fwrite( STDERR, "Primary-dominance allowance regression.\n" ); exit( 1 ); }
$distributed = revelations_editorial_ai_evaluate_pillar_dominance( array( array( 'importance' => 'central', 'sources' => array( $secondary, $other ) ), array( 'importance' => 'supporting', 'sources' => array( $primary ) ), array( 'importance' => 'supporting', 'sources' => array( $other ) ), array( 'importance' => 'supporting', 'sources' => array( $secondary ) ), array( 'importance' => 'supporting', 'sources' => array( $primary ) ) ) );
if ( ! empty( $distributed['hard_failure'] ) ) { fwrite( STDERR, "Distributed-support allowance regression.\n" ); exit( 1 ); }
echo "Editorial AI research diagnostics passed.\n";

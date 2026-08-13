<?php
/* Focused, network-free regression diagnostic for the evidence-first research layer. */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function esc_url_raw( string $value ): string { return $value; }
function sanitize_text_field( string $value ): string { return trim( $value ); }
function sanitize_key( string $value ): string { return strtolower( preg_replace( '/[^a-z0-9_]/', '', $value ) ?? '' ); }
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
$citation_url = 'https://example.org/report?utm_source=newsletter';
$payload_both = array( 'output' => array( array( 'type' => 'web_search_call', 'status' => 'completed', 'action' => array( 'type' => 'search', 'query' => 'neurotechnology', 'sources' => array( array( 'url' => 'https://primary.example.org/report' ) ) ) ), array( 'type' => 'message', 'role' => 'assistant', 'content' => array( array( 'type' => 'output_text', 'annotations' => array( array( 'type' => 'url_citation', 'url' => $citation_url ) ) ) ) ) ) );
$both = revelations_editorial_ai_research_web_provenance( $payload_both );
if ( 1 !== $both['web_search_call_count'] || 2 !== $both['unique_url_count'] || 1 !== $both['citation_count'] || 1 !== $both['action_source_url_count'] ) { fwrite( STDERR, "Combined web provenance regression.\n" ); exit( 1 ); }
$annotation_only = revelations_editorial_ai_research_web_provenance( array( 'output' => array( array( 'type' => 'web_search_call', 'status' => 'completed', 'action' => array( 'type' => 'search' ) ), array( 'type' => 'message', 'role' => 'assistant', 'content' => array( array( 'annotations' => array( array( 'type' => 'url_citation', 'url' => 'https://citation.example.org/a' ) ) ) ) ) ) ) );
if ( 1 !== $annotation_only['unique_url_count'] ) { fwrite( STDERR, "Annotation-only provenance regression.\n" ); exit( 1 ); }
$action_only = revelations_editorial_ai_research_web_provenance( array( 'output' => array( array( 'type' => 'web_search_call', 'status' => 'completed', 'action' => array( 'type' => 'search', 'sources' => array( array( 'url' => 'https://action.example.org/a' ) ) ) ) ) ) );
if ( 1 !== $action_only['unique_url_count'] ) { fwrite( STDERR, "Action-source provenance regression.\n" ); exit( 1 ); }
if ( revelations_editorial_ai_research_url_key( $citation_url ) !== revelations_editorial_ai_research_url_key( 'https://example.org/report/' ) ) { fwrite( STDERR, "Canonical URL normalization regression.\n" ); exit( 1 ); }
if ( isset( $both['urls'][ revelations_editorial_ai_research_url_key( 'https://invented.example.org/nope' ) ] ) ) { fwrite( STDERR, "Invented URL provenance regression.\n" ); exit( 1 ); }
$no_tool = revelations_editorial_ai_research_web_provenance( array( 'output' => array( array( 'type' => 'message', 'role' => 'assistant', 'content' => array() ) ) ) );
if ( 0 !== $no_tool['web_search_call_count'] ) { fwrite( STDERR, "Tool-not-used regression.\n" ); exit( 1 ); }
if ( revelations_editorial_ai_research_is_independent_url( 'https://www.wired.com/story/x', 'www.wired.com' ) ) { fwrite( STDERR, "Lead-host exclusion regression.\n" ); exit( 1 ); }
$usage = revelations_editorial_ai_used_evidence_sources(
    array( array( 'evidence_ids' => array( 'p001', 'p003' ) ) ),
    array( array( 'evidence_ids' => array( 'p003' ) ) ),
    array( 'p001' => array( 'url' => 'https://gao.gov/report', 'host' => 'gao.gov' ), 'p002' => array( 'url' => 'https://columbia.edu/yuste', 'host' => 'columbia.edu' ), 'p003' => array( 'url' => 'https://nih.gov/study', 'host' => 'nih.gov' ) ),
    array( array( 'url' => 'https://gao.gov/report', 'name' => 'U.S. Government Accountability Office', 'reliability' => 'primary_authoritative', 'source_type' => 'official_government' ), array( 'url' => 'https://columbia.edu/yuste', 'name' => 'Rafael Yuste - Columbia University NeuroTechnology Center', 'reliability' => 'primary_authoritative', 'source_type' => 'official_government' ), array( 'url' => 'https://nih.gov/study', 'name' => 'National Institutes of Health', 'reliability' => 'primary_authoritative', 'source_type' => 'official_government' ) )
);
if ( 2 !== $usage['public_source_count'] || 1 !== $usage['unused_research_source_count'] || 2 !== $usage['primary_used_count'] ) { fwrite( STDERR, "Used-source selection regression.\n" ); exit( 1 ); }
echo "Editorial AI research diagnostics passed.\n";

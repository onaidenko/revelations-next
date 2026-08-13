<?php
/* Focused, network-free regression diagnostic for the evidence-first research layer. */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
class WP_Error { public function __construct( public string $code = '', public string $message = '', public array $data = array() ) {} }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
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
$sources = array(); for ( $index = 1; $index <= 5; ++$index ) $sources[] = array( 'url' => 'https://source' . $index . '.example/report', 'name' => 'Source ' . $index, 'host' => 'source' . $index . '.example', 'source_type' => 1 === $index ? 'research_paper' : 'reported_news', 'reliability' => 1 === $index ? 'primary_authoritative' : 'major_editorial', 'publication_date' => '2026-08-13' );
$registry_data = revelations_editorial_ai_research_source_registry( $sources ); $units = array(); $provenance = array();
for ( $index = 1; $index <= 25; ++$index ) { $source_index = ( ( $index - 1 ) % 5 ) + 1; $id = sprintf( 'p%03d', $index ); $source_url = $sources[ $source_index - 1 ]['url']; $source_id = $registry_data['source_ids'][ revelations_editorial_ai_research_url_key( $source_url ) ]; $support = 25 === $index ? 'Neural data applies only when it can be processed with device assistance.' : ( 5 === $index ? 'The finding may apply under the studied conditions.' : 'qualifying context.' ); $units[] = array( 'id' => $id, 'text' => '[' . $source_id . '] Claim: evidence ' . $index . "\nSupport: " . $support ); $provenance[ $id ] = array( 'url' => $source_url, 'host' => $sources[ $source_index - 1 ]['host'], 'role' => 1 === $source_index ? 'primary' : 'secondary', 'source_id' => $source_id ); }
$brief = array( 'factual_pillars' => array( array( 'evidence_ids' => array( 'p001', 'p002' ) ), array( 'evidence_ids' => array( 'p003' ) ), array( 'evidence_ids' => array( 'p004' ) ) ), 'sensitive_evidence_ids' => array( 'p025' ), 'attribution_evidence_ids' => array( 'p005' ), 'essential_context_evidence_ids' => array(), 'pillar_support' => array( array( 'importance' => 'central', 'sources' => array( $provenance['p001'], $provenance['p002'] ) ), array( 'importance' => 'supporting', 'sources' => array( $provenance['p003'] ) ), array( 'importance' => 'supporting', 'sources' => array( $provenance['p004'] ) ) ) );
$final_pack = revelations_editorial_ai_final_evidence_pack( array( 'evidence_units' => $units, 'source_registry' => $registry_data['registry'], 'provenance' => $provenance, 'lead_classification' => array( 'requires_independent_corroboration' => false ) ), $brief );
if ( is_wp_error( $final_pack ) || 6 !== $final_pack['evidence_count'] || false === strpos( $final_pack['evidence_text'], '[p025]' ) || false === strpos( $final_pack['evidence_text'], 'SOURCE REGISTRY' ) ) { fwrite( STDERR, "Final evidence selection regression.\n" ); exit( 1 ); }
if ( 1 !== substr_count( $final_pack['evidence_text'], 'URL: https://source1.example/report' ) || 5 !== count( $final_pack['source_registry'] ) ) { fwrite( STDERR, "Source registry compaction regression.\n" ); exit( 1 ); }
if ( false === strpos( $final_pack['evidence_text'], 'only when it can be processed with device assistance' ) || false === strpos( $final_pack['evidence_text'], 'may apply under the studied conditions' ) ) { fwrite( STDERR, "Formal qualifier preservation regression.\n" ); exit( 1 ); }
$final_usage = revelations_editorial_ai_used_evidence_sources( array( array( 'evidence_ids' => array( 'p001', 'p025' ) ) ), array(), $provenance, $sources );
if ( 2 !== $final_usage['public_source_count'] || ! in_array( 'p025', $final_usage['used_evidence_ids'], true ) ) { fwrite( STDERR, "Registry provenance public-source regression.\n" ); exit( 1 ); }
$formal_rule = revelations_editorial_ai_formal_definition_safeguard();
if ( false === strpos( $formal_rule, 'category boundary' ) || false === strpos( $formal_rule, 'uncertainty level' ) || false !== stripos( $formal_rule, 'Colorado' ) ) { fwrite( STDERR, "Formal-definition safeguard regression.\n" ); exit( 1 ); }
echo "Editorial AI research diagnostics passed.\n";

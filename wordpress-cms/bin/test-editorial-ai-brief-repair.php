<?php
/**
 * Network-free regressions for the bounded editorial-brief dominance repair.
 * The fake transport returns predeclared briefs only; it never calls OpenAI.
 */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

class WP_Error {
    public function __construct(
        private string $code = '',
        private string $message = '',
        private array $data = array()
    ) {
    }

    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data( string $code = '' ): array { return $this->data; }
}

function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
function absint( mixed $value ): int { return abs( (int) $value ); }
function esc_url_raw( string $value ): string { return $value; }
function sanitize_text_field( string $value ): string { return trim( $value ); }
function sanitize_textarea_field( string $value ): string { return trim( $value ); }
function sanitize_key( string $value ): string { return strtolower( preg_replace( '/[^a-z0-9_]/', '', $value ) ?? '' ); }
function wp_parse_url( string $url, int $component = -1 ) { return parse_url( $url, $component ); }
function wp_json_encode( mixed $value, int $flags = 0 ): string|false { return json_encode( $value, $flags ); }
function revelations_editorial_ai_generation_profile_prompt( string $section, array $profile ): string { return 'Profile: ' . $section; }
function revelations_editorial_ai_generation_extract_text( array $response ): string { return (string) ( $response['test_output'] ?? '' ); }

$revelations_brief_repair_transport = array();
$revelations_brief_repair_transport_calls = 0;
$revelations_brief_repair_transport_requests = array();

function wp_remote_post( string $url, array $arguments ): array {
    global $revelations_brief_repair_transport;
    global $revelations_brief_repair_transport_calls;
    global $revelations_brief_repair_transport_requests;

    ++$revelations_brief_repair_transport_calls;
    $revelations_brief_repair_transport_requests[] = json_decode(
        (string) ( $arguments['body'] ?? '' ),
        true
    );
    $brief = array_shift( $revelations_brief_repair_transport );
    if ( ! is_array( $brief ) ) {
        return array( 'status' => 500, 'body' => '{}' );
    }

    return array(
        'status' => 200,
        'body' => json_encode(
            array(
                'status' => 'completed',
                'usage' => array(
                    'input_tokens' => 10,
                    'output_tokens' => 20,
                    'total_tokens' => 30,
                ),
                'test_output' => json_encode( $brief ),
            )
        ),
    );
}

function wp_remote_retrieve_response_code( array $response ): int { return (int) ( $response['status'] ?? 0 ); }
function wp_remote_retrieve_body( array $response ): string { return (string) ( $response['body'] ?? '' ); }

require dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-ai-research.php';

function revelations_brief_repair_assert( bool $condition, string $message ): void {
    if ( ! $condition ) {
        fwrite( STDERR, $message . "\n" );
        exit( 1 );
    }
}

/** @return array<string, mixed> */
function revelations_brief_repair_research( bool $primary_matches = true ): array {
    $sources = array(
        array( 'url' => 'https://official.example/matching', 'name' => 'Official matching record', 'host' => 'official.example', 'source_type' => 'official_government', 'reliability' => 'primary_authoritative', 'publication_date' => '2026-10-01' ),
        array( 'url' => 'https://reporting.example/story', 'name' => 'Independent reporting', 'host' => 'reporting.example', 'source_type' => 'reported_news', 'reliability' => 'major_editorial', 'publication_date' => '2026-10-01' ),
        array( 'url' => 'https://primary-b.example/record', 'name' => 'Primary record', 'host' => 'primary-b.example', 'source_type' => 'official_government', 'reliability' => 'primary_authoritative', 'publication_date' => '2026-10-01' ),
    );
    $registry_data = revelations_editorial_ai_research_source_registry( $sources );
    $units = array(
        array( 'id' => 'p001', 'text' => $primary_matches ? '[s001] Claim: Official record confirms the project policy change.\nSupport: The same policy change is recorded by the responsible agency.' : '[s001] Claim: Official record confirms an unrelated environmental filing.\nSupport: The filing does not describe the company policy change.' ),
        array( 'id' => 'p002', 'text' => '[s002] Claim: Reporting describes the company policy change.\nSupport: The report attributes the change to a named filing.' ),
        array( 'id' => 'p003', 'text' => '[s003] Claim: Primary record confirms the separate implementation date.\nSupport: The record documents when implementation began.' ),
    );
    $provenance = array(
        'p001' => array( 'source_id' => 's001', 'host' => 'official.example', 'role' => 'primary', 'url' => $sources[0]['url'] ),
        'p002' => array( 'source_id' => 's002', 'host' => 'reporting.example', 'role' => 'secondary', 'url' => $sources[1]['url'] ),
        'p003' => array( 'source_id' => 's003', 'host' => 'primary-b.example', 'role' => 'primary', 'url' => $sources[2]['url'] ),
    );

    return array(
        'sources' => $sources,
        'source_registry' => $registry_data['registry'],
        'evidence_units' => $units,
        'evidence_text' => revelations_editorial_ai_research_format_evidence_pack( $registry_data['registry'], $units ),
        'provenance' => $provenance,
        'evidence_count' => count( $units ),
        'primary_count' => 2,
        'lead_classification' => array( 'requires_independent_corroboration' => false ),
        'usage' => array( 'input_tokens' => 1, 'output_tokens' => 2, 'total_tokens' => 3 ),
        'duration_ms' => 1,
        'response_status' => 'completed',
    );
}

/** @return array<string, mixed> */
function revelations_brief_repair_brief( array $pillars ): array {
    return array(
        'what_happened' => 'A documented policy event occurred.',
        'why_revelations_cares' => 'It has accountable technical consequences.',
        'thesis' => 'Evidence determines the brief.',
        'factual_pillars' => $pillars,
        'confirmed' => array(),
        'attributed' => array(),
        'interpretation' => array(),
        'do_not_claim' => array(),
        'pillar_order' => array( 0, 1, 2 ),
        'sensitive_evidence_ids' => array(),
        'attribution_evidence_ids' => array(),
        'essential_context_evidence_ids' => array(),
    );
}

/** @return array<int, array<string, mixed>> */
function revelations_brief_repair_initial_pillars(): array {
    return array(
        array( 'pillar' => 'Company policy change', 'importance' => 'central', 'evidence_ids' => array( 'p002' ) ),
        array( 'pillar' => 'Official policy context', 'importance' => 'supporting', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
    );
}

function revelations_brief_repair_run( array $responses, bool $primary_matches = true ) {
    global $revelations_brief_repair_transport;
    global $revelations_brief_repair_transport_calls;
    global $revelations_brief_repair_transport_requests;

    $revelations_brief_repair_transport = $responses;
    $revelations_brief_repair_transport_calls = 0;
    $revelations_brief_repair_transport_requests = array();

    return revelations_editorial_ai_editorial_brief(
        array( 'model' => 'test-model', 'api_key' => 'test-key' ),
        'news',
        array(),
        array( 'editorial_policy' => 'Test policy.' ),
        revelations_brief_repair_research( $primary_matches )
    );
}

// 1. A matching primary claim can be attached by the one repair and pass.
$initial = revelations_brief_repair_brief( revelations_brief_repair_initial_pillars() );
$matching_repair = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Company policy change', 'importance' => 'central', 'evidence_ids' => array( 'p001', 'p002' ) ),
        array( 'pillar' => 'Official policy context', 'importance' => 'supporting', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
    )
);
$result = revelations_brief_repair_run( array( $initial, $matching_repair ) );
revelations_brief_repair_assert( ! is_wp_error( $result ), 'Matching primary repair should pass.' );
revelations_brief_repair_assert( 2 === $revelations_brief_repair_transport_calls, 'Matching primary repair should make exactly two brief requests.' );
revelations_brief_repair_assert( ! empty( $result['repair_diagnostics']['brief_repair_attempted'] ) && 'secondary_source_sole_central_pillar' === $result['repair_diagnostics']['initial_dominance_failure_code'] && 'passed' === $result['repair_diagnostics']['repaired_validation_result'], 'Matching primary repair diagnostics regression.' );
revelations_brief_repair_assert( empty( $revelations_brief_repair_transport_requests[1]['tools'] ) && false === ( $revelations_brief_repair_transport_requests[1]['store'] ?? true ), 'Brief repair must remain an in-memory, no-tool request.' );

// 2. The existing majority-secondary failure gets the same one bounded repair.
$majority_initial = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Official policy context', 'importance' => 'central', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Reported policy context', 'importance' => 'supporting', 'evidence_ids' => array( 'p002' ) ),
        array( 'pillar' => 'Reported implementation context', 'importance' => 'supporting', 'evidence_ids' => array( 'p002' ) ),
    )
);
$majority_repair = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Official policy context', 'importance' => 'central', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Reported policy context', 'importance' => 'supporting', 'evidence_ids' => array( 'p002', 'p003' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
    )
);
$result = revelations_brief_repair_run( array( $majority_initial, $majority_repair ) );
revelations_brief_repair_assert( ! is_wp_error( $result ) && 'secondary_source_sole_majority_pillars' === ( $result['repair_diagnostics']['initial_dominance_failure_code'] ?? '' ), 'Majority-secondary dominance should trigger one repair.' );

// 3. An unrelated primary is not attached to the secondary-only claim; it is removed.
$unrelated_repair = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Official environmental filing', 'importance' => 'central', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
        array( 'pillar' => 'Reported company account', 'importance' => 'supporting', 'evidence_ids' => array( 'p002' ) ),
    )
);
$result = revelations_brief_repair_run( array( $initial, $unrelated_repair ), false );
revelations_brief_repair_assert( ! is_wp_error( $result ), 'Unrelated-primary repair should safely remove the unsupported central claim.' );
$company_pillar = array_values( array_filter( $result['brief']['factual_pillars'], static fn( array $pillar ): bool => 'Reported company account' === $pillar['pillar'] ) );
revelations_brief_repair_assert( 1 === count( $company_pillar ) && array( 'p002' ) === $company_pillar[0]['evidence_ids'], 'Unrelated primary must not be attached to a secondary-only claim.' );

// 4. A secondary-only central claim can be removed safely when no repairable support is used.
$secondary_removed = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Official environmental filing', 'importance' => 'central', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
        array( 'pillar' => 'Independent reported context', 'importance' => 'supporting', 'evidence_ids' => array( 'p002', 'p003' ) ),
    )
);
$result = revelations_brief_repair_run( array( $initial, $secondary_removed ), false );
revelations_brief_repair_assert( ! is_wp_error( $result ), 'Secondary-only central claim should be removable safely.' );
revelations_brief_repair_assert( 'Official environmental filing' === $result['brief']['factual_pillars'][0]['pillar'], 'Secondary-only central claim was not removed.' );

// 5. A still-invalid repair stops after the single permitted repair attempt.
$still_invalid = revelations_brief_repair_brief( revelations_brief_repair_initial_pillars() );
$result = revelations_brief_repair_run( array( $initial, $still_invalid ) );
revelations_brief_repair_assert( is_wp_error( $result ) && 'insufficient_independent_evidence' === $result->get_error_code(), 'Still-invalid repair must preserve the dominance failure.' );
revelations_brief_repair_assert( 2 === $revelations_brief_repair_transport_calls, 'Still-invalid repair must stop after one repair attempt.' );
$diagnostics = $result->get_error_data()['generation_diagnostics'] ?? array();
revelations_brief_repair_assert( ! empty( $diagnostics['brief_repair_attempted'] ) && 'failed' === ( $diagnostics['repaired_validation_result'] ?? '' ), 'Still-invalid repair diagnostics regression.' );
revelations_brief_repair_assert( false === strpos( (string) json_encode( $diagnostics ), 'Company policy change' ), 'Repair failure diagnostics must not persist private pillar text.' );

// 6. A non-dominance validation error never invokes repair.
$non_dominance = revelations_brief_repair_brief(
    array(
        array( 'pillar' => 'Unknown evidence', 'importance' => 'central', 'evidence_ids' => array( 'p999' ) ),
        array( 'pillar' => 'Official policy context', 'importance' => 'supporting', 'evidence_ids' => array( 'p001' ) ),
        array( 'pillar' => 'Implementation date', 'importance' => 'supporting', 'evidence_ids' => array( 'p003' ) ),
    )
);
$result = revelations_brief_repair_run( array( $non_dominance ) );
revelations_brief_repair_assert( is_wp_error( $result ) && 'brief_failed' === $result->get_error_code(), 'Non-dominance brief error should remain a normal validation failure.' );
revelations_brief_repair_assert( 1 === $revelations_brief_repair_transport_calls, 'Non-dominance failure must not invoke repair.' );
$diagnostics = $result->get_error_data()['generation_diagnostics'] ?? array();
revelations_brief_repair_assert( empty( $diagnostics['brief_repair_attempted'] ), 'Non-dominance failure incorrectly recorded a repair attempt.' );

echo "Editorial AI brief repair regressions passed.\n";

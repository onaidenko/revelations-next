<?php
/**
 * Isolated diagnostics for the AI editorial review metadata panel.
 *
 * This script does not load WordPress, call an API or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$test_meta = array();

/**
 * WordPress stubs used only by the isolated renderer.
 */
function add_action( mixed ...$arguments ): void {
}

function current_user_can( string $capability ): bool {
    return 'manage_options' === $capability;
}

function get_post_meta(
    int $post_id,
    string $key,
    bool $single
): mixed {
    global $test_meta;

    return $test_meta[ $key ] ?? '';
}

function esc_html( mixed $value ): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

$panel_file =
    $plugin_dir .
    '/revelations-editorial-ai-review-metadata.php';

$drafts_file =
    $plugin_dir .
    '/revelations-editorial-drafts.php';

$review_file =
    $plugin_dir .
    '/revelations-editorial-review.php';

require_once $panel_file;

$panel_source = file_get_contents(
    $panel_file
);

$drafts_source = file_get_contents(
    $drafts_file
);

$review_source = file_get_contents(
    $review_file
);

if (
    false === $panel_source ||
    false === $drafts_source ||
    false === $review_source
) {
    fwrite(
        STDERR,
        "FAIL: unable to read review metadata sources\n"
    );
    exit( 1 );
}

$passed = 0;
$failed = 0;

/**
 * Record one diagnostic result.
 */
function revelations_review_metadata_test(
    bool $condition,
    string $message
): void {
    global $passed, $failed;

    if ( $condition ) {
        $passed++;
        echo 'PASS: ' . $message . "\n";
        return;
    }

    $failed++;
    echo 'FAIL: ' . $message . "\n";
}

/**
 * Return complete current metadata in raw WordPress-meta form.
 *
 * @return array<string, mixed>
 */
function revelations_review_metadata_raw(): array {
    return array(
        '_revelations_ai_source_section' =>
            'news',

        '_revelations_ai_alternative_titles' =>
            json_encode(
                array(
                    'Alternative title one',
                    'Alternative title two',
                ),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),

        '_revelations_ai_section_mismatch' =>
            '1',

        '_revelations_ai_suggested_section' =>
            'tech',

        '_revelations_ai_section_mismatch_reason' =>
            'The article centers on a technical system.',

        '_revelations_ai_fact_check_flags' =>
            json_encode(
                array(
                    array(
                        'claim' =>
                            'The system reached 95% accuracy.',
                        'claim_type' =>
                            'benchmark',
                        'source_evidence' =>
                            'The source reported 95% accuracy.',
                        'evidence_ids' =>
                            array( 'p004' ),
                        'verification_required' =>
                            true,
                        'reason' =>
                            'The benchmark requires editorial review.',
                    ),
                ),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),

        '_revelations_ai_direct_quotes' =>
            json_encode(
                array(
                    array(
                        'quote_text' =>
                            'AI is changing how the team works.',
                        'source_fragment' =>
                            'The founder said AI is changing how the team works.',
                        'evidence_id' =>
                            'p006',
                        'verbatim_match' =>
                            true,
                    ),
                ),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
    );
}

/**
 * Render the current panel with supplied raw metadata.
 *
 * @param array<string, mixed> $raw Raw metadata.
 */
function revelations_review_metadata_render(
    array $raw
): string {
    global $test_meta;

    $test_meta = $raw;

    ob_start();
    revelations_editorial_render_ai_review_metadata_action(
        100
    );

    return (string) ob_get_clean();
}

$decoded =
    revelations_editorial_ai_review_decode_json_list(
        '[{"claim":"Example"}]'
    );

revelations_review_metadata_test(
    isset( $decoded[0]['claim'] ) &&
    'Example' === $decoded[0]['claim'],
    'valid metadata JSON decodes safely'
);

revelations_review_metadata_test(
    array() ===
        revelations_editorial_ai_review_decode_json_list(
            '{"broken":'
        ),
    'invalid JSON returns an empty list'
);

revelations_review_metadata_test(
    array() ===
        revelations_editorial_ai_review_decode_json_list(
            ''
        ),
    'empty JSON metadata returns an empty list'
);

$legacy =
    revelations_editorial_ai_review_normalize_metadata(
        array()
    );

revelations_review_metadata_test(
    ! revelations_editorial_ai_review_has_metadata(
        $legacy
    ),
    'legacy draft without metadata remains supported'
);

$legacy_html =
    revelations_review_metadata_render(
        array()
    );

revelations_review_metadata_test(
    str_contains(
        $legacy_html,
        'No AI review metadata available'
    ),
    'legacy draft renders a neutral empty state'
);

$metadata =
    revelations_editorial_ai_review_normalize_metadata(
        revelations_review_metadata_raw()
    );

revelations_review_metadata_test(
    'news' === $metadata['source_section'] &&
    2 === count(
        $metadata['alternative_titles']
    ) &&
    1 === count(
        $metadata['fact_check_flags']
    ) &&
    1 === count(
        $metadata['direct_quotes']
    ),
    'complete current metadata normalizes successfully'
);

$html =
    revelations_review_metadata_render(
        revelations_review_metadata_raw()
    );

revelations_review_metadata_test(
    str_contains(
        $html,
        'Alternative title one'
    ) &&
    str_contains(
        $html,
        'Alternative title two'
    ),
    'alternative titles render'
);

revelations_review_metadata_test(
    str_contains(
        $html,
        'Immutable source section'
    ) &&
    str_contains(
        $html,
        'Suggested section'
    ) &&
    str_contains(
        $html,
        'editorial advice only'
    ) &&
    str_contains(
        $html,
        'does not change the WordPress category'
    ),
    'advisory section metadata renders without category authority'
);

revelations_review_metadata_test(
    str_contains(
        $html,
        'The system reached 95% accuracy.'
    ) &&
    str_contains(
        $html,
        'Found in source'
    ) &&
    str_contains(
        $html,
        'Requires manual verification'
    ),
    'fact-check flag semantics render'
);

revelations_review_metadata_test(
    str_contains(
        $html,
        'AI is changing how the team works.'
    ) &&
    str_contains(
        $html,
        'Exact source match'
    ),
    'validated direct quote evidence renders'
);

revelations_review_metadata_test(
    ! str_contains(
        $html,
        'Verified true'
    ) &&
    ! str_contains(
        $html,
        'Fact checked'
    ),
    'panel avoids misleading verification language'
);

$malicious = revelations_review_metadata_raw();
$malicious_string =
    '<script>alert("review")</script>';

$malicious[
    '_revelations_ai_source_section'
] = $malicious_string;

$malicious[
    '_revelations_ai_alternative_titles'
] = json_encode(
    array(
        $malicious_string,
        $malicious_string,
    )
);

$malicious[
    '_revelations_ai_section_mismatch_reason'
] = $malicious_string;

$malicious[
    '_revelations_ai_fact_check_flags'
] = json_encode(
    array(
        array(
            'claim' =>
                $malicious_string,
            'claim_type' =>
                $malicious_string,
            'source_evidence' =>
                $malicious_string,
            'verification_required' =>
                true,
            'reason' =>
                $malicious_string,
        ),
    )
);

$malicious[
    '_revelations_ai_direct_quotes'
] = json_encode(
    array(
        array(
            'quote_text' =>
                $malicious_string,
            'source_fragment' =>
                $malicious_string,
            'verbatim_match' =>
                true,
        ),
    )
);

$malicious_html =
    revelations_review_metadata_render(
        $malicious
    );

revelations_review_metadata_test(
    ! str_contains(
        $malicious_html,
        '<script>'
    ) &&
    str_contains(
        $malicious_html,
        '&lt;script&gt;'
    ),
    'all model and source strings are HTML escaped'
);

revelations_review_metadata_test(
    ! str_contains( $panel_source, '<form' ) &&
    ! str_contains( $panel_source, '<button' ) &&
    ! str_contains( $panel_source, 'admin_post_' ) &&
    ! str_contains( $panel_source, 'check_admin_referer' ),
    'panel contains no interactive approval controls or POST actions'
);

revelations_review_metadata_test(
    ! str_contains(
        $panel_source,
        'wp_set_post_categories'
    ) &&
    ! str_contains(
        $panel_source,
        'update_post_meta'
    ) &&
    ! str_contains(
        $panel_source,
        'wp_update_post'
    ),
    'panel cannot change category or draft data'
);

$raw_a = revelations_review_metadata_raw();

$raw_b = $raw_a;
$raw_b['_revelations_ai_fact_check_flags'] =
    ' [ { ' .
    '"reason" : "The benchmark requires editorial review.",' .
    '"verification_required" : true,' .
    '"evidence_ids" : [ "p004" ],' .
    '"source_evidence" : "The source reported 95% accuracy.",' .
    '"claim_type" : "benchmark",' .
    '"claim" : "The system reached 95% accuracy."' .
    ' } ] ';

$raw_b['_revelations_ai_direct_quotes'] =
    '[{' .
    '"verbatim_match":true,' .
    '"evidence_id":"p006",' .
    '"source_fragment":"The founder said AI is changing how the team works.",' .
    '"quote_text":"AI is changing how the team works."' .
    '}]';

$hash_a =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $raw_a
        )
    );

$hash_b =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $raw_b
        )
    );

revelations_review_metadata_test(
    $hash_a === $hash_b,
    'JSON key order and serialization whitespace do not change hash'
);

$changed_flag = $raw_a;
$flags = json_decode(
    (string) $changed_flag[
        '_revelations_ai_fact_check_flags'
    ],
    true
);
$flags[0]['reason'] =
    'A changed editorial reason.';
$changed_flag[
    '_revelations_ai_fact_check_flags'
] = json_encode( $flags );

$changed_flag_hash =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $changed_flag
        )
    );

revelations_review_metadata_test(
    $hash_a !== $changed_flag_hash,
    'fact-check flag change invalidates metadata hash'
);

$changed_quote = $raw_a;
$quotes = json_decode(
    (string) $changed_quote[
        '_revelations_ai_direct_quotes'
    ],
    true
);
$quotes[0]['source_fragment'] =
    'A changed source fragment.';
$changed_quote[
    '_revelations_ai_direct_quotes'
] = json_encode( $quotes );

$changed_quote_hash =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $changed_quote
        )
    );

revelations_review_metadata_test(
    $hash_a !== $changed_quote_hash,
    'quote evidence change invalidates metadata hash'
);

$changed_section = $raw_a;
$changed_section[
    '_revelations_ai_suggested_section'
] = 'people';

$changed_section_hash =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $changed_section
        )
    );

revelations_review_metadata_test(
    $hash_a !== $changed_section_hash,
    'suggested section change invalidates metadata hash'
);

$with_previous_version = $raw_a;
$with_previous_version[
    '_rev_ai_fact_check_flags'
] = '[{"claim":"Historical version only"}]';
$with_previous_version[
    '_rev_ai_direct_quotes'
] = '[{"quote_text":"Historical quote"}]';

$previous_version_hash =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $with_previous_version
        )
    );

revelations_review_metadata_test(
    $hash_a === $previous_version_hash,
    'previous AI version metadata does not affect current hash'
);

$restored_raw = $changed_quote;

$restored_hash =
    revelations_editorial_ai_review_metadata_hash(
        revelations_editorial_ai_review_normalize_metadata(
            $restored_raw
        )
    );

revelations_review_metadata_test(
    $restored_hash === $changed_quote_hash &&
    $restored_hash !== $hash_a,
    'restored current metadata produces its restored hash'
);

$readiness_position = strpos(
    $drafts_source,
    'revelations_editorial_render_ai_readiness_action('
);

$metadata_position = strpos(
    $drafts_source,
    'revelations_editorial_render_ai_review_metadata_action('
);

$review_position = strpos(
    $drafts_source,
    'revelations_editorial_render_review_action('
);

revelations_review_metadata_test(
    false !== $readiness_position &&
    false !== $metadata_position &&
    false !== $review_position &&
    $readiness_position < $metadata_position &&
    $metadata_position < $review_position,
    'panel is placed between AI readiness and Human Review'
);

revelations_review_metadata_test(
    str_contains(
        $review_source,
        "'ai_review_metadata' =>"
    ) &&
    str_contains(
        $review_source,
        'revelations_editorial_ai_review_metadata_for_draft('
    ),
    'current normalized metadata participates in review hash'
);

revelations_review_metadata_test(
    ! str_contains(
        $panel_source,
        "'_rev_ai_"
    ) &&
    ! str_contains(
        $review_source,
        "'_rev_ai_"
    ),
    'panel and review hash do not read previous version metadata'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

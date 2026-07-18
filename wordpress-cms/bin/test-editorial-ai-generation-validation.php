<?php
/**
 * Isolated diagnostics for generated editorial validation.
 *
 * This script does not load WordPress, call OpenAI or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

require_once
    $plugin_dir .
    '/revelations-editorial-ai-generation-validation.php';

$generation_source = file_get_contents(
    $plugin_dir .
    '/revelations-editorial-ai-generate.php'
);

if ( false === $generation_source ) {
    fwrite(
        STDERR,
        "FAIL: unable to read AI generation source\n"
    );
    exit( 1 );
}

$passed = 0;
$failed = 0;

/**
 * Record one diagnostic result.
 */
function revelations_validation_test(
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
 * Return a safe baseline article with no sensitive claims.
 *
 * @return array<string, mixed>
 */
function revelations_validation_article(): array {
    return array(
        'recommended_title' =>
            'AI Systems Move Into Daily Operations',

        'alternative_titles' => array(
            'New Tools Reach Working Environments',
            'Practical Automation Enters Routine Use',
        ),

        'section_mismatch' =>
            false,

        'suggested_section' =>
            null,

        'section_mismatch_reason' =>
            null,

        'excerpt' =>
            'A deployed AI system is now part of routine operations.',

        'seo_title' =>
            'Deployed AI in Routine Operations',

        'seo_description' =>
            'How an operational AI system fits into everyday workflows.',

        'fact_check_flags' =>
            array(),

        'direct_quotes' =>
            array(),

        'blocks' => array(
            array(
                'type' =>
                    'paragraph',
                'text' =>
                    'The source describes a deployed system in regular operation.',
                'heading_level' =>
                    0,
                'items' =>
                    array(),
            ),
        ),
    );
}

/**
 * Validate one article using a supplied source snapshot.
 *
 * @param array<string, mixed> $article Article.
 * @return array<string, mixed>
 */
function revelations_validation_run(
    array $article,
    string $source_section = 'news',
    string $source_snapshot = 'The source describes a deployed system in regular operation.'
): array {
    return revelations_editorial_ai_validate_generated_article(
        $article,
        $source_section,
        $source_snapshot
    );
}

/**
 * Replace article blocks with one paragraph.
 *
 * @param array<string, mixed> $article Article.
 * @return array<string, mixed>
 */
function revelations_validation_with_claim(
    array $article,
    string $claim
): array {
    $article['blocks'] = array(
        array(
            'type' =>
                'paragraph',
            'text' =>
                $claim,
            'heading_level' =>
                0,
            'items' =>
                array(),
        ),
    );

    return $article;
}

/**
 * Return one valid fact-check flag.
 *
 * @return array<string, mixed>
 */
function revelations_validation_flag(
    string $claim,
    string $claim_type,
    string $source_evidence
): array {
    return array(
        'claim' =>
            $claim,
        'claim_type' =>
            $claim_type,
        'source_evidence' =>
            $source_evidence,
        'verification_required' =>
            true,
        'reason' =>
            'Manual verification is required.',
    );
}

$result = revelations_validation_run(
    revelations_validation_article()
);

revelations_validation_test(
    true === ( $result['valid'] ?? false ),
    'three normalized titles are unique'
);


$metadata_sensitive_article =
    revelations_validation_article();

$metadata_sensitive_article[
    'recommended_title'
] =
    'First AI Investment Reaches $18 Million in July 2026';

$result = revelations_validation_run(
    $metadata_sensitive_article,
    'news'
);

revelations_validation_test(
    true === ( $result['valid'] ?? false ),
    'sensitive metadata does not require a duplicate body fact-check flag'
);

$article = revelations_validation_article();
$article['alternative_titles'][0] =
    'AI — Systems, Move Into Daily Operations!';

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_title_set' === (
        $result['code'] ?? ''
    ),
    'punctuation-only title duplicate is rejected'
);

$article = revelations_validation_article();
$article['alternative_titles'][0] =
    "  ai systems   MOVE into daily operations  ";

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_title_set' === (
        $result['code'] ?? ''
    ),
    'case and whitespace title duplicate is rejected'
);

$article = revelations_validation_article();
$article['section_mismatch'] = false;
$article['suggested_section'] = 'tech';

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_section_advisory' === (
        $result['code'] ?? ''
    ),
    'false mismatch cannot include a suggested section'
);

$article = revelations_validation_article();
$article['section_mismatch_reason'] =
    'A reason without a mismatch.';

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_section_advisory' === (
        $result['code'] ?? ''
    ),
    'false mismatch cannot include a reason'
);

$article = revelations_validation_article();
$article['section_mismatch'] = true;
$article['suggested_section'] = null;
$article['section_mismatch_reason'] =
    'A different section may fit.';

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_section_advisory' === (
        $result['code'] ?? ''
    ),
    'true mismatch requires a suggested section'
);

$article = revelations_validation_article();
$article['section_mismatch'] = true;
$article['suggested_section'] = 'news';
$article['section_mismatch_reason'] =
    'The assigned section is repeated.';

$result = revelations_validation_run( $article );

revelations_validation_test(
    'invalid_section_advisory' === (
        $result['code'] ?? ''
    ),
    'suggested section cannot equal source section'
);

$article = revelations_validation_article();
$article['section_mismatch'] = true;
$article['suggested_section'] = 'tech';
$article['section_mismatch_reason'] =
    'The article centers on a technical system.';

$result = revelations_validation_run( $article );

revelations_validation_test(
    true === ( $result['valid'] ?? false ),
    'complete section mismatch advisory is accepted'
);

$article = revelations_validation_article();
$article['seo_description'] =
    " A DEPLOYED AI SYSTEM is now part of routine\u{00A0}operations. ";

$result = revelations_validation_run( $article );

revelations_validation_test(
    'duplicate_descriptions' === (
        $result['code'] ?? ''
    ),
    'normalized identical excerpt and SEO description are rejected'
);

$benchmark_claim =
    'The model achieved 95% accuracy.';

$article = revelations_validation_with_claim(
    revelations_validation_article(),
    $benchmark_claim
);

$article['fact_check_flags'] = array(
    revelations_validation_flag(
        $benchmark_claim,
        'benchmark',
        'The source measured 95% accuracy.'
    ),
);

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    true === ( $result['valid'] ?? false ),
    'valid structured fact-check flag is accepted'
);

$missing_flag_claims = array(
    'number' =>
        'The system processed 42 tasks.',
    'date' =>
        'The launch happened in March 2026.',
    'money' =>
        'Revenue reached $5 million.',
    'investment' =>
        'The company raised a new funding round.',
    'benchmark' =>
        'The model achieved 95% accuracy.',
    'superlative' =>
        'It is the fastest model in the market.',
    'medical' =>
        'The system diagnosed disease in patients.',
    'legal' =>
        'Acme faced a copyright lawsuit.',
    'regulatory' =>
        'The FTC opened an antitrust review.',
    'reputational' =>
        'Acme Corporation was accused of fraud.',
);

foreach (
    $missing_flag_claims
    as $type => $claim
) {
    $article = revelations_validation_with_claim(
        revelations_validation_article(),
        $claim
    );

    $result = revelations_validation_run(
        $article,
        'news',
        $claim
    );

    revelations_validation_test(
        'missing_fact_check_flag' === (
            $result['code'] ?? ''
        ),
        'missing ' . $type . ' flag is rejected'
    );
}

$article = revelations_validation_with_claim(
    revelations_validation_article(),
    $benchmark_claim
);

$article['fact_check_flags'] = array(
    revelations_validation_flag(
        $benchmark_claim,
        'benchmark',
        ''
    ),
);

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    'invalid_fact_check_flag' === (
        $result['code'] ?? ''
    ),
    'empty source evidence is rejected'
);

$article['fact_check_flags'][0][
    'source_evidence'
] = 'Evidence absent from the snapshot.';

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    'invalid_fact_check_evidence' === (
        $result['code'] ?? ''
    ),
    'source evidence absent from snapshot is rejected'
);

$inkling_source_evidence =
    'Inkling is a mixture-of-experts system with 975 billion ' .
    'total parameters, though it only draws on a fraction of ' .
    'that — about 41 billion — for any given task, a common ' .
    'design that keeps very large models faster and cheaper to ' .
    'run.';

$article = revelations_validation_with_claim(
    revelations_validation_article(),
    $benchmark_claim
);

$article['fact_check_flags'] = array(
    revelations_validation_flag(
        $benchmark_claim,
        'benchmark',
        "Inkling is a mixture-of-experts system with 975 billion\r\n" .
        "total parameters, though it only draws on a fraction of " .
        "that &mdash; about 41 billion &mdash; for any given task, " .
        "a common design that keeps very large models faster and " .
        "cheaper to run."
    ),
);

$result = revelations_validation_run(
    $article,
    'tech',
    $inkling_source_evidence
);

revelations_validation_test(
    true === ( $result['valid'] ?? false ),
    'production Inkling evidence accepts only transport normalization'
);

$article['fact_check_flags'][0][
    'source_evidence'
] = str_replace(
    '975 billion',
    'nearly one trillion',
    $article['fact_check_flags'][0]['source_evidence']
);

$result = revelations_validation_run(
    $article,
    'tech',
    $inkling_source_evidence
);

revelations_validation_test(
    'invalid_fact_check_evidence' === (
        $result['code'] ?? ''
    ),
    'semantic evidence paraphrase remains rejected'
);

$article['fact_check_flags'][0][
    'source_evidence'
] = mb_strtolower(
    $inkling_source_evidence,
    'UTF-8'
);

$result = revelations_validation_run(
    $article,
    'tech',
    $inkling_source_evidence
);

revelations_validation_test(
    'invalid_fact_check_evidence' === (
        $result['code'] ?? ''
    ),
    'source evidence case changes remain rejected'
);

$article['fact_check_flags'][0][
    'source_evidence'
] = str_replace(
    '—',
    '-',
    $inkling_source_evidence
);

$result = revelations_validation_run(
    $article,
    'tech',
    $inkling_source_evidence
);

revelations_validation_test(
    'invalid_fact_check_evidence' === (
        $result['code'] ?? ''
    ),
    'source evidence punctuation changes remain rejected'
);

$article['fact_check_flags'][0] =
    revelations_validation_flag(
        'A claim absent from generated text.',
        'benchmark',
        'The source measured 95% accuracy.'
    );

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    'invalid_fact_check_evidence' === (
        $result['code'] ?? ''
    ),
    'claim absent from generated text is rejected'
);

$article['fact_check_flags'][0] =
    revelations_validation_flag(
        $benchmark_claim,
        'benchmark',
        'The source measured 95% accuracy.'
    );

$article['fact_check_flags'][0][
    'verification_required'
] = false;

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    'invalid_fact_check_flag' === (
        $result['code'] ?? ''
    ),
    'verification_required false is rejected'
);

$duplicate_flag =
    revelations_validation_flag(
        $benchmark_claim,
        'benchmark',
        'The source measured 95% accuracy.'
    );

$article['fact_check_flags'] = array(
    $duplicate_flag,
    $duplicate_flag,
);

$result = revelations_validation_run(
    $article,
    'tech',
    'The source measured 95% accuracy.'
);

revelations_validation_test(
    'duplicate_fact_check_flag' === (
        $result['code'] ?? ''
    ),
    'duplicate claim and claim type are rejected'
);

$quote_text =
    'AI is changing how the team works.';

$quote_fragment =
    'The founder said, “AI is changing how the team works.” during the briefing.';

$article = revelations_validation_article();
$article['blocks'] = array(
    array(
        'type' =>
            'quote',
        'text' =>
            $quote_text,
        'heading_level' =>
            0,
        'items' =>
            array(),
    ),
);

$article['direct_quotes'] = array(
    array(
        'quote_text' =>
            $quote_text,
        'source_fragment' =>
            $quote_fragment,
    ),
);

$article['fact_check_flags'] = array(
    revelations_validation_flag(
        $quote_text,
        'quote',
        $quote_text
    ),
);

$result = revelations_validation_run(
    $article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    true === ( $result['valid'] ?? false ) &&
    true === (
        $result['article'][
            'direct_quotes'
        ][0]['verbatim_match'] ?? false
    ),
    'valid exact quote receives server verbatim_match true'
);

$case_mismatch_article = $article;
$case_mismatch_article['direct_quotes'][0][
    'quote_text'
] = 'AI is changing how the Team works.';

$result = revelations_validation_run(
    $case_mismatch_article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    'invalid_direct_quote' === (
        $result['code'] ?? ''
    ),
    'case-mismatched quote is rejected'
);

$punctuation_mismatch_article = $article;
$punctuation_mismatch_article['direct_quotes'][0][
    'quote_text'
] = 'AI is changing how the team works!';

$result = revelations_validation_run(
    $punctuation_mismatch_article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    'invalid_direct_quote' === (
        $result['code'] ?? ''
    ),
    'punctuation-mismatched quote is rejected'
);

$fragment_mismatch_article = $article;
$fragment_mismatch_article['direct_quotes'][0][
    'source_fragment'
] = 'The briefing continued after the statement.';

$result = revelations_validation_run(
    $fragment_mismatch_article,
    'people',
    $quote_fragment .
    ' The briefing continued after the statement.'
);

revelations_validation_test(
    'invalid_direct_quote' === (
        $result['code'] ?? ''
    ),
    'source fragment without quote is rejected'
);

$missing_item_article = $article;
$missing_item_article['direct_quotes'] = array();

$result = revelations_validation_run(
    $missing_item_article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    'direct_quote_usage_mismatch' === (
        $result['code'] ?? ''
    ),
    'quote block without direct quote item is rejected'
);

$unused_item_article = $article;
$unused_item_article['blocks'] = array(
    array(
        'type' =>
            'paragraph',
        'text' =>
            'The article paraphrases the source before noting that ' .
            'the team changed its workflow.',
        'heading_level' =>
            0,
        'items' =>
            array(),
    ),
);

$unused_item_article[
    'fact_check_flags'
] = array();

$result = revelations_validation_run(
    $unused_item_article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    'direct_quote_usage_mismatch' === (
        $result['code'] ?? ''
    ),
    'unused direct quote item is rejected'
);

$missing_quote_flag_article = $article;
$missing_quote_flag_article[
    'fact_check_flags'
] = array();

$result = revelations_validation_run(
    $missing_quote_flag_article,
    'people',
    $quote_fragment
);

revelations_validation_test(
    'missing_fact_check_flag' === (
        $result['code'] ?? ''
    ),
    'quote without quote fact-check flag is rejected'
);

$result = revelations_validation_run(
    revelations_validation_article(),
    'unspoken'
);

revelations_validation_test(
    'unspoken_fact_check_required' === (
        $result['code'] ?? ''
    ),
    'Unspoken generation cannot have empty fact-check flags'
);

$evidence_snapshot =
    "First &amp; stable paragraph.\r\n\r\n" .
    "Morocco&#8217;s   AI\torchestration uses 25 systems.\n\n" .
    'The director said, “Keep people in control.”';

$evidence_units =
    revelations_editorial_ai_source_evidence_units(
        $evidence_snapshot
    );

revelations_validation_test(
    array(
        array(
            'id' => 'p001',
            'text' => 'First & stable paragraph.',
        ),
        array(
            'id' => 'p002',
            'text' =>
                'Morocco’s AI orchestration uses 25 systems.',
        ),
        array(
            'id' => 'p003',
            'text' =>
                'The director said, “Keep people in control.”',
        ),
    ) === $evidence_units,
    'evidence units normalize entities, CRLF and insignificant whitespace deterministically'
);

revelations_validation_test(
    $evidence_units ===
        revelations_editorial_ai_source_evidence_units(
            $evidence_snapshot
        ) &&
    str_contains(
        revelations_editorial_ai_format_source_evidence_units(
            $evidence_units
        ),
        '[p002] Morocco’s AI orchestration uses 25 systems.'
    ),
    'stable paragraph IDs are repeatable and rendered with their exact normalized text'
);

$referenced_article =
    revelations_validation_with_claim(
        revelations_validation_article(),
        'Morocco’s AI orchestration uses 25 systems.'
    );

$referenced_article['fact_check_flags'] = array(
    array(
        'claim_unit_id' =>
            'blocks.0.text',
        'requires_manual_verification' => true,
        'evidence_ids' => array(
            'p001',
            'p002',
        ),
    ),
);

$resolved =
    revelations_editorial_ai_resolve_evidence_references(
        $referenced_article,
        $evidence_units
    );

$resolved_flags =
    $resolved['article']['fact_check_flags']
    ?? array();

revelations_validation_test(
    true === ( $resolved['valid'] ?? false ) &&
    array( 'p001', 'p002' ) === (
        $resolved_flags[0]['evidence_ids']
        ?? array()
    ) &&
    'blocks.0.text' === (
        $resolved_flags[0]['claim_unit_id']
        ?? ''
    ) &&
    (
        "First & stable paragraph.\n\n" .
        'Morocco’s AI orchestration uses 25 systems.'
    ) === (
            $resolved_flags[0]['source_evidence']
            ?? ''
        ),
    'multiple known evidence IDs are reconstructed server-side in model order'
);


$repeated_reference_article =
    $referenced_article;

$repeated_reference_article[
    'fact_check_flags'
] = array(
    array(
        'claim_unit_id' =>
            'blocks.0.text',
        'requires_manual_verification' =>
            true,
        'evidence_ids' =>
            array( 'p001' ),
    ),
    array(
        'claim_unit_id' =>
            'blocks.0.text',
        'requires_manual_verification' =>
            true,
        'evidence_ids' =>
            array( 'p002' ),
    ),
);

$repeated_resolution =
    revelations_editorial_ai_resolve_evidence_references(
        $repeated_reference_article,
        $evidence_units
    );

$repeated_flags =
    $repeated_resolution[
        'article'
    ]['fact_check_flags'] ?? array();

revelations_validation_test(
    true === (
        $repeated_resolution[
            'valid'
        ] ?? false
    ) &&
    1 === count( $repeated_flags ) &&
    array(
        'p001',
        'p002',
    ) === (
        $repeated_flags[0][
            'evidence_ids'
        ] ?? array()
    ),
    'repeated raw body-unit references merge their evidence IDs'
);


$block_evidence_article =
    $referenced_article;

$block_evidence_article[
    'fact_check_flags'
] = array();

$block_evidence_article[
    'blocks'
][0]['evidence_ids'] = array(
    'p001',
    'p002',
);

$block_evidence_resolution =
    revelations_editorial_ai_resolve_evidence_references(
        $block_evidence_article,
        $evidence_units
    );

$block_evidence_flags =
    $block_evidence_resolution[
        'article'
    ]['fact_check_flags'] ?? array();

revelations_validation_test(
    true === (
        $block_evidence_resolution[
            'valid'
        ] ?? false
    ) &&
    1 === count(
        $block_evidence_flags
    ) &&
    'blocks.0.text' === (
        $block_evidence_flags[0][
            'claim_unit_id'
        ] ?? ''
    ) &&
    array(
        'p001',
        'p002',
    ) === (
        $block_evidence_flags[0][
            'evidence_ids'
        ] ?? array()
    ),
    'server derives sensitive flags from generated block evidence'
);

$unknown_block_evidence_article =
    $block_evidence_article;

$unknown_block_evidence_article[
    'blocks'
][0]['evidence_ids'] = array(
    'p999',
);

$unknown_block_evidence_result =
    revelations_editorial_ai_resolve_evidence_references(
        $unknown_block_evidence_article,
        $evidence_units
    );

revelations_validation_test(
    false === (
        $unknown_block_evidence_result[
            'valid'
        ] ?? true
    ) &&
    'invalid_fact_check_evidence' === (
        $unknown_block_evidence_result[
            'code'
        ] ?? ''
    ),
    'unknown generated block evidence is rejected'
);

$unknown_reference_article = $referenced_article;
$unknown_reference_article['fact_check_flags'][0][
    'evidence_ids'
] = array( 'p999' );

$unknown_result =
    revelations_editorial_ai_resolve_evidence_references(
        $unknown_reference_article,
        $evidence_units
    );

revelations_validation_test(
    false === ( $unknown_result['valid'] ?? true ) &&
    'invalid_fact_check_evidence' === (
        $unknown_result['code'] ?? ''
    ),
    'unknown evidence IDs are rejected without fuzzy fallback'
);


$inline_quote_text =
    'Keep people in control.';

$inline_quote_article =
    revelations_validation_article();

$inline_quote_article[
    'blocks'
] = array(
    array(
        'type' =>
            'paragraph',

        'text' =>
            'The instruction was explicit: ' .
            $inline_quote_text,

        'heading_level' =>
            0,

        'items' =>
            array(),

        'evidence_ids' =>
            array( 'p003' ),
    ),
);

$inline_quote_article[
    'fact_check_flags'
] = array();

$inline_quote_article[
    'direct_quotes'
] = array(
    array(
        'quote_text' =>
            $inline_quote_text,

        'evidence_id' =>
            'p003',
    ),
);

$inline_quote_resolution =
    revelations_editorial_ai_resolve_evidence_references(
        $inline_quote_article,
        $evidence_units
    );

$inline_quote_validation =
    ! empty(
        $inline_quote_resolution[
            'valid'
        ]
    )
        ? revelations_validation_run(
            $inline_quote_resolution[
                'article'
            ],
            'people',
            $evidence_snapshot
        )
        : $inline_quote_resolution;

$inline_quote_flags =
    $inline_quote_validation[
        'article'
    ]['fact_check_flags'] ?? array();

revelations_validation_test(
    true === (
        $inline_quote_validation[
            'valid'
        ] ?? false
    ) &&
    1 === count(
        $inline_quote_flags
    ) &&
    'quote' === (
        $inline_quote_flags[0][
            'claim_type'
        ] ?? ''
    ) &&
    'blocks.0.text' === (
        $inline_quote_flags[0][
            'claim_unit_id'
        ] ?? ''
    ) &&
    true === (
        $inline_quote_validation[
            'article'
        ]['direct_quotes'][0][
            'verbatim_match'
        ] ?? false
    ),
    'inline direct quote receives a server-derived quote flag'
);

$quote_article = revelations_validation_article();
$quote_article['blocks'] = array(
    array(
        'type' => 'quote',
        'text' => 'Keep people in control.',
        'heading_level' => 0,
        'items' => array(),
    ),
);
$quote_article['fact_check_flags'] = array(
    array(
        'claim' => 'Keep people in control.',
        'requires_manual_verification' => true,
        'evidence_ids' => array( 'p003' ),
    ),
);
$quote_article['direct_quotes'] = array(
    array(
        'quote_text' => 'Keep people in control.',
        'evidence_id' => 'p003',
    ),
);

$quote_resolution =
    revelations_editorial_ai_resolve_evidence_references(
        $quote_article,
        $evidence_units
    );

$quote_validation = ! empty(
    $quote_resolution['valid']
)
    ? revelations_validation_run(
        $quote_resolution['article'],
        'news',
        $evidence_snapshot
    )
    : $quote_resolution;

revelations_validation_test(
    true === ( $quote_validation['valid'] ?? false ) &&
    true === (
        $quote_validation['article'][
            'direct_quotes'
        ][0]['verbatim_match'] ?? false
    ) &&
    'p003' === (
        $quote_validation['article'][
            'direct_quotes'
        ][0]['evidence_id'] ?? ''
    ),
    'exact quote references receive server-generated verbatim_match'
);

$apostrophe_quote = $quote_article;
$apostrophe_quote['blocks'][0]['text'] =
    "Morocco's AI orchestration";
$apostrophe_quote['fact_check_flags'][0]['claim'] =
    "Morocco's AI orchestration";
$apostrophe_quote['fact_check_flags'][0]['evidence_ids'] =
    array( 'p002' );
$apostrophe_quote['direct_quotes'][0] = array(
    'quote_text' => "Morocco's AI orchestration",
    'evidence_id' => 'p002',
);

$apostrophe_result =
    revelations_editorial_ai_resolve_evidence_references(
        $apostrophe_quote,
        $evidence_units
    );

revelations_validation_test(
    false === ( $apostrophe_result['valid'] ?? true ) &&
    'invalid_direct_quote' === (
        $apostrophe_result['code'] ?? ''
    ),
    'typographic apostrophes are not fuzzily matched to ASCII apostrophes'
);

$function_start = strpos(
    $generation_source,
    'function revelations_editorial_generate_draft_with_ai('
);

$function_end = strpos(
    $generation_source,
    '/**' . "\n" . ' * Register private AI draft versions.',
    false === $function_start ? 0 : $function_start
);

$generation_function =
    false !== $function_start &&
    false !== $function_end
        ? substr(
            $generation_source,
            $function_start,
            $function_end - $function_start
        )
        : '';

$validation_position = strpos(
    $generation_function,
    'revelations_editorial_ai_validate_generated_article('
);

$backup_position = strpos(
    $generation_function,
    'revelations_editorial_ai_create_version_backup('
);

$post_update_position = strpos(
    $generation_function,
    'wp_update_post('
);

$metadata_write_position = strpos(
    $generation_function,
    'update_post_meta('
);

revelations_validation_test(
    false !== $validation_position &&
    false !== $backup_position &&
    false !== $post_update_position &&
    false !== $metadata_write_position &&
    $validation_position < $backup_position &&
    $validation_position < $post_update_position &&
    $validation_position < $metadata_write_position,
    'validation runs before backup and all WordPress writes'
);

revelations_validation_test(
    ! str_contains(
        substr(
            $generation_function,
            0,
            false === $validation_position
                ? 0
                : $validation_position
        ),
        'wp_set_post_categories('
    ) &&
    ! str_contains(
        $generation_function,
        'get_category_by_slug('
    ),
    'validation path cannot change category before validation'
);

revelations_validation_test(
    revelations_editorial_ai_is_validation_error_code(
        'invalid_direct_quote'
    ) &&
    revelations_editorial_ai_is_validation_error_code(
        'missing_fact_check_flag'
    ) &&
    ! revelations_editorial_ai_is_validation_error_code(
        'api_error'
    ),
    'validation error codes are identified without masking other failures'
);

revelations_validation_test(
    str_contains(
        $generation_source,
        '$is_validation_error ='
    ) &&
    str_contains(
        $generation_source,
        'if ( ! $is_validation_error )'
    ) &&
    str_contains(
        $generation_source,
        "'_revelations_ai_error'"
    ),
    'validation failures skip draft error metadata writes'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

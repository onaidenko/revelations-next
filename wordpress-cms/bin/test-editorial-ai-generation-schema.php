<?php
/**
 * Isolated diagnostics for the editorial generation schema and storage.
 *
 * This script does not load WordPress, call OpenAI or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

/**
 * Prevent registration callbacks from running without WordPress.
 */
function add_action( mixed ...$arguments ): void {
}

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

$generation_file =
    $plugin_dir .
    '/revelations-editorial-ai-generate.php';

$restore_file =
    $plugin_dir .
    '/revelations-editorial-version-restore.php';

$logs_file =
    $plugin_dir .
    '/revelations-editorial-logs.php';

require_once $generation_file;

$generation_source =
    file_get_contents( $generation_file );

$restore_source =
    file_get_contents( $restore_file );

$logs_source =
    file_get_contents( $logs_file );

if (
    false === $generation_source ||
    false === $restore_source ||
    false === $logs_source
) {
    fwrite(
        STDERR,
        "FAIL: unable to read stage 5 source files\n"
    );
    exit( 1 );
}

$passed = 0;
$failed = 0;

/**
 * Record one diagnostic result.
 */
function revelations_schema_test(
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
 * Extract one function from source using the next top-level function.
 */
function revelations_schema_function_source(
    string $source,
    string $function_name
): string {
    $start = strpos(
        $source,
        'function ' . $function_name . '('
    );

    if ( false === $start ) {
        return '';
    }

    $next = strpos(
        $source,
        "\nfunction ",
        $start + 1
    );

    if ( false === $next ) {
        return substr( $source, $start );
    }

    return substr(
        $source,
        $start,
        $next - $start
    );
}

$schema =
    revelations_editorial_ai_article_schema();

$properties =
    $schema['properties'] ?? array();

$required =
    $schema['required'] ?? array();

revelations_schema_test(
    ! array_key_exists( 'title', $properties ) &&
    ! in_array( 'title', $required, true ),
    'schema no longer contains legacy title'
);

revelations_schema_test(
    ! array_key_exists( 'section', $properties ) &&
    ! in_array( 'section', $required, true ),
    'schema no longer contains legacy section'
);

revelations_schema_test(
    ! array_key_exists( 'source_section', $properties ) &&
    ! in_array( 'source_section', $required, true ),
    'server source section is not model output'
);

revelations_schema_test(
    isset( $properties['recommended_title'] ) &&
    in_array( 'recommended_title', $required, true ),
    'recommended title is required'
);

$alternative_titles =
    $properties['alternative_titles'] ?? array();

revelations_schema_test(
    'array' === (
        $alternative_titles['type'] ?? ''
    ) &&
    2 === (
        $alternative_titles['minItems'] ?? 0
    ) &&
    2 === (
        $alternative_titles['maxItems'] ?? 0
    ) &&
    'string' === (
        $alternative_titles['items']['type']
        ?? ''
    ),
    'alternative titles contain exactly two strings'
);

foreach (
    array(
        'section_mismatch',
        'suggested_section',
        'section_mismatch_reason',
    ) as $advisory_field
) {
    revelations_schema_test(
        array_key_exists(
            $advisory_field,
            $properties
        ) &&
        in_array(
            $advisory_field,
            $required,
            true
        ),
        $advisory_field . ' is required model output'
    );
}

$suggested_section =
    $properties['suggested_section'] ?? array();

revelations_schema_test(
    array(
        'string',
        'null',
    ) === (
        $suggested_section['type'] ?? null
    ),
    'suggested section is nullable'
);

revelations_schema_test(
    array(
        'news',
        'tech',
        'people',
        'places',
        'unspoken',
        null,
    ) === (
        $suggested_section['enum'] ?? null
    ),
    'suggested section is limited to five sections or null'
);

revelations_schema_test(
    'boolean' === (
        $properties['section_mismatch']['type']
        ?? ''
    ),
    'section mismatch is boolean'
);

revelations_schema_test(
    array(
        'string',
        'null',
    ) === (
        $properties[
            'section_mismatch_reason'
        ]['type'] ?? null
    ),
    'section mismatch reason is nullable'
);

$fact_flags =
    $properties['fact_check_flags'] ?? array();

$fact_flag_item =
    $fact_flags['items'] ?? array();

$fact_flag_properties =
    $fact_flag_item['properties'] ?? array();

revelations_schema_test(
    array(
        'claim',
        'claim_type',
        'source_evidence',
        'verification_required',
        'reason',
    ) === (
        $fact_flag_item['required'] ?? null
    ) &&
    false === (
        $fact_flag_item[
            'additionalProperties'
        ] ?? true
    ),
    'fact-check flags require the complete structured contract'
);

revelations_schema_test(
    'boolean' === (
        $fact_flag_properties[
            'verification_required'
        ]['type'] ?? ''
    ),
    'verification_required is boolean'
);

revelations_schema_test(
    array(
        'number',
        'date',
        'money',
        'investment',
        'company_valuation',
        'quote',
        'superlative',
        'benchmark',
        'medical',
        'legal',
        'regulatory',
        'reputational',
        'other_sensitive',
    ) === (
        $fact_flag_properties[
            'claim_type'
        ]['enum'] ?? null
    ),
    'all approved fact-check claim types are supported'
);

$direct_quotes =
    $properties['direct_quotes'] ?? array();

$direct_quote_item =
    $direct_quotes['items'] ?? array();

$direct_quote_properties =
    $direct_quote_item['properties'] ?? array();

revelations_schema_test(
    array(
        'quote_text',
        'source_fragment',
    ) === (
        $direct_quote_item['required'] ?? null
    ) &&
    false === (
        $direct_quote_item[
            'additionalProperties'
        ] ?? true
    ),
    'direct quotes contain exact quote and source fragment candidates'
);

revelations_schema_test(
    ! array_key_exists(
        'verbatim_match',
        $direct_quote_properties
    ),
    'model cannot control verbatim_match'
);

$generation_function =
    revelations_schema_function_source(
        $generation_source,
        'revelations_editorial_generate_draft_with_ai'
    );

revelations_schema_test(
    str_contains(
        $generation_function,
        "'_rev_section'"
    ) &&
    str_contains(
        $generation_function,
        "'_revelations_ai_source_section'"
    ) &&
    str_contains(
        $generation_function,
        "'source_section' =>"
    ),
    'source section is read and propagated by the server'
);

revelations_schema_test(
    str_contains(
        $generation_function,
        "'post_title'   =>" . "\n" .
        '                $recommended_title'
    ),
    'recommended title becomes WordPress post_title'
);

revelations_schema_test(
    ! str_contains(
        $generation_function,
        'get_category_by_slug('
    ) &&
    ! str_contains(
        $generation_function,
        'wp_set_post_categories('
    ),
    'generation path does not assign a WordPress category'
);

revelations_schema_test(
    ! str_contains(
        $generation_function,
        "'post_author'"
    ) &&
    ! str_contains(
        $generation_function,
        "\$article['author']"
    ) &&
    str_contains(
        $generation_function,
        "'Julia U.'"
    ),
    'model cannot assign author and existing conditional default remains'
);

$draft_meta_keys = array(
    '_revelations_ai_alternative_titles',
    '_revelations_ai_source_section',
    '_revelations_ai_section_mismatch',
    '_revelations_ai_suggested_section',
    '_revelations_ai_section_mismatch_reason',
    '_revelations_ai_fact_check_flags',
    '_revelations_ai_direct_quotes',
);

foreach ( $draft_meta_keys as $draft_meta_key ) {
    revelations_schema_test(
        str_contains(
            $generation_function,
            "'" . $draft_meta_key . "'"
        ),
        'generation stores ' . $draft_meta_key
    );
}

revelations_schema_test(
    ! str_contains(
        $generation_function,
        "'_revelations_ai_section_suggestion' =>"
    ),
    'legacy section suggestion is not populated from model output'
);

$backup_function =
    revelations_schema_function_source(
        $generation_source,
        'revelations_editorial_ai_create_version_backup'
    );

$restore_function =
    revelations_schema_function_source(
        $restore_source,
        'revelations_editorial_ai_restore_version'
    );

$version_meta_keys = array(
    '_rev_ai_alternative_titles',
    '_rev_ai_source_section',
    '_rev_ai_section_mismatch',
    '_rev_ai_suggested_section',
    '_rev_ai_section_mismatch_reason',
    '_rev_ai_fact_check_flags',
    '_rev_ai_direct_quotes',
);

foreach (
    $version_meta_keys
    as $index => $version_meta_key
) {
    revelations_schema_test(
        str_contains(
            $backup_function,
            "'" . $version_meta_key . "'"
        ),
        'private version backs up ' .
        $version_meta_key
    );

    revelations_schema_test(
        str_contains(
            $restore_function,
            "'" . $version_meta_key . "'"
        ) &&
        str_contains(
            $restore_function,
            "'" . $draft_meta_keys[ $index ] . "'"
        ),
        'private version restores ' .
        $draft_meta_keys[ $index ]
    );
}

revelations_schema_test(
    str_contains(
        $restore_function,
        'metadata_exists('
    ) &&
    str_contains(
        $restore_function,
        'delete_post_meta('
    ),
    'legacy versions tolerate absent stage 5 metadata'
);

revelations_schema_test(
    str_contains(
        $restore_function,
        '$readiness[\'categories\']'
    ) &&
    ! str_contains(
        $restore_function,
        "'post_author'"
    ),
    'restore uses saved categories and does not change author'
);

$log_function =
    revelations_schema_function_source(
        $logs_source,
        'revelations_editorial_log_ai_generation'
    );

revelations_schema_test(
    str_contains(
        $log_function,
        "\$result['source_section']"
    ) &&
    str_contains(
        $log_function,
        "'_rev_section'"
    ),
    'generation logs use server source section'
);

revelations_schema_test(
    ! str_contains(
        $log_function,
        'suggested_section'
    ) &&
    ! str_contains(
        $log_function,
        '_revelations_ai_section_suggestion'
    ) &&
    ! str_contains(
        $log_function,
        'wp_get_post_categories('
    ),
    'generation logs never infer section from advice or category'
);

revelations_schema_test(
    ! str_contains(
        $log_function,
        'api_key'
    ) &&
    ! str_contains(
        $log_function,
        'instructions'
    ) &&
    ! str_contains(
        $log_function,
        'source_text'
    ),
    'generation logs do not contain secrets, prompt or source snapshot'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

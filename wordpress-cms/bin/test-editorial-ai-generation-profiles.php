<?php
/**
 * Isolated diagnostics for section-specific AI generation profiles.
 *
 * This script does not load WordPress, call OpenAI or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

require_once
    $plugin_dir .
    '/revelations-editorial-ai-generation-profiles.php';

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

$function_start = strpos(
    $generation_source,
    'function revelations_editorial_generate_draft_with_ai('
);

$function_end = strpos(
    $generation_source,
    '/**' . "\n" . ' * Register private AI draft versions.',
    false === $function_start ? 0 : $function_start
);

if (
    false === $function_start ||
    false === $function_end
) {
    fwrite(
        STDERR,
        "FAIL: unable to isolate generation function\n"
    );
    exit( 1 );
}

$generation_function = substr(
    $generation_source,
    $function_start,
    $function_end - $function_start
);

$passed = 0;
$failed = 0;

/**
 * Record one diagnostic result.
 */
function revelations_profile_test(
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
 * Check source ordering inside the generation function.
 */
function revelations_profile_test_before(
    string $source,
    string $earlier,
    string $later,
    string $message
): void {
    $earlier_position = strpos(
        $source,
        $earlier
    );

    $later_position = strpos(
        $source,
        $later
    );

    revelations_profile_test(
        false !== $earlier_position &&
        false !== $later_position &&
        $earlier_position < $later_position,
        $message
    );
}

$profiles =
    revelations_editorial_ai_generation_profiles();

$expected_sections = array(
    'news',
    'tech',
    'people',
    'places',
    'unspoken',
);

revelations_profile_test(
    $expected_sections === array_keys( $profiles ),
    'registry contains exactly the five supported sections'
);

foreach ( $expected_sections as $section ) {
    $profile =
        revelations_editorial_ai_generation_profile(
            $section
        );

    revelations_profile_test(
        is_array( $profile ) &&
        '' !== ( $profile['label'] ?? '' ) &&
        '' !== ( $profile['instructions'] ?? '' ),
        $section . ' resolves to a complete profile'
    );

    if ( ! is_array( $profile ) ) {
        continue;
    }

    $prompt =
        revelations_editorial_ai_generation_profile_prompt(
            $section,
            $profile
        );

    revelations_profile_test(
        str_contains(
            $prompt,
            'Assigned REVELATIONS section: ' . $section
        ) &&
        str_contains(
            $prompt,
            'Return the legacy section field exactly as "' .
            $section .
            '"'
        ),
        $section . ' prompt locks the exact legacy section'
    );

    $contains_only_selected_profile = str_contains(
        $prompt,
        $profile['instructions']
    );

    foreach ( $profiles as $other_section => $other_profile ) {
        if ( $other_section === $section ) {
            continue;
        }

        $contains_only_selected_profile =
            $contains_only_selected_profile &&
            ! str_contains(
                $prompt,
                $other_profile['instructions']
            );
    }

    revelations_profile_test(
        $contains_only_selected_profile,
        $section . ' prompt contains only its selected profile'
    );
}

revelations_profile_test(
    null === revelations_editorial_ai_generation_profile( '' ),
    'missing section is rejected'
);

revelations_profile_test(
    null === revelations_editorial_ai_generation_profile(
        'unknown'
    ),
    'unknown section is rejected'
);

revelations_profile_test(
    null === revelations_editorial_ai_generation_profile(
        'podcast'
    ),
    'legacy podcast section is rejected'
);

revelations_profile_test(
    null === revelations_editorial_ai_generation_profile(
        'News'
    ),
    'registry has no normalized or default fallback'
);

revelations_profile_test(
    str_contains(
        $generation_function,
        "'unsupported_generation_section'"
    ),
    'unsupported source section returns the required error code'
);

revelations_profile_test_before(
    $generation_function,
    '$current_section = (string) get_post_meta(',
    '$source_text = trim(',
    'source section is checked before source text is read'
);

revelations_profile_test_before(
    $generation_function,
    "'unsupported_generation_section'",
    '$is_regeneration =',
    'unsupported section guard precedes regeneration metadata reads'
);

revelations_profile_test_before(
    $generation_function,
    "'unsupported_generation_section'",
    '$settings = function_exists(',
    'unsupported section guard precedes generation settings reads'
);

revelations_profile_test_before(
    $generation_function,
    "'unsupported_generation_section'",
    'wp_remote_post(',
    'unsupported section guard precedes the OpenAI request'
);

revelations_profile_test(
    str_contains(
        $generation_function,
        '$profile_prompt ='
    ) &&
    str_contains(
        $generation_function,
        '$profile_prompt .' . "\n"
    ) &&
    ! str_contains(
        $generation_function,
        'Choose the most appropriate section from:'
    ),
    'generation prompt uses the selected profile without a section chooser'
);

$global_prompt_fragments = array(
    'using only facts explicitly contained',
    'Do not invent facts, quotations, dates, statistics',
    "Editorial policy:\\n",
    "\\n\\nTone of voice:\\n",
    "\\n\\nPreferred structure:\\n",
    "\\n\\nBanned phrases, one per line:\\n",
);

foreach ( $global_prompt_fragments as $fragment ) {
    revelations_profile_test(
        str_contains(
            $generation_function,
            $fragment
        ),
        'global prompt rule remains: ' . $fragment
    );
}

revelations_profile_test(
    str_contains(
        $generation_function,
        '$response_section =' . "\n" .
        "        (string) \$article['section'];"
    ) &&
    str_contains(
        $generation_function,
        '$response_section !== $current_section'
    ),
    'legacy response section is compared as an exact raw string'
);

revelations_profile_test(
    str_contains(
        $generation_function,
        "'generation_section_mismatch'"
    ),
    'legacy section mismatch returns a dedicated validation error'
);

foreach (
    array(
        'revelations_editorial_ai_article_word_count(',
        'revelations_editorial_ai_create_version_backup(',
        'wp_update_post(',
        'wp_set_post_categories(',
        'update_post_meta(',
    ) as $mutation_or_later_validation
) {
    revelations_profile_test_before(
        $generation_function,
        "'generation_section_mismatch'",
        $mutation_or_later_validation,
        'section mismatch blocks before ' .
        $mutation_or_later_validation
    );
}

revelations_profile_test(
    str_contains(
        $generation_source,
        "'section' => array("
    ) &&
    str_contains(
        $generation_source,
        "'podcast',"
    ),
    'temporary legacy response schema remains present and unchanged'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

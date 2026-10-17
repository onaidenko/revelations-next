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
    'art',
    'unspoken',
);

revelations_profile_test(
    $expected_sections === array_keys( $profiles ),
    'registry contains exactly the six supported sections'
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
            'immutable server context'
        ) &&
        str_contains(
            $prompt,
            'not part of the model output'
        ),
        $section . ' prompt keeps source section server-controlled'
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

$word_limit_settings = array(
    'article_length_min' => 350,
    'article_length_max' => 600,
);
$art_profile = revelations_editorial_ai_generation_profile( 'art' );
$news_profile = revelations_editorial_ai_generation_profile( 'news' );
$art_word_limits = revelations_editorial_ai_generation_word_limits(
    is_array( $art_profile ) ? $art_profile : array(),
    $word_limit_settings
);
$news_word_limits = revelations_editorial_ai_generation_word_limits(
    is_array( $news_profile ) ? $news_profile : array(),
    $word_limit_settings
);

revelations_profile_test(
    250 === $art_word_limits['minimum'] &&
    350 === $art_word_limits['preferred_minimum'] &&
    600 === $art_word_limits['maximum'] &&
    revelations_editorial_ai_generation_word_count_is_valid(
        280,
        $art_word_limits
    ),
    'Art accepts an evidence-complete 280-word article while retaining a 350-word preferred floor'
);

revelations_profile_test(
    ! revelations_editorial_ai_generation_word_count_is_valid(
        249,
        $art_word_limits
    ),
    'Art rejects a 249-word article below its hard minimum'
);

revelations_profile_test(
    350 === $news_word_limits['minimum'] &&
    ! revelations_editorial_ai_generation_word_count_is_valid(
        280,
        $news_word_limits
    ),
    'News retains its existing 350-word minimum and rejects 280 words'
);

revelations_profile_test(
    str_contains(
        $generation_function,
        "if ( 'art' === \$current_section )"
    ) &&
    str_contains(
        $generation_function,
        'where the material supports it. Do not pad the article merely to reach a target.'
    ) &&
    str_contains(
        $generation_function,
        'revelations_editorial_ai_generation_word_count_is_valid('
    ),
    'Art uses its profile word floor in server validation and receives no-padding target guidance'
);

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
    ! str_contains(
        $generation_function,
        "\$article['section']"
    ) &&
    ! str_contains(
        $generation_function,
        'generation_section_mismatch'
    ),
    'generation path no longer reads a legacy model section'
);

revelations_profile_test(
    ! str_contains(
        $generation_source,
        "'section' => array("
    ),
    'model output schema has no source section field'
);

revelations_profile_test(
    ! str_contains(
        $generation_function,
        'get_category_by_slug('
    ) &&
    ! str_contains(
        $generation_function,
        'wp_set_post_categories('
    ),
    'generation keeps the existing WordPress category'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

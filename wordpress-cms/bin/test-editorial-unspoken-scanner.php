<?php
/**
 * Isolated diagnostics for the Unspoken scanner policy and integration.
 *
 * No WordPress bootstrap, feeds, database, candidates or network are used.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

function add_action( mixed ...$arguments ): void {
}

function get_option(
    string $option,
    mixed $default = false
): mixed {
    return $default;
}

function sanitize_key( mixed $value ): string {
    return preg_replace(
        '/[^a-z0-9_\\-]/',
        '',
        strtolower( (string) $value )
    ) ?? '';
}

function sanitize_text_field( mixed $value ): string {
    return trim( strip_tags( (string) $value ) );
}

function wp_unslash( mixed $value ): mixed {
    return $value;
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

$settings_file =
    $plugin_dir .
    '/revelations-editorial-scanner-settings.php';
$scanner_file =
    $plugin_dir .
    '/revelations-editorial-unspoken-scanner.php';
$engine_file =
    $plugin_dir .
    '/revelations-editorial-scanner-engine.php';

require_once $settings_file;
require_once $scanner_file;

$settings_source =
    file_get_contents( $settings_file );
$scanner_source =
    file_get_contents( $scanner_file );
$engine_source =
    file_get_contents( $engine_file );

if (
    false === $settings_source ||
    false === $scanner_source ||
    false === $engine_source
) {
    fwrite(
        STDERR,
        "FAIL: unable to read Unspoken sources\n"
    );
    exit( 1 );
}

$passed = 0;
$failed = 0;

/**
 * Record one result.
 */
function revelations_unspoken_test(
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
 * Return a current synthetic RSS story.
 *
 * @return array<string, mixed>
 */
function revelations_unspoken_story(
    string $title,
    string $summary,
    int $age_hours = 2
): array {
    return array(
        'title' => $title,
        'summary' => $summary,
        'published_timestamp' =>
            time() -
            $age_hours * HOUR_IN_SECONDS,
        'published_at' =>
            gmdate(
                'c',
                time() -
                $age_hours * HOUR_IN_SECONDS
            ),
        'source_name' => 'Synthetic Source',
        'source_url' => 'https://example.test/story',
        'duplicate_key' => 'synthetic-key',
    );
}

$defaults =
    revelations_editorial_default_scanner_settings();
$profile =
    $defaults['unspoken'] ?? array();

revelations_unspoken_test(
    false === ( $profile['enabled'] ?? null ),
    'Unspoken profile is disabled by default'
);

$source_names = array_column(
    $profile['active_sources'] ?? array(),
    'name'
);

revelations_unspoken_test(
    array(
        'MIT Technology Review',
        'WIRED',
        'BBC Technology',
        'The Verge',
    ) === $source_names,
    'source pool contains exactly the four approved media'
);

revelations_unspoken_test(
    array(
        'https://www.technologyreview.com/feed/',
        'https://www.wired.com/feed/rss',
        'https://feeds.bbci.co.uk/news/technology/rss.xml',
        'https://www.theverge.com/rss/index.xml',
    ) === array_column(
        $profile['active_sources'] ?? array(),
        'url'
    ),
    'source pool retains the approved feed URLs'
);

$strictest_existing_total = 0.0;

foreach (
    array(
        'news',
        'people',
        'tech',
        'places',
    ) as $section
) {
    foreach (
        $defaults[ $section ]['thresholds'] ?? array()
        as $values
    ) {
        if (
            is_array( $values ) &&
            isset( $values['total_score'] )
        ) {
            $strictest_existing_total = max(
                $strictest_existing_total,
                (float) $values['total_score']
            );
        }
    }
}

$unspoken_total = (float) (
    $profile['thresholds']['unspoken_signal'][
        'total_score'
    ] ?? 0
);

revelations_unspoken_test(
    5.2 === $unspoken_total &&
    $unspoken_total >= $strictest_existing_total,
    'Unspoken total threshold 5.2 exceeds the strictest existing 4.8 threshold'
);

$fallback =
    revelations_editorial_unspoken_scan_dry_run( 5 );

revelations_unspoken_test(
    'unspoken' === ( $fallback['section'] ?? '' ) &&
    4 === count(
        $fallback['sources_failed'] ?? array()
    ) &&
    0 === ( $fallback['candidates_created'] ?? -1 ) &&
    0 === ( $fallback['run_logs_created'] ?? -1 ),
    'missing shared engine returns safe source diagnostics without writes'
);

require_once $engine_file;

$track_cases = array(
    'documented_harm' =>
        revelations_unspoken_story(
            'Published research found OpenAI AI system bias harmed users',
            'Researchers found discrimination in the AI system. ' .
            'Published research reported harm to users and public rights ' .
            'after a documented review of the deployed service.'
        ),

    'failure_or_reversal' =>
        revelations_unspoken_story(
            'Google withdrew failed AI system after major outage',
            'A company statement confirmed Google withdrew the AI system ' .
            'after a shutdown and outage harmed users. The reversal ' .
            'affected public access to the service.'
        ),

    'economic_model_failure' =>
        revelations_unspoken_story(
            'Microsoft reported AI service losses and hidden cost',
            'A company statement confirmed the AI business model was ' .
            'unprofitable. Microsoft reported losses and a hidden cost ' .
            'affecting millions in the wider market.'
        ),

    'legal_or_governance_conflict' =>
        revelations_unspoken_story(
            'Regulator filed lawsuit over harm from OpenAI AI service',
            'A court filing said the AI service harmed users and public ' .
            'rights. The regulator launched an investigation and filed ' .
            'a complaint naming OpenAI.'
        ),

    'labor_or_social_cost' =>
        revelations_unspoken_story(
            'Amazon announced AI layoffs and job losses',
            'A company statement confirmed Amazon laid off workers after ' .
            'introducing the AI system. The layoffs, job losses and ' .
            'workplace surveillance affected workers and jobs.'
        ),
);

foreach ( $track_cases as $track => $story ) {
    $result =
        revelations_editorial_score_unspoken_story(
            $story
        );

    revelations_unspoken_test(
        is_array( $result ) &&
        true === ( $result['qualified'] ?? false ) &&
        $track === (
            $result['editorial_track'] ?? ''
        ),
        $track . ' qualifies through its explicit track'
    );
}

$generic_opinion =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Opinion: AI could harm society',
            'Opinion: this commentary predicts a future risk from AI ' .
            'without reporting a new event, official document or ' .
            'attributed evidence.'
        )
    );

revelations_unspoken_test(
    true === (
        $generic_opinion['hard_rejected'] ?? false
    ) &&
    'opinion_or_advice' === (
        $generic_opinion['rejection_code'] ?? ''
    ),
    'generic opinion is hard-rejected'
);

$speculation =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'AI systems may create future harm',
            'Experts fear AI could potentially create a future risk. ' .
            'The hypothetical scenario contains no confirmed event or ' .
            'published evidence.'
        )
    );

revelations_unspoken_test(
    true === (
        $speculation['hard_rejected'] ?? false
    ) &&
    'speculation_or_prediction' === (
        $speculation['rejection_code'] ?? ''
    ),
    'prediction and speculation without an event are hard-rejected'
);

$promotional =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Sponsored: OpenAI responds to AI safety concerns',
            'Sponsored partner content announced a system and discussed ' .
            'harm to users in promotional language from a press release.'
        )
    );

revelations_unspoken_test(
    true === (
        $promotional['hard_rejected'] ?? false
    ) &&
    'promotional' === (
        $promotional['rejection_code'] ?? ''
    ),
    'promotional and sponsored material is hard-rejected'
);

$anonymous_allegation =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Anonymous sources alleged AI harm at a startup',
            'Unnamed sources alleged the AI system caused harm to users. ' .
            'The rumor contains no official document, filing, response, ' .
            'published research or named participant.'
        )
    );

revelations_unspoken_test(
    true === (
        $anonymous_allegation['hard_rejected'] ?? false
    ) &&
    'allegation_without_attribution' === (
        $anonymous_allegation['rejection_code'] ?? ''
    ),
    'anonymous unsupported allegation is hard-rejected'
);

$attributed_allegation =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Alice Smith filed complaint alleging OpenAI AI harm',
            'Alice Smith filed a complaint after the AI system harmed ' .
            'users and public rights. “The system caused documented ' .
            'harm,” Alice Smith said in the attributed account.'
        )
    );

revelations_unspoken_test(
    is_array( $attributed_allegation ) &&
    true === (
        $attributed_allegation['qualified']
        ?? false
    ) &&
    true === (
        $attributed_allegation[
            'single_source_allegation'
        ] ?? false
    ) &&
    true === (
        $attributed_allegation[
            'requires_reputational_review'
        ] ?? false
    ) &&
    'direct_attributed_quote' === (
        $attributed_allegation['evidence_type']
        ?? ''
    ),
    'attributed single-source allegation remains preview-only with safeguards'
);

$strict_threshold_story =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'OpenAI reported AI harm',
            'OpenAI reported harm connected to AI. The named company ' .
            'described an event but the account contains no ' .
            'additional significance or impact signal for qualification.'
        )
    );

revelations_unspoken_test(
    is_array( $strict_threshold_story ) &&
    false === (
        $strict_threshold_story['qualified']
        ?? true
    ) &&
    (float) (
        $strict_threshold_story['total_score']
        ?? 10
    ) < 5.2,
    'mandatory gates do not bypass the strict total threshold'
);

$stale =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Published research found OpenAI AI system bias harmed users',
            'Published research reported discrimination and harm to ' .
            'users and public rights after a confirmed review.',
            240
        )
    );

revelations_unspoken_test(
    true === (
        $stale['hard_rejected'] ?? false
    ) &&
    'stale_story' === (
        $stale['rejection_code'] ?? ''
    ),
    'stale story is hard-rejected'
);

$headline_only =
    revelations_editorial_score_unspoken_story(
        revelations_unspoken_story(
            'Shocking AI disaster at OpenAI',
            'OpenAI reported harm.'
        )
    );

revelations_unspoken_test(
    true === (
        $headline_only['hard_rejected'] ?? false
    ) &&
    'headline_only_sensationalism' === (
        $headline_only['rejection_code'] ?? ''
    ),
    'headline-only sensationalism is hard-rejected'
);

$ordinary_problem =
    revelations_unspoken_story(
        'Company reports losses after an outage',
        'A company statement confirmed losses and harm to users after ' .
        'an outage, but artificial intelligence is not part of the event.'
    );
$ordinary_gate =
    revelations_editorial_scanner_ai_gate(
        $ordinary_problem
    );

revelations_unspoken_test(
    empty( $ordinary_gate['qualified'] ),
    'ordinary company problem without central AI relevance fails the global gate'
);

foreach (
    array(
        'tech' =>
            revelations_unspoken_story(
                'Published research found OpenAI AI model bias harmed users',
                'Published research reported the AI model and system ' .
                'harmed users and public rights after researchers found ' .
                'discrimination in the technical capability.'
            ),
        'people' =>
            revelations_unspoken_story(
                'OpenAI CEO announced response after AI harm',
                'The CEO announced an official response after the AI ' .
                'system harmed users. A company statement confirmed ' .
                'the founder-led action affected public rights.'
            ),
        'news' =>
            revelations_unspoken_story(
                'Regulator announced investigation into OpenAI harm',
                'The regulator announced and launched an investigation ' .
                'after OpenAI AI harmed users and public rights. A ' .
                'regulator statement named the company.'
            ),
        'places' =>
            revelations_unspoken_story(
                'Hospital disclosed harm from deployed OpenAI AI',
                'An official report disclosed the AI system harmed ' .
                'patients at the hospital. The documented event affected ' .
                'public safety in the physical location.'
            ),
    ) as $secondary => $story
) {
    $result =
        revelations_editorial_score_unspoken_story(
            $story
        );

    revelations_unspoken_test(
        is_array( $result ) &&
        true === ( $result['qualified'] ?? false ) &&
        $secondary === (
            $result['secondary_section'] ?? ''
        ) &&
        '' !== (
            $result['editorial_track'] ?? ''
        ),
        'central negative angle keeps Unspoken primary with ' .
            $secondary .
            ' advisory'
    );
}

$ai_story = $track_cases['documented_harm'];
$ai_gate =
    revelations_editorial_scanner_ai_gate(
        $ai_story
    );
$ai_score = ! empty( $ai_gate['qualified'] )
    ? revelations_editorial_score_unspoken_story(
        $ai_story
    )
    : null;

revelations_unspoken_test(
    ! empty( $ai_gate['qualified'] ) &&
    is_array( $ai_score ) &&
    ! empty( $ai_score['qualified'] ),
    'global AI gate and strict Unspoken scorer both qualify a valid story'
);

$gate_position = strpos(
    $engine_source,
    'revelations_editorial_scanner_ai_gate('
);
$score_position = strpos(
    $engine_source,
    'call_user_func(',
    false === $gate_position
        ? 0
        : $gate_position
);

revelations_unspoken_test(
    false !== $gate_position &&
    false !== $score_position &&
    $gate_position < $score_position,
    'shared engine invokes the global AI gate before section scoring'
);

revelations_unspoken_test(
    str_contains(
        $engine_source,
        'if ( is_wp_error( $feed ) )'
    ) &&
    str_contains(
        $engine_source,
        "'success' =>\n                    false"
    ) &&
    str_contains(
        $engine_source,
        '$sources_failed[] ='
    ) &&
    str_contains(
        $engine_source,
        'continue;'
    ),
    'feed failures produce source diagnostics and continue safely'
);

revelations_unspoken_test(
    str_contains(
        $scanner_source,
        "'documented_harm' =>"
    ) &&
    str_contains(
        $scanner_source,
        "'failure_or_reversal' =>"
    ) &&
    str_contains(
        $scanner_source,
        "'economic_model_failure' =>"
    ) &&
    str_contains(
        $scanner_source,
        "'legal_or_governance_conflict' =>"
    ) &&
    str_contains(
        $scanner_source,
        "'labor_or_social_cost' =>"
    ) &&
    ! str_contains(
        $scanner_source,
        "'general_unspoken' =>"
    ),
    'scanner defines exactly the five named policy tracks without a catch-all'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

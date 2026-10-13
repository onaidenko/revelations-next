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

function esc_url_raw(
    mixed $url,
    array $protocols = array()
): string {
    $url = trim( (string) $url );

    if (
        array() !== $protocols &&
        ! str_starts_with( $url, 'https://' )
    ) {
        return '';
    }

    return $url;
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
        'TechCrunch',
        'TechCrunch AI',
        'WIRED',
        'The Verge',
        '404 Media',
        'Rest of World',
        'MIT Technology Review',
        'BBC Technology',
        'The Markup',
        'EFF Deeplinks',
        'Grist',
        'Smart Cities Dive',
    ) === $source_names,
    'source pool contains the approved accountability reporting sources'
);

revelations_unspoken_test(
    array(
        'https://techcrunch.com/feed/',
        'https://techcrunch.com/category/artificial-intelligence/feed/',
        'https://www.wired.com/feed/rss',
        'https://www.theverge.com/rss/index.xml',
        'https://www.404media.co/rss/',
        'https://restofworld.org/feed/latest/',
        'https://www.technologyreview.com/feed/',
        'https://feeds.bbci.co.uk/news/technology/rss.xml',
        'https://themarkup.org/feeds/rss.xml',
        'https://www.eff.org/rss/updates.xml',
        'https://grist.org/feed/',
        'https://www.smartcitiesdive.com/feeds/news/',
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
    12 === count(
        $fallback['sources_failed'] ?? array()
    ) &&
    0 === ( $fallback['candidates_created'] ?? -1 ) &&
    0 === ( $fallback['run_logs_created'] ?? -1 ),
    'missing shared engine returns safe source diagnostics without writes'
);

require_once $engine_file;

$snapshot_router_cases = array(
    'data-center accountability' => revelations_unspoken_story(
        'AI data center deals face scrutiny over NDAs',
        'Amazon disclosed a data center deal after community opponents asked for accountability.'
    ),
    'police location inference' => revelations_unspoken_story(
        'AI company lets police infer a location from an interior photo',
        'A report describes police use of AI location inference and surveillance.'
    ),
    'documented public-process failure' => revelations_unspoken_story(
        'AI agent gave police a fake tip in an active case',
        'The reported failure affected a public investigation.'
    ),
    'safety researcher retaliation' => revelations_unspoken_story(
        'AI safety researchers say they were fired after raising concerns',
        'Workers described retaliation linked to the company safety process.'
    ),
    'court AI evidence' => revelations_unspoken_story(
        'Court examines AI-generated evidence after a disputed ruling',
        'A court report describes an incident involving AI-generated evidence.'
    ),
);

foreach ( $snapshot_router_cases as $label => $story ) {
    $routing = revelations_editorial_unspoken_snapshot_routing( $story );
    revelations_unspoken_test(
        ! empty( $routing['plausible'] ) &&
        array() !== ( $routing['families'] ?? array() ) &&
        array() !== ( $routing['anchors'] ?? array() ),
        'snapshot router selects ' . $label
    );
}

$opinion_fetches = 0;
$opinion_state = array(
    'attempts' => 0,
    'max_attempts' => 8,
);
$opinion_with_signals =
    revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'Opinion: AI data center privacy risks need attention',
            'This commentary considers privacy and community concerns ' .
            'around an Amazon data center.'
        ),
        $opinion_state,
        static function ( string $url ) use ( &$opinion_fetches ): array {
            $opinion_fetches++;
            return array(
                'ok' => true,
                'status' => 'fetched',
                'text' => 'This snapshot should never be fetched.',
            );
        }
    );

revelations_unspoken_test(
    0 === $opinion_fetches &&
    0 === $opinion_state['attempts'] &&
    'not_needed' === (
        $opinion_with_signals['snapshot_status'] ?? ''
    ),
    'hard-rejected opinion is never sent to the snapshot reader'
);

foreach (
    array(
        'generic safety essay' => revelations_unspoken_story(
            'Why AI safety matters',
            'An essay considers risk and safety in the future.'
        ),
        'regulatory opinion' => revelations_unspoken_story(
            'Opinion: Governments should regulate AI',
            'An opinion argues for future rules without a concrete event.'
        ),
        'generic explainer' => revelations_unspoken_story(
            'What could go wrong with AI?',
            'A guide discusses hypothetical future risk.'
        ),
        'ordinary product story' => revelations_unspoken_story(
            'Company launches a new AI assistant',
            'The product release adds features for customers.'
        ),
    ) as $label => $story
) {
    revelations_unspoken_test(
        empty(
            revelations_editorial_unspoken_snapshot_routing(
                $story
            )['plausible']
        ),
        'snapshot router does not default-fetch ' . $label
    );
}

$snapshot_fetches = 0;
$snapshot_state = array(
    'attempts' => 0,
    'max_attempts' => 8,
);
$snapshot_eligible =
    revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'AI company lets police infer locations from interior photos',
            'A report describes an AI privacy and surveillance concern.'
        ),
        $snapshot_state,
        static function ( string $url ) use ( &$snapshot_fetches ): array {
            $snapshot_fetches++;
            return array(
                'ok' => true,
                'status' => 'fetched',
                'text' =>
                    'A court filing said the system enabled surveillance ' .
                    'and tracking of residents. A regulator said the AI ' .
                    'system harmed public privacy. NONPERSISTENTBODYMARKER',
            );
        }
    );

revelations_unspoken_test(
    1 === $snapshot_fetches &&
    1 === $snapshot_state['attempts'] &&
    true === ( $snapshot_eligible['qualified'] ?? false ) &&
    'fetched' === ( $snapshot_eligible['snapshot_status'] ?? '' ) &&
    ! str_contains(
        (string) json_encode( $snapshot_eligible ),
        'NONPERSISTENTBODYMARKER'
    ),
    'successful bounded snapshot confirms existing angle and evidence without returning body text'
);

$angle_state = array(
    'attempts' => 0,
    'max_attempts' => 8,
);
$angle_not_confirmed =
    revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'AI data center operator faces questions over an NDA',
            'Community opponents ask for accountability over the data center deal.'
        ),
        $angle_state,
        static fn( string $url ): array => array(
            'ok' => true,
            'status' => 'fetched',
            'text' =>
                'The company described a routine construction schedule ' .
                'for its new facility and announced an opening date.',
        )
    );

revelations_unspoken_test(
    true === ( $angle_not_confirmed['hard_rejected'] ?? false ) &&
    'insufficient_unspoken_angle' === (
        $angle_not_confirmed['rejection_code'] ?? ''
    ) &&
    'angle_not_confirmed' === (
        $angle_not_confirmed['snapshot_status'] ?? ''
    ),
    'successful snapshot without an existing Unspoken angle remains rejected'
);

$evidence_state = array(
    'attempts' => 0,
    'max_attempts' => 8,
);
$evidence_not_confirmed =
    revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'AI system lets police infer locations from photos',
            'Privacy and surveillance concerns affect residents.'
        ),
        $evidence_state,
        static fn( string $url ): array => array(
            'ok' => true,
            'status' => 'fetched',
            'text' =>
                'The system enables surveillance and tracking of residents ' .
                'through location inference without a named source.',
        )
    );

revelations_unspoken_test(
    true === ( $evidence_not_confirmed['hard_rejected'] ?? false ) &&
    'insufficient_evidence' === (
        $evidence_not_confirmed['rejection_code'] ?? ''
    ) &&
    'evidence_not_confirmed' === (
        $evidence_not_confirmed['snapshot_status'] ?? ''
    ),
    'snapshot without attributable evidence remains unverified and rejected'
);

foreach (
    array( 'paywalled', 'timeout', 'too_short', 'fetch_failed' ) as $status
) {
    $failure_state = array(
        'attempts' => 0,
        'max_attempts' => 8,
    );
    $failure = revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'AI data center deal faces NDA accountability questions',
            'Community opponents seek disclosure from Amazon over the deal.'
        ),
        $failure_state,
        static fn( string $url ): array => array(
            'ok' => false,
            'status' => $status,
            'text' => '',
        )
    );

    revelations_unspoken_test(
        true === ( $failure['hard_rejected'] ?? false ) &&
        'insufficient_evidence' === (
            $failure['rejection_code'] ?? ''
        ) &&
        $status === ( $failure['snapshot_status'] ?? '' ),
        'unverified ' . $status . ' snapshot never qualifies'
    );
}

$budget_state = array(
    'attempts' => 0,
    'max_attempts' => 8,
);
$budget_fetches = 0;
for ( $index = 0; $index < 9; $index++ ) {
    revelations_editorial_score_unspoken_story_with_snapshot(
        revelations_unspoken_story(
            'AI data center deal faces NDA accountability questions',
            'Community opponents seek disclosure from Amazon over the deal.'
        ),
        $budget_state,
        static function ( string $url ) use ( &$budget_fetches ): array {
            $budget_fetches++;
            return array(
                'ok' => false,
                'status' => 'too_short',
                'text' => '',
            );
        }
    );
}

revelations_unspoken_test(
    8 === $budget_fetches &&
    8 === $budget_state['attempts'],
    'Unspoken scanner caps ephemeral snapshot attempts at eight per run'
);

$reader_file =
    $plugin_dir . '/revelations-editorial-source-reader.php';
$reader_source = file_get_contents( $reader_file );

revelations_unspoken_test(
    false !== $reader_source &&
    str_contains( $reader_source, "'timeout' => 8" ) &&
    str_contains( $reader_source, "'redirection' => 2" ) &&
    str_contains(
        $reader_source,
        "'limit_response_size' => 768 * KB_IN_BYTES"
    ) &&
    str_contains( $reader_source, "mb_substr( \$text, 0, 8000" ) &&
    str_contains(
        $reader_source,
        'function revelations_editorial_fetch_scanner_snapshot'
    ) &&
    ! str_contains(
        substr(
            $reader_source,
            (int) strpos(
                $reader_source,
                'function revelations_editorial_fetch_scanner_snapshot'
            )
        ),
        'update_post_meta('
    ),
    'scanner snapshot reader is bounded and has no persistence write path'
);

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

$unspoken_future_cases = array(
    'hidden human operators' => revelations_unspoken_story(
        'Humanoid robots marketed as autonomous still depend on remote human operators',
        'Published research found that the robots require continuous human supervision from remote operators.'
    ),
    'hidden manual labor' => revelations_unspoken_story(
        'AI automation creates a new layer of hidden manual labor',
        'Researchers found content moderation and labeling workers supervise the automated system.'
    ),
    'human-machine relationship' => revelations_unspoken_story(
        'Research shows people forming emotional relationships with AI agents',
        'A peer-reviewed study found that users form emotional relationships with AI companions.'
    ),
    'autonomy gap' => revelations_unspoken_story(
        'Robotaxis create unexpected changes in city behavior',
        'Published research found an unexpected city behavior and new human supervision needs.'
    ),
);

foreach ( $unspoken_future_cases as $label => $story ) {
    $gate = revelations_editorial_scanner_ai_gate( $story, 'unspoken' );
    $score = ! empty( $gate['qualified'] )
        ? revelations_editorial_score_unspoken_story( $story )
        : null;

    revelations_unspoken_test(
        in_array(
            $gate['gate_branch'] ?? '',
            array( 'ai', 'future_tech' ),
            true
        ) &&
        true === ( $score['qualified'] ?? false ),
        'future-tech Unspoken qualifies ' . $label
    );
}

$generic_negative_story = revelations_unspoken_story(
    'AI startup loses money',
    'A company statement reported losses after a difficult quarter.'
);
$generic_negative_gate = revelations_editorial_scanner_ai_gate(
    $generic_negative_story,
    'unspoken'
);
$generic_negative = revelations_editorial_score_unspoken_story(
    $generic_negative_story
);

revelations_unspoken_test(
    empty( $generic_negative_gate['qualified'] ) ||
    'generic_negative_news' === (
        $generic_negative['rejection_code'] ?? ''
    ),
    'ordinary negative business news without an Unspoken angle is rejected'
);

$neural_privacy = revelations_editorial_unspoken_angle(
    'Brain-interface companies seek access to neural data and ownership of thoughts.'
);
revelations_unspoken_test(
    'privacy_control' === ( $neural_privacy['category'] ?? '' ),
    'neural-data privacy story recognizes a privacy/control angle'
);

$neural_privacy_hard_reject = revelations_editorial_score_unspoken_story(
    revelations_unspoken_story(
        'Brain-interface company seeks access to neural data',
        'The company discusses neural data and ownership of thoughts without an attributed source.'
    )
);
revelations_unspoken_test(
    true === ( $neural_privacy_hard_reject['hard_rejected'] ?? false ) &&
    'insufficient_evidence' === (
        $neural_privacy_hard_reject['rejection_code'] ?? ''
    ) &&
    'privacy_control' === (
        $neural_privacy_hard_reject['section_angle_categories'][0]
        ?? ''
    ),
    'hard Unspoken rejection retains the recognized angle category'
);

$neural_privacy_emerging = revelations_editorial_score_unspoken_story(
    revelations_unspoken_story(
        'Companies seek access to neural data',
        'A company statement describes a concrete neural-data practice and ownership of thoughts.'
    )
);

revelations_unspoken_test(
    true === ( $neural_privacy_emerging['qualified'] ?? false ) &&
    'privacy_control' === (
        $neural_privacy_emerging['section_angle_categories'][0]
        ?? ''
    ) &&
    'official_company_response' === (
        $neural_privacy_emerging['evidence_type'] ?? ''
    ),
    'evidence-backed emerging neural-data issue qualifies without deployment minimum'
);

$hypothetical_neural_fear = revelations_editorial_score_unspoken_story(
    revelations_unspoken_story(
        'Brain interfaces could one day steal our thoughts',
        'A vague opinion considers mental privacy without a named actor or evidence.'
    )
);

revelations_unspoken_test(
    true === ( $hypothetical_neural_fear['hard_rejected'] ?? false ) &&
    'insufficient_evidence' === (
        $hypothetical_neural_fear['rejection_code'] ?? ''
    ),
    'hypothetical neural-data fear without evidence remains rejected'
);

$hidden_operators = revelations_editorial_unspoken_angle(
    'Autonomous robots still require remote human operators and continuous supervision.'
);
revelations_unspoken_test(
    'hidden_labor' === ( $hidden_operators['category'] ?? '' ),
    'hidden human operators recognize an autonomy-related angle'
);

$synthetic_evidence = revelations_editorial_unspoken_angle(
    'Synthetic media and deepfakes cause erosion of visual evidence and authenticity.'
);
revelations_unspoken_test(
    'trust_identity' === ( $synthetic_evidence['category'] ?? '' ),
    'synthetic-media evidence story recognizes a trust/identity angle'
);

$ordinary_privacy = revelations_editorial_score_unspoken_story(
    revelations_unspoken_story(
        'AI company faces a privacy lawsuit',
        'A court filing concerns a routine privacy dispute without neural data or broader technology consequences.'
    )
);
revelations_unspoken_test(
    'generic_negative_news' === (
        $ordinary_privacy['rejection_code'] ?? ''
    ),
    'generic privacy lawsuit without a deeper consequence remains rejected'
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

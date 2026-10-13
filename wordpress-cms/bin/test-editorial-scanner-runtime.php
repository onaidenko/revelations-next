<?php
/**
 * Isolated diagnostics for scanner settings and rejection reporting.
 *
 * No WordPress bootstrap, network, API or database is used.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPINC', 'wp-includes' );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['scanner_test_option'] = array();
$GLOBALS['scanner_test_updates'] = 0;

function add_action( mixed ...$arguments ): void {
}

function get_option(
    string $option,
    mixed $default = false
): mixed {
    return $GLOBALS['scanner_test_option'] ?? $default;
}

function update_option(
    string $option,
    mixed $value,
    bool $autoload = false
): bool {
    $GLOBALS['scanner_test_updates']++;
    $GLOBALS['scanner_test_option'] = $value;

    return true;
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

function esc_url_raw( mixed $value ): string {
    return filter_var(
        (string) $value,
        FILTER_VALIDATE_URL
    )
        ? (string) $value
        : '';
}

function wp_strip_all_tags(
    mixed $value,
    bool $remove_breaks = false
): string {
    return strip_tags( (string) $value );
}

function wp_unslash( mixed $value ): mixed {
    return $value;
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

function wp_json_encode(
    mixed $value,
    int $flags = 0
): string|false {
    return json_encode( $value, $flags );
}

function is_wp_error( mixed $value ): bool {
    return false;
}

final class RevelationsScannerTestWpdb {
    public string $postmeta = 'wp_postmeta';
    public string $posts = 'wp_posts';

    /** @return string[] */
    public function get_col( string $query ): array {
        return array();
    }
}

final class RevelationsScannerTestItem {
    public function __construct(
        private string $title,
        private string $summary,
        private string $url,
        private int $timestamp
    ) {
    }

    public function get_title(): string {
        return $this->title;
    }

    public function get_permalink(): string {
        return $this->url;
    }

    public function get_description(): string {
        return $this->summary;
    }

    public function get_content(): string {
        return '';
    }

    public function get_date( string $format ): int {
        return $this->timestamp;
    }
}

final class RevelationsScannerTestFeed {
    /** @param RevelationsScannerTestItem[] $items */
    public function __construct(
        private array $items
    ) {
    }

    public function get_item_quantity( int $limit ): int {
        return min( $limit, count( $this->items ) );
    }

    /** @return RevelationsScannerTestItem[] */
    public function get_items(
        int $offset,
        int $quantity
    ): array {
        return array_slice(
            $this->items,
            $offset,
            $quantity
        );
    }
}

function fetch_feed( string $url ): RevelationsScannerTestFeed {
    return $GLOBALS['scanner_test_feed'];
}

$plugin_dir = dirname( __DIR__ ) . '/mu-plugins';

require_once $plugin_dir .
    '/revelations-editorial-scanner-settings.php';
require_once $plugin_dir .
    '/revelations-editorial-scanner-engine.php';
require_once $plugin_dir .
    '/revelations-editorial-news-scanner.php';
require_once $plugin_dir .
    '/revelations-editorial-people-scanner.php';
require_once $plugin_dir .
    '/revelations-editorial-tech-scanner.php';
require_once $plugin_dir .
    '/revelations-editorial-places-scanner.php';
require_once $plugin_dir .
    '/revelations-editorial-unspoken-scanner.php';
require_once $plugin_dir .
    '/revelations-editorial-logs.php';
require_once $plugin_dir .
    '/revelations-editorial-storage.php';

$GLOBALS['wpdb'] =
    new RevelationsScannerTestWpdb();

$passed = 0;
$failed = 0;

function revelations_scanner_runtime_test(
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
 * Return one current synthetic story.
 *
 * @return array<string, mixed>
 */
function revelations_scanner_runtime_story(
    string $title,
    string $summary
): array {
    return array(
        'title' => $title,
        'summary' => $summary,
        'published_timestamp' => time() - HOUR_IN_SECONDS,
        'published_at' => gmdate(
            'c',
            time() - HOUR_IN_SECONDS
        ),
        'source_name' => 'Synthetic Source',
        'source_url' => 'https://example.test/story',
        'duplicate_key' => md5( $title ),
    );
}

$defaults =
    revelations_editorial_default_scanner_settings();

/** @return string[] */
function revelations_scanner_runtime_source_names( array $profile ): array {
    return array_values(
        array_filter(
            array_map(
                static fn ( mixed $source ): string =>
                    is_array( $source )
                        ? (string) ( $source['name'] ?? '' )
                        : '',
                is_array( $profile['active_sources'] ?? null )
                    ? $profile['active_sources']
                    : array()
            )
        )
    );
}

$news_sources = revelations_scanner_runtime_source_names(
    $defaults['news']
);
$people_sources = revelations_scanner_runtime_source_names(
    $defaults['people']
);
$places_sources = revelations_scanner_runtime_source_names(
    $defaults['places']
);
$unspoken_sources = revelations_scanner_runtime_source_names(
    $defaults['unspoken']
);

revelations_scanner_runtime_test(
    array(
        'TechCrunch',
        'TechCrunch AI',
        'The Robot Report',
        'Smart Cities Dive',
        'FreightWaves Autonomous Trucking',
        'Dubai Media Office',
        'The Verge',
        'WIRED',
        'MIT Technology Review',
        'BBC Technology',
        'IEEE Spectrum Robotics',
        'Global Construction Review',
    ) === $news_sources &&
    array(
        'TechCrunch',
        'TechCrunch AI',
        'The Verge',
        'The Robot Report',
        'TechCrunch Startups',
        'WIRED',
        'MIT Technology Review',
        'IEEE Spectrum Robotics',
        'Ars Technica',
        'Nature Machine Learning',
        'Quanta Magazine',
    ) === $people_sources &&
    array(
        'Dubai Media Office',
        'Smart Cities Dive',
        'Cities Today',
        'The Robot Report',
        'FreightWaves Autonomous Trucking',
        'Construction Dive',
        'Global Construction Review',
        'Designboom',
    ) === $places_sources,
    'News, People and Places pools match the approved discovery configuration'
);

revelations_scanner_runtime_test(
    in_array( 'FreightWaves Autonomous Trucking', $news_sources, true ) &&
    in_array( 'Smart Cities Dive', $news_sources, true ),
    'News discovery pool covers autonomous freight and deployment regulation'
);

revelations_scanner_runtime_test(
    in_array( 'Dubai Media Office', $places_sources, true ) &&
    in_array( 'The Robot Report', $places_sources, true ) &&
    in_array( 'Construction Dive', $places_sources, true ),
    'Places discovery pool covers smart-city mobility, robotics and infrastructure'
);

revelations_scanner_runtime_test(
    in_array( 'TechCrunch AI', $people_sources, true ) &&
    in_array( 'IEEE Spectrum Robotics', $people_sources, true ) &&
    in_array( 'Quanta Magazine', $people_sources, true ) &&
    ! in_array( 'Fast Company Leadership', $people_sources, true ) &&
    ! in_array( 'Entrepreneur Latest', $people_sources, true ),
    'People discovery pool is broad technical reporting without leadership feeds'
);

revelations_scanner_runtime_test(
    in_array( '404 Media', $unspoken_sources, true ) &&
    in_array( 'The Markup', $unspoken_sources, true ) &&
    in_array( 'EFF Deeplinks', $unspoken_sources, true ) &&
    in_array( 'Grist', $unspoken_sources, true ),
    'Unspoken discovery pool covers infrastructure, privacy, accountability and community impact'
);

revelations_scanner_runtime_test(
    array( 'item_limit' => 50, 'freshness_hours' => 72 ) ===
        revelations_editorial_scanner_source_window(
            array( 'discovery_volume' => 'high' )
        ) &&
    array( 'item_limit' => 30, 'freshness_hours' => 168 ) ===
        revelations_editorial_scanner_source_window(
            array( 'discovery_volume' => 'medium' )
        ) &&
    array( 'item_limit' => 20, 'freshness_hours' => 336 ) ===
        revelations_editorial_scanner_source_window(
            array( 'discovery_volume' => 'low' )
        ),
    'discovery volume uses bounded 72-hour, 7-day and 14-day RSS windows'
);

$legacy_coverage = array();
foreach (
    revelations_editorial_legacy_scanner_source_signatures()
    as $section => $signatures
) {
    $legacy_coverage[ $section ] = array(
        'active_sources' => array_map(
            static function ( string $signature ): array {
                $parts = explode( '|', $signature, 2 );
                return array(
                    'name' => $parts[0],
                    'url' => $parts[1],
                );
            },
            $signatures
        ),
    );
}

$coverage_normalized =
    revelations_editorial_normalize_scanner_source_coverage_settings(
        $legacy_coverage,
        $defaults
    );

revelations_scanner_runtime_test(
    12 === count( $coverage_normalized['news']['active_sources'] ?? array() ) &&
    11 === count( $coverage_normalized['people']['active_sources'] ?? array() ) &&
    8 === count( $coverage_normalized['places']['active_sources'] ?? array() ) &&
    12 === count( $coverage_normalized['unspoken']['active_sources'] ?? array() ) &&
    'primary' === ( $coverage_normalized['news']['active_sources'][0]['discovery_priority'] ?? '' ),
    'exact legacy source pools receive the approved discovery update in memory'
);

$coverage_custom = $legacy_coverage;
$coverage_custom['news']['active_sources'][] = array(
    'name' => 'Operator Source',
    'url' => 'https://example.test/feed.xml',
);

revelations_scanner_runtime_test(
    $coverage_custom['news']['active_sources'] === (
        revelations_editorial_normalize_scanner_source_coverage_settings(
            $coverage_custom,
            $defaults
        )['news']['active_sources'] ?? null
    ),
    'source-coverage normalization preserves operator-edited pools'
);

$legacy = array(
    'news' => array( 'sentinel' => 'unchanged' ),
    'unspoken' =>
        revelations_editorial_empty_scanner_profile(),
);
$normalized =
    revelations_editorial_normalize_unspoken_scanner_settings(
        $legacy,
        $defaults
    );

revelations_scanner_runtime_test(
    12 === count(
        $normalized['unspoken']['active_sources'] ?? array()
    ),
    'legacy-empty Unspoken profile receives approved feeds'
);

revelations_scanner_runtime_test(
    false === (
        $normalized['unspoken']['enabled'] ?? null
    ),
    'legacy explicit enabled=false remains false'
);

revelations_scanner_runtime_test(
    $legacy['news'] === $normalized['news'],
    'normalization does not alter other sections'
);

$custom_sources = array(
    array(
        'name' => 'Operator Source',
        'url' => 'https://example.test/feed.xml',
    ),
);
$partial = array(
    'news' => array( 'sentinel' => 'news' ),
    'tech' => array( 'sentinel' => 'tech' ),
    'unspoken' => array(
        'enabled' => false,
        'active_sources' => $custom_sources,
        'keywords' => array(
            'relevance' => array( 'operator signal' ),
        ),
    ),
);
$partial_normalized =
    revelations_editorial_normalize_unspoken_scanner_settings(
        $partial,
        $defaults
    );

revelations_scanner_runtime_test(
    $custom_sources === (
        $partial_normalized['unspoken']['active_sources']
        ?? null
    ),
    'operator source override is preserved'
);

revelations_scanner_runtime_test(
    array( 'operator signal' ) === (
        $partial_normalized['unspoken']['keywords'][
            'relevance'
        ] ?? null
    ) &&
    isset(
        $partial_normalized['unspoken']['keywords'][
            'implementation'
        ]
    ) &&
    isset(
        $partial_normalized['unspoken']['thresholds'][
            'unspoken_signal'
        ]
    ),
    'partial profile fills only missing nested fields'
);

revelations_scanner_runtime_test(
    $partial['news'] ===
        $partial_normalized['news'] &&
    $partial['tech'] ===
        $partial_normalized['tech'],
    'partial normalization leaves News and Tech byte-for-byte unchanged'
);

revelations_scanner_runtime_test(
    $partial_normalized ===
        revelations_editorial_normalize_unspoken_scanner_settings(
            $partial_normalized,
            $defaults
        ),
    'Unspoken normalization is idempotent'
);

$current = array(
    'unspoken' => $defaults['unspoken'],
);

revelations_scanner_runtime_test(
    $current ===
        revelations_editorial_normalize_unspoken_scanner_settings(
            $current,
            $defaults
        ),
    'current Unspoken profile remains unchanged'
);

$GLOBALS['scanner_test_option'] = $legacy;
$GLOBALS['scanner_test_updates'] = 0;
$migration_first =
    revelations_editorial_migrate_unspoken_scanner_settings();
$migration_second =
    revelations_editorial_migrate_unspoken_scanner_settings();

revelations_scanner_runtime_test(
    true === $migration_first &&
    false === $migration_second &&
    1 === $GLOBALS['scanner_test_updates'],
    'explicit migration writes once and is idempotent'
);

$GLOBALS['scanner_test_option'] = $legacy;
$GLOBALS['scanner_test_updates'] = 0;
$runtime_settings =
    revelations_editorial_get_scanner_settings();

revelations_scanner_runtime_test(
    12 === count(
        $runtime_settings['unspoken']['active_sources']
        ?? array()
    ) &&
    0 === $GLOBALS['scanner_test_updates'],
    'ordinary runtime read repairs Unspoken in memory without option write'
);

$all_codes =
    revelations_editorial_scanner_rejection_codes();

foreach ( $all_codes as $scope => $codes ) {
    foreach ( $codes as $code ) {
        revelations_scanner_runtime_test(
            $code === sanitize_key( $code ),
            $scope . ' exposes stable rejection code ' . $code
        );
    }
}

$gate_cases = array(
    'no_ai_signal' => array(
        'Luxury hotel opens',
        'The property adds restaurants and a spa.',
    ),
    'broad_signal_without_technical_context' => array(
        'Autonomous robot begins a route',
        'The robot carries packages across campus.',
    ),
    'no_meaningful_ai_action' => array(
        'OpenAI model overview',
        'A profile of OpenAI and its model.',
    ),
    'ambiguous_product_without_ai_context' => array(
        'Gemini launches a model',
        'Gemini launches a model for teams.',
    ),
);

foreach ( $gate_cases as $expected => $case ) {
    $result = revelations_editorial_scanner_ai_gate(
        array(
            'title' => $case[0],
            'summary' => $case[1],
        )
    );

    revelations_scanner_runtime_test(
        empty( $result['qualified'] ) &&
        $expected === (
            $result['rejection_code'] ?? ''
        ),
        'global gate reports ' . $expected
    );
}

$global_gate_regressions = array(
    'autonomous trucking exemption' => array(
        'section' => 'news',
        'story' => array(
            'title' => 'AV truck fleets win 5-Year exemption on warning beacons',
            'summary' => 'Federal regulators granted the five-year exemption.',
            'source_name' => 'FreightWaves Autonomous Trucking',
        ),
        'branch' => 'future_tech',
    ),
    'daily driverless freight deployment' => array(
        'section' => 'places',
        'story' => array(
            'title' => 'Einride, EASE begin daily driverless freight runs in Ohio',
            'summary' => 'The companies announced the new route.',
        ),
        'branch' => 'future_tech',
    ),
    'ai safety emergency-brake proposal' => array(
        'section' => 'people',
        'story' => array(
            'title' => 'Satya Nadella says AI models need an emergency brake',
            'summary' => 'The technical leader proposes a safety measure for AI models.',
        ),
        'branch' => 'ai',
    ),
    'ai data-center nda accountability change' => array(
        'section' => 'unspoken',
        'story' => array(
            'title' => 'Can the AI industry persuade data center opponents by getting rid of NDAs?',
            'summary' => 'The accountability proposal would disclose data-center agreements.',
        ),
        'branch' => 'ai',
    ),
);

foreach ( $global_gate_regressions as $label => $fixture ) {
    $result = revelations_editorial_scanner_ai_gate(
        $fixture['story'],
        $fixture['section']
    );

    revelations_scanner_runtime_test(
        true === ( $result['qualified'] ?? false ) &&
        $fixture['branch'] === ( $result['gate_branch'] ?? '' ),
        'global gate passes ' . $label
    );
}

$news_semantic_regressions = array(
    'autonomous trucking exemption' => revelations_scanner_runtime_story(
        'AV truck fleets win 5-Year exemption on warning beacons',
        'Federal regulators granted the five-year exemption for autonomous trucking fleets.'
    ),
    'named AI safety proposal' => revelations_scanner_runtime_story(
        'Microsoft’s Satya Nadella says AI models need an emergency brake',
        'The Microsoft leader proposed a safety measure for AI models.'
    ),
    'AI infrastructure accountability change' => revelations_scanner_runtime_story(
        'Can the AI industry persuade data center opponents by getting rid of NDAs?',
        'The disclosure change would make AI data-center agreements public.'
    ),
);

foreach ( $news_semantic_regressions as $label => $story ) {
    $gate = revelations_editorial_scanner_ai_gate( $story, 'news' );
    $score = revelations_editorial_score_news_story(
        revelations_editorial_scanner_attach_gate_semantics(
            $story,
            $gate
        )
    );

    revelations_scanner_runtime_test(
        true === ( $gate['qualified'] ?? false ) &&
        'insufficient_section_signal' !== (
            $score['rejection_code'] ?? ''
        ) &&
        (float) ( $score['relevance_score'] ?? 0 ) >= 2.0 &&
        array() !== ( $score['section_signals'] ?? array() ),
        'News recognizes ' . $label . ' as a concrete event signal'
    );
}

$satya_people_story = revelations_scanner_runtime_story(
    'Microsoft’s Satya Nadella says AI models need an emergency brake',
    'The CEO proposed a concrete safety measure for AI models.'
);
$satya_people_gate = revelations_editorial_scanner_ai_gate(
    $satya_people_story,
    'people'
);
$satya_people_score = revelations_editorial_score_people_story(
    revelations_editorial_scanner_attach_gate_semantics(
        $satya_people_story,
        $satya_people_gate
    )
);

revelations_scanner_runtime_test(
    true === ( $satya_people_gate['qualified'] ?? false ) &&
    (float) ( $satya_people_score['implementation_score'] ?? 0 ) >= 2.0 &&
    in_array(
        'governance safety proposal',
        $satya_people_score['section_signals'] ?? array(),
        true
    ),
    'People gives a central named leader a governance/safety implementation signal'
);

$place_positive_regressions = array(
    'named autonomous freight lane' => revelations_scanner_runtime_story(
        'Kodiak AI launches autonomous freight on the Laredo lane',
        'The companies launch the autonomous freight deployment.'
    ),
    'driverless freight deployment on I-45' => revelations_scanner_runtime_story(
        'Einride begins daily driverless freight runs on I-45',
        'The companies begin commercial autonomous operations.'
    ),
    'daily autonomous mobility in a named city' => revelations_scanner_runtime_story(
        'Autonomous shuttle begins daily service in Austin',
        'The service begins daily autonomous mobility operations.'
    ),
);

foreach ( $place_positive_regressions as $label => $story ) {
    $gate = revelations_editorial_scanner_ai_gate( $story, 'places' );
    $score = revelations_editorial_score_places_story(
        revelations_editorial_scanner_attach_gate_semantics(
            $story,
            $gate
        )
    );

    revelations_scanner_runtime_test(
        true === ( $gate['qualified'] ?? false ) &&
        'place_not_central' !== (
            $score['rejection_code'] ?? ''
        ) &&
        in_array(
            'physical deployment environment',
            $score['section_signals'] ?? array(),
            true
        ),
        'Places recognizes ' . $label . ' as a physical deployment environment'
    );
}

$place_negative_regressions = array(
    'product story merely naming a city' => revelations_scanner_runtime_story(
        'AI company launches a new model in Austin',
        'The company launches its product from a city office.'
    ),
    'funding story with headquarters location' => revelations_scanner_runtime_story(
        'AI startup raises funding for its Austin headquarters',
        'The company announces an investment round.'
    ),
    'exemption without deployment environment' => revelations_scanner_runtime_story(
        'AV truck fleets win 5-Year exemption on warning beacons',
        'Federal regulators granted the exemption.'
    ),
    'generic robotics without place context' => revelations_scanner_runtime_story(
        'Robotics company deploys a new robot platform',
        'The company deploys autonomous robots for customers.'
    ),
);

foreach ( $place_negative_regressions as $label => $story ) {
    $gate = revelations_editorial_scanner_ai_gate( $story, 'places' );
    $score = revelations_editorial_score_places_story(
        revelations_editorial_scanner_attach_gate_semantics(
            $story,
            $gate
        )
    );

    revelations_scanner_runtime_test(
        'place_not_central' === (
            $score['rejection_code'] ?? ''
        ),
        'Places rejects ' . $label . ' without central deployment environment'
    );
}

$generic_runs_gate = revelations_editorial_scanner_ai_gate(
    array(
        'title' => 'Driverless company runs a workshop',
        'summary' => 'The company hosts an internal workshop.',
    ),
    'places'
);

revelations_scanner_runtime_test(
    array() ===
        revelations_editorial_scanner_compositional_operations_actions(
            'Driverless company runs a workshop'
        ) &&
    'future_tech_without_action' === (
        $generic_runs_gate['rejection_code'] ?? ''
    ),
    'generic runs remains outside the compositional operations action'
);

$global_gate_negative_fixtures = array(
    'career advice' => array(
        'Career advice: how to use AI at work',
        'A columnist shares generic career tips.',
    ),
    'hotel opening' => array(
        'Luxury hotel opens downtown',
        'The property adds restaurants and a spa.',
    ),
    'generic leadership' => array(
        'The AI leadership playbook for executives',
        'A generic leadership article offers management advice.',
    ),
    'entertainment' => array(
        'AI film wins at a festival',
        'An entertainment review discusses the winner.',
    ),
    'ordinary construction' => array(
        'Construction company begins building apartments',
        'The developer starts ordinary housing work downtown.',
    ),
);

foreach ( $global_gate_negative_fixtures as $label => $fixture ) {
    $result = revelations_editorial_scanner_ai_gate(
        array(
            'title' => $fixture[0],
            'summary' => $fixture[1],
        ),
        'news'
    );

    revelations_scanner_runtime_test(
        empty( $result['qualified'] ),
        'global gate still rejects generic ' . $label
    );
}

$fetched_items_json = revelations_editorial_sanitize_fetched_items_meta(
    '[{"source":"People Feed","title":"<Technical> leader joins AI lab","canonical_url":"https://example.test/item","published_at":"2026-10-13T00:00:00+00:00","stage":"scored","global_rejection_code":"","section_rejection_code":""}]'
);

revelations_scanner_runtime_test(
    is_array( json_decode( $fetched_items_json, true ) ) &&
    str_contains( $fetched_items_json, '<Technical> leader joins AI lab' ) &&
    str_contains(
        (string) file_get_contents( $plugin_dir . '/revelations-editorial-storage.php' ),
        "'_rev_fetched_items' === \$key"
    ),
    'fetched-item diagnostics retain valid JSON without textarea sanitization'
);

$people_sized_fetched_items = array();
for ( $index = 0; $index < 210; $index++ ) {
    $people_sized_fetched_items[] = array(
        'source' => 'People Feed ' . ( $index % 11 ),
        'title' => '<Technical> leader ' . $index . ' joins AI lab',
        'canonical_url' => 'https://example.test/people/' . $index,
        'published_at' => '2026-10-13T00:00:00+00:00',
        'stage' => 'scored',
        'global_rejection_code' => '',
        'section_rejection_code' => 'below_threshold',
    );
}
$people_sized_payload = revelations_editorial_encode_fetched_items_meta(
    $people_sized_fetched_items
);
$people_sized_stored = revelations_editorial_sanitize_fetched_items_meta(
    $people_sized_payload['payload']
);

revelations_scanner_runtime_test(
    '' === $people_sized_payload['error'] &&
    $people_sized_payload['payload'] === $people_sized_stored &&
    210 === count( json_decode( $people_sized_stored, true ) ?? array() ),
    'People-sized fetched diagnostics encode once and survive meta sanitization'
);

$invalid_fetched_payload = revelations_editorial_encode_fetched_items_meta(
    array(
        array(
            'title' => "Invalid \xB1 UTF-8",
        ),
    )
);

revelations_scanner_runtime_test(
    '[]' === $invalid_fetched_payload['payload'] &&
    'fetched_items_json_encode_failed' === $invalid_fetched_payload['error'],
    'fetched diagnostics report an encoding failure without an empty meta payload'
);

$people_advice =
    revelations_editorial_score_people_story(
        revelations_scanner_runtime_story(
            'Three things every leader must do about AI',
            'Leadership advice discusses ChatGPT models and training.'
        )
    );

revelations_scanner_runtime_test(
    true === (
        $people_advice['hard_rejected'] ?? false
    ) &&
    'opinion_or_advice' === (
        $people_advice['rejection_code'] ?? ''
    ),
    'generic People leadership advice is hard-rejected'
);

$people_news =
    revelations_editorial_score_people_story(
        revelations_scanner_runtime_story(
            'Sam Altman announces a new OpenAI model',
            'The CEO launched the model after a major global release.'
        )
    );

$people_future_tech = revelations_scanner_runtime_story(
    'Ada Lovelace, leading humanoid robotics researcher, joins a major lab',
    'The researcher led embodied systems research and built a robotics breakthrough.'
);

$news_model_release = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'OpenAI launches a major new AI model',
        'The company launched a new model with updated reasoning capabilities.'
    )
);

revelations_scanner_runtime_test(
    true === ( $news_model_release['qualified'] ?? false ),
    'major AI model release is recognized as a significant News event'
);

$news_research_breakthrough = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'Published research reports an AI research breakthrough',
        'Researchers found a breakthrough result in a new AI system.'
    )
);

revelations_scanner_runtime_test(
    true === ( $news_research_breakthrough['qualified'] ?? false ),
    'meaningful AI research breakthrough is recognized as News'
);

$news_minor_feature = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'AI app adds a minor feature',
        'The company released a small feature update for users.'
    )
);

revelations_scanner_runtime_test(
    empty( $news_minor_feature['qualified'] ),
    'minor AI feature update does not automatically qualify as News'
);

$news_generic_funding = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'AI startup announces funding',
        'The startup announced an investment round.'
    )
);

revelations_scanner_runtime_test(
    empty( $news_generic_funding['qualified'] ),
    'generic funding news without scale does not automatically qualify'
);

$news_major_capital = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'Investors commit $500bn to AI infrastructure projects',
        'The financing will fund AI data centers and compute capacity.'
    )
);

revelations_scanner_runtime_test(
    true === ( $news_major_capital['qualified'] ?? false ) &&
    'major_capital_event' === (
        $news_major_capital['editorial_track'] ?? ''
    ) &&
    0.0 === (float) (
        $news_major_capital['implementation_score'] ?? -1
    ),
    'exceptional AI capital event qualifies without implementation score'
);

$news_research_roundup = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'The next big thing in LLMs',
        'A roundup considers how AI academic research is shifting.'
    )
);

revelations_scanner_runtime_test(
    empty( $news_research_roundup['qualified'] ) &&
    'insufficient_section_signal' === (
        $news_research_roundup['rejection_code'] ?? ''
    ),
    'generic AI research roundup remains outside the research-event track'
);

$news_strategic_company = revelations_editorial_score_news_story(
    revelations_scanner_runtime_story(
        'AI company completes major acquisition',
        'The company acquired a major AI platform in a strategic merger.'
    )
);

revelations_scanner_runtime_test(
    true === ( $news_strategic_company['qualified'] ?? false ) &&
    'strategic_company_event' === (
        $news_strategic_company['editorial_track'] ?? ''
    ),
    'substantive strategic company event uses its narrow event track'
);
$people_future_gate = revelations_editorial_scanner_ai_gate(
    $people_future_tech,
    'people'
);
$people_future_score = ! empty( $people_future_gate['qualified'] )
    ? revelations_editorial_score_people_story( $people_future_tech )
    : null;

revelations_scanner_runtime_test(
    'future_tech' === ( $people_future_gate['gate_branch'] ?? '' ) &&
    true === ( $people_future_score['qualified'] ?? false ),
    'People qualifies a central future-tech researcher without deployment action'
);

$people_noise = revelations_editorial_score_people_story(
    revelations_scanner_runtime_story(
        'Taylor Smith, hotel CEO, talks about innovation',
        'The executive discusses a luxury hospitality brand.'
    )
);

revelations_scanner_runtime_test(
    empty( $people_noise['qualified'] ),
    'People rejects a generic executive profile without technological significance'
);

revelations_scanner_runtime_test(
    is_array( $people_news ) &&
    empty( $people_news['hard_rejected'] ),
    'real People news about a named action remains eligible'
);

$section_cases = array(
    'news opinion' =>
        revelations_editorial_score_news_story(
            revelations_scanner_runtime_story(
                'Opinion: AI market launch',
                'Commentary about an AI release.'
            )
        ),
    'news section signal' =>
        revelations_editorial_score_news_story(
            revelations_scanner_runtime_story(
                'OpenAI builds a model',
                'The AI model automates work.'
            )
        ),
    'people centrality' =>
        revelations_editorial_score_people_story(
            revelations_scanner_runtime_story(
                'OpenAI launches a model',
                'The AI system automates work.'
            )
        ),
    'tech section signal' =>
        revelations_editorial_score_tech_story(
            revelations_scanner_runtime_story(
                'OpenAI changes sales policy',
                'The company announced a deal.'
            )
        ),
    'places centrality' =>
        revelations_editorial_score_places_story(
            revelations_scanner_runtime_story(
                'OpenAI launches a model',
                'The AI model automates work.'
            )
        ),
    'unspoken harm' =>
        revelations_editorial_score_unspoken_story(
            revelations_scanner_runtime_story(
                'OpenAI launches an AI model',
                'A company statement confirmed the model release.'
            )
        ),
    'unspoken evidence' =>
        revelations_editorial_score_unspoken_story(
            revelations_scanner_runtime_story(
                'AI system harm reported',
                'the AI system harmed users and reported a failure.'
            )
        ),
);

$expected_section_codes = array(
    'news opinion' => 'opinion_or_advice',
    'news section signal' => 'insufficient_section_signal',
    'people centrality' => 'person_not_central',
    'tech section signal' => 'insufficient_section_signal',
    'places centrality' => 'place_not_central',
    'unspoken harm' => 'insufficient_unspoken_angle',
    'unspoken evidence' => 'insufficient_evidence',
);

foreach (
    $expected_section_codes
    as $label => $expected
) {
    $actual = (string) (
        $section_cases[ $label ][
            'rejection_code'
        ] ?? ''
    );

    revelations_scanner_runtime_test(
        $expected === $actual,
        $label .
            ' reports ' .
            $expected .
            ' (got ' .
            $actual .
            ')'
    );
}

$aggregate_summary =
    'FULL_SOURCE_TEXT_SENTINEL OpenAI launched a model ' .
    'with automation and inference.';
$GLOBALS['scanner_test_feed'] =
    new RevelationsScannerTestFeed(
        array(
            new RevelationsScannerTestItem(
                'OpenAI launches opinion scanner model',
                $aggregate_summary,
                'https://example.test/opinion',
                time() - HOUR_IN_SECONDS
            ),
            new RevelationsScannerTestItem(
                'OpenAI launches below scanner model',
                $aggregate_summary,
                'https://example.test/below',
                time() - HOUR_IN_SECONDS
            ),
            new RevelationsScannerTestItem(
                'OpenAI launches accepted scanner model',
                $aggregate_summary,
                'https://example.test/accepted',
                time() - HOUR_IN_SECONDS
            ),
        )
    );

$aggregate = revelations_editorial_scanner_run_dry_run(
    'news',
    array(
        array(
            'name' => 'Synthetic Feed',
            'url' => 'https://example.test/feed',
        ),
    ),
    array(),
    static function ( array $story ): array {
        if ( str_contains( $story['title'], 'opinion' ) ) {
            return revelations_editorial_scanner_rejection(
                'opinion_or_advice',
                'Synthetic opinion rejection.'
            );
        }

        $qualified =
            str_contains(
                $story['title'],
                'accepted'
            );

        return array(
            'freshness_score' => 10.0,
            'implementation_score' => 4.0,
            'relevance_score' => 4.0,
            'impact_score' => 0.0,
            'fit_score' => 5.0,
            'total_score' =>
                $qualified ? 6.0 : 3.0,
            'qualified' => $qualified,
            'editorial_track' =>
                $qualified ? 'news_signal' : null,
            'rejection_code' =>
                $qualified ? '' : 'below_threshold',
            'scoring_reason' =>
                'Synthetic aggregate score.',
        );
    },
    array(),
    5
);

revelations_scanner_runtime_test(
    1 === (
        $aggregate['rejection_counts']['section'][
            'opinion_or_advice'
        ] ?? 0
    ) &&
    1 === (
        $aggregate['rejection_counts']['section'][
            'below_threshold'
        ] ?? 0
    ),
    'shared engine aggregates section rejection counts'
);

revelations_scanner_runtime_test(
    1 === count(
        $aggregate['below_threshold_scores'] ?? array()
    ) &&
    3.0 === (float) (
        $aggregate['below_threshold_scores'][0][
            'scores'
        ]['total'] ?? 0
    ),
    'aggregate report retains below-threshold section score'
);

$aggregate_stages = array_column(
    $aggregate['fetched_items'] ?? array(),
    'stage'
);

revelations_scanner_runtime_test(
    array( 'scored', 'scored', 'qualified' ) === $aggregate_stages &&
    30 === (int) ( $aggregate['source_results'][0]['item_limit'] ?? 0 ) &&
    168 === (int) ( $aggregate['source_results'][0]['freshness_hours'] ?? 0 ),
    'fetched diagnostics record every in-window item and its discovery stage'
);

$safe_diagnostics = wp_json_encode(
    array(
        'counts' =>
            $aggregate['rejection_counts'] ?? array(),
        'samples' =>
            $aggregate['rejection_samples'] ?? array(),
        'below' =>
            $aggregate['below_threshold_scores'] ?? array(),
    )
);

revelations_scanner_runtime_test(
    is_string( $safe_diagnostics ) &&
    ! str_contains(
        $safe_diagnostics,
        'FULL_SOURCE_TEXT_SENTINEL'
    ),
    'rejection diagnostics never include full RSS summary text'
);

$persisted_diagnostics =
    revelations_editorial_sanitize_scan_diagnostics(
        array(
            'source_results' => array(
                array(
                    'source' => 'Synthetic Source',
                    'success' => true,
                    'items' => 3,
                ),
            ),
            'rejection_counts' =>
                $aggregate['rejection_counts'] ?? array(),
            'closest_rejected' => array(
                array(
                    'title' => 'Near-miss story',
                    'source' => 'Synthetic Source',
                    'summary' => 'FULL_SOURCE_TEXT_SENTINEL',
                    'matched_future_tech_families' =>
                        array( 'autonomous_mobility' ),
                    'evidence_type' => 'published_research',
                    'evidence_signals' => array( 'study found' ),
                    'scores' => array( 'total' => 3.0 ),
                ),
            ),
            'qualified_items' => array(
                array(
                    'title' => 'Qualified AI model release',
                    'source' => 'Synthetic Source',
                    'gate_branch' => 'ai',
                    'matched_ai_signals' => array( 'ai', 'model' ),
                    'section_signals' => array( 'model release' ),
                    'editorial_track' => 'news_signal',
                    'scores' => array( 'total' => 8.0 ),
                    'summary' => 'FULL_SOURCE_TEXT_SENTINEL',
                ),
            ),
            'fetched_items' => array(
                array(
                    'source' => 'Synthetic Source',
                    'title' => 'Fetched AI infrastructure story',
                    'canonical_url' => 'https://example.test/fetched',
                    'published_at' => '2026-10-13T00:00:00+00:00',
                    'stage' => 'global_rejected',
                    'global_rejection_code' => 'insufficient_ai_context',
                    'section_rejection_code' => '',
                    'summary' => 'FULL_SOURCE_TEXT_SENTINEL',
                ),
            ),
        )
    );

revelations_scanner_runtime_test(
    3 === (int) (
        $persisted_diagnostics['source_results'][0]['items']
        ?? 0
    ) &&
    'autonomous_mobility' === (
        $persisted_diagnostics['closest_rejected'][0][
            'matched_future_tech_families'
        ][0] ?? ''
    ) &&
    'published_research' === (
        $persisted_diagnostics['closest_rejected'][0][
            'evidence_type'
        ] ?? ''
    ) &&
    'study found' === (
        $persisted_diagnostics['closest_rejected'][0][
            'evidence_signals'
        ][0] ?? ''
    ) &&
    ! str_contains(
        wp_json_encode( $persisted_diagnostics ) ?: '',
        'FULL_SOURCE_TEXT_SENTINEL'
    ),
    'permanent diagnostics retain bounded signals and exclude RSS summaries'
);

revelations_scanner_runtime_test(
    'Qualified AI model release' === (
        $persisted_diagnostics['qualified_items'][0]['title']
        ?? ''
    ) &&
    'ai' === (
        $persisted_diagnostics['qualified_items'][0]['gate_branch']
        ?? ''
    ) &&
    'news_signal' === (
        $persisted_diagnostics['qualified_items'][0]['editorial_track']
        ?? ''
    ) &&
    8.0 === (float) (
        $persisted_diagnostics['qualified_items'][0]['scores']['total']
        ?? 0
    ) &&
    ! str_contains(
        wp_json_encode( $persisted_diagnostics ) ?: '',
        'FULL_SOURCE_TEXT_SENTINEL'
    ),
    'permanent diagnostics retain qualified branch and scores without RSS summaries'
);

revelations_scanner_runtime_test(
    'Fetched AI infrastructure story' === (
        $persisted_diagnostics['fetched_items'][0]['title']
        ?? ''
    ) &&
    'global_rejected' === (
        $persisted_diagnostics['fetched_items'][0]['stage']
        ?? ''
    ) &&
    'insufficient_ai_context' === (
        $persisted_diagnostics['fetched_items'][0][
            'global_rejection_code'
        ] ?? ''
    ) &&
    ! str_contains(
        wp_json_encode( $persisted_diagnostics['fetched_items'] ?? array() ) ?: '',
        'FULL_SOURCE_TEXT_SENTINEL'
    ),
    'permanent fetched diagnostics retain metadata and rejection stage without RSS text'
);

$preview_engine_source = file_get_contents(
    $plugin_dir .
    '/revelations-editorial-preview-engine.php'
);
$logs_source = file_get_contents(
    $plugin_dir .
    '/revelations-editorial-logs.php'
);

revelations_scanner_runtime_test(
    is_string( $preview_engine_source ) &&
    str_contains(
        $preview_engine_source,
        "'source_results' =>"
    ) &&
    str_contains(
        $preview_engine_source,
        "'rejection_counts' =>"
    ) &&
    str_contains(
        $preview_engine_source,
        "'closest_rejected' =>"
    ) &&
    str_contains(
        $preview_engine_source,
        "'qualified_items' =>"
    ) &&
    str_contains(
        $preview_engine_source,
        "'fetched_items' =>"
    ),
    'persistent run-log mapping stores bounded source, rejection, qualified and fetched diagnostics'
);

revelations_scanner_runtime_test(
    is_string( $logs_source ) &&
    str_contains( $logs_source, "'_rev_fetched_items'" ) &&
    str_contains(
        $logs_source,
        'revelations_editorial_retain_latest_scan_fetched_items'
    ) &&
    str_contains(
        $logs_source,
        "delete_post_meta( (int) \$run_id, '_rev_fetched_items' )"
    ),
    'only the latest RSS run per section retains fetched-item diagnostics'
);

revelations_scanner_runtime_test(
    is_string( $logs_source ) &&
    str_contains(
        $logs_source,
        'revelations_editorial_encode_fetched_items_meta'
    ) &&
    str_contains(
        $logs_source,
        'fetched_items_meta_persistence_failed'
    ) &&
    str_contains( $logs_source, 'hash_equals(' ),
    'run logs encode fetched diagnostics once and report persistence failures'
);

echo "\nScanner runtime diagnostics: " .
    $passed .
    ' passed, ' .
    $failed .
    ', ' .
    ( $passed + $failed ) .
    " total.\n";

exit( 0 === $failed ? 0 : 1 );

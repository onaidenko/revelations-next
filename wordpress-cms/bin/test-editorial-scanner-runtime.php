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
    4 === count(
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
    4 === count(
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
    'insufficient_ai_context' => array(
        'AI launches a service',
        'AI launches a service for customers.',
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
    5,
    20
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
                    'scores' => array( 'total' => 3.0 ),
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
    ! str_contains(
        wp_json_encode( $persisted_diagnostics ) ?: '',
        'FULL_SOURCE_TEXT_SENTINEL'
    ),
    'permanent diagnostics retain bounded signals and exclude RSS summaries'
);

$preview_engine_source = file_get_contents(
    $plugin_dir .
    '/revelations-editorial-preview-engine.php'
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
    ),
    'persistent run-log mapping stores bounded source and rejection diagnostics'
);

echo "\nScanner runtime diagnostics: " .
    $passed .
    ' passed, ' .
    $failed .
    ', ' .
    ( $passed + $failed ) .
    " total.\n";

exit( 0 === $failed ? 0 : 1 );

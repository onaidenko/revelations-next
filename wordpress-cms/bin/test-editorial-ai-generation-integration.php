<?php
/**
 * Isolated integration diagnostics for the complete AI generation flow.
 *
 * This script does not load WordPress, access a database or use a network.
 * Production functions run against in-memory WordPress stubs and a fake
 * OpenAI transport.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

/**
 * Minimal WordPress post value object for the in-memory store.
 */
class WP_Post {
    public int $ID;
    public string $post_type;
    public string $post_status;
    public int $post_parent;
    public string $post_title;
    public string $post_content;
    public string $post_excerpt;
    public int $post_author;

    /**
     * @param array<string, mixed> $data Post fields.
     */
    public function __construct( array $data ) {
        $this->ID = (int) ( $data['ID'] ?? 0 );
        $this->post_type =
            (string) ( $data['post_type'] ?? 'post' );
        $this->post_status =
            (string) ( $data['post_status'] ?? 'draft' );
        $this->post_parent =
            (int) ( $data['post_parent'] ?? 0 );
        $this->post_title =
            (string) ( $data['post_title'] ?? '' );
        $this->post_content =
            (string) ( $data['post_content'] ?? '' );
        $this->post_excerpt =
            (string) ( $data['post_excerpt'] ?? '' );
        $this->post_author =
            (int) ( $data['post_author'] ?? 0 );
    }
}

/**
 * Minimal WordPress error value object.
 */
class WP_Error {
    public array $data;

    public function __construct(
        private string $code,
        private string $message,
        array $data = array()
    ) {
        $this->data = $data;
    }

    public function get_error_code(): string {
        return $this->code;
    }

    public function get_error_message(): string {
        return $this->message;
    }

    public function get_error_data( string $code = '' ): array {
        return $this->data;
    }
}

/**
 * In-memory replacement for the one version-count query used by production.
 */
class Revelations_Integration_WPDB {
    public string $posts = 'wp_posts';

    public function prepare(
        string $query,
        mixed ...$arguments
    ): string {
        return vsprintf( $query, $arguments );
    }

    public function get_var( string $query ): int {
        global $revelations_integration_posts;

        preg_match(
            '/post_parent\\s*=\\s*(\\d+)/',
            $query,
            $matches
        );

        $draft_id = (int) ( $matches[1] ?? 0 );
        $count = 0;

        foreach ( $revelations_integration_posts as $post ) {
            if (
                'rev_ai_version' === $post->post_type &&
                'private' === $post->post_status &&
                $draft_id === $post->post_parent
            ) {
                $count++;
            }
        }

        return $count;
    }
}

$wpdb = new Revelations_Integration_WPDB();

$revelations_integration_posts = array();
$revelations_integration_meta = array();
$revelations_integration_categories = array();
$revelations_integration_next_post_id = 1000;
$revelations_integration_transport_calls = 0;
$revelations_integration_transport_requests = array();
$revelations_integration_transport_article = array();
$revelations_integration_settings = array();
$revelations_integration_transport_overrides = array();

/**
 * WordPress registration stub. Callbacks are intentionally not executed.
 */
function add_action( mixed ...$arguments ): void {
}

function is_wp_error( mixed $value ): bool {
    return $value instanceof WP_Error;
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

function sanitize_key( mixed $value ): string {
    return preg_replace(
        '/[^a-z0-9_\\-]/',
        '',
        strtolower( (string) $value )
    ) ?? '';
}

function sanitize_text_field( mixed $value ): string {
    return trim(
        preg_replace(
            '/\\s+/u',
            ' ',
            strip_tags( (string) $value )
        ) ?? ''
    );
}

function sanitize_textarea_field( mixed $value ): string {
    return trim( strip_tags( (string) $value ) );
}

function esc_html( mixed $value ): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function esc_url( mixed $value ): string {
    return trim( (string) $value );
}

function esc_url_raw( mixed $value ): string {
    return trim( (string) $value );
}

function wp_parse_url(
    string $url,
    int $component = -1
): array|string|int|null|false {
    return parse_url( $url, $component );
}

function wp_json_encode(
    mixed $value,
    int $flags = 0
): string|false {
    return json_encode( $value, $flags );
}

function wp_slash( mixed $value ): mixed {
    return $value;
}

function get_current_user_id(): int {
    return 7;
}

function get_post( int $post_id ): ?WP_Post {
    global $revelations_integration_posts;

    if (
        ! isset(
            $revelations_integration_posts[ $post_id ]
        )
    ) {
        return null;
    }

    return clone $revelations_integration_posts[ $post_id ];
}

function get_post_type( int $post_id ): string|false {
    $post = get_post( $post_id );

    return $post instanceof WP_Post
        ? $post->post_type
        : false;
}

function get_the_title( int $post_id ): string {
    $post = get_post( $post_id );

    return $post instanceof WP_Post
        ? $post->post_title
        : '';
}

function get_post_field(
    string $field,
    int $post_id
): mixed {
    $post = get_post( $post_id );

    return $post instanceof WP_Post &&
        property_exists( $post, $field )
            ? $post->{$field}
            : '';
}

function get_post_meta(
    int $post_id,
    string $key,
    bool $single = false
): mixed {
    global $revelations_integration_meta;

    if (
        ! array_key_exists(
            $key,
            $revelations_integration_meta[ $post_id ]
                ?? array()
        )
    ) {
        return $single ? '' : array();
    }

    $value =
        $revelations_integration_meta[ $post_id ][ $key ];

    return $single
        ? $value
        : array( $value );
}

function metadata_exists(
    string $meta_type,
    int $post_id,
    string $key
): bool {
    global $revelations_integration_meta;

    return 'post' === $meta_type &&
        array_key_exists(
            $key,
            $revelations_integration_meta[ $post_id ]
                ?? array()
        );
}

function update_post_meta(
    int $post_id,
    string $key,
    mixed $value
): bool {
    global $revelations_integration_meta;

    $revelations_integration_meta[ $post_id ][ $key ] =
        $value;

    return true;
}

function delete_post_meta(
    int $post_id,
    string $key
): bool {
    global $revelations_integration_meta;

    if (
        ! array_key_exists(
            $key,
            $revelations_integration_meta[ $post_id ]
                ?? array()
        )
    ) {
        return false;
    }

    unset(
        $revelations_integration_meta[ $post_id ][ $key ]
    );

    return true;
}

/**
 * @param array<string, mixed> $data Post fields.
 */
function wp_update_post(
    array $data,
    bool $wp_error = false
): int|WP_Error {
    global $revelations_integration_posts;

    $post_id = absint( $data['ID'] ?? 0 );

    if (
        $post_id < 1 ||
        ! isset(
            $revelations_integration_posts[ $post_id ]
        )
    ) {
        return new WP_Error(
            'missing_post',
            'Synthetic post does not exist.'
        );
    }

    $post =
        $revelations_integration_posts[ $post_id ];

    foreach (
        array(
            'post_status',
            'post_title',
            'post_content',
            'post_excerpt',
        ) as $field
    ) {
        if ( array_key_exists( $field, $data ) ) {
            $post->{$field} = (string) $data[ $field ];
        }
    }

    return $post_id;
}

/**
 * @param array<string, mixed> $data Post fields.
 */
function wp_insert_post(
    array $data,
    bool $wp_error = false
): int|WP_Error {
    global $revelations_integration_posts;
    global $revelations_integration_next_post_id;

    $post_id = $revelations_integration_next_post_id++;
    $data['ID'] = $post_id;

    $revelations_integration_posts[ $post_id ] =
        new WP_Post( $data );

    return $post_id;
}

/**
 * @return int[]
 */
function wp_get_post_categories(
    int $post_id
): array {
    global $revelations_integration_categories;

    return array_values(
        $revelations_integration_categories[ $post_id ]
            ?? array()
    );
}

/**
 * @param int[] $categories Category IDs.
 * @return int[]
 */
function wp_set_post_categories(
    int $post_id,
    array $categories,
    bool $append = false
): array {
    global $revelations_integration_categories;

    $revelations_integration_categories[ $post_id ] =
        array_values(
            array_unique(
                array_map( 'absint', $categories )
            )
        );

    return $revelations_integration_categories[ $post_id ];
}

function term_exists(
    int $term_id,
    string $taxonomy
): int|false {
    return 'category' === $taxonomy &&
        in_array( $term_id, array( 10, 20 ), true )
            ? $term_id
            : false;
}

function clean_post_cache( int $post_id ): void {
}

function do_blocks( string $content ): string {
    return $content;
}

function wp_strip_all_tags(
    string $text,
    bool $remove_breaks = false
): string {
    return strip_tags( $text );
}

/**
 * @return array<int, array<string, mixed>>
 */
function parse_blocks( string $content ): array {
    preg_match_all(
        '/<!--\\s+wp:[^>]+-->/',
        $content,
        $matches
    );

    return array_fill(
        0,
        count( $matches[0] ?? array() ),
        array()
    );
}

/**
 * Return controlled editorial settings used by production prompt logic.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_get_settings(): array {
    global $revelations_integration_settings;

    $defaults = array(
        'article_length_min' => 100,
        'article_length_max' => 800,
        'editorial_policy' =>
            'AI must remain the central subject.',
        'tone_of_voice' =>
            'Precise, calm and evidence-led.',
        'preferred_article_structure' =>
            'Context, development and implications.',
        'banned_words' =>
            "revolutionary\ngame-changing",
    );

    return array_merge( $defaults, $revelations_integration_settings );
}

/**
 * Fake OpenAI transport. No network function is called from this process.
 *
 * @param array<string, mixed> $arguments Request arguments.
 * @return array<string, mixed>
 */
function wp_remote_post(
    string $url,
    array $arguments
): mixed {
    global $revelations_integration_transport_calls;
    global $revelations_integration_transport_requests;
    global $revelations_integration_transport_article;
    global $revelations_integration_transport_overrides;

    $request_body = json_decode( (string) ( $arguments['body'] ?? '' ), true );
    $is_research = is_array( $request_body ) && ! empty( $request_body['tools'] );
    $is_brief = is_array( $request_body ) && 'revelations_editorial_brief' === ( $request_body['text']['format']['name'] ?? '' );

    $call_index = $revelations_integration_transport_calls;
    $revelations_integration_transport_calls++;
    $revelations_integration_transport_requests[] =
        array(
            'url' => $url,
            'arguments' => $arguments,
        );

    $override = $revelations_integration_transport_overrides[ $call_index ] ?? null;
    if ( is_callable( $override ) ) return $override( $is_research, $is_brief );
    if ( null !== $override ) return $override;

    $output_text = wp_json_encode(
        $is_research ? revelations_integration_research() : ( $is_brief ? revelations_integration_brief() : $revelations_integration_transport_article ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $body = wp_json_encode(
        array(
            'id' => 'synthetic-response',
            'status' => 'completed',
            'model' => 'synthetic-model',
            'usage' => array(
                'input_tokens' => 80,
                'output_tokens' => 120,
                'total_tokens' => 200,
            ),
            'output' => array(
                ...( $is_research ? array( array( 'type' => 'web_search_call', 'action' => array( 'sources' => array( array( 'url' => 'https://agency.gov/record' ), array( 'url' => 'https://editorial.example.test/report' ) ) ) ) ) : array() ),
                array(
                    'content' => array(
                        array(
                            'type' => 'output_text',
                            'text' => $output_text,
                        ),
                    ),
                ),
            ),
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    return array(
        'response' => array(
            'code' => 200,
        ),
        'body' => $body,
    );
}

/** @return array<string, mixed> */
function revelations_integration_responses_fixture( int $http_status, string $status, string $output, bool $research = false ): array {
    return array( 'response' => array( 'code' => $http_status ), 'headers' => array( 'x-request-id' => 'synthetic-request-id' ), 'body' => wp_json_encode( array( 'id' => 'synthetic-response-id', 'status' => $status, 'usage' => array( 'input_tokens' => 11, 'output_tokens' => 7, 'total_tokens' => 18 ), 'incomplete_details' => array( 'reason' => 'max_output_tokens' ), 'error' => array( 'type' => 'rate_limit_error', 'code' => 'rate_limit_exceeded', 'message' => 'raw-response-marker' ), 'output' => array( ...( $research ? array( array( 'type' => 'web_search_call', 'status' => 'completed', 'action' => array( 'sources' => array( array( 'url' => 'https://agency.gov/record' ), array( 'url' => 'https://editorial.example.test/report' ) ) ) ) ) : array() ), array( 'content' => array( array( 'type' => 'output_text', 'text' => $output ) ) ) ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/**
 * @param array<string, mixed> $response Response.
 */
function wp_remote_retrieve_response_code(
    array $response
): int {
    return (int) (
        $response['response']['code'] ?? 0
    );
}

/**
 * @param array<string, mixed> $response Response.
 */
function wp_remote_retrieve_body(
    array $response
): string {
    return (string) ( $response['body'] ?? '' );
}

$plugin_dir =
    dirname( __DIR__ ) . '/mu-plugins';

require_once
    $plugin_dir .
    '/revelations-editorial-ai-config.php';
require_once
    $plugin_dir .
    '/revelations-editorial-ai-generation-profiles.php';
require_once
    $plugin_dir .
    '/revelations-editorial-ai-generation-validation.php';
require_once
    $plugin_dir .
    '/revelations-editorial-ai-research.php';
require_once
    $plugin_dir .
    '/revelations-editorial-ai-review-metadata.php';
require_once
    $plugin_dir .
    '/revelations-editorial-ai-generate.php';
require_once
    $plugin_dir .
    '/revelations-editorial-review.php';
require_once
    $plugin_dir .
    '/revelations-editorial-version-restore.php';

$revelations_integration_passed = 0;
$revelations_integration_failed = 0;

/**
 * Record one integration assertion.
 */
function revelations_integration_check(
    bool $condition,
    string $message
): void {
    global $revelations_integration_passed;
    global $revelations_integration_failed;

    if ( $condition ) {
        $revelations_integration_passed++;
        echo 'PASS: ' . $message . "\n";
        return;
    }

    $revelations_integration_failed++;
    echo 'FAIL: ' . $message . "\n";
}

/**
 * Configure the production resolver entirely through synthetic environment.
 */
function revelations_integration_configure(
    bool $enabled = true,
    bool $with_key = true,
    bool $with_model = true
): void {
    putenv(
        'OPENAI_API_KEY=' .
        (
            $with_key
                ? 'synthetic-integration-' .
                    str_repeat( 'x', 24 )
                : ''
        )
    );
    putenv(
        'OPENAI_MODEL=' .
        (
            $with_model
                ? 'synthetic-model'
                : ''
        )
    );
    putenv(
        'REVELATIONS_AI_GENERATION_ENABLED=' .
        (
            $enabled
                ? 'true'
                : 'false'
        )
    );
}

/**
 * Source material used by successful and failing generated responses.
 */
function revelations_integration_source_text(): string {
    $context =
        'The source describes an AI workflow used during routine ' .
        'editorial operations. Staff compare the generated material ' .
        'with the supplied record before making editorial decisions. ' .
        'The workflow supports drafting while people retain control. ';

    $quote_fragment =
        'The source states: Teams now use the system during routine ' .
        'editorial work.';

    $canonical_evidence =
        '[s001] Claim: The source describes an AI workflow used during routine editorial operations. Staff compare the generated material with the supplied record before making editorial decisions. The workflow supports drafting while people retain control. The source states: Teams now use the system during routine editorial work.' .
        "\nSupport: The source describes an AI workflow used during routine editorial operations. Staff compare the generated material with the supplied record before making editorial decisions. The workflow supports drafting while people retain control. The source states: Teams now use the system during routine editorial work.";

    return
        str_repeat( $context, 5 ) .
        $quote_fragment . ' ' .
        $canonical_evidence . ' ' .
        str_repeat( $context, 2 );
}

/**
 * Return a valid structured response fixture.
 *
 * @return array<string, mixed>
 */
function revelations_integration_article(
    string $section,
    string $variant = 'initial'
): array {
    $section_label = ucfirst( $section );
    $renewed = 'regenerated' === $variant
        ? ' Renewed'
        : '';

    $suggestions = array(
        'news' => 'tech',
        'tech' => 'people',
        'people' => 'places',
        'places' => 'unspoken',
        'unspoken' => 'news',
    );

    return array(
        'recommended_title' =>
            $section_label .
            ' AI Workflow Shapes Editorial Practice' .
            $renewed,

        'alternative_titles' => array(
            $section_label .
                ' Teams Adopt a Careful AI Drafting Routine' .
                $renewed,
            $section_label .
                ' Editors Keep Control of an AI Workflow' .
                $renewed,
        ),

        'section_mismatch' => true,
        'suggested_section' =>
            $suggestions[ $section ],
        'section_mismatch_reason' =>
            'The technical emphasis may support another editorial angle.',

        'excerpt' =>
            'An AI workflow enters routine editorial practice while ' .
            'human judgment remains central' .
            (
                'regenerated' === $variant
                    ? ' in the updated draft.'
                    : '.'
            ),

        'seo_title' =>
            $section_label .
            ' Editorial AI Workflow' .
            $renewed,

        'seo_description' =>
            'A measured account of how editors use an AI drafting ' .
            'system while retaining responsibility for the result' .
            (
                'regenerated' === $variant
                    ? ' after regeneration.'
                    : '.'
            ),

        'fact_check_flags' => array(),

        'direct_quotes' => array(),

        'blocks' => array(
            array(
                'type' => 'paragraph',
                'text' =>
                    'The supplied account describes an AI drafting ' .
                    'workflow moving into ordinary editorial routines. ' .
                    'Editors begin with source material, examine the ' .
                    'generated structure, and retain responsibility for ' .
                    'every decision that shapes the finished article.',
                'heading_level' => 0,
                'items' => array(),
                'evidence_ids' => array( 'p001' ),
            ),
            array(
                'type' => 'paragraph',
                'text' =>
                    'The system supports preparation rather than ' .
                    'publication. Staff compare wording with the source, ' .
                    'consider whether context is missing, and revise the ' .
                    'draft before it can move through the established ' .
                    'human review process.',
                'heading_level' => 0,
                'items' => array(),
                'evidence_ids' => array( 'p002' ),
            ),
            array(
                'type' => 'paragraph',
                'text' =>
                    'This approach keeps automation within a bounded ' .
                    'editorial role. The generated material remains a ' .
                    'draft, the assigned section stays under server ' .
                    'control, and an editor remains accountable for ' .
                    'accuracy, emphasis, and publication readiness.' .
                    (
                        'regenerated' === $variant
                            ? ' The renewed version changes the editorial framing.'
                            : ''
                    ),
                'heading_level' => 0,
                'items' => array(),
                'evidence_ids' => array( 'p001', 'p002' ),
            ),
            array(
                'type' => 'paragraph',
                'text' =>
                    'The source also presents the workflow as a practical ' .
                    'tool for organizing material. It does not transfer ' .
                    'editorial authority to the system, and it leaves ' .
                    'final wording, verification, and approval with the ' .
                    'people responsible for the publication.',
                'heading_level' => 0,
                'items' => array(),
                'evidence_ids' => array( 'p002' ),
            ),
            array(
                'type' => 'paragraph',
                'text' => 'The source says the system processed 77 tasks during routine editorial work.',
                'heading_level' => 0,
                'items' => array(),
                'evidence_ids' => array( 'p001' ),
            ),
        ),
    );
}

/** @return array<string, mixed> */
function revelations_integration_research(): array {
    $claim = 'The source describes an AI workflow used during routine editorial operations. Staff compare the generated material with the supplied record before making editorial decisions. The workflow supports drafting while people retain control. The source states: Teams now use the system during routine editorial work.';
    $source = static function ( string $url, string $name, string $reliability ) use ( $claim ): array {
        return array( 'url' => $url, 'name' => $name, 'publication_date' => '2026-01-01', 'source_type' => 'reported_news', 'reliability' => $reliability, 'claims' => array( array( 'claim' => $claim, 'context' => $claim, 'attribution' => '' ) ) );
    };
    return array( 'sources' => array( $source( 'https://agency.gov/record', 'Primary Record', 'primary_authoritative' ), $source( 'https://editorial.example.test/report', 'Editorial Report', 'major_editorial' ) ) );
}

/** @return array<string, mixed> */
function revelations_integration_brief(): array {
    return array( 'what_happened' => 'An AI workflow is used in editorial work.', 'why_revelations_cares' => 'It shows human control around AI.', 'thesis' => 'AI drafting depends on review.', 'factual_pillars' => array( array( 'pillar_id' => 'pillar_1', 'pillar' => 'AI supports drafting.', 'importance' => 'central', 'evidence_ids' => array( 'p001' ) ), array( 'pillar_id' => 'pillar_2', 'pillar' => 'Staff compare records.', 'importance' => 'supporting', 'evidence_ids' => array( 'p002' ) ), array( 'pillar_id' => 'pillar_3', 'pillar' => 'People retain control.', 'importance' => 'supporting', 'evidence_ids' => array( 'p001', 'p002' ) ) ), 'confirmed' => array( 'The workflow exists.' ), 'attributed' => array(), 'interpretation' => array( 'The workflow changes practice.' ), 'do_not_claim' => array( 'Do not claim broad industry adoption.' ), 'pillar_order' => array( 0, 1, 2 ), 'sensitive_evidence_ids' => array(), 'attribution_evidence_ids' => array(), 'essential_context_evidence_ids' => array() );
}

/**
 * Reset the complete in-memory WordPress and transport state.
 */
function revelations_integration_reset(
    string $section = 'news'
): void {
    global $revelations_integration_posts;
    global $revelations_integration_meta;
    global $revelations_integration_categories;
    global $revelations_integration_next_post_id;
    global $revelations_integration_transport_calls;
    global $revelations_integration_transport_requests;
    global $revelations_integration_transport_article;
    global $revelations_integration_settings;
    global $revelations_integration_transport_overrides;

    $revelations_integration_posts = array(
        100 => new WP_Post(
            array(
                'ID' => 100,
                'post_type' => 'post',
                'post_status' => 'draft',
                'post_title' => 'Original source draft',
                'post_content' =>
                    '<!-- wp:paragraph --><p>Source draft.</p><!-- /wp:paragraph -->',
                'post_excerpt' => 'Original excerpt',
                'post_author' => 41,
            )
        ),
        200 => new WP_Post(
            array(
                'ID' => 200,
                'post_type' => 'rev_candidate',
                'post_status' => 'private',
                'post_title' => 'Source headline',
                'post_content' => '',
                'post_excerpt' => '',
                'post_author' => 41,
            )
        ),
    );

    $revelations_integration_meta = array(
        100 => array(
            '_revelations_editorial_candidate_id' => 200,
            'revelations_author' => 'Existing Author',
        ),
        200 => array(
            '_rev_section' => $section,
            '_rev_source_text' =>
                revelations_integration_source_text(),
            '_rev_source_name' => 'Synthetic Source',
            '_rev_source_url' =>
                'https://example.test/source',
            '_rev_summary' =>
                'An AI workflow enters editorial operations.',
            '_rev_editorial_track' => 'main',
            '_rev_status' => 'ready',
            '_rev_error_message' => '',
        ),
    );

    $revelations_integration_categories = array(
        100 => array( 10 ),
    );

    $revelations_integration_next_post_id = 1000;
    $revelations_integration_transport_calls = 0;
    $revelations_integration_transport_requests = array();
    $revelations_integration_transport_overrides = array();
    $revelations_integration_settings = array();
    $revelations_integration_transport_article =
        revelations_integration_article(
            in_array(
                $section,
                array(
                    'news',
                    'tech',
                    'people',
                    'places',
                    'unspoken',
                ),
                true
            )
                ? $section
                : 'news'
        );

    revelations_integration_configure();
}

/**
 * Return a deterministic signature of every mutable in-memory WP record.
 */
function revelations_integration_state_signature(): string {
    global $revelations_integration_posts;
    global $revelations_integration_meta;
    global $revelations_integration_categories;

    $posts = array();

    foreach (
        $revelations_integration_posts
        as $post_id => $post
    ) {
        $posts[ $post_id ] = get_object_vars( $post );
    }

    ksort( $posts );

    $meta = $revelations_integration_meta;
    $categories = $revelations_integration_categories;
    ksort( $meta );
    ksort( $categories );

    return hash(
        'sha256',
        serialize(
            array(
                'posts' => $posts,
                'meta' => $meta,
                'categories' => $categories,
            )
        )
    );
}

/**
 * @return int[]
 */
function revelations_integration_version_ids(): array {
    global $revelations_integration_posts;

    $ids = array();

    foreach (
        $revelations_integration_posts
        as $post_id => $post
    ) {
        if ( 'rev_ai_version' === $post->post_type ) {
            $ids[] = (int) $post_id;
        }
    }

    sort( $ids );

    return $ids;
}

/**
 * Decode one JSON metadata list from the current draft.
 *
 * @return array<int, mixed>
 */
function revelations_integration_meta_list(
    int $post_id,
    string $key
): array {
    $decoded = json_decode(
        (string) get_post_meta(
            $post_id,
            $key,
            true
        ),
        true
    );

    return is_array( $decoded )
        ? $decoded
        : array();
}

/**
 * Mark the current draft as reviewed using the real review hash.
 */
function revelations_integration_mark_reviewed(
    int $draft_id
): string {
    $hash =
        revelations_editorial_review_content_hash(
            $draft_id
        );

    update_post_meta(
        $draft_id,
        '_revelations_editorial_review_status',
        'reviewed'
    );
    update_post_meta(
        $draft_id,
        '_revelations_editorial_review_hash',
        $hash
    );

    return $hash;
}

/**
 * Return the request body captured by the fake transport.
 *
 * @return array<string, mixed>
 */
function revelations_integration_request_body(): array {
    global $revelations_integration_transport_requests;

    $request =
        $revelations_integration_transport_requests[ count( $revelations_integration_transport_requests ) - 1 ]
        ?? array();

    $body = json_decode(
        (string) (
            $request['arguments']['body']
            ?? ''
        ),
        true
    );

    return is_array( $body )
        ? $body
        : array();
}

$saved_environment = array(
    'OPENAI_API_KEY' => getenv( 'OPENAI_API_KEY' ),
    'OPENAI_MODEL' => getenv( 'OPENAI_MODEL' ),
    'REVELATIONS_AI_GENERATION_ENABLED' =>
        getenv( 'REVELATIONS_AI_GENERATION_ENABLED' ),
);

/*
 * Pre-transport blocking.
 */
$blocking_cases = array(
    'generation disabled' => array(
        'section' => 'news',
        'enabled' => false,
        'key' => true,
        'model' => true,
        'error' => 'ai_requests_disabled',
    ),
    'missing API key' => array(
        'section' => 'news',
        'enabled' => true,
        'key' => false,
        'model' => true,
        'error' => 'ai_key_missing',
    ),
    'missing model' => array(
        'section' => 'news',
        'enabled' => true,
        'key' => true,
        'model' => false,
        'error' => 'ai_model_missing',
    ),
    'empty source section' => array(
        'section' => '',
        'enabled' => true,
        'key' => true,
        'model' => true,
        'error' => 'unsupported_generation_section',
    ),
    'unsupported section' => array(
        'section' => 'unknown',
        'enabled' => true,
        'key' => true,
        'model' => true,
        'error' => 'unsupported_generation_section',
    ),
    'legacy podcast' => array(
        'section' => 'podcast',
        'enabled' => true,
        'key' => true,
        'model' => true,
        'error' => 'unsupported_generation_section',
    ),
);

foreach ( $blocking_cases as $label => $case ) {
    revelations_integration_reset(
        $case['section']
    );
    revelations_integration_configure(
        $case['enabled'],
        $case['key'],
        $case['model']
    );

    $before =
        revelations_integration_state_signature();
    $result =
        revelations_editorial_generate_draft_with_ai(
            100
        );

    revelations_integration_check(
        is_wp_error( $result ) &&
        $case['error'] === $result->get_error_code(),
        $label . ' returns the expected blocking error'
    );
    revelations_integration_check(
        0 === $revelations_integration_transport_calls,
        $label . ' blocks fake transport'
    );
    revelations_integration_check(
        $before ===
            revelations_integration_state_signature() &&
        array() ===
            revelations_integration_version_ids(),
        $label . ' leaves all draft, author, category and metadata state unchanged'
    );
}

/*
 * Successful generation for every supported section.
 */
$supported_sections = array(
    'news',
    'tech',
    'people',
    'places',
    'unspoken',
);

foreach ( $supported_sections as $section ) {
    revelations_integration_reset( $section );
    $category_before = wp_get_post_categories( 100 );
    $author_before = get_post( 100 )->post_author;
    $displayed_author_before = get_post_meta(
        100,
        'revelations_author',
        true
    );
    revelations_integration_mark_reviewed( 100 );

    $result =
        revelations_editorial_generate_draft_with_ai(
            100
        );
    if ( is_wp_error( $result ) ) {
        fwrite(
            STDERR,
            "Successful generation regression: " .
            $result->get_error_code() .
            ' — ' .
            $result->get_error_message() .
            ' ' . json_encode( $result->data ) .
            "\n"
        );
        exit( 1 );
    }
    $draft = get_post( 100 );
    $request_body =
        revelations_integration_request_body();
    $profile =
        revelations_editorial_ai_generation_profile(
            $section
        );
    $expected_profile_prompt =
        revelations_editorial_ai_generation_profile_prompt(
            $section,
            $profile ?? array()
        );
    $alternative_titles =
        revelations_integration_meta_list(
            100,
            '_revelations_ai_alternative_titles'
        );
    $flags =
        revelations_integration_meta_list(
            100,
            '_revelations_ai_fact_check_flags'
        );
    $quotes =
        revelations_integration_meta_list(
            100,
            '_revelations_ai_direct_quotes'
        );
    $research_fixture =
        revelations_integration_research();
    $primary_claim =
        $research_fixture['sources'][0]['claims'][0];
    $canonical_evidence_map =
        revelations_editorial_ai_source_evidence_map(
            revelations_editorial_ai_source_evidence_units(
                revelations_editorial_ai_format_source_evidence_units(
                    array(
                        array(
                            'id' => 'p001',
                            'text' =>
                                '[s001] Claim: ' .
                                $primary_claim['claim'] .
                                "\nSupport: " .
                                $primary_claim['context'],
                        ),
                    )
                )
            )
        );
    $review =
        revelations_editorial_review_status( 100 );

    revelations_integration_check(
        is_array( $result ) &&
        3 === $revelations_integration_transport_calls,
        $section . ' completes through the fake structured transport'
    );
    revelations_integration_check(
        str_contains(
            (string) (
                $request_body['instructions'] ?? ''
            ),
            $expected_profile_prompt
        ) &&
        'json_schema' === (
            $request_body['text']['format']['type']
            ?? ''
        ) &&
        str_contains(
            (string) (
                $request_body['input'] ?? ''
            ),
            'Current section: ' . $section
        ) &&
            ! array_key_exists(
                'source_section',
            $request_body['text']['format']['schema'][
                'properties'
            ] ?? array()
        ) &&
        str_contains(
            (string) (
                $request_body['input'] ?? ''
            ),
            '[p001] '
        ),
        $section . ' uses its profile and server-only section context in the production prompt and schema'
    );
    revelations_integration_check(
        $draft instanceof WP_Post &&
        revelations_integration_article(
            $section
        )['recommended_title'] ===
            $draft->post_title &&
        2 === count( $alternative_titles ) &&
        $section === get_post_meta(
            100,
            '_revelations_ai_source_section',
            true
        ),
        $section . ' stores recommended, alternative and server source-section metadata'
    );
    revelations_integration_check(
        $category_before ===
            wp_get_post_categories( 100 ) &&
        $author_before === $draft->post_author &&
        $displayed_author_before === get_post_meta(
            100,
            'revelations_author',
            true
        ) &&
        'draft' === $draft->post_status,
        $section . ' preserves category, author and unpublished status'
    );
    revelations_integration_check(
        ! empty( $result['suggested_section'] ) &&
        array( 10 ) ===
            wp_get_post_categories( 100 ) &&
        ! empty( $flags ) &&
        array() === $quotes &&
        str_contains(
            $draft->post_content,
            '<!-- wp:paragraph -->'
        ),
        $section . ' stores advisory, server-derived flags and Gutenberg content'
    );
    revelations_integration_check(
        'outdated' === $review['status'],
        $section . ' generation invalidates the existing Human Review'
    );
}

/* Runtime editorial configuration is re-read for every generation request. */
revelations_integration_reset( 'news' );
$revelations_integration_settings = array(
    'editorial_policy' => 'Runtime Policy Alpha: retain human editorial responsibility.',
    'tone_of_voice' => 'Runtime Tone Alpha.',
    'preferred_article_structure' => 'Runtime Structure Alpha.',
    'banned_words' => 'Runtime banned alpha',
);
$runtime_policy_result = revelations_editorial_generate_draft_with_ai( 100 );
$runtime_requests = $revelations_integration_transport_requests;
$runtime_research = json_decode( (string) ( $runtime_requests[0]['arguments']['body'] ?? '' ), true );
$runtime_brief = json_decode( (string) ( $runtime_requests[1]['arguments']['body'] ?? '' ), true );
$runtime_final = json_decode( (string) ( $runtime_requests[2]['arguments']['body'] ?? '' ), true );
revelations_integration_check(
    is_array( $runtime_policy_result ) &&
    ! str_contains( (string) ( $runtime_research['instructions'] ?? '' ), 'Runtime Policy Alpha' ) &&
    str_contains( (string) ( $runtime_brief['instructions'] ?? '' ), 'Runtime Policy Alpha' ) &&
    str_contains( (string) ( $runtime_final['instructions'] ?? '' ), 'Runtime Policy Alpha' ) &&
    str_contains( (string) ( $runtime_final['instructions'] ?? '' ), 'Runtime Tone Alpha' ) &&
    str_contains( (string) ( $runtime_final['instructions'] ?? '' ), 'Runtime Structure Alpha' ) &&
    str_contains( (string) ( $runtime_final['instructions'] ?? '' ), 'Runtime banned alpha' ),
    'runtime editorial settings reach only their configured brief/final stages'
);
$revelations_integration_settings['editorial_policy'] = 'Runtime Policy Beta: use a distinct angle.';
$runtime_policy_second_result = revelations_editorial_generate_draft_with_ai( 100 );
$runtime_policy_second_request = revelations_integration_request_body();
revelations_integration_check(
    is_array( $runtime_policy_second_result ) &&
    str_contains( (string) ( $runtime_policy_second_request['instructions'] ?? '' ), 'Runtime Policy Beta' ) &&
    ! str_contains( (string) ( $runtime_policy_second_request['instructions'] ?? '' ), 'Runtime Policy Alpha' ),
    'updated stored Editorial Policy is used by the next generation without deploy'
);

/* Every Responses boundary retains a bounded, stage-specific failure record. */
$responses_failure_cases = array(
    'transport' => array( 'suffix' => 'transport_failed', 'response' => static fn( bool $research, bool $brief ): WP_Error => new WP_Error( 'synthetic_transport', 'Synthetic transport failure.' ) ),
    'http' => array( 'suffix' => 'http_failed', 'response' => static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 429, 'failed', '{}', $research ) ),
    'invalid_json' => array( 'suffix' => 'invalid_response_json', 'response' => static fn( bool $research, bool $brief ): array => array( 'response' => array( 'code' => 200 ), 'headers' => array( 'x-request-id' => 'synthetic-request-id' ), 'body' => '{not-json' ) ),
    'incomplete' => array( 'suffix' => 'incomplete_response', 'response' => static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 200, 'incomplete', '{}', $research ) ),
    'failed' => array( 'suffix' => 'response_failed', 'response' => static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 200, 'failed', '{}', $research ) ),
    'invalid_structured_output' => array( 'suffix' => 'invalid_structured_output', 'response' => static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 200, 'completed', 'not-json', $research ) ),
);
$responses_stages = array(
    'research' => array( 'call' => 0, 'code_prefix' => 'research', 'diagnostic_stage' => 'research' ),
    'brief' => array( 'call' => 1, 'code_prefix' => 'brief', 'diagnostic_stage' => 'editorial_brief' ),
    'final' => array( 'call' => 2, 'code_prefix' => 'final', 'diagnostic_stage' => 'final_generation' ),
);
foreach ( $responses_stages as $stage_name => $stage ) foreach ( $responses_failure_cases as $case_name => $case ) {
    revelations_integration_reset( 'news' );
    $revelations_integration_transport_overrides[ $stage['call'] ] = $case['response'];
    $before = revelations_integration_state_signature();
    $result = revelations_editorial_generate_draft_with_ai( 100 );
    $error_data = is_wp_error( $result ) ? $result->get_error_data( $result->get_error_code() ) : array();
    $diagnostics = is_array( $error_data['research_diagnostics'] ?? null ) ? $error_data['research_diagnostics'] : (array) ( $error_data['generation_diagnostics'] ?? array() );
    $response_records = is_array( $diagnostics['responses_diagnostics'] ?? null ) ? $diagnostics['responses_diagnostics'] : array();
    $response_diagnostic = is_array( $response_records ) ? end( $response_records ) : array();
    revelations_integration_check(
        is_wp_error( $result ) &&
        $stage['code_prefix'] . '_' . $case['suffix'] === $result->get_error_code() &&
        $stage['diagnostic_stage'] === ( $response_diagnostic['stage'] ?? '' ) &&
        count( $response_records ) === ( $stage['call'] + 1 ) &&
        ( 'transport' === $case_name ? ! empty( $response_diagnostic['transport_error'] ) : 0 < (int) ( $response_diagnostic['body_chars'] ?? 0 ) ) &&
        ! str_contains( wp_json_encode( $diagnostics ), 'Synthetic transport failure.' ) &&
        ! str_contains( wp_json_encode( $diagnostics ), 'raw-response-marker' ),
        $stage_name . ' ' . $case_name . ' Responses failure has a distinct bounded diagnostic'
    );
    revelations_integration_check(
        $before === revelations_integration_state_signature() &&
        array() === revelations_integration_version_ids(),
        $stage_name . ' ' . $case_name . ' Responses failure preserves the draft before writes'
    );
}
revelations_integration_reset( 'news' );
$revelations_integration_transport_overrides[2] = static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 200, 'completed', '{}', false );
$final_schema_result = revelations_editorial_generate_draft_with_ai( 100 );
$final_schema_data = is_wp_error( $final_schema_result ) ? $final_schema_result->get_error_data( $final_schema_result->get_error_code() ) : array();
revelations_integration_check(
    is_wp_error( $final_schema_result ) && 'final_schema_validation_failed' === $final_schema_result->get_error_code() &&
    ! empty( $final_schema_data['generation_diagnostics']['responses_diagnostics'] ),
    'final completed response with missing structured fields has a distinct schema failure'
);

/* API-compatible schemas retain all uniqueness invariants on the server. */
$duplicate_brief_cases = array(
    'duplicate pillar evidence IDs' => static function( array $brief ): array {
        $brief['factual_pillars'][0]['evidence_ids'] = array( 'p001', 'p001' );
        return $brief;
    },
    'duplicate pillar order' => static function( array $brief ): array {
        $brief['pillar_order'] = array( 0, 0, 2 );
        return $brief;
    },
    'duplicate sensitive evidence IDs' => static function( array $brief ): array {
        $brief['sensitive_evidence_ids'] = array( 'p001', 'p001' );
        return $brief;
    },
);
foreach ( $duplicate_brief_cases as $label => $mutate_brief ) {
    revelations_integration_reset( 'news' );
    $duplicate_brief = $mutate_brief( revelations_integration_brief() );
    $revelations_integration_transport_overrides[1] = static fn( bool $research, bool $brief ): array => revelations_integration_responses_fixture( 200, 'completed', wp_json_encode( $duplicate_brief ), false );
    $before = revelations_integration_state_signature();
    $result = revelations_editorial_generate_draft_with_ai( 100 );
    revelations_integration_check(
        is_wp_error( $result ) &&
        'brief_schema_validation_failed' === $result->get_error_code() &&
        2 === $revelations_integration_transport_calls &&
        $before === revelations_integration_state_signature(),
        $label . ' is rejected server-side before final generation or writes'
    );
}
revelations_integration_reset( 'news' );
$duplicate_final = revelations_integration_article( 'news' );
$duplicate_final['blocks'][0]['evidence_ids'] = array( 'p001', 'p001' );
$revelations_integration_transport_article = $duplicate_final;
$before = revelations_integration_state_signature();
$duplicate_final_result = revelations_editorial_generate_draft_with_ai( 100 );
revelations_integration_check(
    is_wp_error( $duplicate_final_result ) &&
    'invalid_fact_check_evidence' === $duplicate_final_result->get_error_code() &&
    3 === $revelations_integration_transport_calls &&
    $before === revelations_integration_state_signature(),
    'duplicate final block evidence IDs remain rejected server-side before writes'
);

/*
 * Over-maximum output remains a usable unpublished draft.
 */
revelations_integration_reset( 'news' );

$overlong_article =
    revelations_integration_article( 'news' );

$overlong_article['blocks'][] = array(
    'type' =>
        'paragraph',
    'text' =>
        trim(
            str_repeat(
                'context ',
                850
            )
        ),
    'heading_level' =>
        0,
    'items' =>
        array(),
    'evidence_ids' => array( 'p001' ),
);

$revelations_integration_transport_article =
    $overlong_article;

$overlong_result =
    revelations_editorial_generate_draft_with_ai(
        100
    );

$overlong_draft = get_post( 100 );

revelations_integration_check(
    is_array( $overlong_result ) &&
    (
        $overlong_result['word_count']
        ?? 0
    ) > 800 &&
    'over_max' === (
        $overlong_result[
            'word_count_status'
        ] ?? ''
    ) &&
    '' !== (
        $overlong_result[
            'word_count_warning'
        ] ?? ''
    ),
    'over-maximum generation completes with a warning'
);

revelations_integration_check(
    $overlong_draft instanceof WP_Post &&
    'draft' ===
        $overlong_draft->post_status &&
    'ai_generated' ===
        get_post_meta(
            100,
            '_revelations_draft_kind',
            true
        ) &&
    'over_max' ===
        get_post_meta(
            100,
            '_revelations_ai_word_count_status',
            true
        ) &&
    '' !==
        get_post_meta(
            100,
            '_revelations_ai_word_count_warning',
            true
        ),
    'over-maximum generation is preserved as an unpublished AI draft'
);

/*
 * Validation failures after transport but before WordPress writes.
 */
$validation_cases = array(
    'duplicate titles' => array(
        'section' => 'news',
        'error' => 'invalid_title_set',
        'mutate' =>
            static function ( array $article ): array {
                $article['alternative_titles'][0] =
                    $article['recommended_title'];
                return $article;
            },
    ),
    'invalid advisory section combination' => array(
        'section' => 'news',
        'error' => 'invalid_section_advisory',
        'mutate' =>
            static function ( array $article ): array {
                $article['section_mismatch'] = false;
                return $article;
            },
    ),
    'quote missing from source snapshot' => array(
        'section' => 'news',
        'error' => 'invalid_direct_quote',
        'mutate' =>
            static function ( array $article ): array {
                $article['direct_quotes'][0]['quote_text'] =
                    'This quotation is absent from the source.';
                return $article;
            },
    ),
    'direct quote with unknown evidence ID' => array(
        'section' => 'news',
        'error' => 'invalid_direct_quote',
        'mutate' =>
            static function ( array $article ): array {
                $article['direct_quotes'][0]['evidence_id'] =
                    'p999';
                return $article;
            },
    ),
    'unknown source evidence reference' => array(
        'section' => 'news',
        'error' => 'invalid_fact_check_evidence',
        'mutate' =>
            static function ( array $article ): array {
                $article['blocks'][0]['evidence_ids'] = array( 'p999' );
                return $article;
            },
    ),
    'sensitive claim without flag' => array(
        'section' => 'news',
        'error' => 'missing_fact_check_flag',
        'mutate' =>
            static function ( array $article ): array {
                $article['fact_check_flags'] = array();
                $article['direct_quotes'] = array();
                foreach ( $article['blocks'] as $index => $block ) unset( $article['blocks'][ $index ]['evidence_ids'] );
                $article['blocks'][4] = array(
                    'type' => 'paragraph',
                    'text' =>
                        'The system processed 77 tasks during evaluation.',
                    'heading_level' => 0,
                    'items' => array(),
                );
                return $article;
            },
    ),
    'matching excerpt and SEO description' => array(
        'section' => 'news',
        'error' => 'duplicate_descriptions',
        'mutate' =>
            static function ( array $article ): array {
                $article['seo_description'] =
                    $article['excerpt'];
                return $article;
            },
    ),
    'Unspoken without required flags' => array(
        'section' => 'unspoken',
        'error' => 'unspoken_fact_check_required',
        'mutate' =>
            static function ( array $article ): array {
                $article['fact_check_flags'] = array();
                foreach ( $article['blocks'] as $index => $block ) unset( $article['blocks'][ $index ]['evidence_ids'] );
                return $article;
            },
    ),
);

foreach ( $validation_cases as $label => $case ) {
    revelations_integration_reset(
        $case['section']
    );
    $revelations_integration_transport_article =
        $case['mutate'](
            revelations_integration_article(
                $case['section']
            )
        );
    $before =
        revelations_integration_state_signature();
    $result =
        revelations_editorial_generate_draft_with_ai(
            100
        );

    revelations_integration_check(
        is_wp_error( $result ) &&
        $case['error'] === $result->get_error_code(),
        $label . ' returns the expected validation error'
    );
    revelations_integration_check(
        3 === $revelations_integration_transport_calls,
        $label . ' uses fake transport before server validation'
    );
    revelations_integration_check(
        $before ===
            revelations_integration_state_signature() &&
        array() ===
            revelations_integration_version_ids(),
        $label . ' creates no draft writes, metadata or version backup'
    );
}

/*
 * Regeneration and restore of current-version metadata.
 */
revelations_integration_reset( 'news' );
$initial_result =
    revelations_editorial_generate_draft_with_ai( 100 );
$first_post = get_post( 100 );
$first_title = $first_post->post_title;
$first_content = $first_post->post_content;
$first_metadata =
    revelations_editorial_ai_review_metadata_for_draft(
        100
    );
$first_hash =
    revelations_editorial_review_content_hash( 100 );
$category_before_regeneration =
    wp_get_post_categories( 100 );
$author_before_regeneration =
    $first_post->post_author;
$displayed_author_before_regeneration =
    get_post_meta(
        100,
        'revelations_author',
        true
    );
revelations_integration_mark_reviewed( 100 );
$revelations_integration_transport_article =
    revelations_integration_article(
        'news',
        'regenerated'
    );
$regeneration_result =
    revelations_editorial_generate_draft_with_ai( 100 );
$version_ids =
    revelations_integration_version_ids();
$saved_version = isset( $version_ids[0] )
    ? get_post( $version_ids[0] )
    : null;
$regenerated_metadata =
    revelations_editorial_ai_review_metadata_for_draft(
        100
    );
$regenerated_review =
    revelations_editorial_review_status( 100 );
$regenerated_post = get_post( 100 );

revelations_integration_check(
    is_array( $initial_result ) &&
    is_array( $regeneration_result ) &&
    1 === count( $version_ids ),
    'regeneration completes and creates exactly one prior-version backup'
);
revelations_integration_check(
    $saved_version instanceof WP_Post &&
    str_contains(
        $saved_version->post_title,
        $first_title
    ) &&
    $first_content ===
        $saved_version->post_content,
    'regeneration backup preserves the previous title and content'
);
revelations_integration_check(
    $first_metadata !== $regenerated_metadata &&
    $regenerated_metadata['alternative_titles'] ===
        revelations_integration_article(
            'news',
            'regenerated'
        )['alternative_titles'],
    'regeneration makes new AI review metadata current'
);
revelations_integration_check(
    'outdated' === $regenerated_review['status'] &&
    $category_before_regeneration ===
        wp_get_post_categories( 100 ) &&
    $author_before_regeneration ===
        $regenerated_post->post_author &&
    $displayed_author_before_regeneration ===
        get_post_meta(
            100,
            'revelations_author',
            true
        ),
    'regeneration invalidates review while preserving category and author'
);

$regenerated_hash =
    revelations_integration_mark_reviewed( 100 );
$restore_result =
    revelations_editorial_ai_restore_version(
        100,
        $version_ids[0]
    );
$restored_post = get_post( 100 );
$restored_metadata =
    revelations_editorial_ai_review_metadata_for_draft(
        100
    );
$restored_review =
    revelations_editorial_review_status( 100 );

revelations_integration_check(
    is_array( $restore_result ) &&
    $first_title === $restored_post->post_title &&
    $first_content === $restored_post->post_content &&
    $first_metadata === $restored_metadata,
    'restore makes saved title, content and metadata current'
);
revelations_integration_check(
    'outdated' === $restored_review['status'] &&
    $regenerated_hash !==
        $restored_review['current_hash'],
    'restore makes the restored draft require a fresh Human Review'
);
revelations_integration_check(
    $category_before_regeneration ===
        wp_get_post_categories( 100 ) &&
    $author_before_regeneration ===
        $restored_post->post_author &&
    $displayed_author_before_regeneration ===
        get_post_meta(
            100,
            'revelations_author',
            true
        ) &&
    'draft' === $restored_post->post_status,
    'restore preserves category, author and unpublished status'
);

/*
 * Legacy private version without stage 5 metadata.
 */
revelations_integration_reset( 'tech' );
$legacy_initial =
    revelations_editorial_generate_draft_with_ai( 100 );
$legacy_category =
    wp_get_post_categories( 100 );
$legacy_author = get_post( 100 )->post_author;
$legacy_displayed_author =
    get_post_meta(
        100,
        'revelations_author',
        true
    );
$legacy_version_id =
    revelations_editorial_ai_create_version_backup(
        100
    );
$legacy_optional_keys = array(
    '_rev_ai_alternative_titles',
    '_rev_ai_source_section',
    '_rev_ai_section_mismatch',
    '_rev_ai_suggested_section',
    '_rev_ai_section_mismatch_reason',
    '_rev_ai_fact_check_flags',
    '_rev_ai_direct_quotes',
);

foreach ( $legacy_optional_keys as $key ) {
    delete_post_meta(
        (int) $legacy_version_id,
        $key
    );
}

wp_update_post(
    array(
        'ID' => 100,
        'post_title' => 'Current draft before legacy restore',
        'post_content' =>
            '<!-- wp:paragraph --><p>Current changed content.</p><!-- /wp:paragraph -->',
    ),
    true
);
revelations_integration_mark_reviewed( 100 );

$legacy_throwable = null;
$legacy_restore = null;
set_error_handler(
    static function (
        int $severity,
        string $message,
        string $file,
        int $line
    ): never {
        throw new ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }
);

try {
    $legacy_restore =
        revelations_editorial_ai_restore_version(
            100,
            (int) $legacy_version_id
        );
} catch ( Throwable $throwable ) {
    $legacy_throwable = $throwable;
} finally {
    restore_error_handler();
}

$legacy_metadata =
    revelations_editorial_ai_review_metadata_for_draft(
        100
    );
$legacy_post = get_post( 100 );

revelations_integration_check(
    is_array( $legacy_initial ) &&
    is_int( $legacy_version_id ) &&
    null === $legacy_throwable &&
    is_array( $legacy_restore ),
    'legacy AI version restores without warning or fatal error'
);
revelations_integration_check(
    ! revelations_editorial_ai_review_has_metadata(
        $legacy_metadata
    ),
    'legacy restore safely clears unavailable optional AI review metadata'
);
revelations_integration_check(
    $legacy_category ===
        wp_get_post_categories( 100 ) &&
    $legacy_author === $legacy_post->post_author &&
    $legacy_displayed_author ===
        get_post_meta(
            100,
            'revelations_author',
            true
        ) &&
    'draft' === $legacy_post->post_status,
    'legacy restore preserves category, author and unpublished status'
);

foreach (
    $saved_environment
    as $environment_key => $environment_value
) {
    if ( false === $environment_value ) {
        putenv( $environment_key );
        continue;
    }

    putenv(
        $environment_key .
        '=' .
        $environment_value
    );
}

echo "\n" .
    'AI generation integration diagnostics: ' .
    $revelations_integration_passed .
    ' passed, ' .
    $revelations_integration_failed .
    " failed.\n";

exit(
    0 === $revelations_integration_failed
        ? 0
        : 1
);

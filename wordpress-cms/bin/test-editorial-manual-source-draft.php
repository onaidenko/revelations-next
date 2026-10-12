<?php
/**
 * Isolated regression diagnostics for manual source-draft intake.
 *
 * No WordPress bootstrap, database, network fetch or AI request is used.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

class WP_Post {
    public int $ID;
    public string $post_type;
    public string $post_status;
    public string $post_title;
    public string $post_content;
    public string $post_excerpt;
    public int $post_author;

    /** @param array<string, mixed> $data */
    public function __construct( array $data ) {
        $this->ID = (int) ( $data['ID'] ?? 0 );
        $this->post_type = (string) ( $data['post_type'] ?? 'post' );
        $this->post_status = (string) ( $data['post_status'] ?? 'draft' );
        $this->post_title = (string) ( $data['post_title'] ?? '' );
        $this->post_content = (string) ( $data['post_content'] ?? '' );
        $this->post_excerpt = (string) ( $data['post_excerpt'] ?? '' );
        $this->post_author = (int) ( $data['post_author'] ?? 0 );
    }
}

class WP_Term {
    public int $term_id;

    public function __construct( int $term_id ) {
        $this->term_id = $term_id;
    }
}

$revelations_manual_draft_actions = array();
$revelations_manual_draft_posts = array();
$revelations_manual_draft_meta = array();
$revelations_manual_draft_next_id = 100;
$revelations_manual_draft_redirect = '';
$revelations_manual_draft_fetch_calls = 0;

function add_action( string $hook, callable $callback ): void {
    global $revelations_manual_draft_actions;

    $revelations_manual_draft_actions[ $hook ] = $callback;
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

function sanitize_key( mixed $value ): string {
    return preg_replace(
        '/[^a-z0-9_-]/',
        '',
        strtolower( (string) $value )
    ) ?? '';
}

function sanitize_text_field( mixed $value ): string {
    return trim( strip_tags( (string) $value ) );
}

function sanitize_textarea_field( mixed $value ): string {
    return trim( strip_tags( (string) $value ) );
}

function wp_unslash( mixed $value ): mixed {
    return $value;
}

function esc_url_raw( mixed $value ): string {
    return trim( (string) $value );
}

function esc_url( mixed $value ): string {
    return (string) $value;
}

function esc_html( mixed $value ): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function wp_http_validate_url( string $url ): bool {
    $parts = wp_parse_url( $url );

    return is_array( $parts ) &&
        isset( $parts['host'] ) &&
        in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true );
}

function wp_parse_url( string $url, int $component = -1 ): mixed {
    return parse_url( $url, $component );
}

function untrailingslashit( string $value ): string {
    return rtrim( $value, '/' );
}

function current_user_can( string $capability ): bool {
    return 'manage_options' === $capability;
}

function check_admin_referer( string $action ): void {
}

function admin_url( string $path = '' ): string {
    return 'https://cms.example.test/' . ltrim( $path, '/' );
}

/** @param array<string, scalar> $args */
function add_query_arg( array $args, string $url ): string {
    return $url . '?' . http_build_query( $args );
}

function wp_safe_redirect( string $location ): void {
    global $revelations_manual_draft_redirect;

    $revelations_manual_draft_redirect = $location;
}

function get_current_user_id(): int {
    return 7;
}

function get_posts( array $args ): array {
    return array();
}

function is_wp_error( mixed $value ): bool {
    return false;
}

function wp_insert_post( array $data, bool $wp_error = false ): int {
    global $revelations_manual_draft_next_id,
        $revelations_manual_draft_posts;

    $post_id = $revelations_manual_draft_next_id++;
    $data['ID'] = $post_id;
    $revelations_manual_draft_posts[ $post_id ] = new WP_Post( $data );

    return $post_id;
}

function get_post( int $post_id ): ?WP_Post {
    global $revelations_manual_draft_posts;

    return isset( $revelations_manual_draft_posts[ $post_id ] )
        ? clone $revelations_manual_draft_posts[ $post_id ]
        : null;
}

function get_post_type( int $post_id ): string|false {
    $post = get_post( $post_id );

    return $post instanceof WP_Post ? $post->post_type : false;
}

function get_post_status( int $post_id ): string|false {
    $post = get_post( $post_id );

    return $post instanceof WP_Post ? $post->post_status : false;
}

function get_the_title( int $post_id ): string {
    $post = get_post( $post_id );

    return $post instanceof WP_Post ? $post->post_title : '';
}

function get_post_meta(
    int $post_id,
    string $key,
    bool $single = false
): mixed {
    global $revelations_manual_draft_meta;

    if ( ! array_key_exists( $key, $revelations_manual_draft_meta[ $post_id ] ?? array() ) ) {
        return $single ? '' : array();
    }

    $value = $revelations_manual_draft_meta[ $post_id ][ $key ];

    return $single ? $value : array( $value );
}

function update_post_meta(
    int $post_id,
    string $key,
    mixed $value
): void {
    global $revelations_manual_draft_meta;

    $revelations_manual_draft_meta[ $post_id ][ $key ] = $value;
}

function delete_post_meta( int $post_id, string $key ): void {
    global $revelations_manual_draft_meta;

    unset( $revelations_manual_draft_meta[ $post_id ][ $key ] );
}

function get_category_by_slug( string $slug ): ?WP_Term {
    return null;
}

function wp_slash( mixed $value ): mixed {
    return $value;
}

function revelations_editorial_fetch_source_text( string $url ): array {
    global $revelations_manual_draft_fetch_calls;

    $revelations_manual_draft_fetch_calls++;

    return array(
        'ok' => true,
        'text' => str_repeat( 'Synthetic source evidence. ', 30 ),
        'paragraphs' => 1,
        'method' => 'synthetic',
        'http_status' => 200,
        'duration_ms' => 1,
    );
}

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-storage.php';
require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-manual.php';
require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-drafts.php';
require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-source-snapshot.php';

/** @return array<string, mixed> */
function revelations_manual_draft_state(): array {
    global $revelations_manual_draft_posts,
        $revelations_manual_draft_meta,
        $revelations_manual_draft_next_id,
        $revelations_manual_draft_redirect,
        $revelations_manual_draft_fetch_calls;

    return array(
        'posts' => array_map(
            static fn ( WP_Post $post ): array => get_object_vars( $post ),
            $revelations_manual_draft_posts
        ),
        'meta' => $revelations_manual_draft_meta,
        'next_id' => $revelations_manual_draft_next_id,
        'redirect' => $revelations_manual_draft_redirect,
        'fetch_calls' => $revelations_manual_draft_fetch_calls,
    );
}

/** @param array<string, mixed> $state */
function revelations_manual_draft_restore_state( array $state ): void {
    global $revelations_manual_draft_posts,
        $revelations_manual_draft_meta,
        $revelations_manual_draft_next_id;

    foreach ( (array) ( $state['posts'] ?? array() ) as $post_id => $post ) {
        $revelations_manual_draft_posts[ (int) $post_id ] = new WP_Post(
            (array) $post
        );
    }

    $revelations_manual_draft_meta = (array) ( $state['meta'] ?? array() );
    $revelations_manual_draft_next_id = (int) ( $state['next_id'] ?? 100 );
}

function revelations_manual_draft_find_post_id( string $post_type ): int {
    global $revelations_manual_draft_posts;

    foreach ( $revelations_manual_draft_posts as $post ) {
        if ( $post_type === $post->post_type ) {
            return $post->ID;
        }
    }

    return 0;
}

$scenario = '';
$encoded_state = '';

foreach ( array_slice( $argv, 1 ) as $argument ) {
    if ( str_starts_with( $argument, '--scenario=' ) ) {
        $scenario = substr( $argument, strlen( '--scenario=' ) );
    }

    if ( str_starts_with( $argument, '--state=' ) ) {
        $encoded_state = substr( $argument, strlen( '--state=' ) );
    }
}

if ( '' !== $scenario ) {
    $decoded_state = json_decode(
        base64_decode( $encoded_state, true ) ?: '{}',
        true
    );

    if ( is_array( $decoded_state ) ) {
        revelations_manual_draft_restore_state( $decoded_state );
    }

    register_shutdown_function(
        static function (): void {
            echo json_encode( revelations_manual_draft_state() );
        }
    );

    if ( str_starts_with( $scenario, 'manual-' ) ) {
        $section = 'news';
        $title = 'A manual source title';
        $url = 'https://example.test/source';
        $summary = '';

        if ( 'manual-with-summary' === $scenario ) {
            $summary = 'Editor-provided source context.';
        }

        if ( 'manual-missing-url' === $scenario ) {
            $url = '';
        }

        if ( 'manual-missing-title' === $scenario ) {
            $title = '';
        }

        if ( 'manual-invalid-section' === $scenario ) {
            $section = 'invalid';
        }

        $_POST = array(
            'candidate_title' => $title,
            'source_url' => $url,
            'source_name' => 'Example Source',
            'section' => $section,
            'summary' => $summary,
        );

        $revelations_manual_draft_actions[
            'admin_post_revelations_add_editorial_candidate'
        ]();
    }

    $candidate_id = revelations_manual_draft_find_post_id( 'rev_candidate' );

    if ( 'source-draft' === $scenario ) {
        $_POST = array( 'candidate_id' => $candidate_id );
        $revelations_manual_draft_actions[
            'admin_post_revelations_create_source_draft'
        ]();
    }

    if ( 'source-snapshot' === $scenario ) {
        $_POST = array(
            'candidate_id' => $candidate_id,
            'draft_id' => revelations_manual_draft_find_post_id( 'post' ),
        );
        $revelations_manual_draft_actions[
            'admin_post_revelations_fetch_source_snapshot'
        ]();
    }

    exit( 1 );
}

$failures = 0;
$cases = 0;

$assert = static function ( bool $condition, string $label ) use ( &$failures, &$cases ): void {
    $cases++;

    if ( $condition ) {
        echo 'PASS: ' . $label . PHP_EOL;
        return;
    }

    $failures++;
    echo 'FAIL: ' . $label . PHP_EOL;
};

/** @param array<string, mixed> $state */
$run = static function ( string $scenario, array $state = array() ): array {
    $command = array(
        PHP_BINARY,
        __FILE__,
        '--scenario=' . $scenario,
        '--state=' . base64_encode( json_encode( $state ) ?: '{}' ),
    );
    $pipes = array();
    $process = proc_open(
        $command,
        array(
            0 => array( 'pipe', 'r' ),
            1 => array( 'pipe', 'w' ),
            2 => array( 'pipe', 'w' ),
        ),
        $pipes
    );

    if ( ! is_resource( $process ) ) {
        throw new RuntimeException( 'Unable to run scenario.' );
    }

    fclose( $pipes[0] );
    $output = stream_get_contents( $pipes[1] );
    $errors = stream_get_contents( $pipes[2] );
    fclose( $pipes[1] );
    fclose( $pipes[2] );
    $exit_code = proc_close( $process );
    $state = json_decode( trim( $output ), true );

    if ( 0 !== $exit_code || ! is_array( $state ) ) {
        throw new RuntimeException( 'Scenario failed: ' . $errors );
    }

    return $state;
};

/** @param array<string, mixed> $state */
$post = static function ( array $state, string $type ): array {
    foreach ( (array) ( $state['posts'] ?? array() ) as $post ) {
        if ( $type === ( $post['post_type'] ?? '' ) ) {
            return (array) $post;
        }
    }

    return array();
};

$empty_manual = $run( 'manual-empty' );
$empty_candidate = $post( $empty_manual, 'rev_candidate' );
$assert(
    '' === ( $empty_manual['meta'][100]['_rev_summary'] ?? null ),
    'manual candidate preserves an empty optional summary'
);

$empty_draft_state = $run( 'source-draft', $empty_manual );
$empty_draft = $post( $empty_draft_state, 'post' );
$assert(
    'draft' === ( $empty_draft['post_status'] ?? '' ) &&
        '' === ( $empty_draft['post_excerpt'] ?? null ) &&
        str_contains( (string) ( $empty_draft['post_content'] ?? '' ), 'Source:' ) &&
        ! str_contains( (string) ( $empty_draft['post_content'] ?? '' ), '<p></p>' ),
    'empty-summary manual source creates a source-only WordPress draft'
);

$empty_snapshot_state = $run( 'source-snapshot', $empty_draft_state );
$assert(
    1 === ( $empty_snapshot_state['fetch_calls'] ?? 0 ) &&
        '' !== ( $empty_snapshot_state['meta'][100]['_rev_source_text'] ?? '' ),
    'empty-summary source draft reaches the synthetic snapshot-fetch path'
);

$summary_manual = $run( 'manual-with-summary' );
$summary_draft_state = $run( 'source-draft', $summary_manual );
$summary_draft = $post( $summary_draft_state, 'post' );
$assert(
    'Editor-provided source context.' === ( $summary_draft['post_excerpt'] ?? '' ) &&
        str_contains(
            (string) ( $summary_draft['post_content'] ?? '' ),
            'Editor-provided source context.'
        ),
    'editor-provided summary retains the existing draft content behavior'
);

foreach ( array(
    'manual-missing-url' => 'missing URL fails manual intake',
    'manual-missing-title' => 'missing title fails manual intake',
    'manual-invalid-section' => 'invalid section fails manual intake',
) as $scenario => $label ) {
    $state = $run( $scenario );

    $assert(
        array() === (array) ( $state['posts'] ?? array() ) &&
            str_contains(
                (string) ( $state['redirect'] ?? '' ),
                'candidate_error=invalid'
            ),
        $label
    );
}

echo PHP_EOL . sprintf(
    'Manual source-draft diagnostics: %d passed, %d failed.',
    $cases - $failures,
    $failures
) . PHP_EOL;

exit( $failures > 0 ? 1 : 0 );

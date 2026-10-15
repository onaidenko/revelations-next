<?php
/**
 * Isolated diagnostics for the required Revelation publication boundary.
 *
 * This script does not load WordPress, call OpenAI or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$revelations_revelation_filters = array();
$revelations_revelation_meta = array();
$revelations_revelation_status = array(
    1 => 'draft',
    2 => 'publish',
);

class WP_Post {
}

class WP_Error {
}

class WP_REST_Request {
}

function add_filter(
    string $hook,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): void {
    global $revelations_revelation_filters;

    $revelations_revelation_filters[ $hook ][] = $callback;
}

function add_action( mixed ...$arguments ): void {
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

function get_post_type( int $post_id ): string {
    return $post_id > 0 ? 'post' : '';
}

function get_post_status( int $post_id ): string {
    global $revelations_revelation_status;

    return $revelations_revelation_status[ $post_id ] ?? 'draft';
}

function get_post_meta(
    int $post_id,
    string $key,
    bool $single = true
): mixed {
    global $revelations_revelation_meta;

    return $revelations_revelation_meta[ $post_id ][ $key ] ?? '';
}

function sanitize_textarea_field( mixed $value ): string {
    return trim( strip_tags( (string) $value ) );
}

function wp_is_serving_rest_request(): bool {
    return false;
}

function is_wp_error( mixed $value ): bool {
    return $value instanceof WP_Error;
}

function wp_unslash( mixed $value ): mixed {
    return $value;
}

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-publish-gate.php';

$passed = 0;
$failed = 0;

function revelations_publish_gate_test(
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

revelations_publish_gate_test(
    'The Revelation is required before publication.' ===
        revelations_editorial_publish_gate_reason( 1 ),
    'empty Revelation blocks a manual draft publication'
);

$revelations_revelation_meta[1]['revelations_revelation'] =
    'The article makes the human editorial boundary visible.';

revelations_publish_gate_test(
    '' === revelations_editorial_publish_gate_reason( 1 ),
    'manual draft with a Revelation clears the Revelation publication gate'
);

$revelations_revelation_meta[1]['revelations_revelation'] = trim(
    str_repeat( 'word ', 181 )
);

revelations_publish_gate_test(
    'THE REVELATION must not exceed 180 words.' ===
        revelations_editorial_publish_gate_reason( 1 ),
    'over-limit Revelation blocks publication readiness'
);

$revelations_revelation_meta[2]['revelations_revelation'] = '';

revelations_publish_gate_test(
    '' === revelations_editorial_publish_gate_reason( 2 ),
    'existing published article is not retroactively blocked'
);

$fallback =
    $revelations_revelation_filters[
        'wp_insert_post_data'
    ][0];

$draft_data = array(
    'post_type'   => 'post',
    'post_status' => 'draft',
    'post_title'  => 'Manual draft',
    'post_content'=> '',
    'post_excerpt'=> '',
);

revelations_publish_gate_test(
    $draft_data === $fallback(
        $draft_data,
        array( 'ID' => 1 )
    ),
    'manual draft save remains allowed with an empty Revelation'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

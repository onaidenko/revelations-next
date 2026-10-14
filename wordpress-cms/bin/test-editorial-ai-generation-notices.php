<?php
/**
 * Isolated regression for draft-scoped AI-generation UI notices.
 *
 * This script does not load WordPress, call OpenAI or access a database.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

$revelations_notice_transients = array();

function add_action( mixed ...$arguments ): void {
}

function get_current_user_id(): int {
    return 7;
}

function get_transient( string $key ): mixed {
    global $revelations_notice_transients;

    return $revelations_notice_transients[ $key ] ?? false;
}

function set_transient(
    string $key,
    mixed $value,
    int $expiration
): bool {
    global $revelations_notice_transients;

    $revelations_notice_transients[ $key ] = $value;

    return true;
}

function delete_transient( string $key ): bool {
    global $revelations_notice_transients;

    unset( $revelations_notice_transients[ $key ] );

    return true;
}

function absint( mixed $value ): int {
    return abs( (int) $value );
}

function sanitize_key( mixed $value ): string {
    return preg_replace(
        '/[^a-z0-9_\-]/',
        '',
        strtolower( (string) $value )
    ) ?? '';
}

function wp_unslash( mixed $value ): mixed {
    return $value;
}

function esc_attr( mixed $value ): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function esc_html( mixed $value ): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-ai-generate.php';

$passed = 0;
$failed = 0;

function revelations_generation_notice_test(
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

function revelations_generation_notice_render(): string {
    ob_start();
    revelations_editorial_render_ai_generation_notices();

    return (string) ob_get_clean();
}

$key = revelations_editorial_ai_generation_result_key();

revelations_editorial_store_ai_generation_result(
    486,
    array(
        'ok'      => false,
        'message' =>
            'A secondary source is the sole support for the central or majority factual pillars.',
    )
);

$_GET = array(
    'ai_generation'       => 'error',
    'ai_generation_draft' => '486',
);

$failed_output = revelations_generation_notice_render();

revelations_generation_notice_test(
    str_contains(
        $failed_output,
        'A secondary source is the sole support'
    ),
    'draft-scoped failed generation displays its own error'
);

delete_transient( $key );

revelations_editorial_store_ai_generation_result(
    486,
    array(
        'ok'            => true,
        'message'       => 'AI article regenerated successfully.',
        'word_count'     => 610,
        'source_section' => 'news',
        'input_tokens'   => 100,
        'output_tokens'  => 200,
        'duration_ms'    => 3000,
    )
);

$_GET = array(
    'ai_generation'       => 'success',
    'ai_generation_draft' => '486',
);

$success_output = revelations_generation_notice_render();

revelations_generation_notice_test(
    str_contains(
        $success_output,
        'AI article regenerated successfully.'
    ) &&
    ! str_contains(
        $success_output,
        'A secondary source is the sole support'
    ),
    'successful regeneration replaces the previous UI error'
);

$_GET = array(
    'ai_generation' => 'error',
);

$legacy_output = revelations_generation_notice_render();

revelations_generation_notice_test(
    '' === trim( $legacy_output ),
    'unscoped legacy result cannot be rendered as a current draft result'
);

echo "\n" .
    $passed .
    ' passed, ' .
    $failed .
    " failed\n";

exit( 0 === $failed ? 0 : 1 );

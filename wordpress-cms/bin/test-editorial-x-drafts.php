<?php
/**
 * Isolated diagnostics for REVELATIONS X drafts.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

function add_action( mixed ...$arguments ): void {}
function wp_strip_all_tags( string $text ): string { return strip_tags( $text ); }

$plugin = $argv[1] ?? '';
if ( '' === $plugin || ! is_file( $plugin ) ) {
    fwrite( STDERR, "FAIL: plugin path missing\n" );
    exit( 1 );
}

require_once $plugin;

$passed = 0;
$failed = 0;

function revelations_x_test( bool $condition, string $message ): void {
    global $passed, $failed;
    if ( $condition ) {
        $passed++;
        echo 'PASS: ' . $message . "\n";
        return;
    }
    $failed++;
    echo 'FAIL: ' . $message . "\n";
}

revelations_x_test(
    23 === revelations_editorial_x_weighted_length(
        'https://revelations.me/example'
    ),
    'one URL counts as 23 weighted characters'
);

revelations_x_test(
    29 === revelations_editorial_x_weighted_length(
        'Read: https://revelations.me/example'
    ),
    'ASCII text and URL weights combine correctly'
);

revelations_x_test(
    2 === revelations_editorial_x_weighted_length( '🤖' ),
    'non-ASCII characters are counted conservatively'
);

revelations_x_test(
    "Line one\n\nLine two" ===
        revelations_editorial_x_normalize_text(
            "  Line   one \r\n\r\n\r\n Line two  "
        ),
    'normalization preserves one paragraph break'
);

$schema = revelations_editorial_x_schema();
revelations_x_test(
    array( 'post_text' ) === ( $schema['required'] ?? array() ) &&
    false === ( $schema['additionalProperties'] ?? true ),
    'strict schema requires only post_text'
);

$source = file_get_contents( $plugin );
revelations_x_test(
    is_string( $source ) &&
    str_contains( $source, 'admin_post_revelations_generate_x_draft' ) &&
    str_contains( $source, 'admin_post_revelations_save_x_draft' ) &&
    str_contains( $source, 'admin_post_revelations_set_x_draft_status' ),
    'generate, save and status handlers exist'
);

revelations_x_test(
    is_string( $source ) &&
    str_contains( $source, '_revelations_x_draft_text' ) &&
    str_contains( $source, '_revelations_x_draft_source_hash' ) &&
    str_contains( $source, '_revelations_x_draft_previous' ),
    'X entity and previous-version backup are stored'
);

revelations_x_test(
    is_string( $source ) &&
    str_contains( $source, 'Nothing is published to X automatically.' ),
    'manual publication contract is explicit'
);

revelations_x_test(
    is_string( $source ) &&
    str_contains(
        $source,
        "if ( 'publish' === \$post->post_status )"
    ) &&
    str_contains(
        $source,
        "'posts_per_page' => -1"
    ) &&
    ! str_contains(
        $source,
        "'posts_per_page' => 50"
    ),
    'all published articles are eligible without a hard query limit'
);

echo "x_draft_tests_passed=$passed\n";
echo "x_draft_tests_failed=$failed\n";
exit( 0 === $failed ? 0 : 1 );

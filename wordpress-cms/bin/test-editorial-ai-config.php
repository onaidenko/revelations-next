<?php
/**
 * Isolated diagnostics for AI configuration and request enforcement.
 *
 * No WordPress bootstrap, network, API or database access is used.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

define(
    'REVELATIONS_OPENAI_API_KEY',
    str_repeat( 'k', 24 )
);

define(
    'REVELATIONS_OPENAI_MODEL',
    'synthetic-model'
);

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public function __construct(
            private string $code,
            private string $message
        ) {
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( mixed $value ): bool {
        return $value instanceof WP_Error;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( mixed $value ): string {
        return trim(
            strip_tags(
                (string) $value
            )
        );
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( mixed ...$args ): void {
    }
}

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-ai-config.php';

require_once dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-ai-readiness.php';

$failures = 0;
$cases    = 0;

$assert = static function (
    bool $condition,
    string $label
) use ( &$failures, &$cases ): void {
    $cases++;

    if ( $condition ) {
        echo 'PASS: ' . $label . PHP_EOL;
        return;
    }

    $failures++;
    echo 'FAIL: ' . $label . PHP_EOL;
};

$base_sources = array(
    'constant_api_key' =>
        null,
    'environment_api_key' =>
        null,
    'constant_model' =>
        null,
    'environment_model' =>
        null,
    'enabled_defined' =>
        false,
    'enabled_value' =>
        null,
    'environment_enabled' =>
        null,
);

$default_config =
    revelations_editorial_ai_resolve_config(
        $base_sources
    );

$assert(
    false === $default_config['requests_enabled'],
    'enabled defaults to false'
);

$assert(
    false === $default_config['key_configured'] &&
    false === $default_config['model_configured'],
    'empty sources remain unconfigured'
);

$environment_config =
    revelations_editorial_ai_resolve_config(
        array_merge(
            $base_sources,
            array(
                'environment_api_key' =>
                    str_repeat( 'e', 24 ),
                'environment_model' =>
                    'environment-model',
            )
        )
    );

$assert(
    'environment' ===
        $environment_config['key_source'] &&
    'environment-model' ===
        $environment_config['model'],
    'environment fallback resolves key and model'
);

$constant_config =
    revelations_editorial_ai_resolve_config(
        array_merge(
            $base_sources,
            array(
                'constant_api_key' =>
                    str_repeat( 'c', 24 ),
                'environment_api_key' =>
                    str_repeat( 'e', 24 ),
                'constant_model' =>
                    'constant-model',
                'environment_model' =>
                    'environment-model',
                'enabled_defined' =>
                    true,
                'enabled_value' =>
                    true,
            )
        )
    );

$assert(
    'wordpress_constant' ===
        $constant_config['key_source'] &&
    str_repeat( 'c', 24 ) ===
        $constant_config['api_key'],
    'WordPress constant key has precedence'
);

$assert(
    'constant-model' ===
        $constant_config['model'],
    'WordPress constant model has precedence'
);

$assert(
    true === $constant_config['requests_enabled'],
    'literal boolean true enables requests'
);

$constant_false_config =
    revelations_editorial_ai_resolve_config(
        array_merge(
            $base_sources,
            array(
                'enabled_defined' =>
                    true,
                'enabled_value' =>
                    false,
                'environment_enabled' =>
                    'true',
            )
        )
    );

$assert(
    false ===
        $constant_false_config['requests_enabled'],
    'constant false overrides environment true'
);

$truthy_config =
    revelations_editorial_ai_resolve_config(
        array_merge(
            $base_sources,
            array(
                'enabled_defined' =>
                    true,
                'enabled_value' =>
                    1,
            )
        )
    );

$assert(
    false === $truthy_config['requests_enabled'],
    'non-boolean truthy value does not enable requests'
);

$environment_cases = array(
    array( '1', true ),
    array( 'true', true ),
    array( ' TRUE ', true ),
    array( '0', false ),
    array( 'false', false ),
    array( '', false ),
    array( 'garbage', false ),
);

foreach (
    $environment_cases as $environment_case
) {
    [ $value, $expected ] =
        $environment_case;

    $environment_enabled_config =
        revelations_editorial_ai_resolve_config(
            array_merge(
                $base_sources,
                array(
                    'environment_enabled' =>
                        $value,
                )
            )
        );

    $assert(
        $expected ===
            $environment_enabled_config[
                'requests_enabled'
            ],
        sprintf(
            'environment enabled value %s resolves to %s',
            var_export( $value, true ),
            $expected
                ? 'true'
                : 'false'
        )
    );
}

$missing_key =
    revelations_editorial_ai_request_config(
        array_merge(
            $default_config,
            array(
                'model' =>
                    'synthetic-model',
                'model_configured' =>
                    true,
                'requests_enabled' =>
                    true,
            )
        )
    );

$assert(
    is_wp_error( $missing_key ) &&
    'ai_key_missing' ===
        $missing_key->get_error_code(),
    'request guard rejects missing key'
);

$missing_model =
    revelations_editorial_ai_request_config(
        array_merge(
            $constant_config,
            array(
                'model' =>
                    '',
                'model_configured' =>
                    false,
            )
        )
    );

$assert(
    is_wp_error( $missing_model ) &&
    'ai_model_missing' ===
        $missing_model->get_error_code(),
    'request guard rejects missing model'
);

$disabled =
    revelations_editorial_ai_request_config(
        array_merge(
            $constant_config,
            array(
                'requests_enabled' =>
                    false,
            )
        )
    );

$assert(
    is_wp_error( $disabled ) &&
    'ai_requests_disabled' ===
        $disabled->get_error_code(),
    'request guard blocks disabled requests'
);

$enabled =
    revelations_editorial_ai_request_config(
        $constant_config
    );

$assert(
    is_array( $enabled ) &&
    'constant-model' === $enabled['model'],
    'request guard returns enabled configuration'
);

$assert(
    is_wp_error( $disabled ) &&
    ! str_contains(
        $disabled->get_error_message(),
        (string) $constant_config['api_key']
    ),
    'blocking error does not expose API key'
);

putenv( 'REVELATIONS_AI_GENERATION_ENABLED' );

$runtime_config =
    revelations_editorial_ai_config();

$assert(
    false === $runtime_config['requests_enabled'],
    'undefined runtime flag remains disabled'
);

$readiness =
    revelations_editorial_ai_readiness();

$assert(
    'disabled' === $readiness['status'] &&
    false === $readiness['requests_enabled'],
    'readiness reports disabled runtime state'
);

$assert(
    ! array_key_exists( 'api_key', $readiness ),
    'readiness does not expose API key'
);

$generation_source = file_get_contents(
    dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-ai-generate.php'
);

$test_source = file_get_contents(
    dirname( __DIR__ ) .
    '/mu-plugins/revelations-editorial-ai-test.php'
);

$assert(
    is_string( $generation_source ) &&
    str_contains(
        $generation_source,
        'revelations_editorial_ai_request_config()'
    ),
    'generation backend uses the shared request guard'
);

$assert(
    is_string( $test_source ) &&
    str_contains(
        $test_source,
        'revelations_editorial_ai_request_config()'
    ),
    'connection test uses the shared request guard'
);

$assert(
    is_string( $generation_source ) &&
    ! str_contains(
        $generation_source,
        "define(\n        'REVELATIONS_AI_GENERATION_ENABLED'"
    ),
    'generation no longer enables requests by default'
);

echo PHP_EOL;
echo sprintf(
    'RESULT: %d passed, %d failed, %d total',
    $cases - $failures,
    $failures,
    $cases
) . PHP_EOL;

exit(
    0 === $failures
        ? 0
        : 1
);

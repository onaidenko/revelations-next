<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Configuration
 * Description: Resolves and validates private server-side AI configuration.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve normalized AI configuration from explicit sources.
 *
 * WordPress constants take precedence over environment variables.
 * A constant must be literal boolean true. Without a constant, only
 * environment values "1" and "true" enable requests. Default: false.
 *
 * @param array<string, mixed> $sources Raw configuration sources.
 * @return array<string, mixed>
 */
function revelations_editorial_ai_resolve_config(
    array $sources
): array {
    $constant_api_key =
        $sources['constant_api_key'] ?? null;

    $environment_api_key =
        $sources['environment_api_key'] ?? null;

    $api_key    = '';
    $key_source = 'none';

    if ( is_string( $constant_api_key ) ) {
        $api_key = trim( $constant_api_key );

        if ( '' !== $api_key ) {
            $key_source = 'wordpress_constant';
        }
    } elseif ( is_string( $environment_api_key ) ) {
        $api_key = trim( $environment_api_key );

        if ( '' !== $api_key ) {
            $key_source = 'environment';
        }
    }

    $constant_model =
        $sources['constant_model'] ?? null;

    $environment_model =
        $sources['environment_model'] ?? null;

    $model = '';

    if ( is_string( $constant_model ) ) {
        $model = sanitize_text_field(
            $constant_model
        );
    } elseif ( is_string( $environment_model ) ) {
        $model = sanitize_text_field(
            $environment_model
        );
    }

    $enabled_defined =
        ! empty( $sources['enabled_defined'] );

    if ( $enabled_defined ) {
        $enabled =
            true === (
                $sources['enabled_value'] ?? null
            );
    } else {
        $environment_enabled =
            $sources['environment_enabled'] ?? null;

        $enabled = is_string(
            $environment_enabled
        ) && in_array(
            strtolower(
                trim( $environment_enabled )
            ),
            array(
                '1',
                'true',
            ),
            true
        );
    }

    return array(
        'provider'         => 'openai',
        'api_key'          => $api_key,
        'key_configured'   =>
            strlen( $api_key ) >= 20,
        'key_source'       => $key_source,
        'model'            => trim( $model ),
        'model_configured' =>
            '' !== trim( $model ),
        'requests_enabled' => $enabled,
    );
}

/**
 * Resolve the current private server-side AI configuration.
 *
 * The API key is returned for internal request handlers only. Callers
 * must never expose or log the complete configuration array.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_ai_config(): array {
    $constant_api_key = null;

    if (
        defined( 'REVELATIONS_OPENAI_API_KEY' ) &&
        is_string( REVELATIONS_OPENAI_API_KEY )
    ) {
        $constant_api_key =
            REVELATIONS_OPENAI_API_KEY;
    }

    $environment_api_key = getenv(
        'OPENAI_API_KEY'
    );

    $constant_model = null;

    if (
        defined( 'REVELATIONS_OPENAI_MODEL' ) &&
        is_string( REVELATIONS_OPENAI_MODEL )
    ) {
        $constant_model =
            REVELATIONS_OPENAI_MODEL;
    }

    $environment_model = getenv(
        'OPENAI_MODEL'
    );

    $environment_enabled = getenv(
        'REVELATIONS_AI_GENERATION_ENABLED'
    );

    return revelations_editorial_ai_resolve_config(
        array(
            'constant_api_key' =>
                $constant_api_key,

            'environment_api_key' =>
                is_string( $environment_api_key )
                    ? $environment_api_key
                    : null,

            'constant_model' =>
                $constant_model,

            'environment_model' =>
                is_string( $environment_model )
                    ? $environment_model
                    : null,

            'enabled_defined' =>
                defined(
                    'REVELATIONS_AI_GENERATION_ENABLED'
                ),

            'enabled_value' =>
                defined(
                    'REVELATIONS_AI_GENERATION_ENABLED'
                )
                    ? REVELATIONS_AI_GENERATION_ENABLED
                    : null,

            'environment_enabled' =>
                is_string( $environment_enabled )
                    ? $environment_enabled
                    : null,
        )
    );
}

/**
 * Return request-ready configuration or a safe blocking error.
 *
 * @param array<string, mixed>|null $config Optional isolated-test input.
 * @return array<string, mixed>|WP_Error
 */
function revelations_editorial_ai_request_config(
    ?array $config = null
) {
    if ( null === $config ) {
        $config =
            revelations_editorial_ai_config();
    }

    if ( empty( $config['key_configured'] ) ) {
        return new WP_Error(
            'ai_key_missing',
            'The server API key is not configured.'
        );
    }

    if ( empty( $config['model_configured'] ) ) {
        return new WP_Error(
            'ai_model_missing',
            'The server AI model is not configured.'
        );
    }

    if ( empty( $config['requests_enabled'] ) ) {
        return new WP_Error(
            'ai_requests_disabled',
            'External AI requests are disabled.'
        );
    }

    $api_key = trim(
        (string) (
            $config['api_key'] ?? ''
        )
    );

    $model = trim(
        (string) (
            $config['model'] ?? ''
        )
    );

    if ( '' === $api_key || '' === $model ) {
        return new WP_Error(
            'ai_config_incomplete',
            'The private AI configuration is incomplete.'
        );
    }

    return $config;
}

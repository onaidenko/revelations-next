<?php
/**
 * Plugin Name: REVELATIONS Editorial Preview Engine
 * Description: Shared runtime and logging helpers for editorial RSS previews.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Server-side registry of preview-enabled editorial sections.
 *
 * Podcast is intentionally excluded.
 *
 * @return array<string, array<string, mixed>>
 */
function revelations_editorial_preview_sections(): array {
    return array(
        'tech' => array(
            'label' =>
                'Tech',

            'default_enabled' =>
                true,

            'default_preview_limit' =>
                10,

            'scan_callback' =>
                'revelations_editorial_tech_scan_dry_run',
        ),

        'news' => array(
            'label' =>
                'News',

            'default_enabled' =>
                false,

            'default_preview_limit' =>
                5,

            'scan_callback' =>
                'revelations_editorial_news_scan_dry_run',
        ),
    );
}

/**
 * Validate one section against the server-side registry.
 */
function revelations_editorial_preview_section_is_registered(
    string $section
): bool {
    $section = sanitize_key(
        $section
    );

    return isset(
        revelations_editorial_preview_sections()[
            $section
        ]
    );
}

/**
 * Return one registered section configuration.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_preview_section_config(
    string $section
): array {
    $section = sanitize_key(
        $section
    );

    $sections =
        revelations_editorial_preview_sections();

    return isset( $sections[ $section ] ) &&
        is_array( $sections[ $section ] )
            ? $sections[ $section ]
            : array();
}

/**
 * Return the user-specific transient key for one section.
 */
function revelations_editorial_preview_key(
    string $section
): string {
    $section = sanitize_key(
        $section
    );

    if (
        ! revelations_editorial_preview_section_is_registered(
            $section
        )
    ) {
        return '';
    }

    return sprintf(
        'rev_%s_preview_%d',
        $section,
        get_current_user_id()
    );
}

/**
 * Load the current runtime configuration for one section.
 *
 * @return array{enabled:bool,preview_limit:int}
 */
function revelations_editorial_preview_runtime_config(
    string $section
): array {
    $section = sanitize_key(
        $section
    );

    $config =
        revelations_editorial_preview_section_config(
            $section
        );

    if ( array() === $config ) {
        return array(
            'enabled' =>
                false,

            'preview_limit' =>
                10,
        );
    }

    $profile = function_exists(
        'revelations_editorial_get_scanner_profile'
    )
        ? revelations_editorial_get_scanner_profile(
            $section
        )
        : array();

    $default_enabled =
        ! empty(
            $config['default_enabled']
        );

    $enabled = array_key_exists(
        'enabled',
        $profile
    )
        ? ! empty(
            $profile['enabled']
        )
        : $default_enabled;

    $default_preview_limit = absint(
        $config['default_preview_limit']
        ?? 10
    );

    $preview_limit = max(
        1,
        min(
            20,
            absint(
                $profile['preview_limit']
                ?? $default_preview_limit
            )
        )
    );

    return array(
        'enabled' =>
            $enabled,

        'preview_limit' =>
            $preview_limit,
    );
}

/**
 * Record one section RSS preview scan privately.
 *
 * The scan remains dry-run: no candidates or articles are created.
 *
 * @param array<string, mixed> $result Scanner result.
 * @return int|WP_Error
 */
function revelations_editorial_log_preview_scan(
    string $section,
    array $result,
    int $duration_ms
) {
    $section = sanitize_key(
        $section
    );

    if (
        ! revelations_editorial_preview_section_is_registered(
            $section
        )
    ) {
        return new WP_Error(
            'invalid_preview_section',
            'The editorial preview section is not registered.'
        );
    }

    if (
        ! function_exists(
            'revelations_editorial_create_run_log'
        )
    ) {
        return new WP_Error(
            'run_logger_unavailable',
            'Editorial run logging is unavailable.'
        );
    }

    $sources_checked = isset(
        $result['sources_checked']
    ) && is_array(
        $result['sources_checked']
    )
        ? $result['sources_checked']
        : array();

    $sources_failed = isset(
        $result['sources_failed']
    ) && is_array(
        $result['sources_failed']
    )
        ? $result['sources_failed']
        : array();

    $errors = isset(
        $result['errors']
    ) && is_array(
        $result['errors']
    )
        ? $result['errors']
        : array();

    $qualified_candidates = isset(
        $result['qualified_candidates']
    ) && is_array(
        $result['qualified_candidates']
    )
        ? $result['qualified_candidates']
        : array();

    if (
        array() === $sources_checked &&
        array() !== $sources_failed
    ) {
        $status = 'failed';
    } elseif ( array() !== $sources_failed ) {
        $status = 'partial_success';
    } elseif ( array() === $sources_checked ) {
        $status = 'failed';

        $errors[] =
            'No RSS sources were checked.';
    } else {
        $status = 'completed';
    }

    return revelations_editorial_create_run_log(
        array(
            'run_date' =>
                sanitize_text_field(
                    (string) (
                        $result['preview_generated_at']
                        ?? gmdate( 'c' )
                    )
                ),

            'event_type' =>
                'rss_scan',

            'status' =>
                $status,

            'section' =>
                $section,

            'sources_checked' =>
                $sources_checked,

            'sources_failed' =>
                $sources_failed,

            'errors' =>
                $errors,

            'total_fetched' =>
                absint(
                    $result['total_feed_items']
                    ?? 0
                ),

            'duplicates_removed' =>
                absint(
                    $result['duplicates_removed']
                    ?? 0
                ),

            'candidates_selected' =>
                count(
                    $qualified_candidates
                ),

            'drafts_requested' =>
                0,

            'drafts_generated' =>
                0,

            'drafts_failed' =>
                0,

            'duration_ms' =>
                max(
                    0,
                    $duration_ms
                ),
        )
    );
}

/**
 * Run one registered section preview.
 *
 * This creates a temporary preview and one private Run Log.
 * It never creates candidates, drafts or articles.
 *
 * @return array<string, mixed>|WP_Error
 */
function revelations_editorial_generate_preview(
    string $section
) {
    $section = sanitize_key(
        $section
    );

    $config =
        revelations_editorial_preview_section_config(
            $section
        );

    if ( array() === $config ) {
        return new WP_Error(
            'invalid_preview_section',
            'The editorial preview section is not registered.'
        );
    }

    $runtime =
        revelations_editorial_preview_runtime_config(
            $section
        );

    if ( ! $runtime['enabled'] ) {
        return new WP_Error(
            'scanner_disabled',
            'The editorial scanner is disabled in Settings.'
        );
    }

    $scan_callback =
        $config['scan_callback'] ?? null;

    if (
        ! is_string( $scan_callback ) ||
        ! is_callable( $scan_callback )
    ) {
        return new WP_Error(
            'scanner_unavailable',
            'The editorial scanner is not available.'
        );
    }

    $preview_key =
        revelations_editorial_preview_key(
            $section
        );

    if ( '' === $preview_key ) {
        return new WP_Error(
            'preview_key_unavailable',
            'The preview storage key is unavailable.'
        );
    }

    $started =
        microtime( true );

    $result = call_user_func(
        $scan_callback,
        $runtime['preview_limit']
    );

    if ( ! is_array( $result ) ) {
        return new WP_Error(
            'invalid_scanner_result',
            'The editorial scanner returned an invalid result.'
        );
    }

    /*
     * The registered server-side section always wins over any
     * section value returned by the callback.
     */
    $result['section'] =
        $section;

    $result['preview_generated_at'] =
        gmdate( 'c' );

    $result['preview_duration_ms'] =
        (int) round(
            (
                microtime( true ) -
                $started
            ) * 1000
        );

    $run_log =
        revelations_editorial_log_preview_scan(
            $section,
            $result,
            absint(
                $result['preview_duration_ms']
            )
        );

    if ( is_wp_error( $run_log ) ) {
        $result['run_logs_created'] =
            0;

        $result['run_log_error'] =
            $run_log->get_error_code();
    } else {
        $result['run_logs_created'] =
            1;

        $result['run_log_id'] =
            absint( $run_log );
    }

    $stored = set_transient(
        $preview_key,
        $result,
        15 * MINUTE_IN_SECONDS
    );

    if ( ! $stored ) {
        return new WP_Error(
            'preview_storage_failed',
            'The temporary editorial preview could not be saved.'
        );
    }

    return $result;
}

<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Test
 * Description: Runs a minimal OpenAI connection test without changing drafts.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * User-specific test result.
 */
function revelations_editorial_ai_test_key(): string {
    return 'rev_ai_test_' . get_current_user_id();
}

/**
 * Extract visible text from a Responses API response.
 *
 * @param array<string, mixed> $response API response.
 */
function revelations_editorial_ai_extract_text(
    array $response
): string {
    $parts = array();

    $output = $response['output'] ?? array();

    if ( ! is_array( $output ) ) {
        return '';
    }

    foreach ( $output as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        $content_items = $item['content'] ?? array();

        if ( ! is_array( $content_items ) ) {
            continue;
        }

        foreach ( $content_items as $content ) {
            if (
                ! is_array( $content ) ||
                'output_text' !== (
                    $content['type'] ?? ''
                )
            ) {
                continue;
            }

            $text = $content['text'] ?? '';

            if ( is_string( $text ) && '' !== trim( $text ) ) {
                $parts[] = trim( $text );
            }
        }
    }

    return trim( implode( "\n", $parts ) );
}

/**
 * Run one minimal paid connection test.
 */
add_action(
    'admin_post_revelations_test_openai_connection',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to test AI.',
                    'revelations'
                )
            );
        }

        check_admin_referer(
            'revelations_test_openai_connection'
        );

        $redirect = static function (
            string $status
        ): void {
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page'    =>
                            'revelations-editorial-desk',
                        'view'    => 'drafts',
                        'ai_test' => $status,
                    ),
                    admin_url( 'admin.php' )
                )
            );

            exit;
        };

        if (
            ! function_exists(
                'revelations_editorial_ai_request_config'
            )
        ) {
            set_transient(
                revelations_editorial_ai_test_key(),
                array(
                    'ok'      => false,
                    'message' =>
                        'AI configuration resolver is unavailable.',
                ),
                10 * MINUTE_IN_SECONDS
            );

            $redirect( 'error' );
        }

        $config =
            revelations_editorial_ai_request_config();

        if ( is_wp_error( $config ) ) {
            set_transient(
                revelations_editorial_ai_test_key(),
                array(
                    'ok'      => false,
                    'message' =>
                        $config->get_error_message(),
                    'code'    =>
                        $config->get_error_code(),
                ),
                10 * MINUTE_IN_SECONDS
            );

            $redirect( 'error' );
        }

        $api_key = trim(
            (string) $config['api_key']
        );

        $model = trim(
            (string) $config['model']
        );

        $request_body = array(
            'model' => $model,

            'instructions' =>
                'Return exactly this text and nothing else: '
                . 'REVELATIONS_AI_OK',

            'input' =>
                'Run the REVELATIONS Editorial Desk '
                . 'connection test.',

            'reasoning' => array(
                'effort' => 'none',
            ),

            'max_output_tokens' => 32,
            'store'             => false,
        );

        $started = microtime( true );

        $http_response = wp_remote_post(
            'https://api.openai.com/v1/responses',
            array(
                'timeout'     => 60,
                'redirection' => 0,

                'headers' => array(
                    'Authorization' =>
                        'Bearer ' . $api_key,

                    'Content-Type' =>
                        'application/json',
                ),

                'body' =>
                    wp_json_encode(
                        $request_body,
                        JSON_UNESCAPED_SLASHES
                    ),

                'data_format' => 'body',
            )
        );

        $duration_ms = (int) round(
            ( microtime( true ) - $started ) * 1000
        );

        if ( is_wp_error( $http_response ) ) {
            set_transient(
                revelations_editorial_ai_test_key(),
                array(
                    'ok'          => false,
                    'model'       => $model,
                    'duration_ms' => $duration_ms,
                    'message'     =>
                        $http_response->get_error_message(),
                ),
                10 * MINUTE_IN_SECONDS
            );

            $redirect( 'error' );
        }

        $http_status = (int)
            wp_remote_retrieve_response_code(
                $http_response
            );

        $response_body =
            wp_remote_retrieve_body(
                $http_response
            );

        $decoded = json_decode(
            $response_body,
            true
        );

        if ( ! is_array( $decoded ) ) {
            set_transient(
                revelations_editorial_ai_test_key(),
                array(
                    'ok'          => false,
                    'model'       => $model,
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'message'     =>
                        'OpenAI returned invalid JSON.',
                ),
                10 * MINUTE_IN_SECONDS
            );

            $redirect( 'error' );
        }

        if ( $http_status < 200 || $http_status >= 300 ) {
            $api_message =
                $decoded['error']['message']
                ?? 'OpenAI request failed.';

            set_transient(
                revelations_editorial_ai_test_key(),
                array(
                    'ok'          => false,
                    'model'       => $model,
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'message'     =>
                        sanitize_text_field(
                            (string) $api_message
                        ),
                ),
                10 * MINUTE_IN_SECONDS
            );

            $redirect( 'error' );
        }

        $output_text =
            revelations_editorial_ai_extract_text(
                $decoded
            );

        $test_passed = str_contains(
            $output_text,
            'REVELATIONS_AI_OK'
        );

        $usage = is_array(
            $decoded['usage'] ?? null
        )
            ? $decoded['usage']
            : array();

        $result = array(
            'ok'          => $test_passed,
            'model'       => sanitize_text_field(
                (string) (
                    $decoded['model'] ?? $model
                )
            ),
            'api_status'   => sanitize_key(
                (string) (
                    $decoded['status'] ?? ''
                )
            ),
            'http_status'  => $http_status,
            'duration_ms'  => $duration_ms,
            'output'       => sanitize_text_field(
                $output_text
            ),
            'input_tokens' => absint(
                $usage['input_tokens'] ?? 0
            ),
            'output_tokens' => absint(
                $usage['output_tokens'] ?? 0
            ),
            'total_tokens' => absint(
                $usage['total_tokens'] ?? 0
            ),
            'tested_at' => gmdate( 'c' ),

            'message' => $test_passed
                ? 'OpenAI connection test passed.'
                : 'The API responded, but the expected test text was absent.',
        );

        set_transient(
            revelations_editorial_ai_test_key(),
            $result,
            10 * MINUTE_IN_SECONDS
        );

        $redirect(
            $test_passed
                ? 'success'
                : 'error'
        );
    }
);

/**
 * Render connection-test controls.
 */
function revelations_editorial_render_ai_connection_test(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $readiness = function_exists(
        'revelations_editorial_ai_readiness'
    )
        ? revelations_editorial_ai_readiness()
        : array(
            'key_configured'   => false,
            'model_configured' => false,
            'model'            => '',
        );

    $ready =
        ! empty( $readiness['key_configured'] ) &&
        ! empty( $readiness['model_configured'] );

    $result = get_transient(
        revelations_editorial_ai_test_key()
    );

    if (
        isset( $_GET['ai_test'] ) &&
        is_array( $result )
    ) {
        $notice_class = ! empty( $result['ok'] )
            ? 'notice-success'
            : 'notice-error';
        ?>
        <div
            class="notice <?php
                echo esc_attr( $notice_class );
            ?> is-dismissible"
        >
            <p>
                <?php echo esc_html(
                    (string) (
                        $result['message']
                        ?? 'AI test finished.'
                    )
                ); ?>
            </p>
        </div>
        <?php
    }
    ?>
    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <div class="revelations-ai-test__header">
            <div>
                <h2>AI connection test</h2>

                <p class="revelations-desk__section-description">
                    Sends one short paid Responses API request.
                    It does not modify candidates or drafts.
                </p>
            </div>

            <form
                method="post"
                action="<?php echo esc_url(
                    admin_url( 'admin-post.php' )
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="revelations_test_openai_connection"
                >

                <?php wp_nonce_field(
                    'revelations_test_openai_connection'
                ); ?>

                <button
                    type="submit"
                    class="button button-primary"
                    <?php disabled( ! $ready ); ?>
                >
                    Run AI connection test
                </button>
            </form>
        </div>

        <p>
            Model:
            <strong>
                <?php echo esc_html(
                    (string) (
                        $readiness['model'] ?: '—'
                    )
                ); ?>
            </strong>
        </p>

        <?php if ( ! $ready ) : ?>
            <p class="description">
                API key and model must both be configured.
            </p>
        <?php endif; ?>

        <?php if ( is_array( $result ) ) : ?>
            <table
                class="widefat striped"
                style="margin-top:16px"
            >
                <tbody>
                    <tr>
                        <th style="width:190px">Result</th>
                        <td>
                            <?php echo esc_html(
                                ! empty( $result['ok'] )
                                    ? 'Passed'
                                    : 'Failed'
                            ); ?>
                        </td>
                    </tr>

                    <tr>
                        <th>HTTP status</th>
                        <td>
                            <?php echo esc_html(
                                (string) (
                                    $result['http_status']
                                    ?? '—'
                                )
                            ); ?>
                        </td>
                    </tr>

                    <tr>
                        <th>API status</th>
                        <td>
                            <?php echo esc_html(
                                (string) (
                                    $result['api_status']
                                    ?? '—'
                                )
                            ); ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Duration</th>
                        <td>
                            <?php echo esc_html(
                                number_format(
                                    absint(
                                        $result['duration_ms']
                                        ?? 0
                                    ) / 1000,
                                    2
                                ) . ' s'
                            ); ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Tokens</th>
                        <td>
                            <?php echo esc_html(
                                sprintf(
                                    '%d input · %d output · %d total',
                                    absint(
                                        $result['input_tokens']
                                        ?? 0
                                    ),
                                    absint(
                                        $result['output_tokens']
                                        ?? 0
                                    ),
                                    absint(
                                        $result['total_tokens']
                                        ?? 0
                                    )
                                )
                            ); ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Output</th>
                        <td>
                            <code>
                                <?php echo esc_html(
                                    (string) (
                                        $result['output']
                                        ?? '—'
                                    )
                                ); ?>
                            </code>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Test UI styles.
 */
add_action(
    'admin_enqueue_scripts',
    static function ( string $hook_suffix ): void {
        if (
            'toplevel_page_revelations-editorial-desk'
            !== $hook_suffix
        ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );

        wp_add_inline_style(
            'dashicons',
            '
            .revelations-ai-test__header {
                display:flex;
                align-items:flex-start;
                justify-content:space-between;
                gap:24px;
            }

            @media (max-width:782px) {
                .revelations-ai-test__header {
                    display:block;
                }
            }
            '
        );
    }
);

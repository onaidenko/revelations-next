<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Readiness
 * Description: Checks server-side AI configuration without making API requests.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return AI configuration status without exposing the API key.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_ai_readiness(): array {
    if (
        ! function_exists(
            'revelations_editorial_ai_config'
        )
    ) {
        return array(
            'provider'         => 'openai',
            'status'           => 'not_configured',
            'label'            => 'Not configured',
            'message'          =>
                'AI configuration resolver is unavailable.',
            'key_configured'   => false,
            'key_source'       => 'none',
            'model_configured' => false,
            'model'            => '',
            'requests_enabled' => false,
        );
    }

    $config =
        revelations_editorial_ai_config();

    if ( empty( $config['key_configured'] ) ) {
        $status  = 'not_configured';
        $label   = 'Not configured';
        $message =
            'Server API key is not configured. '
            . 'No external AI requests can be made.';
    } elseif ( empty( $config['model_configured'] ) ) {
        $status  = 'model_required';
        $label   = 'Model required';
        $message =
            'A server API key was detected, but no model '
            . 'has been selected. Requests remain disabled.';
    } elseif ( empty( $config['requests_enabled'] ) ) {
        $status  = 'disabled';
        $label   = 'Disabled';
        $message =
            'Server key and model were detected. '
            . 'External AI requests are disabled.';
    } else {
        $status  = 'ready';
        $label   = 'Ready';
        $message =
            'Server key, model and AI generation are ready.';
    }

    return array(
        'provider'         => 'openai',
        'status'           => $status,
        'label'            => $label,
        'message'          => $message,
        'key_configured'   =>
            ! empty( $config['key_configured'] ),
        'key_source'       =>
            (string) (
                $config['key_source'] ?? 'none'
            ),
        'model_configured' =>
            ! empty( $config['model_configured'] ),
        'model'            =>
            (string) (
                $config['model'] ?? ''
            ),
        'requests_enabled' =>
            ! empty( $config['requests_enabled'] ),
    );
}

/**
 * Render the currently disabled AI action.
 */
function revelations_editorial_render_ai_readiness_action(
    int $draft_id
): void {
    $readiness =
        revelations_editorial_ai_readiness();

    $candidate_id = absint(
        get_post_meta(
            $draft_id,
            '_revelations_editorial_candidate_id',
            true
        )
    );

    $source_status =
        function_exists(
            'revelations_editorial_source_snapshot_status'
        ) && $candidate_id > 0
            ? revelations_editorial_source_snapshot_status(
                $candidate_id
            )
            : array(
                'ready' => false,
            );

    $generated_at = sanitize_text_field(
        (string) get_post_meta(
            $draft_id,
            '_revelations_ai_generated_at',
            true
        )
    );

    if ( '' !== $generated_at ) {
        $word_count = absint(
            get_post_meta(
                $draft_id,
                '_revelations_ai_word_count',
                true
            )
        );

        $input_tokens = absint(
            get_post_meta(
                $draft_id,
                '_revelations_ai_input_tokens',
                true
            )
        );

        $output_tokens = absint(
            get_post_meta(
                $draft_id,
                '_revelations_ai_output_tokens',
                true
            )
        );

        $generation_number = max(
            1,
            absint(
                get_post_meta(
                    $draft_id,
                    '_revelations_ai_generation_number',
                    true
                )
            )
        );

        $saved_versions = function_exists(
            'revelations_editorial_ai_version_count'
        )
            ? revelations_editorial_ai_version_count(
                $draft_id
            )
            : 0;

        $can_regenerate =
            ! empty( $source_status['ready'] ) &&
            ! empty( $readiness['key_configured'] ) &&
            ! empty( $readiness['model_configured'] ) &&
            ! empty( $readiness['requests_enabled'] );
        ?>
        <div class="revelations-ai-action">
            <span class="revelations-ai-generated">
                AI generated
            </span>

            <div class="revelations-ai-generated-details">
                <?php echo esc_html(
                    sprintf(
                        'Generation %d · %d words · %d input tokens · %d output tokens',
                        $generation_number,
                        $word_count,
                        $input_tokens,
                        $output_tokens
                    )
                ); ?>
            </div>

            <div class="revelations-ai-generated-details">
                <?php echo esc_html(
                    sprintf(
                        '%d previous version(s) saved privately',
                        $saved_versions
                    )
                ); ?>
            </div>

            <?php if ( $can_regenerate ) : ?>
                <form
                    method="post"
                    class="revelations-ai-generate-form"
                    action="<?php echo esc_url(
                        admin_url( 'admin-post.php' )
                    ); ?>"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="revelations_generate_editorial_draft"
                    >

                    <input
                        type="hidden"
                        name="draft_id"
                        value="<?php echo esc_attr(
                            (string) $draft_id
                        ); ?>"
                    >

                    <?php wp_nonce_field(
                        'revelations_generate_editorial_draft_' .
                        $draft_id
                    ); ?>

                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        Regenerate with AI
                    </button>
                </form>

                <div class="revelations-ai-action__message">
                    The current AI article will be saved privately
                    before it is replaced. The draft will not be
                    published.
                </div>
            <?php else : ?>
                <button
                    type="button"
                    class="button button-primary"
                    disabled
                    aria-disabled="true"
                >
                    Regenerate with AI
                </button>
            <?php endif; ?>
        </div>
        <?php
        return;
    }

    $source_ready = ! empty(
        $source_status['ready']
    );

    $configuration_ready =
        ! empty(
            $readiness['key_configured']
        ) &&
        ! empty(
            $readiness['model_configured']
        ) &&
        ! empty(
            $readiness['requests_enabled']
        );

    $enabled =
        $source_ready &&
        $configuration_ready;

    if ( ! $source_ready ) {
        $message =
            'Fetch and save the source text before AI generation.';
    } elseif ( ! $configuration_ready ) {
        $message =
            (string) $readiness['message'];
    } else {
        $message =
            'Creates one paid AI response and updates this WordPress draft. It will not publish the article.';
    }
    ?>
    <div
        class="revelations-ai-action"
        data-draft-id="<?php echo esc_attr(
            (string) $draft_id
        ); ?>"
    >
        <?php if ( $enabled ) : ?>
            <form
                method="post"
                class="revelations-ai-generate-form"
                action="<?php echo esc_url(
                    admin_url( 'admin-post.php' )
                ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="revelations_generate_editorial_draft"
                >

                <input
                    type="hidden"
                    name="draft_id"
                    value="<?php echo esc_attr(
                        (string) $draft_id
                    ); ?>"
                >

                <?php wp_nonce_field(
                    'revelations_generate_editorial_draft_' .
                    $draft_id
                ); ?>

                <button
                    type="submit"
                    class="button button-primary"
                >
                    Generate with AI
                </button>
            </form>
        <?php else : ?>
            <button
                type="button"
                class="button button-primary"
                disabled
                aria-disabled="true"
            >
                Generate with AI
            </button>
        <?php endif; ?>

        <div class="revelations-ai-action__message">
            <?php echo esc_html( $message ); ?>
        </div>
    </div>
    <?php
}

/**
 * Styles for AI readiness controls.
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
            .revelations-ai-action {
                margin-top:8px;
            }

            .revelations-ai-action__message {
                max-width:260px;
                margin-top:5px;
                color:#646970;
                font-size:11px;
                line-height:1.35;
            }

            .revelations-ai-status--not_configured {
                color:#8a2424;
            }

            .revelations-ai-status--model_required {
                color:#6a4b00;
            }

            .revelations-ai-status--ready_for_test {
                color:#1b5e20;
            }
            '
        );
    }
);


/**
 * Immediately show that AI generation has started.
 *
 * The request still runs as a normal server-side form submission.
 */
add_action(
    'admin_footer',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' )
            ? get_current_screen()
            : null;

        if (
            ! $screen ||
            'toplevel_page_revelations-editorial-desk'
            !== $screen->id
        ) {
            return;
        }
        ?>
        <script id="revelations-ai-submit-feedback">
        (() => {
            const initializeGenerationFeedback = () => {
                document
                    .querySelectorAll(
                        '.revelations-ai-generate-form'
                    )
                    .forEach((form) => {
                        if (
                            form.dataset.feedbackReady === '1'
                        ) {
                            return;
                        }

                        form.dataset.feedbackReady = '1';

                        form.addEventListener(
                            'submit',
                            (event) => {
                                if (
                                    form.dataset.submitting === '1'
                                ) {
                                    event.preventDefault();
                                    return;
                                }

                                form.dataset.submitting = '1';

                                const button =
                                    form.querySelector(
                                        'button[type="submit"]'
                                    );

                                if (button) {
                                    button.disabled = true;

                                    button.setAttribute(
                                        'aria-disabled',
                                        'true'
                                    );

                                    button.setAttribute(
                                        'aria-busy',
                                        'true'
                                    );

                                    button.innerHTML =
                                        '<span class="spinner is-active" ' +
                                        'aria-hidden="true"></span>' +
                                        '<span>Generating…</span>';
                                }

                                const action =
                                    form.closest(
                                        '.revelations-ai-action'
                                    );

                                const message =
                                    action
                                        ? action.querySelector(
                                            '.revelations-ai-action__message'
                                        )
                                        : null;

                                if (message) {
                                    message.textContent =
                                        'Generating the article. ' +
                                        'Keep this tab open. ' +
                                        'This may take up to two minutes.';
                                }

                                document.body.classList.add(
                                    'revelations-ai-request-running'
                                );
                            }
                        );
                    });
            };

            if (
                document.readyState === 'loading'
            ) {
                document.addEventListener(
                    'DOMContentLoaded',
                    initializeGenerationFeedback
                );
            } else {
                initializeGenerationFeedback();
            }
        })();
        </script>

        <style id="revelations-ai-submit-feedback-style">
            .revelations-ai-generate-form
            button[aria-busy="true"] {
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:7px;
                min-width:155px;
                cursor:wait;
            }

            .revelations-ai-generate-form
            button[aria-busy="true"] .spinner {
                float:none;
                width:18px;
                height:18px;
                margin:0;
                background-size:18px 18px;
                visibility:visible;
            }

            body.revelations-ai-request-running {
                cursor:progress;
            }

            body.revelations-ai-request-running
            .revelations-ai-action__message {
                font-weight:600;
                color:#0a4b78;
            }
        </style>
        <?php
    }
);

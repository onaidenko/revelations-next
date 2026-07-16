<?php
/**
 * Plugin Name: REVELATIONS Editorial Long Actions UI
 * Description: Loading states and duplicate-submit protection for source and AI actions.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render progress behaviour for long-running Editorial Desk actions.
 */
function revelations_editorial_render_long_actions_progress_ui(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .revelations-long-action-progress {
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-left:10px;
            vertical-align:middle;
            color:#50575e;
            font-size:13px;
        }

        .revelations-long-action-progress .spinner {
            float:none;
            margin:0;
        }

        form[aria-busy="true"] {
            cursor:progress;
        }
    </style>

    <script>
        (() => {
            'use strict';

            const actionConfiguration = {
                revelations_fetch_source_snapshot: {
                    buttonLabel: 'Fetching…',
                    progressLabel: 'Fetching source text…'
                },

                revelations_generate_editorial_draft: {
                    buttonLabel: 'Generating…',
                    progressLabel: 'Generating AI draft…'
                },

                revelations_test_openai_connection: {
                    buttonLabel: 'Testing…',
                    progressLabel: 'Testing AI connection…'
                }
            };

            const getFormAction = (form) => {
                const actionField = form.querySelector(
                    'input[name="action"]'
                );

                return actionField
                    ? actionField.value.trim()
                    : '';
            };

            const actionForms = Array.from(
                document.querySelectorAll('form')
            ).filter((form) =>
                Object.prototype.hasOwnProperty.call(
                    actionConfiguration,
                    getFormAction(form)
                )
            );

            if (actionForms.length === 0) {
                return;
            }

            const allSubmitControls = actionForms.flatMap((form) =>
                Array.from(
                    form.querySelectorAll(
                        'input[type="submit"], button[type="submit"]'
                    )
                )
            );

            const setControlLabel = (control, label) => {
                if (!control) {
                    return;
                }

                if (control.tagName === 'INPUT') {
                    control.value = label;
                } else {
                    control.textContent = label;
                }
            };

            allSubmitControls.forEach((control) => {
                control.dataset.revelationsInitiallyDisabled =
                    control.disabled ? '1' : '0';

                control.dataset.revelationsOriginalLabel =
                    control.tagName === 'INPUT'
                        ? control.value
                        : control.textContent;
            });

            const resetInterface = () => {
                actionForms.forEach((form) => {
                    delete form.dataset.revelationsSubmitting;
                    form.removeAttribute('aria-busy');

                    const progress = form.querySelector(
                        '.revelations-long-action-progress'
                    );

                    if (progress) {
                        progress.remove();
                    }
                });

                allSubmitControls.forEach((control) => {
                    control.disabled =
                        control.dataset
                            .revelationsInitiallyDisabled === '1';

                    setControlLabel(
                        control,
                        control.dataset
                            .revelationsOriginalLabel || ''
                    );

                    if (control.disabled) {
                        control.setAttribute(
                            'aria-disabled',
                            'true'
                        );
                    } else {
                        control.removeAttribute(
                            'aria-disabled'
                        );
                    }
                });
            };

            actionForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    if (
                        form.dataset
                            .revelationsSubmitting === '1'
                    ) {
                        return;
                    }

                    const actionName =
                        getFormAction(form);

                    const configuration =
                        actionConfiguration[actionName];

                    if (!configuration) {
                        return;
                    }

                    form.dataset.revelationsSubmitting = '1';
                    form.setAttribute('aria-busy', 'true');

                    const activeControl =
                        event.submitter ||
                        form.querySelector(
                            'input[type="submit"], button[type="submit"]'
                        );

                    /*
                     * Prevent parallel source or AI operations.
                     */
                    allSubmitControls.forEach((control) => {
                        control.disabled = true;
                        control.setAttribute(
                            'aria-disabled',
                            'true'
                        );
                    });

                    setControlLabel(
                        activeControl,
                        configuration.buttonLabel
                    );

                    const progress =
                        document.createElement('span');

                    progress.className =
                        'revelations-long-action-progress';

                    progress.setAttribute(
                        'role',
                        'status'
                    );

                    progress.setAttribute(
                        'aria-live',
                        'polite'
                    );

                    progress.innerHTML =
                        '<span class="spinner is-active" ' +
                        'aria-hidden="true"></span>' +
                        '<span>' +
                        configuration.progressLabel +
                        '</span>';

                    form.appendChild(progress);

                    /*
                     * Allow the browser to paint the loading state
                     * before the request starts.
                     */
                    window.setTimeout(() => {
                        HTMLFormElement.prototype.submit.call(
                            form
                        );
                    }, 80);
                });
            });

            window.addEventListener(
                'pageshow',
                (event) => {
                    if (event.persisted) {
                        resetInterface();
                    }
                }
            );
        })();
    </script>
    <?php
}

/**
 * Load only inside Editorial Desk.
 */
add_action(
    'admin_footer',
    static function (): void {
        $screen = function_exists(
            'get_current_screen'
        )
            ? get_current_screen()
            : null;

        if (
            ! $screen ||
            'toplevel_page_revelations-editorial-desk'
            !== $screen->id
        ) {
            return;
        }

        revelations_editorial_render_long_actions_progress_ui();
    }
);

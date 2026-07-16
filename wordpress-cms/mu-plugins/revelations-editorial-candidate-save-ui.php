<?php
/**
 * Plugin Name: REVELATIONS Editorial Candidate Save UI
 * Description: Loading state and duplicate-submit protection for candidate save buttons.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render progress behaviour for candidate-saving forms.
 */
function revelations_editorial_render_candidate_save_progress_ui(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .revelations-save-progress {
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-left:10px;
            vertical-align:middle;
            color:#50575e;
            font-size:13px;
        }

        .revelations-save-progress .spinner {
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

            const supportedActions = new Set([
                'revelations_add_editorial_candidate',
                'revelations_save_preview_candidate'
            ]);

            const getFormAction = (form) => {
                const actionField = form.querySelector(
                    'input[name="action"]'
                );

                return actionField
                    ? actionField.value.trim()
                    : '';
            };

            const saveForms = Array.from(
                document.querySelectorAll('form')
            ).filter((form) =>
                supportedActions.has(
                    getFormAction(form)
                )
            );

            if (saveForms.length === 0) {
                return;
            }

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

            const resetForm = (form) => {
                delete form.dataset.revelationsSubmitting;
                form.removeAttribute('aria-busy');

                const progress = form.querySelector(
                    '.revelations-save-progress'
                );

                if (progress) {
                    progress.remove();
                }

                const submitControls = form.querySelectorAll(
                    'input[type="submit"], button[type="submit"]'
                );

                submitControls.forEach((control) => {
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

            saveForms.forEach((form) => {
                const submitControls = Array.from(
                    form.querySelectorAll(
                        'input[type="submit"], button[type="submit"]'
                    )
                );

                submitControls.forEach((control) => {
                    control.dataset.revelationsInitiallyDisabled =
                        control.disabled ? '1' : '0';

                    control.dataset.revelationsOriginalLabel =
                        control.tagName === 'INPUT'
                            ? control.value
                            : control.textContent;
                });

                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    if (
                        form.dataset
                            .revelationsSubmitting === '1'
                    ) {
                        return;
                    }

                    form.dataset.revelationsSubmitting = '1';
                    form.setAttribute('aria-busy', 'true');

                    const activeControl =
                        event.submitter ||
                        submitControls[0] ||
                        null;

                    submitControls.forEach((control) => {
                        control.disabled = true;
                        control.setAttribute(
                            'aria-disabled',
                            'true'
                        );
                    });

                    setControlLabel(
                        activeControl,
                        'Saving…'
                    );

                    const progress =
                        document.createElement('span');

                    progress.className =
                        'revelations-save-progress';

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
                        '<span>Saving candidate…</span>';

                    form.appendChild(progress);

                    /*
                     * Let the browser paint the loading state
                     * before navigation begins.
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
                        saveForms.forEach(resetForm);
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

        revelations_editorial_render_candidate_save_progress_ui();
    }
);

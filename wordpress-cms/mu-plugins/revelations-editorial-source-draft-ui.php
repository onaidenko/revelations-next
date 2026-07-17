<?php
/**
 * Plugin Name: REVELATIONS Editorial Source Draft UI
 * Description: Loading state and duplicate-submit protection for source draft creation.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render progress behaviour for source draft creation.
 */
function revelations_editorial_render_source_draft_progress_ui(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .revelations-source-draft-progress {
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-left:10px;
            vertical-align:middle;
            color:#50575e;
            font-size:13px;
        }

        .revelations-source-draft-progress .spinner {
            float:none;
            margin:0;
        }

        .revelations-source-draft-error {
            display:block;
            margin-top:6px;
            color:#b32d2e;
            font-size:12px;
        }

        form[aria-busy="true"] {
            cursor:progress;
        }
    </style>

    <script>
        (() => {
            'use strict';

            const actionName =
                'revelations_create_source_draft';

            const getFormAction = (form) => {
                const actionField = form.querySelector(
                    'input[name="action"]'
                );

                return actionField
                    ? actionField.value.trim()
                    : '';
            };

            const draftForms = Array.from(
                document.querySelectorAll('form')
            ).filter((form) =>
                getFormAction(form) === actionName
            );

            if (draftForms.length === 0) {
                return;
            }

            const getSubmitControls = (form) =>
                Array.from(
                    form.querySelectorAll(
                        'input[type="submit"], button[type="submit"]'
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

            const resetForm = (form) => {
                delete form.dataset.revelationsSubmitting;
                form.removeAttribute('aria-busy');

                const progress = form.querySelector(
                    '.revelations-source-draft-progress'
                );

                if (progress) {
                    progress.remove();
                }

                const errorMessage = form.querySelector(
                    '.revelations-source-draft-error'
                );

                if (errorMessage) {
                    errorMessage.remove();
                }

                getSubmitControls(form).forEach((control) => {
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

            draftForms.forEach((form) => {
                if (
                    form.dataset
                        .revelationsSourceDraftUiReady === '1'
                ) {
                    return;
                }

                form.dataset.revelationsSourceDraftUiReady = '1';

                const submitControls =
                    getSubmitControls(form);

                submitControls.forEach((control) => {
                    control.dataset
                        .revelationsInitiallyDisabled =
                            control.disabled ? '1' : '0';

                    control.dataset
                        .revelationsOriginalLabel =
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
                        form.querySelector(
                            'input[type="submit"], button[type="submit"]'
                        );

                    /*
                     * Prevent duplicate submission of this form.
                     */
                    submitControls.forEach((control) => {
                        control.disabled = true;
                        control.setAttribute(
                            'aria-disabled',
                            'true'
                        );
                    });

                    setControlLabel(
                        activeControl,
                        'Creating…'
                    );

                    const progress =
                        document.createElement('span');

                    progress.className =
                        'revelations-source-draft-progress';

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
                        '<span>Creating source draft…</span>';

                    form.appendChild(progress);

                    /*
                     * Allow the browser to display the progress
                     * state before navigation starts.
                     */
                    window.setTimeout(() => {
                        try {
                            HTMLFormElement.prototype.submit.call(
                                form
                            );
                        } catch (error) {
                            resetForm(form);

                            const errorMessage =
                                document.createElement('span');

                            errorMessage.className =
                                'revelations-source-draft-error';

                            errorMessage.setAttribute(
                                'role',
                                'alert'
                            );

                            errorMessage.textContent =
                                'The source draft request could ' +
                                'not start. Please try again.';

                            form.appendChild(errorMessage);
                        }
                    }, 80);
                });
            });

            window.addEventListener(
                'pageshow',
                (event) => {
                    if (event.persisted) {
                        draftForms.forEach(resetForm);
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

        revelations_editorial_render_source_draft_progress_ui();
    }
);

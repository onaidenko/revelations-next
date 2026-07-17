<?php
/**
 * Plugin Name: REVELATIONS Editorial Scan UI
 * Description: Loading state and duplicate-submit protection for RSS preview buttons.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render client-side progress behaviour for editorial scans.
 */
function revelations_editorial_render_scan_progress_ui(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .revelations-scan-progress {
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-left:10px;
            vertical-align:middle;
            color:#50575e;
            font-size:13px;
        }

        .revelations-scan-progress .spinner {
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
                'revelations_preview_news_scan',
                'revelations_preview_people_scan',
                'revelations_preview_tech_scan'
            ]);

            const scanForms = Array.from(
                document.querySelectorAll('form')
            ).filter((form) => {
                const actionField = form.querySelector(
                    'input[name="action"]'
                );

                return actionField &&
                    supportedActions.has(actionField.value);
            });

            if (scanForms.length === 0) {
                return;
            }

            const submitControls = scanForms.flatMap((form) =>
                Array.from(
                    form.querySelectorAll(
                        'input[type="submit"], button[type="submit"]'
                    )
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

            const resetInterface = () => {
                scanForms.forEach((form) => {
                    delete form.dataset.revelationsSubmitting;
                    form.removeAttribute('aria-busy');

                    const progress = form.querySelector(
                        '.revelations-scan-progress'
                    );

                    if (progress) {
                        progress.remove();
                    }
                });

                submitControls.forEach((control) => {
                    control.disabled =
                        control.dataset
                            .revelationsInitiallyDisabled === '1';

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

                    setControlLabel(
                        control,
                        control.dataset
                            .revelationsOriginalLabel || ''
                    );
                });
            };

            scanForms.forEach((form) => {
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

                    const activeControl = form.querySelector(
                        'input[type="submit"], button[type="submit"]'
                    );

                    submitControls.forEach((control) => {
                        control.disabled = true;
                        control.setAttribute(
                            'aria-disabled',
                            'true'
                        );
                    });

                    setControlLabel(
                        activeControl,
                        'Scanning…'
                    );

                    const progress =
                        document.createElement('span');

                    progress.className =
                        'revelations-scan-progress';

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
                        '<span>Scanning RSS sources…</span>';

                    form.appendChild(progress);

                    /*
                     * Short delay allows the browser to paint the
                     * disabled state before navigation begins.
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
 * Show the progress behaviour only inside Editorial Desk.
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

        revelations_editorial_render_scan_progress_ui();
    }
);

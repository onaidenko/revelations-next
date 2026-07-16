<?php
/**
 * Plugin Name: REVELATIONS Editorial Notice UI
 * Description: Accessible notice colours inside Editorial Desk.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render readable notice colours.
 */
function revelations_editorial_render_notice_contrast_css(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
        .notice {
            color:#1d2327 !important;
            box-shadow:none;
        }

        .notice p,
        .notice strong {
            color:#1d2327 !important;
        }

        .notice a {
            color:#135e96 !important;
        }

        .notice.notice-success {
            background:#edfaef;
            border-left-color:#00a32a;
        }

        .notice.notice-warning {
            background:#fff8e5;
            border-left-color:#dba617;
        }

        .notice.notice-error {
            background:#fcf0f1;
            border-left-color:#d63638;
        }

        .notice.notice-info {
            background:#f0f6fc;
            border-left-color:#72aee6;
        }

        .notice .notice-dismiss::before {
            color:#50575e !important;
        }

        .notice .notice-dismiss:hover::before,
        .notice .notice-dismiss:focus::before {
            color:#1d2327 !important;
        }
    </style>
    <?php
}

/**
 * Load only inside Editorial Desk.
 */
add_action(
    'admin_head',
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

        revelations_editorial_render_notice_contrast_css();
    }
);

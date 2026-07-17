<?php
/**
 * Plugin Name: REVELATIONS Editorial Desk
 * Description: Private editorial workflow interface for REVELATIONS.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the private Editorial Desk page.
 */
add_action(
    'admin_menu',
    static function (): void {
        add_menu_page(
            'REVELATIONS Editorial Desk',
            'Editorial Desk',
            'manage_options',
            'revelations-editorial-desk',
            'revelations_render_editorial_desk',
            'dashicons-welcome-write-blog',
            6.5
        );
    }
);

/**
 * Add page-specific styling only inside Editorial Desk.
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
            .revelations-desk {
                max-width: 1180px;
                margin: 24px 24px 40px 0;
            }

            .revelations-desk__header {
                background: #111827;
                color: #fff;
                padding: 32px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .revelations-desk__eyebrow {
                margin: 0 0 8px;
                color: #f0a3b4;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .18em;
                text-transform: uppercase;
            }

            .revelations-desk__header h1 {
                margin: 0;
                color: #fff;
                font-size: 32px;
                line-height: 1.2;
            }

            .revelations-desk__header p {
                max-width: 720px;
                margin: 12px 0 0;
                color: #cbd5e1;
                font-size: 14px;
            }

            .revelations-desk__tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0 0 20px;
            }

            .revelations-desk__tab {
                display: inline-flex;
                align-items: center;
                padding: 9px 14px;
                border: 1px solid #c3c4c7;
                border-radius: 999px;
                background: #fff;
                color: #1d2327;
                font-weight: 600;
                text-decoration: none;
            }

            .revelations-desk__tab--active {
                border-color: #111827;
                background: #111827;
                color: #fff;
            }

            .revelations-desk__grid {
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }

            .revelations-desk__card {
                padding: 20px;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                background: #fff;
            }

            .revelations-desk__card h2 {
                margin: 0 0 8px;
                font-size: 16px;
            }

            .revelations-desk__number {
                margin: 0;
                font-size: 30px;
                font-weight: 700;
                line-height: 1;
            }

            .revelations-desk__muted {
                margin: 10px 0 0;
                color: #646970;
            }

            .revelations-desk__notice {
                margin-top: 20px;
                padding: 16px 18px;
                border-left: 4px solid #72aee6;
                background: #fff;
            }
            '
        );
    }
);

/**
 * Render the initial non-functional interface shell.
 */
function revelations_render_editorial_desk(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            esc_html__(
                'You do not have permission to access Editorial Desk.',
                'revelations'
            )
        );
    }

    $allowed_views = array(
        'candidates',
        'drafts',
        'logs',
        'settings',
    );

    $active_view = sanitize_key(
        wp_unslash(
            $_GET['view'] ?? 'candidates'
        )
    );

    if (
        ! in_array(
            $active_view,
            $allowed_views,
            true
        )
    ) {
        $active_view = 'candidates';
    }

    $post_counts = wp_count_posts( 'post' );

    $published_count = isset( $post_counts->publish )
        ? (int) $post_counts->publish
        : 0;

    $draft_count = isset( $post_counts->draft )
        ? (int) $post_counts->draft
        : 0;

    $candidate_count = function_exists(
        'revelations_editorial_candidate_count'
    )
        ? revelations_editorial_candidate_count()
        : 0;
    ?>
    <div class="wrap revelations-desk">
        <div class="revelations-desk__header">
            <p class="revelations-desk__eyebrow">
                Admin only
            </p>

            <h1>AI Editorial Desk</h1>

            <p>
                Research, score and prepare editorial ideas.
                Every generated article will remain a WordPress
                draft until it is reviewed and published manually.
            </p>
        </div>

        <?php
        $tabs = array(
            'candidates' => 'Candidates',
            'drafts'     => 'AI Drafts',
            'logs'       => 'Run Logs',
            'settings'   => 'Settings',
        );
        ?>

        <nav class="revelations-desk__tabs">
            <?php foreach ( $tabs as $view => $label ) : ?>
                <?php
                $tab_url = add_query_arg(
                    array(
                        'page' => 'revelations-editorial-desk',
                        'view' => $view,
                    ),
                    admin_url( 'admin.php' )
                );

                $tab_class = 'revelations-desk__tab';

                if ( $active_view === $view ) {
                    $tab_class .=
                        ' revelations-desk__tab--active';
                }
                ?>

                <a
                    class="<?php echo esc_attr(
                        $tab_class
                    ); ?>"
                    href="<?php echo esc_url(
                        $tab_url
                    ); ?>"
                >
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="revelations-desk__grid">
            <div class="revelations-desk__card">
                <h2>Published articles</h2>

                <p class="revelations-desk__number">
                    <?php echo esc_html(
                        (string) $published_count
                    ); ?>
                </p>

                <p class="revelations-desk__muted">
                    Live in the WordPress CMS.
                </p>
            </div>

            <div class="revelations-desk__card">
                <h2>WordPress drafts</h2>

                <p class="revelations-desk__number">
                    <?php echo esc_html(
                        (string) $draft_count
                    ); ?>
                </p>

                <p class="revelations-desk__muted">
                    Awaiting editorial review.
                </p>
            </div>

            <div class="revelations-desk__card">
                <h2>Editorial candidates</h2>

                <p class="revelations-desk__number">
                    <?php echo esc_html(
                        (string) $candidate_count
                    ); ?>
                </p>

                <p class="revelations-desk__muted">
                    Stored privately in WordPress.
                </p>
            </div>

            <div class="revelations-desk__card">
                <?php
$ai_readiness = function_exists(
    'revelations_editorial_ai_readiness'
)
    ? revelations_editorial_ai_readiness()
    : array(
        'status' => 'not_configured',
        'label' => 'Not configured',
        'message' => 'AI readiness module is unavailable.',
    );
?>
<h2>AI status</h2>

                <p class="revelations-desk__number">
                    <?php echo esc_html(
    (string) $ai_readiness['label']
); ?>
                </p>

                <p class="revelations-desk__muted">
                    <?php echo esc_html(
    (string) $ai_readiness['message']
); ?>
                </p>
            </div>
        </div>

        <?php if ( 'candidates' === $active_view ) : ?>
            <?php
            if (
                function_exists(
                    'revelations_editorial_render_manual_candidates'
                )
            ) {
                revelations_editorial_render_manual_candidates();
            }

            if (
                function_exists(
                    'revelations_editorial_render_news_preview'
                )
            ) {
                revelations_editorial_render_news_preview();
            }

            if (
                function_exists(
                    'revelations_editorial_render_people_preview'
                )
            ) {
                revelations_editorial_render_people_preview();
            }

            if (
                function_exists(
                    'revelations_editorial_render_places_preview'
                )
            ) {
                revelations_editorial_render_places_preview();
            }

            if (
                function_exists(
                    'revelations_editorial_render_tech_preview'
                )
            ) {
                revelations_editorial_render_tech_preview();
            }

            if (
                function_exists(
                    'revelations_editorial_render_unspoken_preview'
                )
            ) {
                revelations_editorial_render_unspoken_preview();
            }
            ?>
        <?php elseif ( 'drafts' === $active_view ) : ?>
            <?php
            if (
                function_exists(
                    'revelations_editorial_render_drafts'
                )
            ) {
                revelations_editorial_render_drafts();
            }
            ?>
        <?php elseif ( 'logs' === $active_view ) : ?>
            <?php
            if (
                function_exists(
                    'revelations_editorial_render_run_logs'
                )
            ) {
                revelations_editorial_render_run_logs();
            }
            ?>
        <?php elseif ( 'settings' === $active_view ) : ?>
            <?php
            if (
                function_exists(
                    'revelations_editorial_render_settings'
                )
            ) {
                revelations_editorial_render_settings();
            }
            ?>
        <?php endif; ?>

        <div class="revelations-desk__notice">
            <strong>Editorial Desk is operational.</strong>

            Private candidate storage, News, People, Places and Tech RSS previews, and source snapshots are active.
            AI draft generation is active. Publication remains manual.
        </div>
    </div>
    <?php
}

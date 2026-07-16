<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Versions
 * Description: Displays private previous AI versions inside Editorial Desk.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return private AI versions belonging to a WordPress draft.
 *
 * @return WP_Post[]
 */
function revelations_editorial_get_ai_versions(
    int $draft_id
): array {
    if ( $draft_id < 1 ) {
        return array();
    }

    $versions = get_posts(
        array(
            'post_type'      => 'rev_ai_version',
            'post_status'    => 'private',
            'post_parent'    => $draft_id,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    return array_values(
        array_filter(
            $versions,
            static fn ( $version ): bool =>
                $version instanceof WP_Post
        )
    );
}

/**
 * Count words in a saved private AI version.
 */
function revelations_editorial_ai_version_word_count(
    WP_Post $version
): int {
    $stored_count = absint(
        get_post_meta(
            $version->ID,
            '_rev_ai_word_count',
            true
        )
    );

    if ( $stored_count > 0 ) {
        return $stored_count;
    }

    $plain_text = trim(
        wp_strip_all_tags(
            do_blocks(
                (string) $version->post_content
            ),
            true
        )
    );

    preg_match_all(
        "/[\p{L}\p{N}]+(?:[’'\-][\p{L}\p{N}]+)*/u",
        $plain_text,
        $matches
    );

    return count(
        $matches[0] ?? array()
    );
}

/**
 * Render previous versions below one AI draft.
 */
function revelations_editorial_render_ai_versions_action(
    int $draft_id
): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $versions =
        revelations_editorial_get_ai_versions(
            $draft_id
        );

    if ( array() === $versions ) {
        return;
    }
    ?>
    <div class="revelations-ai-versions">
        <details>
            <summary>
                <?php
                echo esc_html(
                    sprintf(
                        'Previous AI versions (%d)',
                        count( $versions )
                    )
                );
                ?>
            </summary>

            <div class="revelations-ai-versions__list">
                <?php foreach ( $versions as $version ) : ?>
                    <?php
                    $word_count =
                        revelations_editorial_ai_version_word_count(
                            $version
                        );

                    $saved_at = get_post_time(
                        'Y-m-d H:i:s',
                        true,
                        $version
                    );

                    $rendered_content = do_blocks(
                        (string) $version->post_content
                    );
                    ?>

                    <article class="revelations-ai-version">
                        <div class="revelations-ai-version__header">
                            <strong>
                                <?php echo esc_html(
                                    $version->post_title
                                ); ?>
                            </strong>

                            <span>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        '%d words · saved %s UTC · private',
                                        $word_count,
                                        $saved_at
                                    )
                                );
                                ?>
                            </span>
                        </div>

                        <?php if (
                            '' !== trim(
                                (string) $version->post_excerpt
                            )
                        ) : ?>
                            <div class="revelations-ai-version__excerpt">
                                <strong>Excerpt:</strong>

                                <?php echo esc_html(
                                    $version->post_excerpt
                                ); ?>
                            </div>
                        <?php endif; ?>

                        <details class="revelations-ai-version__content">
                            <summary>View saved content</summary>

                            <div class="revelations-ai-version__body">
                                <?php
                                echo wp_kses_post(
                                    $rendered_content
                                );
                                ?>
                            </div>
                        </details>

                        <?php
                        if (
                            function_exists(
                                'revelations_editorial_render_ai_version_restore_action'
                            )
                        ) {
                            revelations_editorial_render_ai_version_restore_action(
                                $draft_id,
                                $version
                            );
                        }
                        ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
    <?php
}

/**
 * Viewer styling.
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
            .revelations-ai-versions {
                margin-top:10px;
                padding-top:10px;
                border-top:1px solid #dcdcde;
            }

            .revelations-ai-versions > details > summary {
                cursor:pointer;
                font-weight:600;
            }

            .revelations-ai-versions__list {
                margin-top:10px;
            }

            .revelations-ai-version {
                margin-top:8px;
                padding:12px;
                border:1px solid #dcdcde;
                border-radius:6px;
                background:#f6f7f7;
            }

            .revelations-ai-version__header {
                display:flex;
                flex-direction:column;
                gap:3px;
            }

            .revelations-ai-version__header span {
                color:#646970;
                font-size:11px;
            }

            .revelations-ai-version__excerpt {
                margin-top:10px;
                font-size:12px;
                line-height:1.5;
            }

            .revelations-ai-version__content {
                margin-top:10px;
            }

            .revelations-ai-version__content > summary {
                cursor:pointer;
                color:#2271b1;
            }

            .revelations-ai-version__body {
                max-height:420px;
                margin-top:10px;
                padding:12px;
                overflow:auto;
                border:1px solid #c3c4c7;
                background:#fff;
                line-height:1.55;
            }

            .revelations-ai-version__body h1,
            .revelations-ai-version__body h2,
            .revelations-ai-version__body h3 {
                margin-top:18px;
            }
            '
        );
    }
);

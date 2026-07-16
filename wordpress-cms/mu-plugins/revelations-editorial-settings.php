<?php
/**
 * Plugin Name: REVELATIONS Editorial Settings
 * Description: Private editorial policy and AI writing settings.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const REVELATIONS_EDITORIAL_SETTINGS_OPTION =
    'revelations_editorial_settings';

/**
 * Default editorial configuration.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_default_settings(): array {
    return array(
        'editorial_policy' =>
            'REVELATIONS is a future-facing lifestyle media platform. '
            . 'Write in an intelligent, sharp, stylish and slightly '
            . 'ironic editorial voice. Do not sound like a press release. '
            . 'Explain why the story matters, what it says about the '
            . 'future and how it changes human behaviour or culture. '
            . 'Use verified facts only. Separate facts from interpretation. '
            . 'Never invent facts, quotes, dates or statistics.',

        'tone_of_voice' =>
            'Intelligent, direct, editorial and future-facing. '
            . 'Avoid corporate language, empty hype and generic conclusions.',

        'preferred_article_structure' =>
            "1. Strong editorial hook.\n"
            . "2. What happened — verified facts and context.\n"
            . "3. Why it matters.\n"
            . "4. What this changes for people, culture or industry.\n"
            . "5. A concise future-facing conclusion.",

        'article_length_min' => 350,
        'article_length_max' => 600,

        'banned_words' =>
            "cutting-edge\n"
            . "revolutionary\n"
            . "innovative solution\n"
            . "industry-leading\n"
            . "game-changing\n"
            . "dynamic ecosystem\n"
            . "seamless experience\n"
            . "groundbreaking\n"
            . "disruptive\n"
            . "state-of-the-art\n"
            . "next-generation\n"
            . "world-class",
    );
}

/**
 * Load saved settings merged with defaults.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_get_settings(): array {
    $stored = get_option(
        REVELATIONS_EDITORIAL_SETTINGS_OPTION,
        array()
    );

    if ( ! is_array( $stored ) ) {
        $stored = array();
    }

    return wp_parse_args(
        $stored,
        revelations_editorial_default_settings()
    );
}

/**
 * Redirect back to the settings tab.
 *
 * @param array<string, scalar> $args Query arguments.
 */
function revelations_editorial_settings_redirect(
    array $args
): void {
    wp_safe_redirect(
        add_query_arg(
            array_merge(
                array(
                    'page' => 'revelations-editorial-desk',
                    'view' => 'settings',
                ),
                $args
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Save Editorial Desk settings.
 */
add_action(
    'admin_post_revelations_save_editorial_settings',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to change editorial settings.',
                    'revelations'
                )
            );
        }

        check_admin_referer(
            'revelations_save_editorial_settings'
        );

        $minimum = absint(
            $_POST['article_length_min'] ?? 350
        );

        $maximum = absint(
            $_POST['article_length_max'] ?? 600
        );

        $minimum = max( 100, min( 3000, $minimum ) );
        $maximum = max( 100, min( 3000, $maximum ) );

        if ( $minimum > $maximum ) {
            revelations_editorial_settings_redirect(
                array(
                    'settings_error' => 'length',
                )
            );
        }

        $settings = array(
            'editorial_policy' =>
                sanitize_textarea_field(
                    wp_unslash(
                        $_POST['editorial_policy'] ?? ''
                    )
                ),

            'tone_of_voice' =>
                sanitize_textarea_field(
                    wp_unslash(
                        $_POST['tone_of_voice'] ?? ''
                    )
                ),

            'preferred_article_structure' =>
                sanitize_textarea_field(
                    wp_unslash(
                        $_POST['preferred_article_structure'] ?? ''
                    )
                ),

            'article_length_min' => $minimum,
            'article_length_max' => $maximum,

            'banned_words' =>
                sanitize_textarea_field(
                    wp_unslash(
                        $_POST['banned_words'] ?? ''
                    )
                ),
        );

        update_option(
            REVELATIONS_EDITORIAL_SETTINGS_OPTION,
            $settings,
            false
        );

        revelations_editorial_settings_redirect(
            array(
                'settings_saved' => 1,
            )
        );
    }
);

/**
 * Render the private Editorial Settings form.
 */
function revelations_editorial_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings = revelations_editorial_get_settings();

    if ( isset( $_GET['settings_saved'] ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Editorial settings saved.</p>
        </div>
        <?php
    }

    $settings_error = sanitize_key(
        wp_unslash(
            $_GET['settings_error'] ?? ''
        )
    );

    if ( 'length' === $settings_error ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                Minimum article length cannot exceed maximum length.
            </p>
        </div>
        <?php
    }
    ?>
    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <h2>Editorial Settings</h2>

        <p class="revelations-desk__section-description">
            These instructions will control AI-generated WordPress
            drafts. Saving this form does not make any AI request.
        </p>

        <form
            method="post"
            action="<?php echo esc_url(
                admin_url( 'admin-post.php' )
            ); ?>"
        >
            <input
                type="hidden"
                name="action"
                value="revelations_save_editorial_settings"
            >

            <?php wp_nonce_field(
                'revelations_save_editorial_settings'
            ); ?>

            <div class="revelations-desk__field">
                <label for="editorial-policy">
                    Editorial policy
                </label>

                <textarea
                    id="editorial-policy"
                    name="editorial_policy"
                    rows="8"
                    required
                ><?php echo esc_textarea(
                    (string) $settings['editorial_policy']
                ); ?></textarea>
            </div>

            <div class="revelations-desk__field">
                <label for="tone-of-voice">
                    Tone of voice
                </label>

                <textarea
                    id="tone-of-voice"
                    name="tone_of_voice"
                    rows="5"
                    required
                ><?php echo esc_textarea(
                    (string) $settings['tone_of_voice']
                ); ?></textarea>
            </div>

            <div class="revelations-desk__field">
                <label for="article-structure">
                    Preferred article structure
                </label>

                <textarea
                    id="article-structure"
                    name="preferred_article_structure"
                    rows="7"
                    required
                ><?php echo esc_textarea(
                    (string) $settings[
                        'preferred_article_structure'
                    ]
                ); ?></textarea>
            </div>

            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:16px;
                "
            >
                <div class="revelations-desk__field">
                    <label for="article-length-min">
                        Minimum words
                    </label>

                    <input
                        id="article-length-min"
                        name="article_length_min"
                        type="number"
                        min="100"
                        max="3000"
                        value="<?php echo esc_attr(
                            (string) $settings[
                                'article_length_min'
                            ]
                        ); ?>"
                        required
                    >
                </div>

                <div class="revelations-desk__field">
                    <label for="article-length-max">
                        Maximum words
                    </label>

                    <input
                        id="article-length-max"
                        name="article_length_max"
                        type="number"
                        min="100"
                        max="3000"
                        value="<?php echo esc_attr(
                            (string) $settings[
                                'article_length_max'
                            ]
                        ); ?>"
                        required
                    >
                </div>
            </div>

            <div class="revelations-desk__field">
                <label for="banned-words">
                    Banned phrases
                </label>

                <textarea
                    id="banned-words"
                    name="banned_words"
                    rows="10"
                    required
                ><?php echo esc_textarea(
                    (string) $settings['banned_words']
                ); ?></textarea>

                <p class="description">
                    Enter one phrase per line.
                </p>
            </div>

            <?php submit_button(
                'Save editorial settings',
                'primary',
                'submit',
                false
            ); ?>
        </form>
    </section>

    <?php
    if (
        function_exists(
            'revelations_editorial_render_scanner_settings'
        )
    ) {
        revelations_editorial_render_scanner_settings();
    }
    ?>
    <?php
}

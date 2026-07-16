<?php
/**
 * Plugin Name: REVELATIONS Editorial Scanner Settings
 * Description: Private per-section configuration for editorial candidate scanners.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const REVELATIONS_EDITORIAL_SCANNER_SETTINGS_OPTION =
    'revelations_editorial_scanner_settings';

/**
 * Sections allowed to use candidate scanning.
 *
 * Podcast is intentionally excluded because its content
 * is created internally rather than discovered from feeds.
 *
 * @return array<string, string>
 */
function revelations_editorial_scanner_sections(): array {
    return array(
        'news'     => 'News',
        'people'   => 'People',
        'tech'     => 'Tech',
        'places'   => 'Places',
        'unspoken' => 'Unspoken',
    );
}

/**
 * Return a safe empty scanner profile.
 *
 * Empty profiles are disabled and cannot make RSS requests.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_empty_scanner_profile(): array {
    return array(
        'enabled' =>
            false,

        'preview_limit' =>
            10,

        'active_sources' =>
            array(),

        'disabled_sources' =>
            array(),

        'keywords' => array(
            'relevance' =>
                array(),

            'implementation' =>
                array(),

            'speculative' =>
                array(),

            'impact' =>
                array(),

            'product_launch' =>
                array(),

            'avoid' =>
                array(),
        ),

        'thresholds' =>
            array(),
    );
}

/**
 * Default scanner profiles.
 *
 * Tech retains its working configuration. Other sections begin
 * disabled and will be configured separately through the UI.
 *
 * Podcast is intentionally excluded.
 *
 * @return array<string, array<string, mixed>>
 */
function revelations_editorial_default_scanner_settings(): array {
    return array(
        'tech' => array(
            'enabled' =>
                true,

            'preview_limit' =>
                10,

            'active_sources' => array(
                array(
                    'name' =>
                        'TechCrunch AI',

                    'url' =>
                        'https://techcrunch.com/category/artificial-intelligence/feed/',
                ),

                array(
                    'name' =>
                        'The Robot Report',

                    'url' =>
                        'https://www.therobotreport.com/feed/',
                ),

                array(
                    'name' =>
                        'MIT Technology Review',

                    'url' =>
                        'https://www.technologyreview.com/feed/',
                ),
            ),

            'disabled_sources' => array(
                array(
                    'name' =>
                        'Healthcare IT News',

                    'reason' =>
                        'RSS endpoint returns HTTP 403.',
                ),

                array(
                    'name' =>
                        'VentureBeat AI',

                    'reason' =>
                        'RSS feed is stale; newest item was dated 2026-05-19.',
                ),
            ),

            'keywords' => array(
                'relevance' => array(
                    'ai',
                    'artificial intelligence',
                    'robot',
                    'robotics',
                    'automation',
                    'medical ai',
                    'surgical',
                    'diagnostics',
                    'drug discovery',
                    'factory',
                    'warehouse',
                    'autonomous',
                    'fintech',
                    'infrastructure',
                    'cybersecurity',
                    'machine learning',
                    'neural',
                    'industrial ai',
                    'physical ai',
                ),

                'implementation' => array(
                    'deployed',
                    'used',
                    'launched',
                    'piloted',
                    'approved',
                    'operated',
                    'performed',
                    'installed',
                    'live',
                    'commercial',
                    'hospital',
                    'patient',
                    'factory',
                    'warehouse',
                    'robot',
                    'autonomous',
                    'surgery',
                    'diagnostic',
                    'clinical',
                    'manufacturing',
                    'logistics',
                    'agriculture',
                    'trial',
                    'fda',
                    'cleared',
                    'certified',
                    'production',
                ),

                'speculative' => array(
                    'plans to',
                    'could',
                    'may ',
                    'aims to',
                    'intends to',
                    'forecast',
                    'opinion',
                    'predicts',
                    'might',
                    'hopes to',
                    'expects to',
                    'will eventually',
                ),

                'impact' => array(
                    'patient',
                    'surgery',
                    'cancer',
                    'harvest',
                    'warehouse',
                    'factory',
                    'diagnostic',
                    'performs',
                    'detects',
                    'autonomous robot',
                    'first time',
                    'breakthrough',
                    'approval',
                ),

                'product_launch' => array(
                    'launches',
                    'launched',
                    'rolls out',
                    'rolled out',
                    'opens',
                    'opened',
                    'public beta',
                    'available now',
                    'available to everyone',
                    'released',
                    'releases',
                    'debut',
                    'introducing',
                ),

                'avoid' => array(
                    'funding',
                    'raises',
                    'valuation',
                    'ipo',
                    'crypto',
                    'bitcoin',
                    'nft',
                    'metaverse',
                    'opinion:',
                    'commentary:',
                    'editorial:',
                    'review:',
                    'acquires',
                    'acquisition',
                    'merger',
                    'merges',
                    'patent',
                    'rebrands',
                    'startup alley',
                    'apply to',
                ),
            ),

            'thresholds' => array(
                'applied_technology' => array(
                    'total_score' =>
                        4.0,

                    'relevance_score' =>
                        4.0,

                    'implementation_score' =>
                        2.0,
                ),

                'high_impact_technology' => array(
                    'total_score' =>
                        4.3,

                    'relevance_score' =>
                        2.0,

                    'implementation_score' =>
                        2.0,

                    'impact_score' =>
                        2.5,
                ),

                'public_product_launch' => array(
                    'total_score' =>
                        3.2,

                    'freshness_score' =>
                        8.0,

                    'relevance_score' =>
                        2.0,
                ),
            ),
        ),

        'news' => array(
            'enabled' =>
                false,

            'preview_limit' =>
                5,

            'active_sources' => array(
                array(
                    'name' =>
                        'The Verge',

                    'url' =>
                        'https://www.theverge.com/rss/index.xml',
                ),

                array(
                    'name' =>
                        'TechCrunch',

                    'url' =>
                        'https://techcrunch.com/feed/',
                ),

                array(
                    'name' =>
                        'Wired',

                    'url' =>
                        'https://www.wired.com/feed/rss',
                ),

                array(
                    'name' =>
                        'MIT Technology Review',

                    'url' =>
                        'https://www.technologyreview.com/feed/',
                ),

                array(
                    'name' =>
                        'BBC Technology',

                    'url' =>
                        'https://feeds.bbci.co.uk/news/technology/rss.xml',
                ),
            ),

            'disabled_sources' =>
                array(),

            'keywords' => array(
                'relevance' => array(
                    'launch',
                    'regulation',
                    'policy',
                    'acquisition',
                    'partnership',
                    'market',
                    'platform',
                    'government',
                    'law',
                    'ban',
                    'approval',
                    'investment',
                    'crisis',
                    'shift',
                    'expansion',
                    'deal',
                    'merger',
                    'announce',
                    'release',
                    'report',
                ),

                'implementation' => array(
                    'announced',
                    'launched',
                    'signed',
                    'passed',
                    'approved',
                    'banned',
                    'enacted',
                    'acquired',
                    'merged',
                    'released',
                    'published',
                    'confirmed',
                    'revealed',
                ),

                'speculative' => array(
                    'plans to',
                    'could',
                    'may ',
                    'aims to',
                    'intends to',
                    'expected to',
                    'likely to',
                    'might',
                ),

                'impact' => array(
                    'breaking',
                    'exclusive',
                    'first',
                    'major',
                    'historic',
                    'record',
                    'billion',
                    'landmark',
                    'global',
                    'crisis',
                ),

                'product_launch' =>
                    array(),

                'avoid' => array(
                    'opinion:',
                    'commentary:',
                    'sponsored',
                    'advertisement',
                    'press release',
                    'crypto',
                    'bitcoin',
                    'nft',
                ),
            ),

            'thresholds' => array(
                'news_signal' => array(
                    'total_score' =>
                        4.5,

                    'relevance_score' =>
                        2.0,

                    'freshness_score' =>
                        4.0,

                    'significance_relevance_score' =>
                        6.0,

                    'significance_impact_score' =>
                        2.5,
                ),
            ),
        ),

        'people' =>
            revelations_editorial_empty_scanner_profile(),

        'places' =>
            revelations_editorial_empty_scanner_profile(),

        'unspoken' =>
            revelations_editorial_empty_scanner_profile(),
    );
}

/**
 * Load scanner settings merged with defaults.
 *
 * @return array<string, array<string, mixed>>
 */
function revelations_editorial_get_scanner_settings(): array {
    $defaults =
        revelations_editorial_default_scanner_settings();

    $stored = get_option(
        REVELATIONS_EDITORIAL_SCANNER_SETTINGS_OPTION,
        array()
    );

    if ( ! is_array( $stored ) ) {
        $stored = array();
    }

    foreach ( $defaults as $section => $profile ) {
        if (
            isset( $stored[ $section ] ) &&
            is_array( $stored[ $section ] )
        ) {
            /*
             * Replace profile fields at the top level.
             *
             * Numeric lists such as sources and keywords must be
             * replaceable in full so administrators can remove rows.
             */
            $defaults[ $section ] =
                array_replace(
                    $profile,
                    $stored[ $section ]
                );
        }
    }

    return $defaults;
}

/**
 * Return one scanner profile.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_get_scanner_profile(
    string $section
): array {
    $section = sanitize_key(
        $section
    );

    $settings =
        revelations_editorial_get_scanner_settings();

    return isset( $settings[ $section ] ) &&
        is_array( $settings[ $section ] )
            ? $settings[ $section ]
            : array();
}

/**
 * Convert one-value-per-line input into a clean list.
 *
 * @return string[]
 */
function revelations_editorial_scanner_parse_lines(
    string $raw
): array {
    $lines = preg_split(
        '/\R/u',
        wp_unslash( $raw )
    );

    if ( ! is_array( $lines ) ) {
        return array();
    }

    return array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn ( string $line ): string =>
                        sanitize_text_field(
                            trim( $line )
                        ),
                    $lines
                )
            )
        )
    );
}

/**
 * Parse active source rows in "Name | URL" format.
 *
 * @return array<int, array{name:string,url:string}>|WP_Error
 */
function revelations_editorial_scanner_parse_active_sources(
    string $raw
) {
    $lines =
        revelations_editorial_scanner_parse_lines(
            $raw
        );

    $sources = array();

    foreach ( $lines as $line ) {
        $parts = array_map(
            'trim',
            explode(
                '|',
                $line,
                2
            )
        );

        $name = sanitize_text_field(
            (string) (
                $parts[0] ?? ''
            )
        );

        $url = esc_url_raw(
            (string) (
                $parts[1] ?? ''
            )
        );

        if (
            '' === $name ||
            '' === $url ||
            ! wp_http_validate_url( $url )
        ) {
            return new WP_Error(
                'invalid_active_source',
                'Every active source must use: Name | valid RSS URL.'
            );
        }

        $sources[] = array(
            'name' => $name,
            'url'  => $url,
        );
    }

    return $sources;
}

/**
 * Parse disabled source rows in "Name | Reason" format.
 *
 * @return array<int, array{name:string,reason:string}>|WP_Error
 */
function revelations_editorial_scanner_parse_disabled_sources(
    string $raw
) {
    $lines =
        revelations_editorial_scanner_parse_lines(
            $raw
        );

    $sources = array();

    foreach ( $lines as $line ) {
        $parts = array_map(
            'trim',
            explode(
                '|',
                $line,
                2
            )
        );

        $name = sanitize_text_field(
            (string) (
                $parts[0] ?? ''
            )
        );

        $reason = sanitize_text_field(
            (string) (
                $parts[1] ?? ''
            )
        );

        if (
            '' === $name ||
            '' === $reason
        ) {
            return new WP_Error(
                'invalid_disabled_source',
                'Every disabled source must use: Name | reason.'
            );
        }

        $sources[] = array(
            'name'   => $name,
            'reason' => $reason,
        );
    }

    return $sources;
}

/**
 * Return source rows for a textarea.
 *
 * @param array<int, array<string, string>> $sources Sources.
 */
function revelations_editorial_scanner_sources_text(
    array $sources,
    string $value_key
): string {
    $rows = array();

    foreach ( $sources as $source ) {
        if ( ! is_array( $source ) ) {
            continue;
        }

        $name = trim(
            (string) (
                $source['name'] ?? ''
            )
        );

        $value = trim(
            (string) (
                $source[ $value_key ] ?? ''
            )
        );

        if (
            '' !== $name &&
            '' !== $value
        ) {
            $rows[] =
                $name .
                ' | ' .
                $value;
        }
    }

    return implode(
        "\n",
        $rows
    );
}

/**
 * Resolve a scanner section from submitted form data.
 *
 * An empty result means that the section is invalid.
 *
 * @param array<string, mixed> $request Request data.
 */
function revelations_editorial_scanner_section_from_request(
    array $request
): string {
    $section = sanitize_key(
        wp_unslash(
            (string) (
                $request['scanner_section'] ?? ''
            )
        )
    );

    $sections =
        revelations_editorial_scanner_sections();

    return isset( $sections[ $section ] )
        ? $section
        : '';
}

/**
 * Check whether a scanner profile is currently editable.
 *
 * Additional sections will be added only after their rules
 * and defaults have been prepared and tested.
 */
function revelations_editorial_scanner_section_is_editable(
    string $section
): bool {
    return in_array(
        sanitize_key( $section ),
        array(
            'news',
            'tech',
        ),
        true
    );
}

/**
 * Redirect to Scanner Settings.
 *
 * @param array<string, scalar> $args Query arguments.
 */
function revelations_editorial_scanner_settings_redirect(
    array $args
): void {
    wp_safe_redirect(
        add_query_arg(
            array_merge(
                array(
                    'page' =>
                        'revelations-editorial-desk',

                    'view' =>
                        'settings',

                    'scanner_section' =>
                        'tech',
                ),
                $args
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Save the Tech scanner profile.
 */
add_action(
    'admin_post_revelations_save_scanner_settings',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to change scanner settings.',
                    'revelations'
                )
            );
        }

        check_admin_referer(
            'revelations_save_scanner_settings'
        );

        $section =
            revelations_editorial_scanner_section_from_request(
                $_POST
            );

        if ( '' === $section ) {
            revelations_editorial_scanner_settings_redirect(
                array(
                    'scanner_section' =>
                        'tech',

                    'scanner_error' =>
                        'invalid_section',
                )
            );
        }

        if (
            ! revelations_editorial_scanner_section_is_editable(
                $section
            )
        ) {
            revelations_editorial_scanner_settings_redirect(
                array(
                    'scanner_section' =>
                        $section,

                    'scanner_error' =>
                        'section_locked',
                )
            );
        }

        $field_prefix =
            $section .
            '_';

        $settings =
            revelations_editorial_get_scanner_settings();

        $defaults =
            revelations_editorial_default_scanner_settings();

        $active_sources =
            revelations_editorial_scanner_parse_active_sources(
                (string) (
                    $_POST[
                        $field_prefix .
                        'active_sources'
                    ] ?? ''
                )
            );

        if ( is_wp_error( $active_sources ) ) {
            revelations_editorial_scanner_settings_redirect(
                array(
                    'scanner_section' =>
                        $section,

                    'scanner_error' =>
                        'active_sources',
                )
            );
        }

        $disabled_sources =
            revelations_editorial_scanner_parse_disabled_sources(
                (string) (
                    $_POST[
                        $field_prefix .
                        'disabled_sources'
                    ] ?? ''
                )
            );

        if ( is_wp_error( $disabled_sources ) ) {
            revelations_editorial_scanner_settings_redirect(
                array(
                    'scanner_section' =>
                        $section,

                    'scanner_error' =>
                        'disabled_sources',
                )
            );
        }

        $enabled = ! empty(
            $_POST[
                $field_prefix .
                'enabled'
            ]
        );

        if (
            $enabled &&
            array() === $active_sources
        ) {
            revelations_editorial_scanner_settings_redirect(
                array(
                    'scanner_section' =>
                        $section,

                    'scanner_error' =>
                        'no_active_sources',
                )
            );
        }

        $keyword_groups = array(
            'relevance',
            'implementation',
            'speculative',
            'impact',
            'product_launch',
            'avoid',
        );

        $keywords = array();

        foreach ( $keyword_groups as $group ) {
            $keywords[ $group ] =
                revelations_editorial_scanner_parse_lines(
                    (string) (
                        $_POST[
                            $field_prefix .
                            'keywords_' .
                            $group
                        ] ?? ''
                    )
                );
        }

        $thresholds = isset(
            $defaults[ $section ]['thresholds']
        ) && is_array(
            $defaults[ $section ]['thresholds']
        )
            ? $defaults[ $section ]['thresholds']
            : array();

        foreach (
            $thresholds as $track => $values
        ) {
            if ( ! is_array( $values ) ) {
                continue;
            }

            foreach ( $values as $key => $default ) {
                $field =
                    $field_prefix .
                    'threshold_' .
                    $track .
                    '_' .
                    $key;

                $value = isset( $_POST[ $field ] )
                    ? (float) wp_unslash(
                        $_POST[ $field ]
                    )
                    : (float) $default;

                $thresholds[ $track ][ $key ] =
                    max(
                        0,
                        min(
                            10,
                            round(
                                $value,
                                1
                            )
                        )
                    );
            }
        }

        $settings[ $section ] = array(
            'enabled' =>
                $enabled,

            'preview_limit' =>
                max(
                    1,
                    min(
                        20,
                        absint(
                            $_POST[
                                $field_prefix .
                                'preview_limit'
                            ] ?? 10
                        )
                    )
                ),

            'active_sources' =>
                $active_sources,

            'disabled_sources' =>
                $disabled_sources,

            'keywords' =>
                $keywords,

            'thresholds' =>
                $thresholds,
        );

        update_option(
            REVELATIONS_EDITORIAL_SCANNER_SETTINGS_OPTION,
            $settings,
            false
        );

        revelations_editorial_scanner_settings_redirect(
            array(
                'scanner_section' =>
                    $section,

                'scanner_saved' =>
                    $section,
            )
        );
    }
);

/**
 * Render one keyword textarea.
 *
 * @param string[] $values Keywords.
 */
function revelations_editorial_render_scanner_keyword_field(
    string $section,
    string $key,
    string $label,
    array $values
): void {
    $section = sanitize_key(
        $section
    );

    $key = sanitize_key(
        $key
    );

    $field_id =
        $section .
        '-keywords-' .
        $key;

    $field_name =
        $section .
        '_keywords_' .
        $key;
    ?>
    <div class="revelations-desk__field">
        <label for="<?php echo esc_attr(
            $field_id
        ); ?>">
            <?php echo esc_html( $label ); ?>
        </label>

        <textarea
            id="<?php echo esc_attr(
                $field_id
            ); ?>"
            name="<?php echo esc_attr(
                $field_name
            ); ?>"
            rows="8"
        ><?php echo esc_textarea(
            implode(
                "\n",
                $values
            )
        ); ?></textarea>

        <p class="description">
            One phrase per line.
        </p>
    </div>
    <?php
}

/**
 * Return the selected scanner section from the Settings URL.
 */
function revelations_editorial_selected_scanner_section(): string {
    $sections =
        revelations_editorial_scanner_sections();

    $selected = sanitize_key(
        wp_unslash(
            $_GET['scanner_section'] ?? 'tech'
        )
    );

    return isset( $sections[ $selected ] )
        ? $selected
        : 'tech';
}

/**
 * Render Scanner Settings beneath the global policy form.
 */
function revelations_editorial_render_scanner_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $sections =
        revelations_editorial_scanner_sections();

    $selected_section =
        revelations_editorial_selected_scanner_section();

    $selected_label =
        $sections[ $selected_section ]
        ?? 'Tech';

    $profile =
        revelations_editorial_get_scanner_profile(
            $selected_section
        );

    $profile_editable =
        revelations_editorial_scanner_section_is_editable(
            $selected_section
        );

    $scanner_error = sanitize_key(
        (string) (
            $_GET['scanner_error'] ?? ''
        )
    );

    $saved_section = sanitize_key(
        (string) (
            $_GET['scanner_saved'] ?? ''
        )
    );

    if (
        $saved_section === $selected_section &&
        isset( $sections[ $saved_section ] )
    ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php echo esc_html(
                    $selected_label .
                    ' scanner settings saved.'
                ); ?>
            </p>
        </div>
        <?php
    }

    if ( '' !== $scanner_error ) {
        $messages = array(
            'active_sources' =>
                'Check active sources. Each row must use: Name | valid RSS URL.',

            'disabled_sources' =>
                'Check disabled sources. Each row must use: Name | reason.',

            'no_active_sources' =>
                'An enabled scanner must have at least one active RSS source.',

            'invalid_section' =>
                'The requested scanner section is invalid.',

            'section_locked' =>
                'This scanner profile is not editable yet.',
        );
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo esc_html(
                    $messages[ $scanner_error ]
                    ?? 'Scanner settings could not be saved.'
                ); ?>
            </p>
        </div>
        <?php
    }

    $keywords = isset(
        $profile['keywords']
    ) && is_array(
        $profile['keywords']
    )
        ? $profile['keywords']
        : array();

    $thresholds = isset(
        $profile['thresholds']
    ) && is_array(
        $profile['thresholds']
    )
        ? $profile['thresholds']
        : array();
    ?>
    <section
        class="revelations-desk__section"
        style="margin-top:20px"
    >
        <h2>Candidate Scanner Settings</h2>

        <p class="revelations-desk__section-description">
            Scanner profiles are stored separately from the
            global editorial policy. Saving this form does not
            run an RSS scan or make an AI request.
        </p>

        <p>
            <strong>Scannable sections:</strong>
            News, People, Tech, Places and Unspoken.
            Podcast is intentionally excluded because its
            content is created internally.
        </p>

        <nav
            aria-label="Scanner sections"
            style="
                display:flex;
                flex-wrap:wrap;
                gap:8px;
                margin:18px 0 22px;
            "
        >
            <?php foreach (
                $sections as $section_key => $section_label
            ) : ?>
                <?php
                $section_url = add_query_arg(
                    array(
                        'page' =>
                            'revelations-editorial-desk',

                        'view' =>
                            'settings',

                        'scanner_section' =>
                            $section_key,
                    ),
                    admin_url( 'admin.php' )
                );

                $section_class =
                    $section_key === $selected_section
                        ? 'button button-primary'
                        : 'button';
                ?>

                <a
                    class="<?php echo esc_attr(
                        $section_class
                    ); ?>"
                    href="<?php echo esc_url(
                        $section_url
                    ); ?>"
                >
                    <?php echo esc_html(
                        $section_label
                    ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ( ! $profile_editable ) : ?>
            <?php
            $active_sources = isset(
                $profile['active_sources']
            ) && is_array(
                $profile['active_sources']
            )
                ? $profile['active_sources']
                : array();

            $profile_keywords = isset(
                $profile['keywords']
            ) && is_array(
                $profile['keywords']
            )
                ? $profile['keywords']
                : array();

            $profile_thresholds = isset(
                $profile['thresholds']
            ) && is_array(
                $profile['thresholds']
            )
                ? $profile['thresholds']
                : array();

            $keyword_count = array_sum(
                array_map(
                    static fn ( $values ): int =>
                        is_array( $values )
                            ? count( $values )
                            : 0,
                    $profile_keywords
                )
            );
            ?>

            <div
                style="
                    padding:18px;
                    border:1px solid #dcdcde;
                    background:#fff;
                "
            >
                <h3 style="margin-top:0">
                    Scanner:
                    <?php echo esc_html(
                        $selected_label
                    ); ?>
                </h3>

                <p>
                    This scanner profile currently exists in a
                    safe disabled state. Editing will be enabled
                    after its sources, keyword groups and scoring
                    rules are prepared.
                </p>

                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th scope="row">Status</th>
                            <td>
                                <?php echo ! empty(
                                    $profile['enabled']
                                )
                                    ? 'Enabled'
                                    : 'Disabled'; ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Preview limit</th>
                            <td>
                                <?php echo esc_html(
                                    (string) absint(
                                        $profile[
                                            'preview_limit'
                                        ] ?? 10
                                    )
                                ); ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Active RSS sources</th>
                            <td>
                                <?php echo esc_html(
                                    (string) count(
                                        $active_sources
                                    )
                                ); ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Configured keywords</th>
                            <td>
                                <?php echo esc_html(
                                    (string) $keyword_count
                                ); ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Scoring tracks</th>
                            <td>
                                <?php echo esc_html(
                                    (string) count(
                                        $profile_thresholds
                                    )
                                ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="description">
                    No RSS scan or AI request can be started
                    from this profile yet.
                </p>
            </div>
        </section>
            <?php
            return;
            ?>
        <?php endif; ?>

        <form
            method="post"
            action="<?php echo esc_url(
                admin_url( 'admin-post.php' )
            ); ?>"
        >
            <input
                type="hidden"
                name="action"
                value="revelations_save_scanner_settings"
            >

            <input
                type="hidden"
                name="scanner_section"
                value="<?php echo esc_attr(
                    $selected_section
                ); ?>"
            >

            <?php wp_nonce_field(
                'revelations_save_scanner_settings'
            ); ?>

            <h3>
                Scanner:
                <?php echo esc_html(
                    $selected_label
                ); ?>
            </h3>

            <label
                style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    margin:12px 0 18px;
                "
            >
                <input
                    type="checkbox"
                    name="<?php echo esc_attr(
                        $selected_section .
                        '_enabled'
                    ); ?>"
                    value="1"
                    <?php checked(
                        ! empty(
                            $profile['enabled']
                        )
                    ); ?>
                >

                Enable
                <?php echo esc_html(
                    $selected_label
                ); ?>
                candidate scanner
            </label>

            <div class="revelations-desk__field">
                <label for="<?php echo esc_attr(
                    $selected_section .
                    '-preview-limit'
                ); ?>">
                    Maximum candidates in preview
                </label>

                <input
                    id="<?php echo esc_attr(
                        $selected_section .
                        '-preview-limit'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $selected_section .
                        '_preview_limit'
                    ); ?>"
                    type="number"
                    min="1"
                    max="20"
                    value="<?php echo esc_attr(
                        (string) (
                            $profile[
                                'preview_limit'
                            ] ?? 10
                        )
                    ); ?>"
                    required
                >
            </div>

            <div class="revelations-desk__field">
                <label for="<?php echo esc_attr(
                    $selected_section .
                    '-active-sources'
                ); ?>">
                    Active RSS sources
                </label>

                <textarea
                    id="<?php echo esc_attr(
                        $selected_section .
                        '-active-sources'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $selected_section .
                        '_active_sources'
                    ); ?>"
                    rows="7"
                    required
                ><?php echo esc_textarea(
                    revelations_editorial_scanner_sources_text(
                        (array) (
                            $profile[
                                'active_sources'
                            ] ?? array()
                        ),
                        'url'
                    )
                ); ?></textarea>

                <p class="description">
                    One source per line:
                    Name | RSS URL
                </p>
            </div>

            <div class="revelations-desk__field">
                <label for="<?php echo esc_attr(
                    $selected_section .
                    '-disabled-sources'
                ); ?>">
                    Disabled RSS sources
                </label>

                <textarea
                    id="<?php echo esc_attr(
                        $selected_section .
                        '-disabled-sources'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $selected_section .
                        '_disabled_sources'
                    ); ?>"
                    rows="5"
                ><?php echo esc_textarea(
                    revelations_editorial_scanner_sources_text(
                        (array) (
                            $profile[
                                'disabled_sources'
                            ] ?? array()
                        ),
                        'reason'
                    )
                ); ?></textarea>

                <p class="description">
                    One source per line:
                    Name | reason
                </p>
            </div>

            <details open>
                <summary>
                    <strong>
                        <?php echo esc_html(
                            $selected_label
                        ); ?>
                        keyword groups
                    </strong>
                </summary>

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(280px, 1fr)
                            );
                        gap:16px;
                        margin-top:16px;
                    "
                >
                    <?php
                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'relevance',
                        'Relevance keywords',
                        (array) (
                            $keywords['relevance']
                            ?? array()
                        )
                    );

                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'implementation',
                        'Implementation signals',
                        (array) (
                            $keywords['implementation']
                            ?? array()
                        )
                    );

                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'speculative',
                        'Speculative or weak signals',
                        (array) (
                            $keywords['speculative']
                            ?? array()
                        )
                    );

                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'impact',
                        'Impact signals',
                        (array) (
                            $keywords['impact']
                            ?? array()
                        )
                    );

                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'product_launch',
                        'Product-launch signals',
                        (array) (
                            $keywords['product_launch']
                            ?? array()
                        )
                    );

                    revelations_editorial_render_scanner_keyword_field(
                        $selected_section,
                        'avoid',
                        'Avoid keywords',
                        (array) (
                            $keywords['avoid']
                            ?? array()
                        )
                    );
                    ?>
                </div>
            </details>

            <details style="margin-top:20px">
                <summary>
                    <strong>
                        <?php echo esc_html(
                            $selected_label
                        ); ?>
                        scoring thresholds
                    </strong>
                </summary>

                <p class="description">
                    Values range from 0 to 10.
                </p>

                <?php foreach (
                    $thresholds as $track => $values
                ) : ?>
                    <fieldset
                        style="
                            margin-top:14px;
                            padding:14px;
                            border:1px solid #dcdcde;
                        "
                    >
                        <legend>
                            <strong>
                                <?php echo esc_html(
                                    str_replace(
                                        '_',
                                        ' ',
                                        ucfirst( $track )
                                    )
                                ); ?>
                            </strong>
                        </legend>

                        <div
                            style="
                                display:grid;
                                grid-template-columns:
                                    repeat(
                                        auto-fit,
                                        minmax(180px, 1fr)
                                    );
                                gap:12px;
                            "
                        >
                            <?php foreach (
                                $values as $key => $value
                            ) : ?>
                                <?php
                                $field =
                                    $selected_section .
                                    '_threshold_' .
                                    $track .
                                    '_' .
                                    $key;
                                ?>

                                <label>
                                    <?php echo esc_html(
                                        str_replace(
                                            '_',
                                            ' ',
                                            ucfirst( $key )
                                        )
                                    ); ?>

                                    <input
                                        type="number"
                                        name="<?php echo esc_attr(
                                            $field
                                        ); ?>"
                                        min="0"
                                        max="10"
                                        step="0.1"
                                        value="<?php echo esc_attr(
                                            (string) $value
                                        ); ?>"
                                        required
                                        style="
                                            display:block;
                                            width:100%;
                                            margin-top:4px;
                                        "
                                    >
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </details>

            <div style="margin-top:20px">
                <?php submit_button(
                    'Save ' .
                    $selected_label .
                    ' scanner settings',
                    'secondary',
                    'submit',
                    false
                ); ?>
            </div>
        </form>
    </section>
    <?php
}

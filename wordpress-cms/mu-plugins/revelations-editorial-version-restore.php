<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Version Restore
 * Description: Safely restores private AI versions into unpublished Editorial Desk drafts.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Recover the article title stored in a private version.
 */
function revelations_editorial_ai_restore_original_title(
    WP_Post $version
): string {
    $stored_title = trim(
        (string) get_post_meta(
            $version->ID,
            '_rev_ai_original_title',
            true
        )
    );

    if ( '' !== $stored_title ) {
        return $stored_title;
    }

    $derived_title = preg_replace(
        '/^AI version\s+\d+\s+—\s+/u',
        '',
        $version->post_title,
        1
    );

    if (
        ! is_string( $derived_title ) ||
        '' === trim( $derived_title ) ||
        $derived_title === $version->post_title
    ) {
        return '';
    }

    return trim( $derived_title );
}

/**
 * Read and validate categories stored in a private version.
 *
 * @return int[]|WP_Error
 */
function revelations_editorial_ai_restore_categories(
    int $version_id
) {
    $raw_categories = (string) get_post_meta(
        $version_id,
        '_rev_ai_categories',
        true
    );

    $categories = json_decode(
        $raw_categories,
        true
    );

    if ( ! is_array( $categories ) ) {
        return new WP_Error(
            'restore_invalid_categories',
            'The saved version has invalid category metadata.'
        );
    }

    $categories = array_values(
        array_unique(
            array_filter(
                array_map(
                    'absint',
                    $categories
                )
            )
        )
    );

    if ( array() === $categories ) {
        return new WP_Error(
            'restore_missing_categories',
            'The saved version has no category information.'
        );
    }

    foreach ( $categories as $category_id ) {
        if (
            ! term_exists(
                $category_id,
                'category'
            )
        ) {
            return new WP_Error(
                'restore_unknown_category',
                'One of the saved categories no longer exists.'
            );
        }
    }

    sort( $categories );

    return $categories;
}

/**
 * Verify that a private version can be restored safely.
 *
 * @return array<string, mixed>|WP_Error
 */
function revelations_editorial_ai_restore_readiness(
    int $draft_id,
    int $version_id
) {
    $draft = get_post(
        $draft_id
    );

    if (
        ! $draft instanceof WP_Post ||
        'post' !== $draft->post_type
    ) {
        return new WP_Error(
            'restore_invalid_draft',
            'The WordPress draft could not be found.'
        );
    }

    if (
        ! in_array(
            $draft->post_status,
            array(
                'draft',
                'pending',
                'private',
            ),
            true
        )
    ) {
        return new WP_Error(
            'restore_published_draft',
            'Previous versions may only be restored into an unpublished article.'
        );
    }

    $version = get_post(
        $version_id
    );

    if (
        ! $version instanceof WP_Post ||
        'rev_ai_version' !== $version->post_type ||
        'private' !== $version->post_status ||
        $draft_id !== (int) $version->post_parent
    ) {
        return new WP_Error(
            'restore_invalid_version',
            'The selected private version does not belong to this draft.'
        );
    }

    if (
        ! function_exists(
            'revelations_editorial_ai_create_version_backup'
        )
    ) {
        return new WP_Error(
            'restore_backup_unavailable',
            'The current draft cannot be backed up safely.'
        );
    }

    if (
        ! function_exists(
            'revelations_editorial_review_status'
        )
    ) {
        return new WP_Error(
            'restore_review_unavailable',
            'Editorial review verification is unavailable.'
        );
    }

    $original_title =
        revelations_editorial_ai_restore_original_title(
            $version
        );

    if ( '' === $original_title ) {
        return new WP_Error(
            'restore_missing_title',
            'The original article title is missing from the saved version.'
        );
    }

    if (
        '' === trim(
            (string) $version->post_content
        )
    ) {
        return new WP_Error(
            'restore_missing_content',
            'The saved version has no article content.'
        );
    }

    $categories =
        revelations_editorial_ai_restore_categories(
            $version_id
        );

    if ( is_wp_error( $categories ) ) {
        return $categories;
    }

    $generation_number = absint(
        get_post_meta(
            $version_id,
            '_rev_ai_generation_number',
            true
        )
    );

    if ( $generation_number < 1 ) {
        return new WP_Error(
            'restore_missing_generation',
            'The saved AI generation number is missing.'
        );
    }

    $seo_title = trim(
        (string) get_post_meta(
            $version_id,
            '_rev_ai_seo_title',
            true
        )
    );

    $seo_description = trim(
        (string) get_post_meta(
            $version_id,
            '_rev_ai_seo_description',
            true
        )
    );

    if (
        '' === $seo_title ||
        '' === $seo_description
    ) {
        return new WP_Error(
            'restore_missing_seo',
            'The saved SEO metadata is incomplete.'
        );
    }

    $current_candidate_id = absint(
        get_post_meta(
            $draft_id,
            '_revelations_editorial_candidate_id',
            true
        )
    );

    $version_candidate_id = absint(
        get_post_meta(
            $version_id,
            '_rev_ai_candidate_id',
            true
        )
    );

    if (
        $current_candidate_id > 0 &&
        $version_candidate_id > 0 &&
        $current_candidate_id !==
        $version_candidate_id
    ) {
        return new WP_Error(
            'restore_candidate_mismatch',
            'The saved version belongs to a different editorial candidate.'
        );
    }

    $optional_generation_meta = array(
        '_rev_ai_alternative_titles' =>
            '_revelations_ai_alternative_titles',

        '_rev_ai_source_section' =>
            '_revelations_ai_source_section',

        '_rev_ai_section_mismatch' =>
            '_revelations_ai_section_mismatch',

        '_rev_ai_suggested_section' =>
            '_revelations_ai_suggested_section',

        '_rev_ai_section_mismatch_reason' =>
            '_revelations_ai_section_mismatch_reason',

        '_rev_ai_fact_check_flags' =>
            '_revelations_ai_fact_check_flags',

        '_rev_ai_direct_quotes' =>
            '_revelations_ai_direct_quotes',

        '_rev_ai_word_count_status' =>
            '_revelations_ai_word_count_status',

        '_rev_ai_word_count_warning' =>
            '_revelations_ai_word_count_warning',
    );

    $current_generation_meta = array();
    $version_generation_meta = array();

    foreach (
        $optional_generation_meta
        as $version_key => $draft_key
    ) {
        $current_generation_meta[ $draft_key ] =
            metadata_exists(
                'post',
                $draft_id,
                $draft_key
            )
                ? get_post_meta(
                    $draft_id,
                    $draft_key,
                    true
                )
                : null;

        $version_generation_meta[ $draft_key ] =
            metadata_exists(
                'post',
                $version_id,
                $version_key
            )
                ? get_post_meta(
                    $version_id,
                    $version_key,
                    true
                )
                : null;
    }

    $current_categories = array_map(
        'absint',
        wp_get_post_categories(
            $draft_id
        )
    );

    sort( $current_categories );

    $enrichment_version_meta = array(
        'revelations_revelation' => '_rev_ai_revelation',
        'revelations_source_note' => '_rev_ai_source_note',
        'revelations_editorial_note' => '_rev_ai_editorial_note',
        'revelations_disclosure' => '_rev_ai_disclosure',
        'revelations_public_sources' => '_rev_ai_public_sources',
    );
    $current_enrichment = array();
    $version_enrichment = array();
    foreach ( $enrichment_version_meta as $draft_key => $version_key ) {
        $current_enrichment[ $draft_key ] = get_post_meta( $draft_id, $draft_key, true );
        $version_enrichment[ $draft_key ] = get_post_meta( $version_id, $version_key, true );
    }

    if ( function_exists( 'revelations_enrichment_revelation_is_valid' ) && ! revelations_enrichment_revelation_is_valid( $version_enrichment['revelations_revelation'] ) ) {
        return new WP_Error( 'restore_invalid_revelation', 'The saved version contains an over-limit THE REVELATION value.' );
    }
    if ( function_exists( 'revelations_enrichment_sanitize_public_sources' ) && metadata_exists( 'post', $version_id, '_rev_ai_public_sources' ) ) {
        $saved_sources = (string) $version_enrichment['revelations_public_sources'];
        if ( revelations_enrichment_sanitize_public_sources( $saved_sources ) !== $saved_sources ) {
            return new WP_Error( 'restore_invalid_public_sources', 'The saved version has invalid public source metadata.' );
        }
    }

    $current_signature = hash(
        'sha256',
        (string) wp_json_encode(
            array(
                'title' =>
                    $draft->post_title,

                'content' =>
                    $draft->post_content,

                'excerpt' =>
                    $draft->post_excerpt,

                'categories' =>
                    $current_categories,

                'seo_title' =>
                    get_post_meta(
                        $draft_id,
                        'revelations_seo_title',
                        true
                    ),

                'seo_description' =>
                    get_post_meta(
                        $draft_id,
                        'revelations_seo_description',
                        true
                    ),
                'enrichment' => $current_enrichment,

                'generation_metadata' =>
                    $current_generation_meta,
            ),
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
    );

    $version_signature = hash(
        'sha256',
        (string) wp_json_encode(
            array(
                'title' =>
                    $original_title,

                'content' =>
                    $version->post_content,

                'excerpt' =>
                    $version->post_excerpt,

                'categories' =>
                    $categories,

                'seo_title' =>
                    $seo_title,

                'seo_description' =>
                    $seo_description,
                'enrichment' => $version_enrichment,

                'generation_metadata' =>
                    $version_generation_meta,
            ),
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
    );

    if (
        hash_equals(
            $current_signature,
            $version_signature
        )
    ) {
        return new WP_Error(
            'restore_already_current',
            'The current draft already matches this saved version.'
        );
    }

    return array(
        'draft' =>
            $draft,

        'version' =>
            $version,

        'original_title' =>
            $original_title,

        'categories' =>
            $categories,

        'generation_number' =>
            $generation_number,

        'seo_title' =>
            $seo_title,

        'seo_description' =>
            $seo_description,

        'section' =>
            (string) get_post_meta(
                $version_id,
                '_rev_ai_section',
                true
            ),

        'word_count' =>
            absint(
                get_post_meta(
                    $version_id,
                    '_rev_ai_word_count',
                    true
                )
            ),

        'current_signature' =>
            $current_signature,

        'version_signature' =>
            $version_signature,
    );
}

/**
 * Restore one private version after backing up the current draft.
 *
 * @return array<string, mixed>|WP_Error
 */
function revelations_editorial_ai_restore_version(
    int $draft_id,
    int $version_id
) {
    $readiness =
        revelations_editorial_ai_restore_readiness(
            $draft_id,
            $version_id
        );

    if ( is_wp_error( $readiness ) ) {
        return $readiness;
    }

    $version = $readiness['version'];

    $backup_id =
        revelations_editorial_ai_create_version_backup(
            $draft_id
        );

    if ( is_wp_error( $backup_id ) ) {
        return $backup_id;
    }

    $update_result = wp_update_post(
        array(
            'ID' =>
                $draft_id,

            'post_status' =>
                'draft',

            'post_title' =>
                wp_slash(
                    (string) $readiness[
                        'original_title'
                    ]
                ),

            'post_content' =>
                wp_slash(
                    (string) $version->post_content
                ),

            'post_excerpt' =>
                wp_slash(
                    (string) $version->post_excerpt
                ),
        ),
        true
    );

    if ( is_wp_error( $update_result ) ) {
        return $update_result;
    }

    $category_result = wp_set_post_categories(
        $draft_id,
        $readiness['categories'],
        false
    );

    if ( is_wp_error( $category_result ) ) {
        return $category_result;
    }

    $meta_map = array(
        'revelations_seo_title' =>
            $readiness['seo_title'],

        'revelations_seo_description' =>
            $readiness['seo_description'],

        '_revelations_draft_kind' =>
            get_post_meta(
                $version_id,
                '_rev_ai_draft_kind',
                true
            ),

        '_revelations_ai_generated_at' =>
            get_post_meta(
                $version_id,
                '_rev_ai_generated_at',
                true
            ),

        '_revelations_ai_model' =>
            get_post_meta(
                $version_id,
                '_rev_ai_model',
                true
            ),

        '_revelations_ai_section_suggestion' =>
            $readiness['section'],

        '_revelations_ai_word_count' =>
            $readiness['word_count'],

        '_revelations_ai_input_tokens' =>
            get_post_meta(
                $version_id,
                '_rev_ai_input_tokens',
                true
            ),

        '_revelations_ai_output_tokens' =>
            get_post_meta(
                $version_id,
                '_rev_ai_output_tokens',
                true
            ),

        '_revelations_ai_generation_number' =>
            $readiness['generation_number'],

        '_revelations_ai_restored_from_version_id' =>
            $version_id,

        '_revelations_ai_restored_at' =>
            gmdate( 'c' ),

        '_revelations_ai_restored_by' =>
            get_current_user_id(),
    );

    foreach ( $meta_map as $key => $value ) {
        update_post_meta(
            $draft_id,
            $key,
            $value
        );
    }

    foreach ( array( 'revelations_revelation' => '_rev_ai_revelation', 'revelations_source_note' => '_rev_ai_source_note', 'revelations_editorial_note' => '_rev_ai_editorial_note', 'revelations_disclosure' => '_rev_ai_disclosure', 'revelations_public_sources' => '_rev_ai_public_sources' ) as $draft_key => $version_key ) {
        if ( metadata_exists( 'post', $version_id, $version_key ) ) {
            update_post_meta( $draft_id, $draft_key, get_post_meta( $version_id, $version_key, true ) );
        } else {
            delete_post_meta( $draft_id, $draft_key );
        }
    }
    if ( metadata_exists( 'post', $version_id, '_rev_ai_author_profile_ids' ) ) update_post_meta( $draft_id, '_revelations_author_profile_ids', function_exists('revelations_author_relation_json') ? revelations_author_relation_json(get_post_meta($version_id,'_rev_ai_author_profile_ids',true)) : '[]' );

    /*
     * Stage 5 generation metadata is optional so private versions
     * created before these fields existed remain restorable.
     */
    $optional_generation_meta = array(
        '_rev_ai_alternative_titles' =>
            '_revelations_ai_alternative_titles',

        '_rev_ai_source_section' =>
            '_revelations_ai_source_section',

        '_rev_ai_section_mismatch' =>
            '_revelations_ai_section_mismatch',

        '_rev_ai_suggested_section' =>
            '_revelations_ai_suggested_section',

        '_rev_ai_section_mismatch_reason' =>
            '_revelations_ai_section_mismatch_reason',

        '_rev_ai_fact_check_flags' =>
            '_revelations_ai_fact_check_flags',

        '_rev_ai_direct_quotes' =>
            '_revelations_ai_direct_quotes',

        '_rev_ai_word_count_status' =>
            '_revelations_ai_word_count_status',

        '_rev_ai_word_count_warning' =>
            '_revelations_ai_word_count_warning',
    );

    foreach (
        $optional_generation_meta
        as $version_key => $draft_key
    ) {
        if (
            metadata_exists(
                'post',
                $version_id,
                $version_key
            )
        ) {
            update_post_meta(
                $draft_id,
                $draft_key,
                get_post_meta(
                    $version_id,
                    $version_key,
                    true
                )
            );

            continue;
        }

        delete_post_meta(
            $draft_id,
            $draft_key
        );
    }

    delete_post_meta(
        $draft_id,
        '_revelations_ai_error'
    );

    clean_post_cache(
        $draft_id
    );

    $restored_post = get_post(
        $draft_id
    );

    if (
        ! $restored_post instanceof WP_Post ||
        'draft' !== $restored_post->post_status ||
        $restored_post->post_content !==
            $version->post_content
    ) {
        return new WP_Error(
            'restore_verification_failed',
            'The restored draft could not be verified. The previous current version was preserved privately.'
        );
    }

    $review =
        revelations_editorial_review_status(
            $draft_id
        );

    return array(
        'draft_id' =>
            $draft_id,

        'restored_version_id' =>
            $version_id,

        'backup_version_id' =>
            absint( $backup_id ),

        'draft_status' =>
            $restored_post->post_status,

        'review_status' =>
            $review['status'],

        'generation_number' =>
            get_post_meta(
                $draft_id,
                '_revelations_ai_generation_number',
                true
            ),
    );
}

/**
 * Return to the AI Drafts page.
 */
function revelations_editorial_ai_restore_redirect(): void {
    wp_safe_redirect(
        add_query_arg(
            array(
                'page' =>
                    'revelations-editorial-desk',

                'view' =>
                    'drafts',
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Handle the Restore this version button.
 */
add_action(
    'admin_post_revelations_restore_ai_version',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to restore AI versions.',
                    'revelations'
                )
            );
        }

        $draft_id = absint(
            $_POST['draft_id'] ?? 0
        );

        $version_id = absint(
            $_POST['version_id'] ?? 0
        );

        check_admin_referer(
            'revelations_restore_ai_version_' .
            $draft_id .
            '_' .
            $version_id
        );

        $result =
            revelations_editorial_ai_restore_version(
                $draft_id,
                $version_id
            );

        $transient_key =
            'revelations_ai_restore_notice_' .
            get_current_user_id();

        if ( is_wp_error( $result ) ) {
            set_transient(
                $transient_key,
                array(
                    'type' =>
                        'error',

                    'message' =>
                        $result->get_error_message(),
                ),
                120
            );

            revelations_editorial_ai_restore_redirect();
        }

        set_transient(
            $transient_key,
            array(
                'type' =>
                    'success',

                'message' =>
                    sprintf(
                        'Version %d was restored. The previous current draft was saved privately as version %d. Editorial review is now outdated.',
                        absint(
                            $result[
                                'restored_version_id'
                            ]
                        ),
                        absint(
                            $result[
                                'backup_version_id'
                            ]
                        )
                    ),
            ),
            120
        );

        revelations_editorial_ai_restore_redirect();
    }
);

/**
 * Show restore result notices.
 */
add_action(
    'admin_notices',
    static function (): void {
        if (
            'revelations-editorial-desk' !==
            sanitize_key(
                (string) (
                    $_GET['page'] ?? ''
                )
            )
        ) {
            return;
        }

        $transient_key =
            'revelations_ai_restore_notice_' .
            get_current_user_id();

        $notice = get_transient(
            $transient_key
        );

        if (
            ! is_array( $notice ) ||
            empty( $notice['message'] )
        ) {
            return;
        }

        delete_transient(
            $transient_key
        );

        $notice_class =
            'success' === (
                $notice['type'] ?? ''
            )
                ? 'notice-success'
                : 'notice-error';
        ?>
        <div class="notice <?php echo esc_attr(
            $notice_class
        ); ?> is-dismissible">
            <p>
                <?php echo esc_html(
                    (string) $notice['message']
                ); ?>
            </p>
        </div>
        <?php
    }
);

/**
 * Render a restore button under one saved version.
 */
function revelations_editorial_render_ai_version_restore_action(
    int $draft_id,
    WP_Post $version
): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $readiness =
        revelations_editorial_ai_restore_readiness(
            $draft_id,
            $version->ID
        );
    ?>
    <div class="revelations-ai-version-restore">
        <?php if ( is_wp_error( $readiness ) ) : ?>
            <span class="revelations-ai-version-restore__unavailable">
                <?php echo esc_html(
                    $readiness->get_error_message()
                ); ?>
            </span>
        <?php else : ?>
            <p>
                The current draft will first be saved as a new
                private version. Editorial review will become outdated.
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
                    value="revelations_restore_ai_version"
                >

                <input
                    type="hidden"
                    name="draft_id"
                    value="<?php echo esc_attr(
                        (string) $draft_id
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="version_id"
                    value="<?php echo esc_attr(
                        (string) $version->ID
                    ); ?>"
                >

                <?php wp_nonce_field(
                    'revelations_restore_ai_version_' .
                    $draft_id .
                    '_' .
                    $version->ID
                ); ?>

                <button
                    type="submit"
                    class="button button-secondary"
                    onclick="return confirm('Restore this private AI version? The current draft will be backed up first and will remain unpublished.');"
                >
                    Restore this version
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Restore-action styling.
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
            .revelations-ai-version-restore {
                margin-top:12px;
                padding-top:12px;
                border-top:1px solid #dcdcde;
            }

            .revelations-ai-version-restore p {
                margin:0 0 8px;
                color:#646970;
                font-size:11px;
                line-height:1.4;
            }

            .revelations-ai-version-restore__unavailable {
                color:#646970;
                font-size:11px;
            }
            '
        );
    }
);

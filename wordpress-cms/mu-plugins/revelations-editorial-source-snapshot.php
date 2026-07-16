<?php
/**
 * Plugin Name: REVELATIONS Editorial Source Snapshot
 * Description: Saves private source-text snapshots for Editorial Desk drafts.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Redirect back to AI Drafts.
 *
 * @param array<string, scalar> $args Query arguments.
 */
function revelations_editorial_source_snapshot_redirect(
    array $args = array()
): void {
    wp_safe_redirect(
        add_query_arg(
            array_merge(
                array(
                    'page' => 'revelations-editorial-desk',
                    'view' => 'drafts',
                ),
                $args
            ),
            admin_url( 'admin.php' )
        )
    );

    exit;
}

/**
 * Return source snapshot information.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_source_snapshot_status(
    int $candidate_id
): array {
    $text = (string) get_post_meta(
        $candidate_id,
        '_rev_source_text',
        true
    );

    return array(
        'ready' =>
            '' !== trim( $text ),

        'characters' =>
            absint(
                get_post_meta(
                    $candidate_id,
                    '_rev_source_text_characters',
                    true
                )
            ),

        'paragraphs' =>
            absint(
                get_post_meta(
                    $candidate_id,
                    '_rev_source_text_paragraphs',
                    true
                )
            ),

        'method' =>
            sanitize_key(
                (string) get_post_meta(
                    $candidate_id,
                    '_rev_source_text_method',
                    true
                )
            ),

        'fetched_at' =>
            sanitize_text_field(
                (string) get_post_meta(
                    $candidate_id,
                    '_rev_source_text_fetched_at',
                    true
                )
            ),

        'hash' =>
            sanitize_text_field(
                (string) get_post_meta(
                    $candidate_id,
                    '_rev_source_text_hash',
                    true
                )
            ),

        'error' =>
            sanitize_text_field(
                (string) get_post_meta(
                    $candidate_id,
                    '_rev_source_text_error',
                    true
                )
            ),
    );
}

/**
 * Fetch and save one candidate's source text.
 */
add_action(
    'admin_post_revelations_fetch_source_snapshot',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__(
                    'You do not have permission to fetch source text.',
                    'revelations'
                )
            );
        }

        $candidate_id = absint(
            $_POST['candidate_id'] ?? 0
        );

        $draft_id = absint(
            $_POST['draft_id'] ?? 0
        );

        check_admin_referer(
            'revelations_fetch_source_snapshot_' .
            $candidate_id
        );

        if (
            $candidate_id < 1 ||
            'rev_candidate' !== get_post_type(
                $candidate_id
            )
        ) {
            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'candidate',
                )
            );
        }

        if (
            $draft_id < 1 ||
            'post' !== get_post_type( $draft_id )
        ) {
            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'draft',
                )
            );
        }

        $linked_candidate_id = absint(
            get_post_meta(
                $draft_id,
                '_revelations_editorial_candidate_id',
                true
            )
        );

        if ( $linked_candidate_id !== $candidate_id ) {
            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'link',
                )
            );
        }

        if (
            ! function_exists(
                'revelations_editorial_fetch_source_text'
            )
        ) {
            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'reader',
                )
            );
        }

        $source_url = esc_url_raw(
            (string) get_post_meta(
                $candidate_id,
                '_rev_source_url',
                true
            ),
            array( 'https' )
        );

        if ( '' === $source_url ) {
            update_post_meta(
                $candidate_id,
                '_rev_source_text_error',
                'Candidate source URL is missing.'
            );

            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'url',
                )
            );
        }

        $result =
            revelations_editorial_fetch_source_text(
                $source_url
            );

        if (
            empty( $result['ok'] ) ||
            empty( $result['text'] )
        ) {
            $error = sanitize_text_field(
                (string) (
                    $result['error']
                    ?? 'Source text could not be extracted.'
                )
            );

            update_post_meta(
                $candidate_id,
                '_rev_source_text_error',
                $error
            );

            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'fetch',
                    'candidate_id' => $candidate_id,
                )
            );
        }

        $source_text = trim(
            mb_substr(
                (string) $result['text'],
                0,
                20000,
                'UTF-8'
            )
        );

        if (
            mb_strlen(
                $source_text,
                'UTF-8'
            ) < 500
        ) {
            update_post_meta(
                $candidate_id,
                '_rev_source_text_error',
                'Extracted source text is too short.'
            );

            revelations_editorial_source_snapshot_redirect(
                array(
                    'source_error' => 'short',
                    'candidate_id' => $candidate_id,
                )
            );
        }

        $snapshot_meta = array(
            '_rev_source_text' =>
                wp_slash( $source_text ),

            '_rev_source_text_method' =>
                sanitize_key(
                    (string) (
                        $result['method'] ?? 'unknown'
                    )
                ),

            '_rev_source_text_fetched_at' =>
                gmdate( 'c' ),

            '_rev_source_text_characters' =>
                mb_strlen(
                    $source_text,
                    'UTF-8'
                ),

            '_rev_source_text_paragraphs' =>
                absint(
                    $result['paragraphs'] ?? 0
                ),

            '_rev_source_text_hash' =>
                hash(
                    'sha256',
                    $source_text
                ),

            '_rev_source_text_http_status' =>
                absint(
                    $result['http_status'] ?? 0
                ),

            '_rev_source_text_duration_ms' =>
                absint(
                    $result['duration_ms'] ?? 0
                ),

            '_rev_source_text_error' =>
                '',
        );

        foreach (
            $snapshot_meta as $key => $value
        ) {
            update_post_meta(
                $candidate_id,
                $key,
                $value
            );
        }

        revelations_editorial_source_snapshot_redirect(
            array(
                'source_saved' => $candidate_id,
            )
        );
    }
);

/**
 * Render source-text controls for one draft.
 */
function revelations_editorial_render_source_snapshot_action(
    int $candidate_id,
    int $draft_id
): void {
    $status =
        revelations_editorial_source_snapshot_status(
            $candidate_id
        );
    ?>
    <div class="revelations-source-snapshot">
        <?php if ( ! empty( $status['ready'] ) ) : ?>
            <div>
                <span class="revelations-source-ready">
                    Source ready
                </span>
            </div>

            <div class="revelations-source-snapshot__details">
                <?php echo esc_html(
                    number_format(
                        absint(
                            $status['characters']
                        )
                    ) .
                    ' characters · ' .
                    absint(
                        $status['paragraphs']
                    ) .
                    ' paragraphs'
                ); ?>
            </div>
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
                value="revelations_fetch_source_snapshot"
            >

            <input
                type="hidden"
                name="candidate_id"
                value="<?php echo esc_attr(
                    (string) $candidate_id
                ); ?>"
            >

            <input
                type="hidden"
                name="draft_id"
                value="<?php echo esc_attr(
                    (string) $draft_id
                ); ?>"
            >

            <?php wp_nonce_field(
                'revelations_fetch_source_snapshot_' .
                $candidate_id
            ); ?>

            <button
                type="submit"
                class="button button-secondary button-small"
            >
                <?php echo esc_html(
                    ! empty( $status['ready'] )
                        ? 'Refresh source text'
                        : 'Fetch source text'
                ); ?>
            </button>
        </form>
    </div>
    <?php
}

/**
 * Source snapshot notices.
 */
function revelations_editorial_render_source_snapshot_notices(): void {
    if ( isset( $_GET['source_saved'] ) ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Source text was extracted and saved privately.
                No AI request was made.
            </p>
        </div>
        <?php
    }

    $source_error = sanitize_key(
        wp_unslash(
            $_GET['source_error'] ?? ''
        )
    );

    if ( '' === $source_error ) {
        return;
    }

    $candidate_id = absint(
        $_GET['candidate_id'] ?? 0
    );

    $message = '';

    if ( $candidate_id > 0 ) {
        $message = sanitize_text_field(
            (string) get_post_meta(
                $candidate_id,
                '_rev_source_text_error',
                true
            )
        );
    }

    if ( '' === $message ) {
        $message =
            'Source text could not be saved.';
    }
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo esc_html( $message ); ?></p>
    </div>
    <?php
}

/**
 * Snapshot UI styling.
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
            .revelations-source-snapshot {
                margin-top:8px;
            }

            .revelations-source-snapshot form {
                margin-top:6px;
            }

            .revelations-source-ready {
                display:inline-flex;
                padding:4px 8px;
                border-radius:999px;
                background:#e8f5e9;
                color:#1b5e20;
                font-size:11px;
                font-weight:600;
            }

            .revelations-source-snapshot__details {
                margin-top:4px;
                color:#646970;
                font-size:11px;
                line-height:1.35;
            }
            '
        );
    }
);

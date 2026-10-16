<?php
/**
 * Plugin Name: REVELATIONS Editorial Art Preview
 * Description: Admin-only preview for Art RSS candidates.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle an explicitly requested Art preview. The shared runner performs no
 * candidate creation; saving remains an independent human action.
 */
add_action(
    'admin_post_revelations_preview_art_scan',
    static function (): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to run editorial scans.', 'revelations' ) );
        }

        check_admin_referer( 'revelations_preview_art_scan' );

        $result = function_exists( 'revelations_editorial_generate_preview' )
            ? revelations_editorial_generate_preview( 'art' )
            : new WP_Error( 'scanner_unavailable', 'Editorial preview runner is unavailable.' );

        $args = array(
            'page' => 'revelations-editorial-desk',
            'view' => 'candidates',
            'scan_section' => 'art',
        );
        if ( is_wp_error( $result ) ) {
            $args['scan_error'] = 'scanner_disabled' === $result->get_error_code() ? 'disabled' : 'scanner';
        } else {
            $args['scan_ready'] = 1;
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }
);

/** Render the Art preview, Art-specific gate diagnostics and explicit saves. */
function revelations_editorial_render_art_preview(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $runtime = function_exists( 'revelations_editorial_preview_runtime_config' )
        ? revelations_editorial_preview_runtime_config( 'art' )
        : array( 'enabled' => false, 'preview_limit' => 5 );
    $scanner_enabled = ! empty( $runtime['enabled'] );
    $preview_limit = max( 1, min( 20, absint( $runtime['preview_limit'] ?? 5 ) ) );
    $preview_key = function_exists( 'revelations_editorial_preview_key' )
        ? revelations_editorial_preview_key( 'art' )
        : '';
    $preview = '' !== $preview_key ? get_transient( $preview_key ) : false;
    $scan_section = sanitize_key( wp_unslash( $_GET['scan_section'] ?? '' ) );
    $candidate_section = sanitize_key( wp_unslash( $_GET['candidate_section'] ?? '' ) );
    $qualified = is_array( $preview ) && is_array( $preview['qualified_candidates'] ?? null )
        ? $preview['qualified_candidates']
        : array();
    $source_results = is_array( $preview ) && is_array( $preview['source_results'] ?? null )
        ? $preview['source_results']
        : array();
    $rejections = is_array( $preview ) && is_array( $preview['rejection_counts']['art_relevance_gate'] ?? null )
        ? $preview['rejection_counts']['art_relevance_gate']
        : array();
    $closest_rejected = is_array( $preview ) && is_array( $preview['closest_rejected'] ?? null )
        ? $preview['closest_rejected']
        : array();
    ?>
    <section class="revelations-desk__section revelations-tech-preview" style="margin-top:20px">
        <div class="revelations-tech-preview__header">
            <div>
                <h2>Art RSS scan</h2>
                <p class="revelations-desk__section-description">
                    Preview developments in visual and contemporary art, artists, exhibitions, institutions and artistic practice. Art is not required to be AI or future-tech. Results are editorial leads, not automatic drafts.
                    <?php if ( $scanner_enabled ) : ?> Current preview limit: <strong><?php echo esc_html( (string) $preview_limit ); ?></strong>.<?php else : ?> <strong>Scanner is disabled by default in Settings.</strong><?php endif; ?>
                </p>
            </div>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="revelations_preview_art_scan">
                <?php wp_nonce_field( 'revelations_preview_art_scan' ); ?>
                <?php submit_button( 'Preview Art scan', 'primary', 'submit', false, $scanner_enabled ? '' : 'disabled="disabled" aria-disabled="true"' ); ?>
            </form>
        </div>

        <?php if ( 'art' === $scan_section && isset( $_GET['scan_ready'] ) && is_array( $preview ) ) : ?>
            <div class="notice notice-success inline"><p>Art preview completed.</p></div>
        <?php elseif ( 'art' === $scan_section && 'disabled' === ( $_GET['scan_error'] ?? '' ) ) : ?>
            <div class="notice notice-warning inline"><p>Art scanner is disabled in Settings.</p></div>
        <?php elseif ( 'art' === $scan_section && isset( $_GET['scan_error'] ) ) : ?>
            <div class="notice notice-error inline"><p>Art preview could not be completed. Review the source diagnostics below.</p></div>
        <?php endif; ?>
        <?php if ( 'art' === $candidate_section && isset( $_GET['candidate_saved'] ) ) : ?>
            <div class="notice notice-success inline"><p>Art candidate saved for editorial review.</p></div>
        <?php elseif ( 'art' === $candidate_section && isset( $_GET['candidate_duplicate'] ) ) : ?>
            <div class="notice notice-warning inline"><p>This story already exists as a candidate.</p></div>
        <?php elseif ( 'art' === $candidate_section && isset( $_GET['candidate_error'] ) ) : ?>
            <div class="notice notice-error inline"><p>The Art candidate could not be saved.</p></div>
        <?php endif; ?>

        <?php if ( ! is_array( $preview ) ) : ?>
            <p>No Art preview has been generated in this session.</p>
        <?php else : ?>
            <div class="revelations-tech-preview__summary">
                <span><strong><?php echo esc_html( (string) absint( $preview['total_feed_items'] ?? 0 ) ); ?></strong> feed items</span>
                <span><strong><?php echo esc_html( (string) absint( $preview['qualified_stories'] ?? 0 ) ); ?></strong> qualified — showing <strong><?php echo esc_html( (string) count( $qualified ) ); ?></strong></span>
                <span><strong><?php echo esc_html( (string) count( $preview['active_sources'] ?? array() ) ); ?></strong> active sources</span>
                <span><strong>0</strong> records created automatically</span>
            </div>
            <p class="description" style="margin-top:12px">Art relevance gate filtered: <strong><?php echo esc_html( (string) absint( $preview['relevance_filtered'] ?? 0 ) ); ?></strong>. This section does not use the global AI gate.</p>

            <?php if ( array() !== $source_results ) : ?>
                <details style="margin:12px 0"><summary>Source diagnostics</summary><ul>
                    <?php foreach ( $source_results as $source_result ) : ?>
                        <li><?php echo esc_html( (string) ( $source_result['source'] ?? 'Unknown source' ) ); ?>: <?php echo esc_html( ! empty( $source_result['success'] ) ? 'available' : 'skipped safely — ' . (string) ( $source_result['error'] ?? 'feed error' ) ); ?></li>
                    <?php endforeach; ?>
                </ul></details>
            <?php endif; ?>
            <details style="margin:12px 0"><summary>Art gate diagnostics</summary>
                <?php if ( array() === $rejections ) : ?><p>No Art-gate rejections were recorded.</p><?php else : ?><ul><?php foreach ( $rejections as $code => $count ) : ?><li><?php echo esc_html( str_replace( '_', ' ', (string) $code ) ); ?>: <?php echo esc_html( (string) absint( $count ) ); ?></li><?php endforeach; ?></ul><?php endif; ?>
                <?php if ( array() !== $closest_rejected ) : ?><p>Closest rejected: <?php echo esc_html( (string) ( $closest_rejected['title'] ?? 'Untitled item' ) ); ?> — <?php echo esc_html( (string) ( $closest_rejected['reason'] ?? 'No reason recorded.' ) ); ?></p><?php endif; ?>
            </details>

            <?php if ( array() === $qualified ) : ?>
                <p>No stories passed the current Art editorial rules.</p>
            <?php else : ?>
                <table class="widefat striped"><thead><tr><th>Candidate</th><th>Source</th><th>Scores</th><th>Age</th><th>Action</th></tr></thead><tbody>
                <?php foreach ( $qualified as $candidate_index => $item ) : ?>
                    <?php $scores = is_array( $item['scores'] ?? null ) ? $item['scores'] : array(); $duplicate_key = sanitize_text_field( (string) ( $item['duplicate_key'] ?? '' ) ); $stored_candidate_id = function_exists( 'revelations_editorial_preview_candidate_exists' ) ? revelations_editorial_preview_candidate_exists( $duplicate_key ) : 0; ?>
                    <tr><td><strong><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></strong><div class="revelations-candidate-meta"><?php echo esc_html( (string) ( $item['reason'] ?? '' ) ); ?></div><div class="revelations-candidate-meta">Signals: <?php echo esc_html( implode( ', ', array_map( 'strval', is_array( $item['section_signals'] ?? null ) ? $item['section_signals'] : array() ) ) ); ?></div></td>
                    <td><a href="<?php echo esc_url( (string) ( $item['url'] ?? '' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) ( $item['source'] ?? '' ) ); ?></a></td>
                    <td><strong><?php echo esc_html( (string) ( $scores['total'] ?? 0 ) ); ?></strong><div class="revelations-candidate-meta">R <?php echo esc_html( (string) ( $scores['relevance'] ?? 0 ) ); ?> · I <?php echo esc_html( (string) ( $scores['implementation'] ?? 0 ) ); ?> · F <?php echo esc_html( (string) ( $scores['freshness'] ?? 0 ) ); ?></div></td>
                    <td><?php $age_hours = $item['age_hours'] ?? null; echo esc_html( null === $age_hours ? '-' : number_format( (float) $age_hours, 1 ) . ' h' ); ?></td><td>
                    <?php if ( $stored_candidate_id > 0 ) : ?><span class="revelations-preview-saved">Saved</span><?php else : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="revelations_save_preview_candidate"><input type="hidden" name="preview_section" value="art"><input type="hidden" name="candidate_index" value="<?php echo esc_attr( (string) $candidate_index ); ?>"><?php wp_nonce_field( 'revelations_save_preview_candidate_' . 'art_' . $candidate_index ); ?><?php submit_button( 'Save candidate', 'secondary small', 'submit', false ); ?></form><?php endif; ?>
                    </td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
            <p class="description" style="margin-top:12px">Preview expires automatically after 15 minutes. Candidates are saved only when you click “Save candidate”.</p>
        <?php endif; ?>
    </section>
    <?php
}

<?php
/**
 * Plugin Name: REVELATIONS Editorial Taxonomy
 * Description: Topics, series, locations and validated editorial relationship metadata.
 */

defined( 'ABSPATH' ) || exit;

const REVELATIONS_EDITORIAL_TAXONOMY_STATUS = array( 'proposed', 'approved', 'needs-editorial-review' );

function revelations_editorial_taxonomy_term( $term ): ?array {
    if ( ! $term || is_wp_error( $term ) ) {
        return null;
    }

    return array(
        'id'   => (int) $term->term_id,
        'slug' => $term->slug,
        'name' => $term->name,
    );
}

function revelations_editorial_taxonomy_terms( int $post_id, string $taxonomy ): array {
    $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'all' ) );
    if ( is_wp_error( $terms ) ) {
        return array();
    }

    return array_values( array_filter( array_map( 'revelations_editorial_taxonomy_term', $terms ) ) );
}

function revelations_editorial_taxonomy_primary_topic( int $post_id, array $topics = null ): ?array {
    $topics = null === $topics ? revelations_editorial_taxonomy_terms( $post_id, 'revelations_topic' ) : $topics;
    $primary_id = (int) get_post_meta( $post_id, '_revelations_primary_topic', true );
    foreach ( $topics as $topic ) {
        if ( $primary_id === (int) $topic['id'] ) {
            return $topic;
        }
    }

    return null;
}

function revelations_editorial_taxonomy_manual_related( int $post_id ): array {
    $raw = get_post_meta( $post_id, '_revelations_manual_related', true );
    if ( ! is_array( $raw ) ) {
        return array();
    }

    $ids = array();
    foreach ( $raw as $candidate_id ) {
        $candidate_id = absint( $candidate_id );
        if ( ! $candidate_id || $candidate_id === $post_id || in_array( $candidate_id, $ids, true ) ) {
            continue;
        }
        $candidate = get_post( $candidate_id );
        if ( ! $candidate || 'post' !== $candidate->post_type || 'publish' !== $candidate->post_status ) {
            continue;
        }
        $ids[] = $candidate_id;
        if ( 3 === count( $ids ) ) {
            break;
        }
    }

    return array_map(
        static function ( int $candidate_id ): array {
            $candidate = get_post( $candidate_id );
            return array(
                'id'    => $candidate_id,
                'slug'  => $candidate->post_name,
                'title' => get_the_title( $candidate ),
            );
        },
        $ids
    );
}

function revelations_editorial_taxonomy_public_data( int $post_id ): array {
    $topics = revelations_editorial_taxonomy_terms( $post_id, 'revelations_topic' );
    $series = revelations_editorial_taxonomy_terms( $post_id, 'revelations_series' );
    $status = get_post_meta( $post_id, '_revelations_taxonomy_status', true );
    if ( ! in_array( $status, REVELATIONS_EDITORIAL_TAXONOMY_STATUS, true ) ) {
        $status = 'proposed';
    }

    return array(
        'primary_topic'         => revelations_editorial_taxonomy_primary_topic( $post_id, $topics ),
        'topics'                => $topics,
        'series'                => $series[0] ?? null,
        'locations'             => revelations_editorial_taxonomy_terms( $post_id, 'revelations_location' ),
        'public_topic_eligible' => '0' !== (string) get_post_meta( $post_id, '_revelations_public_topic_eligible', true ),
        'taxonomy_status'       => $status,
        'manual_related'        => revelations_editorial_taxonomy_manual_related( $post_id ),
    );
}

function revelations_editorial_taxonomy_register(): void {
    register_taxonomy( 'revelations_topic', array( 'post' ), array(
        'label'        => 'Editorial Topics',
        'public'       => true,
        'show_ui'      => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite'      => false,
    ) );
    register_taxonomy( 'revelations_series', array( 'post' ), array(
        'label'        => 'Editorial Series',
        'public'       => true,
        'show_ui'      => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite'      => false,
        'meta_box_cb'  => 'revelations_editorial_taxonomy_series_metabox',
    ) );
    register_taxonomy( 'revelations_location', array( 'post' ), array(
        'label'        => 'Editorial Locations',
        'public'       => true,
        'show_ui'      => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite'      => false,
    ) );

    foreach ( array( '_revelations_primary_topic', '_revelations_manual_related', '_revelations_public_topic_eligible', '_revelations_taxonomy_status' ) as $key ) {
        register_post_meta( 'post', $key, array( 'single' => true, 'show_in_rest' => false, 'auth_callback' => static function (): bool { return current_user_can( 'edit_posts' ); } ) );
    }
}
add_action( 'init', 'revelations_editorial_taxonomy_register', 20 );

function revelations_editorial_taxonomy_series_metabox( WP_Post $post, array $box ): void {
    $taxonomy = $box['args']['taxonomy'];
    $selected = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
    $selected = absint( $selected[0] ?? 0 );
    $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
    wp_nonce_field( 'revelations_editorial_taxonomy_series', 'revelations_editorial_taxonomy_series_nonce' );
    echo '<p><label><input type="radio" name="tax_input[' . esc_attr( $taxonomy ) . '][]" value="0" ' . checked( 0, $selected, false ) . ' /> None</label></p>';
    foreach ( $terms as $term ) {
        echo '<p><label><input type="radio" name="tax_input[' . esc_attr( $taxonomy ) . '][]" value="' . esc_attr( $term->term_id ) . '" ' . checked( $term->term_id, $selected, false ) . ' /> ' . esc_html( $term->name ) . '</label></p>';
    }
}

function revelations_editorial_taxonomy_metabox(): void {
    add_meta_box( 'revelations-editorial-taxonomy', 'Editorial taxonomy', 'revelations_editorial_taxonomy_metabox_render', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes_post', 'revelations_editorial_taxonomy_metabox' );

function revelations_editorial_taxonomy_metabox_render( WP_Post $post ): void {
    wp_nonce_field( 'revelations_editorial_taxonomy_save', 'revelations_editorial_taxonomy_nonce' );
    $topics = revelations_editorial_taxonomy_terms( $post->ID, 'revelations_topic' );
    $primary = revelations_editorial_taxonomy_primary_topic( $post->ID, $topics );
    $related = get_post_meta( $post->ID, '_revelations_manual_related', true );
    $related = is_array( $related ) ? implode( ',', array_map( 'absint', $related ) ) : '';
    ?>
    <p><label for="revelations_primary_topic">Primary topic</label><select id="revelations_primary_topic" name="revelations_primary_topic"><option value="">None</option><?php foreach ( $topics as $topic ) : ?><option value="<?php echo esc_attr( $topic['id'] ); ?>" <?php selected( $primary['id'] ?? 0, $topic['id'] ); ?>><?php echo esc_html( $topic['name'] ); ?></option><?php endforeach; ?></select></p>
    <p><label for="revelations_manual_related">Manual related post IDs (max 3)</label><input id="revelations_manual_related" name="revelations_manual_related" value="<?php echo esc_attr( $related ); ?>" /></p>
    <p><label><input type="checkbox" name="revelations_public_topic_eligible" value="1" <?php checked( '0' !== (string) get_post_meta( $post->ID, '_revelations_public_topic_eligible', true ) ); ?> /> Public topic eligible</label></p>
    <p><label for="revelations_taxonomy_status">Taxonomy status</label><select id="revelations_taxonomy_status" name="revelations_taxonomy_status"><?php foreach ( REVELATIONS_EDITORIAL_TAXONOMY_STATUS as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( get_post_meta( $post->ID, '_revelations_taxonomy_status', true ), $status ); ?>><?php echo esc_html( $status ); ?></option><?php endforeach; ?></select></p>
    <?php
}

function revelations_editorial_taxonomy_save( int $post_id, WP_Post $post ): void {
    if ( 'post' !== $post->post_type || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! isset( $_POST['revelations_editorial_taxonomy_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['revelations_editorial_taxonomy_nonce'] ) ), 'revelations_editorial_taxonomy_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $topic_ids = wp_get_post_terms( $post_id, 'revelations_topic', array( 'fields' => 'ids' ) );
    $primary_id = isset( $_POST['revelations_primary_topic'] ) ? absint( $_POST['revelations_primary_topic'] ) : 0;
    update_post_meta( $post_id, '_revelations_primary_topic', in_array( $primary_id, $topic_ids, true ) ? $primary_id : 0 );
    $raw_ids = isset( $_POST['revelations_manual_related'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['revelations_manual_related'] ) ) ) : array();
    $ids = array(); foreach ( $raw_ids as $id ) { $id = absint( $id ); if ( $id && $id !== $post_id && ! in_array( $id, $ids, true ) ) { $ids[] = $id; } if ( 3 === count( $ids ) ) { break; } }
    update_post_meta( $post_id, '_revelations_manual_related', $ids );
    update_post_meta( $post_id, '_revelations_public_topic_eligible', isset( $_POST['revelations_public_topic_eligible'] ) ? '1' : '0' );
    $status = isset( $_POST['revelations_taxonomy_status'] ) ? sanitize_key( wp_unslash( $_POST['revelations_taxonomy_status'] ) ) : 'proposed';
    update_post_meta( $post_id, '_revelations_taxonomy_status', in_array( $status, REVELATIONS_EDITORIAL_TAXONOMY_STATUS, true ) ? $status : 'proposed' );
}
add_action( 'save_post', 'revelations_editorial_taxonomy_save', 10, 2 );

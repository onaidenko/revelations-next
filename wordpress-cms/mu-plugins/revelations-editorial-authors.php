<?php
/** Plugin Name: REVELATIONS Editorial Authors */
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;

const REVELATIONS_AUTHOR_RELATION = '_revelations_author_profile_ids';

function revelations_author_schema_type( mixed $value ): string {
    return in_array( $value, array( 'person', 'organization' ), true ) ? (string) $value : 'person';
}
function revelations_author_urls( mixed $value ): string {
    $items = is_string( $value ) ? json_decode( $value, true ) : $value;
    if ( ! is_array( $items ) ) return '[]'; $safe = array();
    foreach ( $items as $url ) { $url = esc_url_raw( (string) $url, array( 'http', 'https' ) ); if ( preg_match( '#^https?://#i', $url ) ) $safe[] = $url; }
    return (string) wp_json_encode( array_values( array_unique( $safe ) ) );
}
function revelations_author_relation( mixed $value ): array {
    $items = is_string( $value ) ? json_decode( $value, true ) : $value;
    if ( ! is_array( $items ) ) return array(); $result = array();
    foreach ( $items as $id ) { $id = absint( $id ); if ( $id && ! in_array( $id, $result, true ) && 'rev_author' === get_post_type( $id ) ) $result[] = $id; }
    return $result;
}
function revelations_author_relation_json( mixed $value ): string { return (string) wp_json_encode( revelations_author_relation( $value ) ); }
function revelations_author_is_public_ready( WP_Post $author, int $published_count = 0 ): bool {
    return 'publish' === $author->post_status && '1' === get_post_meta( $author->ID, 'revelations_author_active', true ) && '' !== trim( $author->post_title ) && '' !== trim( $author->post_excerpt ) && $published_count > 0;
}
function revelations_author_public_data( WP_Post $author, int $published_count = 0 ): ?array {
    if ( ! revelations_author_is_public_ready( $author, $published_count ) ) return null;
    $image = get_post_thumbnail_id( $author->ID ); $image_data = null;
    if ( $image && ( $src = wp_get_attachment_image_src( $image, 'medium' ) ) ) $image_data = array( 'url' => $src[0], 'alt' => get_post_meta( $image, '_wp_attachment_image_alt', true ) ?: $author->post_title );
    $same_as = json_decode( (string) get_post_meta( $author->ID, 'revelations_author_same_as', true ), true );
    return array( 'id' => $author->ID, 'slug' => $author->post_name, 'name' => $author->post_title, 'schema_type' => revelations_author_schema_type( get_post_meta( $author->ID, 'revelations_author_schema_type', true ) ), 'is_public_profile' => true, 'bio' => $author->post_excerpt, 'role' => get_post_meta( $author->ID, 'revelations_author_role', true ) ?: null, 'image' => $image_data, 'url' => 'https://revelations.me/authors/' . rawurlencode( $author->post_name ), 'same_as' => is_array( $same_as ) ? $same_as : array() );
}
/** Public-safe canonical identity for an article relation. It intentionally does
 * not require a public profile page, bio, or indexability. */
function revelations_author_article_data( WP_Post $author, int $published_count = 0 ): ?array {
    if ( 'publish' !== $author->post_status || '' === trim( $author->post_title ) ) return null;
    $public = revelations_author_is_public_ready( $author, $published_count );
    $data = array( 'id'=>$author->ID, 'slug'=>$author->post_name, 'name'=>$author->post_title, 'schema_type'=>revelations_author_schema_type(get_post_meta($author->ID,'revelations_author_schema_type',true)), 'is_public_profile'=>$public, 'url'=>null, 'bio'=>null, 'role'=>null, 'image'=>null, 'same_as'=>array() );
    if ( $public ) return revelations_author_public_data( $author, $published_count );
    return $data;
}
add_action( 'init', static function (): void {
    register_post_type( 'rev_author', array( 'labels' => array( 'name' => 'Editorial Authors', 'singular_name' => 'Editorial Author' ), 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => true, 'supports' => array( 'title', 'excerpt', 'thumbnail', 'custom-fields' ), 'rewrite' => false, 'has_archive' => false ) );
    foreach ( array( 'revelations_author_schema_type' => array( 'sanitize_callback' => 'revelations_author_schema_type', 'default' => 'person' ), 'revelations_author_role' => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ), 'revelations_author_same_as' => array( 'sanitize_callback' => 'revelations_author_urls', 'default' => '[]' ), 'revelations_author_active' => array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ) ) as $key => $field ) register_post_meta( 'rev_author', $key, array( 'single' => true, 'type' => 'string', 'default' => $field['default'], 'sanitize_callback' => $field['sanitize_callback'], 'show_in_rest' => true ) );
    register_post_meta( 'post', REVELATIONS_AUTHOR_RELATION, array( 'single' => true, 'type' => 'string', 'default' => '[]', 'sanitize_callback' => 'revelations_author_relation_json', 'show_in_rest' => true ) );
} );

function revelations_author_migration_map(): array { return array( 'Julia U.' => 'julia-yupiterskaya', 'Julia Yupiterskaya' => 'julia-yupiterskaya', 'Alina B.' => 'alina-b', 'Alina K.' => 'alina-b', '' => 'alina-b', 'Anonymous' => 'editorial-team', 'Editorial Team' => 'editorial-team' ); }
function revelations_author_provision_plan(): array { return array( array( 'slug'=>'julia-yupiterskaya','name'=>'Julia Yupiterskaya','schema_type'=>'person','same_as'=>array('https://www.linkedin.com/in/julia-upiter/') ), array( 'slug'=>'alina-b','name'=>'Alina B.','schema_type'=>'person','same_as'=>array() ), array( 'slug'=>'editorial-team','name'=>'Editorial Team','schema_type'=>'organization','same_as'=>array() ) ); }
function revelations_author_migration_report( array $articles, array $slug_to_id = array() ): array { $report=array( 'total'=>0,'mapped'=>array(),'unexpected'=>array(),'changes'=>array() ); foreach($articles as $article){$report['total']++;$legacy=(string)($article['displayed_author']??'');$slug=revelations_author_migration_map()[$legacy]??null;if(!$slug){$report['unexpected'][]=$legacy;continue;}$report['mapped'][$slug]=($report['mapped'][$slug]??0)+1;if(isset($slug_to_id[$slug]))$report['changes'][]=array('id'=>$article['id']??0,'author_ids'=>array($slug_to_id[$slug]));} return $report; }

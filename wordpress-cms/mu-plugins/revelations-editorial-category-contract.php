<?php
/**
 * Plugin Name: REVELATIONS Editorial Category Contract
 * Description: Shared single-category rules for REVELATIONS Articles.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @return int[] */
function revelations_editorial_category_contract_normalize_ids( array $ids ): array {
    $ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
    sort( $ids, SORT_NUMERIC );
    return $ids;
}

/** @return string[] */
function revelations_editorial_category_contract_allowed_slugs(): array {
    return function_exists( 'revelations_editorial_sections' )
        ? revelations_editorial_sections()
        : array();
}

/** @return array<int, array{id:int,name:string,slug:string}> */
function revelations_editorial_category_contract_allowed_terms(): array {
    $slugs = revelations_editorial_category_contract_allowed_slugs();
    if ( array() === $slugs ) return array();
    $terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'slug' => $slugs ) );
    if ( is_wp_error( $terms ) || ! is_array( $terms ) ) return array();
    $by_slug = array();
    foreach ( $terms as $term ) $by_slug[ $term->slug ] = array( 'id' => (int) $term->term_id, 'name' => (string) $term->name, 'slug' => (string) $term->slug );
    $result = array(); foreach ( $slugs as $slug ) if ( isset( $by_slug[ $slug ] ) ) $result[] = $by_slug[ $slug ];
    return $result;
}

/** @return array{code:string,ids:int[]} */
function revelations_editorial_category_contract_validate( array $ids ): array {
    $ids = revelations_editorial_category_contract_normalize_ids( $ids );
    if ( 0 === count( $ids ) ) return array( 'code' => 'category_missing', 'ids' => $ids );
    if ( 1 !== count( $ids ) ) return array( 'code' => 'category_multiple', 'ids' => $ids );
    $term = get_term( $ids[0], 'category' );
    if ( ! $term instanceof WP_Term ) return array( 'code' => 'category_not_allowed', 'ids' => $ids );
    if ( 'uncategorized' === $term->slug ) return array( 'code' => 'category_uncategorized', 'ids' => $ids );
    if ( ! in_array( $term->slug, revelations_editorial_category_contract_allowed_slugs(), true ) ) return array( 'code' => 'category_not_allowed', 'ids' => $ids );
    return array( 'code' => '', 'ids' => $ids );
}

function revelations_editorial_category_contract_message( string $code ): string {
    return array(
        'category_missing' => 'Select exactly one editorial category before publishing.',
        'category_multiple' => 'This article has multiple categories. Select one editorial category before publishing.',
        'category_uncategorized' => 'Uncategorized cannot be used as an editorial category.',
        'category_not_allowed' => 'Select one allowed editorial category before publishing.',
    )[ $code ] ?? 'Select exactly one editorial category before publishing.';
}

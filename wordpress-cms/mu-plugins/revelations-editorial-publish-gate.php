<?php
/**
 * Plugin Name: REVELATIONS Editorial Publish Gate
 * Description: Prevents publication of Editorial Desk drafts without a current human review.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determine whether the publication gate applies to a post.
 */
function revelations_editorial_publish_gate_applies(
    int $post_id
): bool {
    if (
        $post_id < 1 ||
        'post' !== get_post_type( $post_id )
    ) {
        return false;
    }

    $candidate_id = absint(
        get_post_meta(
            $post_id,
            '_revelations_editorial_candidate_id',
            true
        )
    );

    return (
        $candidate_id > 0 &&
        'rev_candidate' === get_post_type(
            $candidate_id
        )
    );
}

/**
 * Normalize text before comparing saved and proposed content.
 */
function revelations_editorial_publish_gate_normalize_text(
    string $text
): string {
    return str_replace(
        array(
            "\r\n",
            "\r",
        ),
        "\n",
        $text
    );
}

/**
 * Extract a text value from a REST request field.
 *
 * @param mixed $value REST request value.
 */
function revelations_editorial_publish_gate_rest_text(
    $value
): string {
    if ( is_array( $value ) ) {
        if ( array_key_exists( 'raw', $value ) ) {
            return (string) $value['raw'];
        }

        if ( array_key_exists( 'rendered', $value ) ) {
            return (string) $value['rendered'];
        }

        return '';
    }

    return (string) $value;
}

/**
 * WordPress performs an internal wp_insert_post() after REST validation.
 * Keep the REST gate authoritative and do not run the classic fallback twice.
 */
function revelations_editorial_publish_gate_is_rest_request(): bool {
    if ( function_exists( 'wp_is_serving_rest_request' ) ) {
        return wp_is_serving_rest_request();
    }

    return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/** Normalize classic/WP-CLI request text before saved-state comparison. */
function revelations_editorial_publish_gate_unslashed_text( mixed $value ): string {
    return revelations_editorial_publish_gate_normalize_text(
        (string) wp_unslash( $value )
    );
}

/**
 * Check whether proposed editorial fields differ from the reviewed draft.
 *
 * Only fields present in $proposal are checked.
 *
 * @param array<string, mixed> $proposal Proposed post values.
 */
function revelations_editorial_publish_gate_proposal_changed(
    int $post_id,
    array $proposal
): bool {
    return array() !== revelations_editorial_publish_gate_changed_fields( $post_id, $proposal );
}

/** @return string[] */
function revelations_editorial_publish_gate_changed_fields( int $post_id, array $proposal ): array {
    $post = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return array( 'content' );
    }

    $text_fields = array(
        'title'   => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
    );

    foreach ( $text_fields as $key => $saved_value ) {
        if ( ! array_key_exists( $key, $proposal ) ) {
            continue;
        }

        $proposed_value =
            revelations_editorial_publish_gate_normalize_text(
                (string) $proposal[ $key ]
            );

        $saved_value =
            revelations_editorial_publish_gate_normalize_text(
                (string) $saved_value
            );

        if ( $proposed_value !== $saved_value ) {
            $changed[] = $key;
        }
    }

    if ( array_key_exists( 'categories', $proposal ) ) {
        $saved_categories = array_map(
            'absint',
            wp_get_post_categories(
                $post_id
            )
        );

        $proposed_categories = array_map(
            'absint',
            (array) $proposal['categories']
        );

        sort( $saved_categories );
        sort( $proposed_categories );

        if ( $saved_categories !== $proposed_categories ) $changed[] = 'category';
    }

    if ( array_key_exists( 'tags', $proposal ) ) {
        $saved_tags = array_map( 'absint', wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) ) );
        $proposed_tags = array_map( 'absint', (array) $proposal['tags'] ); sort( $saved_tags ); sort( $proposed_tags );
        if ( $saved_tags !== $proposed_tags ) $changed[] = 'tags';
    }
    $meta_fields = array(
        'seo_title' =>
            'revelations_seo_title',

        'seo_description' =>
            'revelations_seo_description',

        'displayed_author' =>
            'revelations_author',
    );

    foreach ( $meta_fields as $proposal_key => $meta_key ) {
        if (
            ! array_key_exists(
                $proposal_key,
                $proposal
            )
        ) {
            continue;
        }

        $saved_value = (string) get_post_meta(
            $post_id,
            $meta_key,
            true
        );

        if (
            (string) $proposal[ $proposal_key ] !==
            $saved_value
        ) {
            $changed[] = $proposal_key;
        }
    }

    return array_values( array_unique( $changed ?? array() ) );
}

function revelations_editorial_publish_gate_changed_field_label( string $key ): string {
    return array( 'title' => 'Title', 'content' => 'Article content', 'excerpt' => 'Excerpt', 'category' => 'Category', 'tags' => 'Tags', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description', 'displayed_author' => 'Displayed author', 'ai_review_metadata' => 'AI editorial review metadata' )[ $key ] ?? 'Article content';
}

/**
 * Return missing publication requirements.
 *
 * Values supplied in the publication request take precedence
 * over the currently stored draft values.
 *
 * @param array<string, mixed> $proposal Proposed post values.
 * @return string[]
 */
function revelations_editorial_publish_gate_missing_requirements(
    int $post_id,
    array $proposal = array()
): array {
    $missing = array();

    $featured_image_id = array_key_exists(
        'featured_image_id',
        $proposal
    )
        ? absint(
            $proposal['featured_image_id']
        )
        : get_post_thumbnail_id(
            $post_id
        );

    if (
        $featured_image_id < 1 ||
        ! wp_attachment_is_image(
            $featured_image_id
        )
    ) {
        $missing[] = 'featured image';
    }

    $displayed_author = array_key_exists(
        'displayed_author',
        $proposal
    )
        ? sanitize_text_field(
            (string) $proposal['displayed_author']
        )
        : sanitize_text_field(
            (string) get_post_meta(
                $post_id,
                'revelations_author',
                true
            )
        );

    if ( '' === trim( $displayed_author ) ) {
        $missing[] = 'Displayed author';
    }

    $excerpt = array_key_exists(
        'excerpt',
        $proposal
    )
        ? (string) $proposal['excerpt']
        : (string) get_post_field(
            'post_excerpt',
            $post_id
        );

    if (
        '' === trim(
            wp_strip_all_tags(
                $excerpt
            )
        )
    ) {
        $missing[] = 'excerpt';
    }

    $categories = array_key_exists(
        'categories',
        $proposal
    )
        ? (array) $proposal['categories']
        : wp_get_post_categories(
            $post_id
        );

    $categories = array_values(
        array_filter(
            array_map(
                'absint',
                $categories
            )
        )
    );

    $category_check = function_exists( 'revelations_editorial_category_contract_validate' )
        ? revelations_editorial_category_contract_validate( $categories ) : array( 'code' => array() === $categories ? 'category_missing' : '' );
    if ( '' !== (string) $category_check['code'] ) $missing[] = revelations_editorial_category_contract_message( (string) $category_check['code'] );

    $seo_title = array_key_exists(
        'seo_title',
        $proposal
    )
        ? sanitize_text_field(
            (string) $proposal['seo_title']
        )
        : sanitize_text_field(
            (string) get_post_meta(
                $post_id,
                'revelations_seo_title',
                true
            )
        );

    if ( '' === trim( $seo_title ) ) {
        $missing[] = 'SEO title';
    }

    $seo_description = array_key_exists(
        'seo_description',
        $proposal
    )
        ? sanitize_textarea_field(
            (string) $proposal['seo_description']
        )
        : sanitize_textarea_field(
            (string) get_post_meta(
                $post_id,
                'revelations_seo_description',
                true
            )
        );

    if ( '' === trim( $seo_description ) ) {
        $missing[] = 'SEO description';
    }

    return $missing;
}

/**
 * Return the reason publication must be blocked.
 *
 * An empty string means publication may proceed.
 *
 * @param array<string, mixed> $proposal Proposed post values.
 */
function revelations_editorial_publish_gate_reason(
    int $post_id,
    array $proposal = array()
): string {
    if (
        ! revelations_editorial_publish_gate_applies(
            $post_id
        )
    ) {
        return '';
    }

    if (
        ! function_exists(
            'revelations_editorial_review_status'
        )
    ) {
        return
            'Editorial review verification is unavailable. ' .
            'The article was not published.';
    }

    $category_ids = array_key_exists( 'categories', $proposal ) ? (array) $proposal['categories'] : wp_get_post_categories( $post_id );
    $category_check = function_exists( 'revelations_editorial_category_contract_validate' )
        ? revelations_editorial_category_contract_validate( $category_ids ) : array( 'code' => '' );
    if ( '' !== (string) $category_check['code'] ) return revelations_editorial_category_contract_message( (string) $category_check['code'] );
    $review =
        revelations_editorial_review_status(
            $post_id
        );

    if (
        empty( $review['is_current'] ) ||
        'reviewed' !== (string) $review['status']
    ) {
        return
            'This article does not have a current editorial review. ' .
            'Save it as a draft, mark it as reviewed, and then publish it.';
    }

    $missing_requirements =
        revelations_editorial_publish_gate_missing_requirements(
            $post_id,
            $proposal
        );

    if ( array() !== $missing_requirements ) {
        return
            'Complete the following publication fields: ' .
            implode(
                ', ',
                $missing_requirements
            ) .
            '. The article was not published.';
    }

    $changed_fields = revelations_editorial_publish_gate_changed_fields( $post_id, $proposal );
    if ( array() !== $changed_fields ) {
        if ( empty( $review['field_hashes'] ) ) return 'The article changed after its editorial review. The older review record does not identify the exact field. Save the draft and review the current version again.';
        $labels = array_map( 'revelations_editorial_publish_gate_changed_field_label', $changed_fields );
        return
            'The following fields changed after editorial review: ' . implode( ', ', $labels ) . '. Save the draft and mark the current version as reviewed.';
    }

    return '';
}

/**
 * Block invalid publication requests from the block editor REST API.
 *
 * @param stdClass|WP_Error $prepared_post Prepared post object.
 * @return WP_Post|WP_Error
 */
add_filter( 'rest_pre_insert_post', static function ( $prepared_post, WP_REST_Request $request ) {
    if ( is_wp_error( $prepared_post ) || ! $request->has_param( 'categories' ) ) return $prepared_post;
    $post_id = absint( $request->get_param( 'id' ) );
    if ( ! revelations_editorial_publish_gate_applies( $post_id ) ) return $prepared_post;
    $check = revelations_editorial_category_contract_validate( (array) $request->get_param( 'categories' ) );
    if ( 'category_multiple' !== $check['code'] && 'category_not_allowed' !== $check['code'] && 'category_uncategorized' !== $check['code'] ) return $prepared_post;
    return new WP_Error( 'revelations_editorial_category_invalid', revelations_editorial_category_contract_message( $check['code'] ), array( 'status' => 400 ) );
}, 98, 2 );

add_filter(
    'rest_pre_insert_post',
    static function (
        $prepared_post,
        WP_REST_Request $request
    ) {
        if (
            is_wp_error( $prepared_post ) ||
            ! is_object( $prepared_post ) ||
            ! isset( $prepared_post->post_status ) ||
            ! in_array(
                $prepared_post->post_status,
                array(
                    'publish',
                    'future',
                ),
                true
            )
        ) {
            return $prepared_post;
        }

        $post_id = absint(
            $request->get_param( 'id' )
        );

        if (
            ! revelations_editorial_publish_gate_applies(
                $post_id
            )
        ) {
            return $prepared_post;
        }

        $proposal = array();

        foreach (
            array(
                'title',
                'content',
                'excerpt',
            ) as $field
        ) {
            if ( ! $request->has_param( $field ) ) {
                continue;
            }

            $proposal[ $field ] =
                revelations_editorial_publish_gate_rest_text(
                    $request->get_param(
                        $field
                    )
                );
        }

        if ( $request->has_param( 'categories' ) ) {
            $proposal['categories'] = (array)
                $request->get_param(
                    'categories'
                );
        }

        if ( $request->has_param( 'tags' ) ) $proposal['tags'] = (array) $request->get_param( 'tags' );

        if (
            $request->has_param(
                'featured_media'
            )
        ) {
            $proposal['featured_image_id'] =
                absint(
                    $request->get_param(
                        'featured_media'
                    )
                );
        }

        if ( $request->has_param( 'meta' ) ) {
            $meta = $request->get_param(
                'meta'
            );

            if ( is_array( $meta ) ) {
                if (
                    array_key_exists(
                        'revelations_author',
                        $meta
                    )
                ) {
                    $proposal['displayed_author'] =
                        (string) $meta[
                            'revelations_author'
                        ];
                }

                if (
                    array_key_exists(
                        'revelations_seo_title',
                        $meta
                    )
                ) {
                    $proposal['seo_title'] =
                        (string) $meta[
                            'revelations_seo_title'
                        ];
                }

                if (
                    array_key_exists(
                        'revelations_seo_description',
                        $meta
                    )
                ) {
                    $proposal['seo_description'] =
                        (string) $meta[
                            'revelations_seo_description'
                        ];
                }
            }
        }

        $reason =
            revelations_editorial_publish_gate_reason(
                $post_id,
                $proposal
            );

        if ( '' === $reason ) {
            return $prepared_post;
        }

        return new WP_Error(
            'revelations_editorial_review_required',
            $reason,
            array(
                'status' => 409,
            )
        );
    },
    99,
    2
);

/**
 * Fallback protection for classic editor, WP-CLI and non-REST updates.
 *
 * @param array<string, mixed> $data Sanitized post data.
 * @param array<string, mixed> $postarr Post data.
 * @return array<string, mixed>
 */
add_filter(
    'wp_insert_post_data',
    static function (
        array $data,
        array $postarr
    ): array {
        if ( revelations_editorial_publish_gate_is_rest_request() ) {
            return $data;
        }

        $requested_status = (string) (
            $data['post_status'] ?? ''
        );

        if (
            ! in_array(
                $requested_status,
                array(
                    'publish',
                    'future',
                ),
                true
            )
        ) {
            return $data;
        }

        $post_id = absint(
            $postarr['ID'] ?? 0
        );

        if (
            ! revelations_editorial_publish_gate_applies(
                $post_id
            )
        ) {
            return $data;
        }

        $proposal = array(
            'title' =>
                revelations_editorial_publish_gate_unslashed_text(
                    $data['post_title'] ?? ''
                ),

            'content' =>
                revelations_editorial_publish_gate_unslashed_text(
                    $data['post_content'] ?? ''
                ),

            'excerpt' =>
                revelations_editorial_publish_gate_unslashed_text(
                    $data['post_excerpt'] ?? ''
                ),
        );

        if (
            array_key_exists(
                'post_category',
                $postarr
            )
        ) {
            $proposal['categories'] =
                (array) $postarr[
                    'post_category'
                ];
        }

        if ( array_key_exists( 'tags_input', $postarr ) ) $proposal['tags'] = (array) $postarr['tags_input'];

        if (
            array_key_exists(
                '_thumbnail_id',
                $postarr
            )
        ) {
            $proposal['featured_image_id'] =
                absint(
                    $postarr['_thumbnail_id']
                );
        }

        $meta_input = $postarr['meta_input'] ?? null;

        if ( is_array( $meta_input ) ) {
            if (
                array_key_exists(
                    '_thumbnail_id',
                    $meta_input
                )
            ) {
                $proposal['featured_image_id'] =
                    absint(
                        $meta_input[
                            '_thumbnail_id'
                        ]
                    );
            }

            if (
                array_key_exists(
                    'revelations_author',
                    $meta_input
                )
            ) {
                $proposal['displayed_author'] =
                    (string) $meta_input[
                        'revelations_author'
                    ];
            }

            if (
                array_key_exists(
                    'revelations_seo_title',
                    $meta_input
                )
            ) {
                $proposal['seo_title'] =
                    (string) $meta_input[
                        'revelations_seo_title'
                    ];
            }

            if (
                array_key_exists(
                    'revelations_seo_description',
                    $meta_input
                )
            ) {
                $proposal['seo_description'] =
                    (string) $meta_input[
                        'revelations_seo_description'
                    ];
            }
        }

        $reason =
            revelations_editorial_publish_gate_reason(
                $post_id,
                $proposal
            );

        if ( '' === $reason ) {
            return $data;
        }

        $current_status = get_post_status(
            $post_id
        );

        $data['post_status'] = in_array(
            $current_status,
            array(
                'draft',
                'pending',
                'private',
            ),
            true
        )
            ? $current_status
            : 'draft';

        $user_id = get_current_user_id();

        if ( $user_id > 0 ) {
            set_transient(
                'revelations_publish_blocked_' .
                $user_id,
                $reason,
                90
            );
        }

        return $data;
    },
    99,
    2
);

/**
 * Show a clear warning after a non-REST publication attempt.
 */
add_action(
    'admin_notices',
    static function (): void {
        $user_id = get_current_user_id();

        if ( $user_id < 1 ) {
            return;
        }

        $transient_key =
            'revelations_publish_blocked_' .
            $user_id;

        $message = get_transient(
            $transient_key
        );

        if (
            ! is_string( $message ) ||
            '' === $message
        ) {
            return;
        }

        delete_transient(
            $transient_key
        );
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong>Publication blocked.</strong>
                <?php echo esc_html( $message ); ?>
            </p>
        </div>
        <?php
    }
);

add_action( 'wp_after_insert_post', static function ( int $post_id, WP_Post $post ): void {
    if ( 'publish' === $post->post_status && revelations_editorial_publish_gate_applies( $post_id ) ) delete_post_meta( $post_id, '_revelations_editorial_review_changed_fields' );
}, 100, 2 );

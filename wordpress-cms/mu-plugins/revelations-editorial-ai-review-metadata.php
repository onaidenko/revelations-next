<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Review Metadata
 * Description: Displays current AI editorial review metadata safely.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Safely decode one JSON list.
 *
 * @return array<int, mixed>
 */
function revelations_editorial_ai_review_decode_json_list(
    mixed $value
): array {
    if (
        ! is_string( $value ) ||
        '' === trim( $value )
    ) {
        return array();
    }

    $decoded = json_decode(
        $value,
        true
    );

    if (
        JSON_ERROR_NONE !== json_last_error() ||
        ! is_array( $decoded ) ||
        ! array_is_list( $decoded )
    ) {
        return array();
    }

    return $decoded;
}

/**
 * Canonicalize nested data for deterministic hashing.
 */
function revelations_editorial_ai_review_canonicalize(
    mixed $value
): mixed {
    if ( ! is_array( $value ) ) {
        return $value;
    }

    if ( array_is_list( $value ) ) {
        return array_map(
            'revelations_editorial_ai_review_canonicalize',
            $value
        );
    }

    ksort( $value, SORT_STRING );

    foreach ( $value as $key => $item ) {
        $value[ $key ] =
            revelations_editorial_ai_review_canonicalize(
                $item
            );
    }

    return $value;
}

/**
 * Sort an unordered metadata list deterministically.
 *
 * @param array<int, array<string, mixed>> $items Items.
 * @return array<int, array<string, mixed>>
 */
function revelations_editorial_ai_review_sort_items(
    array $items
): array {
    usort(
        $items,
        static function (
            array $left,
            array $right
        ): int {
            $left_json = json_encode(
                revelations_editorial_ai_review_canonicalize(
                    $left
                ),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            $right_json = json_encode(
                revelations_editorial_ai_review_canonicalize(
                    $right
                ),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            return strcmp(
                is_string( $left_json )
                    ? $left_json
                    : '',
                is_string( $right_json )
                    ? $right_json
                    : ''
            );
        }
    );

    return $items;
}

/**
 * Normalize current draft AI metadata from raw meta values.
 *
 * @param array<string, mixed> $raw Raw metadata.
 * @return array<string, mixed>
 */
function revelations_editorial_ai_review_normalize_metadata(
    array $raw
): array {
    $alternative_titles = array();

    foreach (
        revelations_editorial_ai_review_decode_json_list(
            $raw[
                '_revelations_ai_alternative_titles'
            ] ?? ''
        ) as $title
    ) {
        if ( is_string( $title ) ) {
            $alternative_titles[] = $title;
        }
    }

    $fact_check_flags = array();

    foreach (
        revelations_editorial_ai_review_decode_json_list(
            $raw[
                '_revelations_ai_fact_check_flags'
            ] ?? ''
        ) as $flag
    ) {
        if ( ! is_array( $flag ) ) {
            continue;
        }

        $fact_check_flags[] = array(
            'claim' =>
                (string) (
                    $flag['claim'] ?? ''
                ),
            'claim_type' =>
                (string) (
                    $flag['claim_type'] ?? ''
                ),
            'source_evidence' =>
                (string) (
                    $flag['source_evidence'] ?? ''
                ),
            'verification_required' =>
                true === (
                    $flag['verification_required']
                    ?? false
                ),
            'reason' =>
                (string) (
                    $flag['reason'] ?? ''
                ),
        );
    }

    $direct_quotes = array();

    foreach (
        revelations_editorial_ai_review_decode_json_list(
            $raw[
                '_revelations_ai_direct_quotes'
            ] ?? ''
        ) as $quote
    ) {
        if ( ! is_array( $quote ) ) {
            continue;
        }

        $direct_quotes[] = array(
            'quote_text' =>
                (string) (
                    $quote['quote_text'] ?? ''
                ),
            'source_fragment' =>
                (string) (
                    $quote['source_fragment'] ?? ''
                ),
            'verbatim_match' =>
                true === (
                    $quote['verbatim_match']
                    ?? false
                ),
        );
    }

    return array(
        'source_section' =>
            (string) (
                $raw[
                    '_revelations_ai_source_section'
                ] ?? ''
            ),

        'alternative_titles' =>
            $alternative_titles,

        'section_mismatch' =>
            in_array(
                $raw[
                    '_revelations_ai_section_mismatch'
                ] ?? false,
                array(
                    true,
                    1,
                    '1',
                    'true',
                ),
                true
            ),

        'suggested_section' =>
            '' === (string) (
                $raw[
                    '_revelations_ai_suggested_section'
                ] ?? ''
            )
                ? null
                : (string) $raw[
                    '_revelations_ai_suggested_section'
                ],

        'section_mismatch_reason' =>
            '' === (string) (
                $raw[
                    '_revelations_ai_section_mismatch_reason'
                ] ?? ''
            )
                ? null
                : (string) $raw[
                    '_revelations_ai_section_mismatch_reason'
                ],

        'fact_check_flags' =>
            revelations_editorial_ai_review_sort_items(
                $fact_check_flags
            ),

        'direct_quotes' =>
            revelations_editorial_ai_review_sort_items(
                $direct_quotes
            ),
    );
}

/**
 * Return whether current AI review metadata is available.
 *
 * @param array<string, mixed> $metadata Normalized metadata.
 */
function revelations_editorial_ai_review_has_metadata(
    array $metadata
): bool {
    return
        '' !== (
            $metadata['source_section'] ?? ''
        ) ||
        array() !== (
            $metadata['alternative_titles']
            ?? array()
        ) ||
        ! empty(
            $metadata['section_mismatch']
        ) ||
        null !== (
            $metadata['suggested_section']
            ?? null
        ) ||
        null !== (
            $metadata['section_mismatch_reason']
            ?? null
        ) ||
        array() !== (
            $metadata['fact_check_flags']
            ?? array()
        ) ||
        array() !== (
            $metadata['direct_quotes']
            ?? array()
        );
}

/**
 * Build a deterministic hash for normalized AI review metadata.
 */
function revelations_editorial_ai_review_metadata_hash(
    array $metadata
): string {
    $canonical =
        revelations_editorial_ai_review_canonicalize(
            $metadata
        );

    $encoded = json_encode(
        $canonical,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    return hash(
        'sha256',
        is_string( $encoded )
            ? $encoded
            : ''
    );
}

/**
 * Read and normalize only current draft AI review metadata.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_ai_review_metadata_for_draft(
    int $draft_id
): array {
    $keys = array(
        '_revelations_ai_source_section',
        '_revelations_ai_alternative_titles',
        '_revelations_ai_section_mismatch',
        '_revelations_ai_suggested_section',
        '_revelations_ai_section_mismatch_reason',
        '_revelations_ai_fact_check_flags',
        '_revelations_ai_direct_quotes',
    );

    $raw = array();

    foreach ( $keys as $key ) {
        $raw[ $key ] = get_post_meta(
            $draft_id,
            $key,
            true
        );
    }

    return revelations_editorial_ai_review_normalize_metadata(
        $raw
    );
}

/**
 * Render one current draft AI review metadata panel.
 */
function revelations_editorial_render_ai_review_metadata_action(
    int $draft_id
): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $metadata =
        revelations_editorial_ai_review_metadata_for_draft(
            $draft_id
        );
    ?>
    <div class="revelations-ai-review-metadata">
        <details>
            <summary>AI editorial review metadata</summary>

            <?php if (
                ! revelations_editorial_ai_review_has_metadata(
                    $metadata
                )
            ) : ?>
                <p class="revelations-ai-review-metadata__empty">
                    No AI review metadata available.
                </p>
            <?php else : ?>
                <?php
                $alternative_titles = is_array(
                    $metadata['alternative_titles']
                    ?? null
                )
                    ? $metadata['alternative_titles']
                    : array();
                ?>

                <section class="revelations-ai-review-metadata__section">
                    <h4>Titles</h4>

                    <?php if (
                        array() === $alternative_titles
                    ) : ?>
                        <p>No alternative titles available.</p>
                    <?php else : ?>
                        <p>
                            Recommended title is the current
                            WordPress title. Alternatives:
                        </p>

                        <ol>
                            <?php foreach (
                                $alternative_titles
                                as $title
                            ) : ?>
                                <li>
                                    <?php echo esc_html(
                                        (string) $title
                                    ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </section>

                <section class="revelations-ai-review-metadata__section">
                    <h4>Section review</h4>

                    <dl>
                        <dt>Immutable source section</dt>
                        <dd>
                            <?php echo esc_html(
                                (string) (
                                    $metadata[
                                        'source_section'
                                    ] ?: 'Unavailable'
                                )
                            ); ?>
                        </dd>

                        <dt>Section mismatch</dt>
                        <dd>
                            <?php echo esc_html(
                                ! empty(
                                    $metadata[
                                        'section_mismatch'
                                    ]
                                )
                                    ? 'Yes'
                                    : 'No'
                            ); ?>
                        </dd>

                        <?php if (
                            null !== (
                                $metadata[
                                    'suggested_section'
                                ] ?? null
                            )
                        ) : ?>
                            <dt>Suggested section</dt>
                            <dd>
                                <?php echo esc_html(
                                    (string) $metadata[
                                        'suggested_section'
                                    ]
                                ); ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (
                            null !== (
                                $metadata[
                                    'section_mismatch_reason'
                                ] ?? null
                            )
                        ) : ?>
                            <dt>Mismatch reason</dt>
                            <dd>
                                <?php echo esc_html(
                                    (string) $metadata[
                                        'section_mismatch_reason'
                                    ]
                                ); ?>
                            </dd>
                        <?php endif; ?>
                    </dl>

                    <p class="revelations-ai-review-metadata__note">
                        Suggested section is editorial advice only.
                        It does not change the WordPress category.
                    </p>
                </section>

                <?php
                $flags = is_array(
                    $metadata['fact_check_flags']
                    ?? null
                )
                    ? $metadata['fact_check_flags']
                    : array();
                ?>

                <section class="revelations-ai-review-metadata__section">
                    <h4>Fact-check flags</h4>

                    <?php if ( array() === $flags ) : ?>
                        <p>No fact-check flags returned.</p>
                    <?php else : ?>
                        <div class="revelations-ai-review-metadata__items">
                            <?php foreach (
                                $flags as $flag
                            ) : ?>
                                <article class="revelations-ai-review-metadata__item">
                                    <div>
                                        <strong>Claim:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $flag['claim']
                                                ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <div>
                                        <strong>Claim type:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $flag['claim_type']
                                                ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <div>
                                        <strong>Found in source:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $flag[
                                                    'source_evidence'
                                                ] ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <div>
                                        <strong>Reason:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $flag['reason']
                                                ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <?php if (
                                        ! empty(
                                            $flag[
                                                'verification_required'
                                            ]
                                        )
                                    ) : ?>
                                        <div class="revelations-ai-review-metadata__status revelations-ai-review-metadata__status--manual">
                                            Requires manual verification
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <?php
                $quotes = is_array(
                    $metadata['direct_quotes']
                    ?? null
                )
                    ? $metadata['direct_quotes']
                    : array();
                ?>

                <section class="revelations-ai-review-metadata__section">
                    <h4>Direct quotes</h4>

                    <?php if ( array() === $quotes ) : ?>
                        <p>No direct quotes used.</p>
                    <?php else : ?>
                        <div class="revelations-ai-review-metadata__items">
                            <?php foreach (
                                $quotes as $quote
                            ) : ?>
                                <article class="revelations-ai-review-metadata__item">
                                    <div>
                                        <strong>Quote text:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $quote[
                                                    'quote_text'
                                                ] ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <div>
                                        <strong>Source fragment:</strong>
                                        <?php echo esc_html(
                                            (string) (
                                                $quote[
                                                    'source_fragment'
                                                ] ?? ''
                                            )
                                        ); ?>
                                    </div>

                                    <?php if (
                                        ! empty(
                                            $quote[
                                                'verbatim_match'
                                            ]
                                        )
                                    ) : ?>
                                        <div class="revelations-ai-review-metadata__status revelations-ai-review-metadata__status--exact">
                                            Exact source match
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </details>
    </div>
    <?php
}

/**
 * Current review panel styling.
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
            .revelations-ai-review-metadata {
                margin-top:10px;
                padding-top:10px;
                border-top:1px solid #dcdcde;
                max-width:760px;
            }

            .revelations-ai-review-metadata summary {
                cursor:pointer;
                font-weight:600;
            }

            .revelations-ai-review-metadata__section {
                margin-top:12px;
                padding:10px;
                border:1px solid #dcdcde;
                border-radius:4px;
                background:#fff;
            }

            .revelations-ai-review-metadata__section h4 {
                margin:0 0 8px;
            }

            .revelations-ai-review-metadata__section dl {
                display:grid;
                grid-template-columns:max-content 1fr;
                gap:4px 10px;
                margin:0;
            }

            .revelations-ai-review-metadata__section dt {
                font-weight:600;
            }

            .revelations-ai-review-metadata__section dd {
                margin:0;
            }

            .revelations-ai-review-metadata__items {
                display:grid;
                gap:8px;
            }

            .revelations-ai-review-metadata__item {
                padding:8px;
                border:1px solid #e2e4e7;
                border-radius:4px;
                overflow-wrap:anywhere;
            }

            .revelations-ai-review-metadata__status {
                display:inline-block;
                margin-top:6px;
                padding:2px 6px;
                border-radius:999px;
                font-size:11px;
                font-weight:600;
            }

            .revelations-ai-review-metadata__status--manual {
                background:#fff3cd;
                color:#664d03;
            }

            .revelations-ai-review-metadata__status--exact {
                background:#e8f5e9;
                color:#1b5e20;
            }

            .revelations-ai-review-metadata__note,
            .revelations-ai-review-metadata__empty {
                color:#646970;
            }
            '
        );
    }
);

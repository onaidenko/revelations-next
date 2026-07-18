<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Generation Validation
 * Description: Pure validation for generated editorial claims and metadata.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Supported fact-check claim types.
 *
 * @return string[]
 */
function revelations_editorial_ai_fact_check_claim_types(): array {
    return array(
        'number',
        'date',
        'money',
        'investment',
        'company_valuation',
        'quote',
        'superlative',
        'benchmark',
        'medical',
        'legal',
        'regulatory',
        'reputational',
        'other_sensitive',
    );
}

/**
 * Return a safe validation failure.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_ai_validation_failure(
    string $code,
    string $message
): array {
    return array(
        'valid'   => false,
        'code'    => $code,
        'message' => $message,
    );
}

/**
 * Determine whether an error belongs to pre-write generation validation.
 */
function revelations_editorial_ai_is_validation_error_code(
    string $code
): bool {
    return in_array(
        $code,
        array(
            'generation_validation_unavailable',
            'generation_validation_failed',
            'invalid_title_set',
            'invalid_section_advisory',
            'duplicate_descriptions',
            'invalid_fact_check_flag',
            'invalid_fact_check_evidence',
            'duplicate_fact_check_flag',
            'missing_fact_check_flag',
            'unspoken_fact_check_required',
            'invalid_direct_quote',
            'missing_quote_fact_check_flag',
            'direct_quote_usage_mismatch',
        ),
        true
    );
}

/**
 * Normalize one title for deterministic uniqueness comparison.
 */
function revelations_editorial_ai_normalize_title(
    string $value
): string {
    $value = strip_tags( $value );
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $value = str_replace(
        array(
            "\u{00A0}",
            "\u{202F}",
        ),
        ' ',
        $value
    );

    $value = strtr(
        $value,
        array(
            '“' => '"',
            '”' => '"',
            '„' => '"',
            '‟' => '"',
            '«' => '"',
            '»' => '"',
            '‹' => '"',
            '›' => '"',
            '‘' => "'",
            '’' => "'",
            '‚' => "'",
            '‛' => "'",
            '–' => '-',
            '—' => '-',
            '−' => '-',
            '‑' => '-',
            '‒' => '-',
        )
    );

    $value = mb_strtolower(
        $value,
        'UTF-8'
    );

    $value = (string) preg_replace(
        '/\p{P}+/u',
        ' ',
        $value
    );

    $value = (string) preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    return trim( $value );
}

/**
 * Normalize descriptions for exact comparison.
 */
function revelations_editorial_ai_normalize_description(
    string $value
): string {
    $value = strip_tags( $value );
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $value = str_replace(
        array(
            "\u{00A0}",
            "\u{202F}",
        ),
        ' ',
        $value
    );

    $value = mb_strtolower(
        $value,
        'UTF-8'
    );

    $value = (string) preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    return trim( $value );
}

/**
 * Apply only the permitted technical quote normalization.
 */
function revelations_editorial_ai_normalize_quote(
    string $value
): string {
    $value = str_replace(
        array(
            "\r\n",
            "\r",
        ),
        "\n",
        $value
    );

    return html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
}

/**
 * Apply only permitted technical normalization to source evidence.
 *
 * Wording, case and punctuation remain unchanged. This only makes
 * equivalent transport formatting comparable.
 */
function revelations_editorial_ai_normalize_source_evidence(
    string $value
): string {
    $value = revelations_editorial_ai_normalize_quote(
        $value
    );

    $value = str_replace(
        array(
            "\u{00A0}",
            "\u{202F}",
        ),
        ' ',
        $value
    );

    $value = (string) preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    return trim( $value );
}

/**
 * Split a source snapshot into stable normalized paragraph evidence units.
 *
 * @return array<int, array{id: string, text: string}>
 */
function revelations_editorial_ai_source_evidence_units(
    string $source_snapshot
): array {
    $normalized =
        revelations_editorial_ai_normalize_quote(
            $source_snapshot
        );

    $normalized = str_replace(
        array(
            "\u{00A0}",
            "\u{202F}",
        ),
        ' ',
        $normalized
    );

    $paragraphs = preg_split(
        '/\n[ \t]*\n+/u',
        $normalized
    );

    if ( ! is_array( $paragraphs ) ) {
        return array();
    }

    $units = array();

    foreach ( $paragraphs as $paragraph ) {
        $text = (string) preg_replace(
            '/\s+/u',
            ' ',
            (string) $paragraph
        );

        $text = trim( $text );

        if ( '' === $text ) {
            continue;
        }

        $units[] = array(
            'id' => sprintf(
                'p%03d',
                count( $units ) + 1
            ),
            'text' => $text,
        );
    }

    return $units;
}

/**
 * Index evidence units by their stable IDs.
 *
 * @param array<int, array{id: string, text: string}> $units Units.
 * @return array<string, string>
 */
function revelations_editorial_ai_source_evidence_map(
    array $units
): array {
    $map = array();

    foreach ( $units as $unit ) {
        if (
            ! is_array( $unit ) ||
            ! is_string( $unit['id'] ?? null ) ||
            ! is_string( $unit['text'] ?? null ) ||
            1 !== preg_match(
                '/^p[0-9]{3,}$/',
                $unit['id']
            ) ||
            '' === $unit['text']
        ) {
            continue;
        }

        $map[ $unit['id'] ] = $unit['text'];
    }

    return $map;
}

/**
 * Render normalized evidence units for the model input.
 *
 * @param array<int, array{id: string, text: string}> $units Units.
 */
function revelations_editorial_ai_format_source_evidence_units(
    array $units
): string {
    $lines = array();

    foreach (
        revelations_editorial_ai_source_evidence_map(
            $units
        ) as $id => $text
    ) {
        $lines[] = '[' . $id . '] ' . $text;
    }

    return implode( "\n\n", $lines );
}

/**
 * Resolve model evidence references into server-owned review metadata.
 *
 * The model supplies IDs only. Source evidence and exact quote fragments are
 * reconstructed here from the normalized snapshot units.
 *
 * @param array<string, mixed> $article Raw structured model output.
 * @param array<int, array{id: string, text: string}> $units Evidence units.
 * @return array<string, mixed>
 */
function revelations_editorial_ai_resolve_evidence_references(
    array $article,
    array $units
): array {
    $evidence_map =
        revelations_editorial_ai_source_evidence_map(
            $units
        );

    if ( array() === $evidence_map ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_fact_check_evidence',
            'Source evidence units are unavailable.'
        );
    }

    $claim_unit_map =
        revelations_editorial_ai_generated_claim_unit_map(
            $article
        );

    $raw_flags = is_array(
        $article['fact_check_flags'] ?? null
    )
        ? $article['fact_check_flags']
        : array();

    $raw_quotes = is_array(
        $article['direct_quotes'] ?? null
    )
        ? $article['direct_quotes']
        : array();

    $quote_texts = array();

    foreach ( $raw_quotes as $quote ) {
        if ( is_array( $quote ) ) {
            $quote_texts[] = (string) (
                $quote['quote_text'] ?? ''
            );
        }
    }

    $blocks = is_array(
        $article['blocks'] ?? null
    )
        ? $article['blocks']
        : array();

    $blocks_with_evidence = 0;

    foreach ( $blocks as $block ) {
        if (
            is_array( $block ) &&
            array_key_exists(
                'evidence_ids',
                $block
            )
        ) {
            $blocks_with_evidence++;
        }
    }

    if (
        $blocks_with_evidence > 0 &&
        $blocks_with_evidence !==
            count( $blocks )
    ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_fact_check_evidence',
            'Every generated article block must include evidence IDs.'
        );
    }

    $uses_block_evidence =
        array() !== $blocks &&
        count( $blocks ) ===
            $blocks_with_evidence;

    if ( $uses_block_evidence ) {
        /*
         * The model maps every complete body block to source paragraph
         * IDs. The server, not the model, determines which generated
         * units contain sensitive claims.
         */
        $raw_flags = array();

        foreach (
            $blocks
            as $block_index => $block
        ) {
            $block_evidence_ids = is_array(
                $block['evidence_ids'] ?? null
            )
                ? array_values(
                    $block['evidence_ids']
                )
                : array();

            if (
                array() === $block_evidence_ids ||
                count( $block_evidence_ids ) !==
                    count(
                        array_unique(
                            $block_evidence_ids,
                            SORT_STRING
                        )
                    )
            ) {
                return revelations_editorial_ai_validation_failure(
                    'invalid_fact_check_evidence',
                    'A generated article block has invalid evidence IDs.'
                );
            }

            foreach (
                $block_evidence_ids
                as $block_evidence_id
            ) {
                if (
                    ! is_string(
                        $block_evidence_id
                    ) ||
                    1 !== preg_match(
                        '/^p[0-9]{3,}$/',
                        $block_evidence_id
                    ) ||
                    ! isset(
                        $evidence_map[
                            $block_evidence_id
                        ]
                    )
                ) {
                    return revelations_editorial_ai_validation_failure(
                        'invalid_fact_check_evidence',
                        'A generated article block references unknown source evidence.'
                    );
                }
            }
        }

        foreach (
            revelations_editorial_ai_required_fact_check_units(
                $article
            ) as $generated_unit
        ) {
            $claim_types =
                revelations_editorial_ai_detect_claim_types(
                    (string) (
                        $generated_unit['text']
                        ?? ''
                    ),
                    (string) (
                        $generated_unit['kind']
                        ?? ''
                    )
                );

            if ( array() === $claim_types ) {
                continue;
            }

            $unit_id = (string) (
                $generated_unit['id'] ?? ''
            );

            if (
                1 !== preg_match(
                    '/^blocks\.([0-9]+)\.(?:text|items\.[0-9]+)$/',
                    $unit_id,
                    $unit_matches
                )
            ) {
                return revelations_editorial_ai_validation_failure(
                    'invalid_fact_check_flag',
                    'A generated claim unit ID is invalid.'
                );
            }

            $block_index =
                (int) $unit_matches[1];

            if (
                ! isset(
                    $blocks[
                        $block_index
                    ]
                ) ||
                ! is_array(
                    $blocks[
                        $block_index
                    ]
                )
            ) {
                return revelations_editorial_ai_validation_failure(
                    'invalid_fact_check_flag',
                    'A generated claim unit references an unavailable block.'
                );
            }

            $raw_flags[] = array(
                'claim_unit_id' =>
                    $unit_id,

                'requires_manual_verification' =>
                    true,

                'evidence_ids' =>
                    array_values(
                        $blocks[
                            $block_index
                        ]['evidence_ids']
                    ),
            );
        }
    }

    $resolved_flags = array();

    foreach ( $raw_flags as $flag ) {
        if ( ! is_array( $flag ) ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_fact_check_flag',
                'A fact-check flag is invalid.'
            );
        }

        $claim_unit_id = (string) (
            $flag['claim_unit_id'] ?? ''
        );

        $claim = '';

        if (
            '' !== $claim_unit_id &&
            isset(
                $claim_unit_map[
                    $claim_unit_id
                ]
            )
        ) {
            $claim =
                $claim_unit_map[
                    $claim_unit_id
                ]['text'];
        } else {
            /*
             * Backward compatibility for old tests and stored
             * pre-reference payloads containing literal claim text.
             */
            $legacy_claim = (string) (
                $flag['claim'] ?? ''
            );

            foreach (
                $claim_unit_map
                as $legacy_unit_id =>
                    $generated_unit
            ) {
                if (
                    '' !== trim( $legacy_claim ) &&
                    str_contains(
                        $generated_unit['text'],
                        $legacy_claim
                    )
                ) {
                    $claim_unit_id =
                        $legacy_unit_id;
                    $claim =
                        $legacy_claim;
                    break;
                }
            }
        }

        $evidence_ids = is_array(
            $flag['evidence_ids'] ?? null
        )
            ? array_values(
                $flag['evidence_ids']
            )
            : array();

        if (
            '' === trim( $claim ) ||
            '' === $claim_unit_id ||
            true !== (
                $flag[
                    'requires_manual_verification'
                ] ?? false
            ) ||
            array() === $evidence_ids ||
            count( $evidence_ids ) !==
                count(
                    array_unique(
                        $evidence_ids,
                        SORT_STRING
                    )
                )
        ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_fact_check_flag',
                'A fact-check flag is invalid.'
            );
        }

        $evidence = array();

        foreach ( $evidence_ids as $evidence_id ) {
            if (
                ! is_string( $evidence_id ) ||
                1 !== preg_match(
                    '/^p[0-9]{3,}$/',
                    $evidence_id
                ) ||
                ! isset(
                    $evidence_map[ $evidence_id ]
                )
            ) {
                return revelations_editorial_ai_validation_failure(
                    'invalid_fact_check_evidence',
                    'A fact-check flag references unknown source evidence.'
                );
            }

            $evidence[] =
                $evidence_map[ $evidence_id ];
        }

        $claim_types =
            revelations_editorial_ai_detect_claim_types(
                $claim,
                'paragraph'
            );

        foreach ( $quote_texts as $quote_text ) {
            if (
                '' !== $claim &&
                str_contains(
                    $quote_text,
                    $claim
                )
            ) {
                $claim_types[] = 'quote';
                break;
            }
        }

        $claim_types = array_values(
            array_unique( $claim_types )
        );

        if ( array() === $claim_types ) {
            $claim_types[] = 'other_sensitive';
        }

        foreach ( $claim_types as $claim_type ) {
            $resolved_flags[] = array(
                'claim' => $claim,
                'claim_unit_id' =>
                    $claim_unit_id,
                'claim_type' => $claim_type,
                'source_evidence' =>
                    implode( "\n\n", $evidence ),
                'evidence_ids' => $evidence_ids,
                'verification_required' => true,
                'reason' =>
                    'Requires manual verification.',
            );
        }
    }

    /*
     * The model may return one item per sensitive claim even though
     * claim_unit_id references a complete generated body unit.
     * Coalesce repeated unit/type pairs and preserve the union of
     * their server-resolved source evidence IDs.
     */
    $merged_flags = array();

    foreach ( $resolved_flags as $resolved_flag ) {
        $merge_key =
            (string) (
                $resolved_flag[
                    'claim_unit_id'
                ] ?? ''
            ) .
            "\0" .
            (string) (
                $resolved_flag[
                    'claim_type'
                ] ?? ''
            );

        if (
            ! isset(
                $merged_flags[
                    $merge_key
                ]
            )
        ) {
            $merged_flags[
                $merge_key
            ] = $resolved_flag;

            continue;
        }

        $merged_evidence_ids = array();

        foreach (
            array_merge(
                is_array(
                    $merged_flags[
                        $merge_key
                    ]['evidence_ids'] ?? null
                )
                    ? $merged_flags[
                        $merge_key
                    ]['evidence_ids']
                    : array(),
                is_array(
                    $resolved_flag[
                        'evidence_ids'
                    ] ?? null
                )
                    ? $resolved_flag[
                        'evidence_ids'
                    ]
                    : array()
            )
            as $merged_evidence_id
        ) {
            if (
                ! is_string(
                    $merged_evidence_id
                ) ||
                in_array(
                    $merged_evidence_id,
                    $merged_evidence_ids,
                    true
                )
            ) {
                continue;
            }

            $merged_evidence_ids[] =
                $merged_evidence_id;
        }

        $merged_evidence = array();

        foreach (
            $merged_evidence_ids
            as $merged_evidence_id
        ) {
            if (
                isset(
                    $evidence_map[
                        $merged_evidence_id
                    ]
                )
            ) {
                $merged_evidence[] =
                    $evidence_map[
                        $merged_evidence_id
                    ];
            }
        }

        $merged_flags[
            $merge_key
        ]['evidence_ids'] =
            $merged_evidence_ids;

        $merged_flags[
            $merge_key
        ]['source_evidence'] =
            implode(
                "\n\n",
                $merged_evidence
            );
    }

    $resolved_flags =
        array_values(
            $merged_flags
        );

    $resolved_quotes = array();

    foreach ( $raw_quotes as $quote ) {
        if ( ! is_array( $quote ) ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_direct_quote',
                'Direct quote evidence is invalid.'
            );
        }

        $quote_text = (string) (
            $quote['quote_text'] ?? ''
        );

        $evidence_id = (string) (
            $quote['evidence_id'] ?? ''
        );

        if (
            '' === trim( $quote_text ) ||
            1 !== preg_match(
                '/^p[0-9]{3,}$/',
                $evidence_id
            ) ||
            ! isset( $evidence_map[ $evidence_id ] ) ||
            ! str_contains(
                $evidence_map[ $evidence_id ],
                revelations_editorial_ai_normalize_quote(
                    $quote_text
                )
            )
        ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_direct_quote',
                'Direct quote evidence could not be verified.'
            );
        }

        $resolved_quotes[] = array(
            'quote_text' => $quote_text,
            'evidence_id' => $evidence_id,
            'source_fragment' =>
                $evidence_map[ $evidence_id ],
        );
    }

    $article['fact_check_flags'] =
        $resolved_flags;

    $article['direct_quotes'] =
        $resolved_quotes;

    return array(
        'valid' => true,
        'article' => $article,
    );
}

/**
 * Return all generated visible-text and metadata claim units.
 *
 * @param array<string, mixed> $article Parsed article.
 * @return array<int, array{id: string, kind: string, text: string}>
 */
function revelations_editorial_ai_generated_claim_units(
    array $article
): array {
    $units = array();

    foreach (
        array(
            'recommended_title',
            'excerpt',
            'seo_title',
            'seo_description',
        ) as $field
    ) {
        $text = (string) (
            $article[ $field ] ?? ''
        );

        if ( '' !== trim( $text ) ) {
            $units[] = array(
                'id' => $field,
                'kind' => $field,
                'text' => $text,
            );
        }
    }

    $alternative_titles =
        $article['alternative_titles'] ?? array();

    if ( is_array( $alternative_titles ) ) {
        foreach (
            $alternative_titles
            as $alternative_index =>
                $alternative_title
        ) {
            $text = (string) $alternative_title;

            if ( '' !== trim( $text ) ) {
                $units[] = array(
                    'id' =>
                        'alternative_titles.' .
                        $alternative_index,
                    'kind' =>
                        'alternative_title',
                    'text' =>
                        $text,
                );
            }
        }
    }

    $blocks = $article['blocks'] ?? array();

    if ( ! is_array( $blocks ) ) {
        return $units;
    }

    foreach (
        $blocks
        as $block_index => $block
    ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $type = (string) (
            $block['type'] ?? ''
        );

        if (
            in_array(
                $type,
                array(
                    'paragraph',
                    'heading',
                    'quote',
                ),
                true
            )
        ) {
            $text = (string) (
                $block['text'] ?? ''
            );

            if ( '' !== trim( $text ) ) {
                $units[] = array(
                    'id' =>
                        'blocks.' .
                        $block_index .
                        '.text',
                    'kind' => $type,
                    'text' => $text,
                );
            }
        }

        if (
            ! in_array(
                $type,
                array(
                    'unordered_list',
                    'ordered_list',
                ),
                true
            ) ||
            ! is_array(
                $block['items'] ?? null
            )
        ) {
            continue;
        }

        foreach (
            $block['items']
            as $item_index => $item
        ) {
            $text = (string) $item;

            if ( '' !== trim( $text ) ) {
                $units[] = array(
                    'id' =>
                        'blocks.' .
                        $block_index .
                        '.items.' .
                        $item_index,
                    'kind' => 'list_item',
                    'text' => $text,
                );
            }
        }
    }

    return $units;
}

/**
 * Index generated article units by stable IDs.
 *
 * @param array<string, mixed> $article Parsed article.
 * @return array<string, array{kind: string, text: string}>
 */
function revelations_editorial_ai_generated_claim_unit_map(
    array $article
): array {
    $map = array();

    foreach (
        revelations_editorial_ai_generated_claim_units(
            $article
        ) as $unit
    ) {
        if (
            ! is_array( $unit ) ||
            ! is_string( $unit['id'] ?? null ) ||
            ! is_string( $unit['kind'] ?? null ) ||
            ! is_string( $unit['text'] ?? null ) ||
            '' === $unit['id'] ||
            '' === trim( $unit['text'] )
        ) {
            continue;
        }

        $map[ $unit['id'] ] = array(
            'kind' => $unit['kind'],
            'text' => $unit['text'],
        );
    }

    return $map;
}

/**
 * Return body units that require sensitive-claim coverage.
 *
 * Titles, descriptions and headings are excluded because they are
 * metadata or structural summaries of the reviewed article body.
 *
 * @param array<string, mixed> $article Parsed article.
 * @return array<int, array{id: string, kind: string, text: string}>
 */
function revelations_editorial_ai_required_fact_check_units(
    array $article
): array {
    $required = array();

    foreach (
        revelations_editorial_ai_generated_claim_units(
            $article
        ) as $unit
    ) {
        if (
            ! in_array(
                $unit['kind'] ?? '',
                array(
                    'paragraph',
                    'quote',
                    'list_item',
                ),
                true
            )
        ) {
            continue;
        }

        $required[] = $unit;
    }

    return $required;
}

/**
 * Detect high-confidence sensitive claim types in one claim unit.
 *
 * @return string[]
 */
function revelations_editorial_ai_detect_claim_types(
    string $text,
    string $kind
): array {
    $types = array();

    if ( 'quote' === $kind ) {
        $types[] = 'quote';
    }

    $has_digits = 1 === preg_match(
        '/(?:\p{N}|%|percent\b)/iu',
        $text
    );

    $has_date = 1 === preg_match(
        '/\b(?:19|20)\d{2}\b|' .
        '\b(?:january|february|march|april|may|june|july|' .
        'august|september|october|november|december)\b|' .
        '\b\d{4}-\d{2}-\d{2}\b/iu',
        $text
    );

    $has_money = 1 === preg_match(
        '/[$€£¥₹₽]|' .
        '\b(?:usd|eur|gbp|jpy|cny|inr|rub)\b|' .
        '\b\d[\d,.]*\s*(?:dollars?|euros?|pounds?|yen|yuan)\b/iu',
        $text
    );

    $has_valuation = 1 === preg_match(
        '/\b(?:valuation|valued\s+at|company\s+value)\b/iu',
        $text
    );

    $has_investment = 1 === preg_match(
        '/\b(?:investment|invested|investor|funding|' .
        'fundraising|raised|financing|seed\s+round|' .
        'series\s+[a-z])\b/iu',
        $text
    );

    $has_benchmark = 1 === preg_match(
        '/\b(?:benchmark|score|accuracy|performance|' .
        'faster|slower|latency|throughput)\b/iu',
        $text
    ) &&
        (
            $has_digits ||
            1 === preg_match(
                '/\b(?:twice|double|triple|\d+(?:\.\d+)?x)\b/iu',
                $text
            )
        );

    $has_superlative = 1 === preg_match(
        '/\b(?:first|only|largest|fastest|leading|' .
        'biggest|most\s+advanced|best|highest|lowest)\b/iu',
        $text
    );

    $has_medical = 1 === preg_match(
        '/\b(?:medical|clinical|patient|diagnos(?:is|e|ed|tic)|' .
        'treatment|therapy|disease|healthcare|drug)\b/iu',
        $text
    );

    $has_legal = 1 === preg_match(
        '/\b(?:lawsuit|sued|court|judge|illegal|lawful|legal|' .
        'copyright|infringement|convicted|settlement)\b/iu',
        $text
    );

    $has_regulatory = 1 === preg_match(
        '/\b(?:regulator|regulatory|regulation|compliance|' .
        'antitrust|ftc|federal\s+trade\s+commission|' .
        'eu\s+ai\s+act)\b/iu',
        $text
    );

    $has_reputational_language = 1 === preg_match(
        '/\b(?:accused|alleged|fraud|fraudulent|misled|' .
        'deceptive|abuse|harmed|scandal|conflict\s+of\s+interest|' .
        'cover-up|wrongdoing)\b/iu',
        $text
    );

    $has_named_subject = 1 === preg_match(
        '/\b\p{Lu}[\p{L}\p{M}.-]+\s+' .
        '(?:\p{Lu}[\p{L}\p{M}.-]+|' .
        'Inc\.?|Corp\.?|Corporation|Ltd\.?|LLC|Company)\b/u',
        $text
    );

    if ( $has_date ) {
        $types[] = 'date';
    }

    if ( $has_valuation ) {
        $types[] = 'company_valuation';
    } elseif ( $has_investment ) {
        $types[] = 'investment';
    } elseif ( $has_money ) {
        $types[] = 'money';
    }

    if ( $has_benchmark ) {
        $types[] = 'benchmark';
    }

    if (
        $has_digits &&
        ! $has_date &&
        ! $has_money &&
        ! $has_valuation &&
        ! $has_investment &&
        ! $has_benchmark
    ) {
        $types[] = 'number';
    }

    if ( $has_superlative ) {
        $types[] = 'superlative';
    }

    if ( $has_medical ) {
        $types[] = 'medical';
    }

    if ( $has_legal ) {
        $types[] = 'legal';
    }

    if ( $has_regulatory ) {
        $types[] = 'regulatory';
    }

    if (
        $has_reputational_language &&
        $has_named_subject
    ) {
        $types[] = 'reputational';
    }

    return array_values(
        array_unique( $types )
    );
}

/**
 * Determine whether a flag type covers a detected claim type.
 */
function revelations_editorial_ai_claim_type_matches(
    string $detected_type,
    string $flag_type
): bool {
    if ( $detected_type === $flag_type ) {
        return true;
    }

    return 'money' === $detected_type &&
        in_array(
            $flag_type,
            array(
                'investment',
                'company_valuation',
            ),
            true
        );
}

/**
 * Validate generated editorial output without WordPress or network access.
 *
 * @param array<string, mixed> $article Parsed and sanitized model output.
 * @return array<string, mixed>
 */
function revelations_editorial_ai_validate_generated_article(
    array $article,
    string $source_section,
    string $source_snapshot
): array {
    $titles = array_merge(
        array(
            (string) (
                $article['recommended_title']
                ?? ''
            ),
        ),
        is_array(
            $article['alternative_titles'] ?? null
        )
            ? $article['alternative_titles']
            : array()
    );

    if ( 3 !== count( $titles ) ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_title_set',
            'Generated titles do not satisfy the editorial contract.'
        );
    }

    $normalized_titles = array_map(
        static fn ( mixed $title ): string =>
            revelations_editorial_ai_normalize_title(
                (string) $title
            ),
        $titles
    );

    if (
        in_array( '', $normalized_titles, true ) ||
        3 !== count(
            array_unique( $normalized_titles )
        )
    ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_title_set',
            'Generated titles must be non-empty and unique.'
        );
    }

    $section_mismatch =
        true === (
            $article['section_mismatch']
            ?? false
        );

    $suggested_section =
        $article['suggested_section'] ?? null;

    $mismatch_reason =
        $article['section_mismatch_reason']
        ?? null;

    if (
        ! $section_mismatch &&
        (
            null !== $suggested_section ||
            null !== $mismatch_reason
        )
    ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_section_advisory',
            'Section advisory fields are inconsistent.'
        );
    }

    if (
        $section_mismatch &&
        (
            ! is_string( $suggested_section ) ||
            ! in_array(
                $suggested_section,
                array(
                    'news',
                    'tech',
                    'people',
                    'places',
                    'unspoken',
                ),
                true
            ) ||
            $suggested_section === $source_section ||
            ! is_string( $mismatch_reason ) ||
            '' === trim( $mismatch_reason )
        )
    ) {
        return revelations_editorial_ai_validation_failure(
            'invalid_section_advisory',
            'Section advisory fields are inconsistent.'
        );
    }

    $normalized_excerpt =
        revelations_editorial_ai_normalize_description(
            (string) (
                $article['excerpt'] ?? ''
            )
        );

    $normalized_seo_description =
        revelations_editorial_ai_normalize_description(
            (string) (
                $article['seo_description'] ?? ''
            )
        );

    if (
        '' !== $normalized_excerpt &&
        $normalized_excerpt ===
            $normalized_seo_description
    ) {
        return revelations_editorial_ai_validation_failure(
            'duplicate_descriptions',
            'Excerpt and SEO description must be distinct.'
        );
    }

    $validated_article = $article;
    $blocks = is_array(
        $article['blocks'] ?? null
    )
        ? $article['blocks']
        : array();

    foreach ( $blocks as $index => $block ) {
        if (
            ! is_array( $block ) ||
            'quote' !== (
                $block['type'] ?? ''
            )
        ) {
            continue;
        }

        $blocks[ $index ]['text'] =
            revelations_editorial_ai_normalize_quote(
                (string) (
                    $block['text'] ?? ''
                )
            );
    }

    $validated_article['blocks'] = $blocks;

    $claim_units =
        revelations_editorial_ai_generated_claim_units(
            $validated_article
        );

    $required_fact_check_units =
        revelations_editorial_ai_required_fact_check_units(
            $validated_article
        );

    $flags = is_array(
        $article['fact_check_flags'] ?? null
    )
        ? $article['fact_check_flags']
        : array();

    if (
        'unspoken' === $source_section &&
        array() === $flags
    ) {
        return revelations_editorial_ai_validation_failure(
            'unspoken_fact_check_required',
            'Unspoken generation requires fact-check flags.'
        );
    }

    $validated_flags = array();
    $seen_flags = array();
    $allowed_claim_types =
        revelations_editorial_ai_fact_check_claim_types();
    $normalized_source_snapshot =
        revelations_editorial_ai_normalize_source_evidence(
            $source_snapshot
        );
    $source_evidence_map =
        revelations_editorial_ai_source_evidence_map(
            revelations_editorial_ai_source_evidence_units(
                $source_snapshot
            )
        );

    foreach ( $flags as $flag ) {
        if ( ! is_array( $flag ) ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_fact_check_flag',
                'A fact-check flag is invalid.'
            );
        }

        $claim = (string) (
            $flag['claim'] ?? ''
        );

        $claim_unit_id = (string) (
            $flag['claim_unit_id'] ?? ''
        );

        $claim_type = (string) (
            $flag['claim_type'] ?? ''
        );

        $source_evidence = (string) (
            $flag['source_evidence'] ?? ''
        );

        $reason = (string) (
            $flag['reason'] ?? ''
        );

        $verification_required =
            true === (
                $flag['verification_required']
                ?? false
            );

        $evidence_ids = is_array(
            $flag['evidence_ids'] ?? null
        )
            ? array_values(
                $flag['evidence_ids']
            )
            : array();

        if (
            '' === trim( $claim ) ||
            '' === trim( $source_evidence ) ||
            '' === trim( $reason ) ||
            ! $verification_required ||
            ! in_array(
                $claim_type,
                $allowed_claim_types,
                true
            )
        ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_fact_check_flag',
                'A fact-check flag is invalid.'
            );
        }

        $claim_is_used = false;

        foreach ( $claim_units as $unit ) {
            if ( '' !== $claim_unit_id ) {
                if (
                    $unit['id'] ===
                        $claim_unit_id &&
                    $unit['text'] ===
                        $claim
                ) {
                    $claim_is_used = true;
                    break;
                }

                continue;
            }

            if (
                str_contains(
                    $unit['text'],
                    $claim
                )
            ) {
                $claim_is_used = true;
                break;
            }
        }

        $normalized_source_evidence =
            revelations_editorial_ai_normalize_source_evidence(
                $source_evidence
            );

        $evidence_is_valid = false;

        if ( array() !== $evidence_ids ) {
            $resolved_evidence = array();

            foreach ( $evidence_ids as $evidence_id ) {
                if (
                    ! is_string( $evidence_id ) ||
                    ! isset(
                        $source_evidence_map[
                            $evidence_id
                        ]
                    )
                ) {
                    $resolved_evidence = array();
                    break;
                }

                $resolved_evidence[] =
                    $source_evidence_map[
                        $evidence_id
                    ];
            }

            $evidence_is_valid =
                array() !== $resolved_evidence &&
                count( $resolved_evidence ) ===
                    count( $evidence_ids ) &&
                implode(
                    "\n\n",
                    $resolved_evidence
                ) === $source_evidence;
        } else {
            $evidence_is_valid =
                '' !== $normalized_source_evidence &&
                str_contains(
                    $normalized_source_snapshot,
                    $normalized_source_evidence
                );
        }

        if (
            ! $claim_is_used ||
            '' === $normalized_source_evidence ||
            ! $evidence_is_valid
        ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_fact_check_evidence',
                'Fact-check evidence could not be verified.'
            );
        }

        $flag_key =
            $claim .
            "\0" .
            $claim_type;

        if ( isset( $seen_flags[ $flag_key ] ) ) {
            return revelations_editorial_ai_validation_failure(
                'duplicate_fact_check_flag',
                'Duplicate fact-check flags are not allowed.'
            );
        }

        $seen_flags[ $flag_key ] = true;

        $validated_flags[] = array(
            'claim' =>
                $claim,
            'claim_unit_id' =>
                $claim_unit_id,
            'claim_type' =>
                $claim_type,
            'source_evidence' =>
                $source_evidence,
            'verification_required' =>
                true,
            'reason' =>
                $reason,
        );

        if ( array() !== $evidence_ids ) {
            $validated_flags[
                array_key_last(
                    $validated_flags
                )
            ]['evidence_ids'] =
                $evidence_ids;
        }
    }

    foreach (
        $required_fact_check_units
        as $unit
    ) {
        $detected_types =
            revelations_editorial_ai_detect_claim_types(
                $unit['text'],
                $unit['kind']
            );

        foreach (
            $detected_types
            as $detected_type
        ) {
            $matching_flag_exists = false;

            foreach (
                $validated_flags
                as $flag
            ) {
                if (
                    revelations_editorial_ai_claim_type_matches(
                        $detected_type,
                        $flag['claim_type']
                    ) &&
                    (
                        (
                            '' !== (
                                $flag[
                                    'claim_unit_id'
                                ] ?? ''
                            ) &&
                            $unit['id'] ===
                                $flag[
                                    'claim_unit_id'
                                ]
                        ) ||
                        (
                            '' === (
                                $flag[
                                    'claim_unit_id'
                                ] ?? ''
                            ) &&
                            str_contains(
                                $unit['text'],
                                $flag['claim']
                            )
                        )
                    )
                ) {
                    $matching_flag_exists = true;
                    break;
                }
            }

            if ( ! $matching_flag_exists ) {
                return revelations_editorial_ai_validation_failure(
                    'missing_fact_check_flag',
                    'A sensitive claim requires a fact-check flag.'
                );
            }
        }
    }

    $normalized_source =
        revelations_editorial_ai_normalize_quote(
            $source_snapshot
        );

    $quote_blocks = array();

    foreach ( $blocks as $block ) {
        if (
            is_array( $block ) &&
            'quote' === (
                $block['type'] ?? ''
            )
        ) {
            $quote_blocks[] = (string) (
                $block['text'] ?? ''
            );
        }
    }

    $direct_quotes = is_array(
        $article['direct_quotes'] ?? null
    )
        ? $article['direct_quotes']
        : array();

    $validated_quotes = array();
    $quote_item_texts = array();

    foreach ( $direct_quotes as $direct_quote ) {
        if ( ! is_array( $direct_quote ) ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_direct_quote',
                'Direct quote evidence is invalid.'
            );
        }

        $quote_text =
            revelations_editorial_ai_normalize_quote(
                (string) (
                    $direct_quote['quote_text']
                    ?? ''
                )
            );

        $source_fragment =
            revelations_editorial_ai_normalize_quote(
                (string) (
                    $direct_quote[
                        'source_fragment'
                    ] ?? ''
                )
            );

        $evidence_id = (string) (
            $direct_quote['evidence_id'] ?? ''
        );

        $quote_evidence_is_valid =
            '' === $evidence_id ||
            (
                isset(
                    $source_evidence_map[
                        $evidence_id
                    ]
                ) &&
                $source_evidence_map[
                    $evidence_id
                ] === $source_fragment
            );

        if (
            '' === trim( $quote_text ) ||
            '' === trim( $source_fragment ) ||
            ! str_contains(
                $normalized_source,
                $quote_text
            ) ||
            ! str_contains(
                $normalized_source,
                $source_fragment
            ) ||
            ! str_contains(
                $source_fragment,
                $quote_text
            ) ||
            ! $quote_evidence_is_valid
        ) {
            return revelations_editorial_ai_validation_failure(
                'invalid_direct_quote',
                'Direct quote evidence could not be verified.'
            );
        }

        $quote_flag_exists = false;

        foreach ( $validated_flags as $flag ) {
            if (
                'quote' === $flag['claim_type'] &&
                str_contains(
                    $quote_text,
                    $flag['claim']
                )
            ) {
                $quote_flag_exists = true;
                break;
            }
        }

        if ( ! $quote_flag_exists ) {
            return revelations_editorial_ai_validation_failure(
                'missing_quote_fact_check_flag',
                'A direct quote requires a quote fact-check flag.'
            );
        }

        $quote_item_texts[] = $quote_text;

        $validated_quotes[] = array(
            'quote_text' =>
                $quote_text,
            'source_fragment' =>
                $source_fragment,
            'verbatim_match' =>
                true,
        );

        if ( '' !== $evidence_id ) {
            $validated_quotes[
                array_key_last(
                    $validated_quotes
                )
            ]['evidence_id'] =
                $evidence_id;
        }
    }

    $quote_block_counts = array_count_values(
        $quote_blocks
    );

    $quote_item_counts = array_count_values(
        $quote_item_texts
    );

    ksort( $quote_block_counts );
    ksort( $quote_item_counts );

    if ( $quote_block_counts !== $quote_item_counts ) {
        return revelations_editorial_ai_validation_failure(
            'direct_quote_usage_mismatch',
            'Direct quote evidence does not match article quote blocks.'
        );
    }

    $validated_article['fact_check_flags'] =
        $validated_flags;

    $validated_article['direct_quotes'] =
        $validated_quotes;

    return array(
        'valid'   => true,
        'article' => $validated_article,
    );
}

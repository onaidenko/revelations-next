<?php
/**
 * Plugin Name: REVELATIONS Editorial Scanner Engine
 * Description: Shared dry-run RSS engine with a mandatory AI relevance gate.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return signals that establish AI as the dominant subject.
 *
 * These terms are intentionally defined in code rather than in
 * editable section settings. Administrators may tune individual
 * section profiles, but cannot accidentally remove the global
 * REVELATIONS AI editorial policy.
 *
 * Signals are grouped into canonical families so variants of one
 * concept cannot inflate the evidence count.
 *
 * @return array<string, array<string, string[]>>
 */
function revelations_editorial_scanner_ai_signals(): array {
    return array(
        'direct' => array(
            'ai' => array(
                'artificial intelligence',
                'artificial-intelligence',
                'generative ai',
                'generative-ai',
                'agentic ai',
                'ai-generated',
                'ai generated',
                'ai-powered',
                'ai powered',
                'ai-driven',
                'ai driven',
                'ai agents',
                'ai agent',
                'meta ai',
                'gen ai',
                'genai',
                'ai',
            ),

            'machine learning' => array(
                'machine learning',
                'machine-learning',
            ),

            'deep learning' => array(
                'deep learning',
                'deep-learning',
            ),

            'large language model' => array(
                'large language models',
                'large-language models',
                'large language model',
                'large-language model',
                'llms',
                'llm',
            ),

            'generative model' => array(
                'generative models',
                'generative model',
            ),

            'foundation model' => array(
                'foundation models',
                'foundation model',
            ),

            'neural network' => array(
                'neural networks',
                'neural network',
            ),

            'computer vision' => array(
                'computer vision',
                'computer-vision',
            ),

            'natural language processing' => array(
                'natural language processing',
            ),

            'openai' => array(
                'openai',
            ),

            'chatgpt' => array(
                'chatgpt',
            ),

            'gpt' => array(
                'gpt-5',
                'gpt-4',
                'gpt',
            ),

            'anthropic' => array(
                'anthropic',
            ),

            'deepmind' => array(
                'google deepmind',
                'deepmind',
            ),

            'xai' => array(
                'xai',
            ),

            'grok' => array(
                'grok',
            ),

            'apple intelligence' => array(
                'apple intelligence',
            ),

            'microsoft copilot' => array(
                'microsoft copilot',
            ),

            'github copilot' => array(
                'github copilot',
            ),

            'midjourney' => array(
                'midjourney',
            ),

            'stable diffusion' => array(
                'stable diffusion',
            ),
        ),

        /*
         * These names require corroborating technical context.
         * In particular, Gemini may refer to subjects unrelated
         * to Google's AI product.
         */
        'contextual' => array(
            'claude' => array(
                'claude',
            ),

            'gemini' => array(
                'gemini',
            ),

            'mistral' => array(
                'mistral',
            ),

            'perplexity' => array(
                'perplexity',
            ),

            'copilot' => array(
                'copilot',
            ),

            'qwen' => array(
                'qwen',
            ),

            'ernie' => array(
                'ernie',
            ),
        ),

        'technical' => array(
            'model' => array(
                'models',
                'model',
            ),

            'inference' => array(
                'inference',
            ),

            'training data' => array(
                'training data',
            ),

            'dataset' => array(
                'datasets',
                'dataset',
            ),

            'algorithm' => array(
                'algorithms',
                'algorithm',
            ),

            'automation' => array(
                'automation',
            ),

            'autonomous' => array(
                'autonomous',
            ),

            'predictive' => array(
                'predictive',
            ),

            'digital twin' => array(
                'digital twins',
                'digital twin',
            ),

            'voice agent' => array(
                'voice agents',
                'voice agent',
            ),

            'chatbot' => array(
                'chatbots',
                'chatbot',
            ),

            'agent' => array(
                'agents',
                'agent',
            ),

            'reasoning' => array(
                'reasoning',
            ),

            'multimodal' => array(
                'multimodal',
            ),

            'image generation' => array(
                'image generation',
            ),

            'platform' => array(
                'platform',
            ),

            'system' => array(
                'systems',
                'system',
            ),

            'gpu' => array(
                'gpus',
                'gpu',
            ),

            'data center' => array(
                'data centers',
                'data centre',
                'data center',
            ),

            'nvidia' => array(
                'nvidia',
            ),

            /*
             * Broad signals cannot establish AI relevance alone.
             */
            'robotics' => array(
                'humanoid robots',
                'humanoid robot',
                'robotaxis',
                'robotaxi',
                'robotics',
                'robots',
                'robot',
            ),

            'autonomous driving' => array(
                'autonomous vehicles',
                'autonomous vehicle',
                'self-driving',
            ),

            'siri' => array(
                'siri',
            ),
        ),

        'action' => array(
            'launch' => array(
                'launching',
                'launches',
                'launched',
                'launch',
            ),

            'release' => array(
                'releasing',
                'releases',
                'released',
                'release',
            ),

            'introduce' => array(
                'introducing',
                'introduces',
                'introduced',
                'introduce',
            ),

            'unveil' => array(
                'unveiling',
                'unveils',
                'unveiled',
                'unveil',
            ),

            'deploy' => array(
                'deploying',
                'deploys',
                'deployed',
                'deploy',
            ),

            'develop' => array(
                'developing',
                'develops',
                'developed',
                'develop',
            ),

            'build' => array(
                'building',
                'builds',
                'built',
                'build',
            ),

            'expand' => array(
                'expanding',
                'expands',
                'expanded',
                'expand',
            ),

            'transform' => array(
                'transforming',
                'transforms',
                'transformed',
                'transform',
            ),

            'automate' => array(
                'automating',
                'automates',
                'automated',
                'automate',
            ),

            'adapt' => array(
                'adapting',
                'adapts',
                'adapted',
                'adapt',
            ),

            'train' => array(
                'training',
                'trains',
                'trained',
                'train',
            ),

            'integrate' => array(
                'integrating',
                'integrates',
                'integrated',
                'integrate',
            ),

            'approve' => array(
                'approving',
                'approves',
                'approved',
                'approve',
            ),
        ),
    );
}

/**
 * Find canonical, non-overlapping signal matches.
 *
 * Candidate phrases are evaluated longest-first across all signal
 * groups. Once a text range is claimed, nested matches cannot use
 * the same fragment as additional evidence.
 *
 * @param array<string, array<string, string[]>> $signal_groups
 * @return array<string, string[]>
 */
function revelations_editorial_scanner_signal_matches(
    string $text,
    array $signal_groups
): array {
    $matches    = array();
    $candidates = array();

    foreach ( $signal_groups as $type => $families ) {
        $type = trim(
            mb_strtolower(
                (string) $type,
                'UTF-8'
            )
        );

        if (
            '' === $type ||
            ! is_array( $families )
        ) {
            continue;
        }

        $matches[ $type ] = array();

        foreach ( $families as $signal => $phrases ) {
            $signal = trim(
                mb_strtolower(
                    (string) $signal,
                    'UTF-8'
                )
            );

            if (
                '' === $signal ||
                ! is_array( $phrases )
            ) {
                continue;
            }

            foreach ( $phrases as $phrase ) {
                $phrase = trim(
                    mb_strtolower(
                        (string) $phrase,
                        'UTF-8'
                    )
                );

                if ( '' === $phrase ) {
                    continue;
                }

                $pattern =
                    '~(?<![\\p{L}\\p{N}])' .
                    preg_quote( $phrase, '~' ) .
                    '(?![\\p{L}\\p{N}])~iu';

                $found = array();

                $found_count = preg_match_all(
                    $pattern,
                    $text,
                    $found,
                    PREG_OFFSET_CAPTURE
                );

                if (
                    false === $found_count ||
                    0 === $found_count
                ) {
                    continue;
                }

                foreach ( $found[0] as $found_match ) {
                    if (
                        ! is_array( $found_match ) ||
                        ! isset( $found_match[0] ) ||
                        ! isset( $found_match[1] )
                    ) {
                        continue;
                    }

                    $matched_text =
                        (string) $found_match[0];

                    $candidates[] = array(
                        'type' =>
                            $type,

                        'signal' =>
                            $signal,

                        'start' =>
                            (int) $found_match[1],

                        /*
                         * PREG_OFFSET_CAPTURE reports byte
                         * offsets, so byte length is intentional.
                         */
                        'length' =>
                            strlen( $matched_text ),
                    );
                }
            }
        }
    }

    usort(
        $candidates,
        static function (
            array $left,
            array $right
        ): int {
            $length_order =
                (int) $right['length']
                <=>
                (int) $left['length'];

            if ( 0 !== $length_order ) {
                return $length_order;
            }

            $start_order =
                (int) $left['start']
                <=>
                (int) $right['start'];

            if ( 0 !== $start_order ) {
                return $start_order;
            }

            return strcmp(
                (string) $left['type'] .
                ':' .
                (string) $left['signal'],
                (string) $right['type'] .
                ':' .
                (string) $right['signal']
            );
        }
    );

    $occupied_ranges = array();

    foreach ( $candidates as $candidate ) {
        $start =
            (int) $candidate['start'];

        $end =
            $start +
            (int) $candidate['length'];

        $overlaps = false;

        foreach ( $occupied_ranges as $range ) {
            if (
                $start < (int) $range['end'] &&
                $end > (int) $range['start']
            ) {
                $overlaps = true;
                break;
            }
        }

        if ( $overlaps ) {
            continue;
        }

        $occupied_ranges[] = array(
            'start' =>
                $start,

            'end' =>
                $end,
        );

        $type =
            (string) $candidate['type'];

        $signal =
            (string) $candidate['signal'];

        if (
            ! in_array(
                $signal,
                $matches[ $type ],
                true
            )
        ) {
            $matches[ $type ][] =
                $signal;
        }
    }

    return $matches;
}

/**
 * Require AI to be the dominant subject of every scanned story.
 *
 * A direct AI subject must be paired with both a meaningful action
 * and technical context, unless a second independent direct signal
 * supplies that context. Ambiguous product names require at least
 * two independent technical signals.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_ai_gate(
    array $story
): array {
    $signals =
        revelations_editorial_scanner_ai_signals();

    $title = mb_strtolower(
        (string) (
            $story['title'] ?? ''
        ),
        'UTF-8'
    );

    $summary = mb_strtolower(
        (string) (
            $story['summary'] ?? ''
        ),
        'UTF-8'
    );

    /*
     * Prevent the artist Ai Weiwei from being classified as an
     * artificial-intelligence signal solely because of his name.
     */
    $title_for_matching = preg_replace(
        '~(?<![\\p{L}\\p{N}])ai\\s+weiwei'
        . '(?![\\p{L}\\p{N}])~iu',
        '',
        $title
    );

    $summary_for_matching = preg_replace(
        '~(?<![\\p{L}\\p{N}])ai\\s+weiwei'
        . '(?![\\p{L}\\p{N}])~iu',
        '',
        $summary
    );

    $title_for_matching = is_string(
        $title_for_matching
    )
        ? $title_for_matching
        : $title;

    $summary_for_matching = is_string(
        $summary_for_matching
    )
        ? $summary_for_matching
        : $summary;

    $title_matches =
        revelations_editorial_scanner_signal_matches(
            $title_for_matching,
            $signals
        );

    $summary_matches =
        revelations_editorial_scanner_signal_matches(
            $summary_for_matching,
            $signals
        );

    $direct_matches = array_values(
        array_unique(
            array_merge(
                $title_matches['direct'] ?? array(),
                $summary_matches['direct'] ?? array()
            )
        )
    );

    $contextual_matches = array_values(
        array_unique(
            array_merge(
                $title_matches['contextual'] ?? array(),
                $summary_matches['contextual'] ?? array()
            )
        )
    );

    $technical_matches = array_values(
        array_unique(
            array_merge(
                $title_matches['technical'] ?? array(),
                $summary_matches['technical'] ?? array()
            )
        )
    );

    $action_matches = array_values(
        array_unique(
            array_merge(
                $title_matches['action'] ?? array(),
                $summary_matches['action'] ?? array()
            )
        )
    );

    $direct_qualified =
        count( $direct_matches ) >= 1 &&
        count( $action_matches ) >= 1 &&
        (
            count( $technical_matches ) >= 1 ||
            count( $direct_matches ) >= 2
        );

    $contextual_qualified =
        0 === count( $direct_matches ) &&
        count( $contextual_matches ) >= 1 &&
        count( $action_matches ) >= 1 &&
        count( $technical_matches ) >= 2;

    $qualified =
        $direct_qualified ||
        $contextual_qualified;

    if ( $direct_qualified ) {
        $reason =
            'AI subject has action and context: ' .
            implode(
                ', ',
                array_slice(
                    array_merge(
                        $direct_matches,
                        $contextual_matches,
                        $action_matches,
                        $technical_matches
                    ),
                    0,
                    6
                )
            );
    } elseif ( $contextual_qualified ) {
        $reason =
            'AI product has corroborating technical context: ' .
            implode(
                ', ',
                array_slice(
                    array_merge(
                        $contextual_matches,
                        $action_matches,
                        $technical_matches
                    ),
                    0,
                    6
                )
            );
    } else {
        $reason =
            'AI lacks the action and technical context required '
            . 'to be the dominant subject.';
    }

    return array(
        'qualified' =>
            $qualified,

        'headline_matches' =>
            array_values(
                array_unique(
                    array_merge(
                        $title_matches['direct'] ?? array(),
                        $title_matches['contextual'] ?? array()
                    )
                )
            ),

        'direct_matches' =>
            $direct_matches,

        'contextual_matches' =>
            $contextual_matches,

        'technical_matches' =>
            $technical_matches,

        'action_matches' =>
            $action_matches,

        'reason' =>
            $reason,
    );
}

/**
 * Normalize text received from an RSS feed.
 */
function revelations_editorial_scanner_clean_feed_text(
    string $value,
    int $limit
): string {
    $value = html_entity_decode(
        $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $value = wp_strip_all_tags(
        $value,
        true
    );

    $value = preg_replace(
        '/\s+/u',
        ' ',
        $value
    );

    $value = is_string( $value )
        ? trim( $value )
        : '';

    return mb_substr(
        $value,
        0,
        max( 1, $limit )
    );
}

/**
 * Load duplicate keys already stored as editorial candidates.
 *
 * Duplicate detection remains global across sections to preserve
 * the current Tech scanner behaviour.
 *
 * @return array<string, true>
 */
function revelations_editorial_scanner_existing_candidate_keys(): array {
    global $wpdb;

    $values = $wpdb->get_col(
        "
        SELECT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p
            ON p.ID = pm.post_id
        WHERE p.post_type = 'rev_candidate'
          AND p.post_status NOT IN (
              'trash',
              'auto-draft'
          )
          AND pm.meta_key = '_rev_duplicate_key'
          AND pm.meta_value <> ''
        "
    );

    $keys = array();

    foreach ( $values as $value ) {
        $keys[ (string) $value ] = true;
    }

    return $keys;
}

/**
 * Convert one scored story into the common preview structure.
 *
 * @param array<string, mixed> $story Story record.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_format_story(
    array $story
): array {
    return array(
        'title' =>
            (string) (
                $story['title'] ?? ''
            ),

        'source' =>
            (string) (
                $story['source_name'] ?? ''
            ),

        'url' =>
            (string) (
                $story['source_url'] ?? ''
            ),

        'published_at' =>
            (string) (
                $story['published_at'] ?? ''
            ),

        'age_hours' =>
            $story['age_hours'] ?? null,

        'summary' =>
            (string) (
                $story['summary'] ?? ''
            ),

        'duplicate_key' =>
            (string) (
                $story['duplicate_key'] ?? ''
            ),

        'scores' => array(
            'total' =>
                (float) (
                    $story['total_score'] ?? 0
                ),

            'freshness' =>
                (float) (
                    $story['freshness_score'] ?? 0
                ),

            'implementation' =>
                (float) (
                    $story['implementation_score'] ?? 0
                ),

            'relevance' =>
                (float) (
                    $story['relevance_score'] ?? 0
                ),

            'impact' =>
                (float) (
                    $story['impact_score'] ?? 0
                ),

            'fit' =>
                (float) (
                    $story['fit_score'] ?? 0
                ),
        ),

        'qualified' =>
            true === (
                $story['qualified'] ?? false
            ),

        'editorial_track' =>
            sanitize_key(
                (string) (
                    $story['editorial_track'] ?? ''
                )
            ),

        'reason' =>
            (string) (
                $story['scoring_reason'] ?? ''
            ),
    );
}

/**
 * Run one section scanner without writing candidates or logs.
 *
 * The scoring callback must return either:
 * - a common score array; or
 * - null when the story should be filtered out.
 *
 * @param array<int, array{name:string,url:string}> $sources
 * @param array<int, array{name:string,reason:string}> $disabled_sources
 * @param callable(array<string,mixed>): ?array<string,mixed> $score_story
 * @param array<string, mixed> $thresholds
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_run_dry_run(
    string $section,
    array $sources,
    array $disabled_sources,
    callable $score_story,
    array $thresholds,
    int $qualified_limit = 10,
    int $items_per_source = 20
): array {
    require_once ABSPATH . WPINC . '/feed.php';

    $section = sanitize_key(
        $section
    );

    $qualified_limit = max(
        1,
        min( 20, $qualified_limit )
    );

    $items_per_source = max(
        1,
        min( 50, $items_per_source )
    );

    $seen_keys =
        revelations_editorial_scanner_existing_candidate_keys();

    $stories            = array();
    $source_results     = array();
    $sources_checked    = array();
    $sources_failed     = array();
    $errors             = array();
    $duplicates_removed = 0;
    $invalid_removed    = 0;
    $ai_gate_filtered   = 0;
    $hard_filtered      = 0;

    foreach ( $sources as $source ) {
        if ( ! is_array( $source ) ) {
            $invalid_removed++;
            continue;
        }

        $source_name = sanitize_text_field(
            (string) (
                $source['name'] ?? ''
            )
        );

        $feed_url = esc_url_raw(
            (string) (
                $source['url'] ?? ''
            )
        );

        if (
            '' === $source_name ||
            '' === $feed_url
        ) {
            $invalid_removed++;
            continue;
        }

        $started = microtime( true );

        $feed = fetch_feed(
            $feed_url
        );

        $duration_ms = (int) round(
            ( microtime( true ) - $started ) * 1000
        );

        if ( is_wp_error( $feed ) ) {
            $sources_failed[] =
                $source_name;

            $errors[] =
                $source_name .
                ': ' .
                $feed->get_error_message();

            $source_results[] = array(
                'source' =>
                    $source_name,

                'success' =>
                    false,

                'items' =>
                    0,

                'duration_ms' =>
                    $duration_ms,

                'error' =>
                    $feed->get_error_message(),
            );

            continue;
        }

        $sources_checked[] =
            $source_name;

        $quantity = (int) $feed->get_item_quantity(
            $items_per_source
        );

        $source_results[] = array(
            'source' =>
                $source_name,

            'success' =>
                true,

            'items' =>
                $quantity,

            'duration_ms' =>
                $duration_ms,
        );

        $items = $feed->get_items(
            0,
            $quantity
        );

        foreach ( $items as $item ) {
            $title =
                revelations_editorial_scanner_clean_feed_text(
                    (string) $item->get_title(),
                    300
                );

            $source_url = esc_url_raw(
                (string) $item->get_permalink()
            );

            if (
                '' === $title ||
                '' === $source_url
            ) {
                $invalid_removed++;
                continue;
            }

            $summary_source =
                (string) $item->get_description();

            if ( '' === $summary_source ) {
                $summary_source =
                    (string) $item->get_content();
            }

            $summary =
                revelations_editorial_scanner_clean_feed_text(
                    $summary_source,
                    600
                );

            $published_timestamp = absint(
                $item->get_date( 'U' )
            );

            $published_at =
                $published_timestamp > 0
                    ? gmdate(
                        'c',
                        $published_timestamp
                    )
                    : '';

            $duplicate_key = function_exists(
                'revelations_editorial_duplicate_key'
            )
                ? revelations_editorial_duplicate_key(
                    $source_url,
                    $title
                )
                : md5(
                    strtolower( $source_url )
                );

            if (
                isset(
                    $seen_keys[ $duplicate_key ]
                )
            ) {
                $duplicates_removed++;
                continue;
            }

            $seen_keys[ $duplicate_key ] =
                true;

            $story = array(
                'title' =>
                    $title,

                'source_url' =>
                    $source_url,

                'source_name' =>
                    $source_name,

                'published_at' =>
                    $published_at,

                'published_timestamp' =>
                    $published_timestamp,

                'summary' =>
                    $summary,

                'duplicate_key' =>
                    $duplicate_key,
            );

            $ai_gate =
                revelations_editorial_scanner_ai_gate(
                    $story
                );

            if (
                empty(
                    $ai_gate['qualified']
                )
            ) {
                $ai_gate_filtered++;
                continue;
            }

            $scores =
                call_user_func(
                    $score_story,
                    $story
                );

            if ( null === $scores ) {
                $hard_filtered++;
                continue;
            }

            $existing_reason = trim(
                (string) (
                    $scores['scoring_reason'] ?? ''
                )
            );

            $scores['scoring_reason'] =
                'AI gate: ' .
                (string) (
                    $ai_gate['reason'] ?? ''
                ) .
                (
                    '' !== $existing_reason
                        ? '. ' . $existing_reason
                        : ''
                );

            $stories[] = array_merge(
                $story,
                $scores
            );
        }
    }

    usort(
        $stories,
        static fn (
            array $left,
            array $right
        ): int =>
            (float) (
                $right['total_score'] ?? 0
            )
            <=>
            (float) (
                $left['total_score'] ?? 0
            )
    );

    $qualified = array_values(
        array_filter(
            $stories,
            static fn ( array $story ): bool =>
                true === (
                    $story['qualified'] ?? false
                )
        )
    );

    return array(
        'mode' =>
            'dry-run',

        'section' =>
            $section,

        'thresholds' =>
            $thresholds,

        'active_sources' =>
            array_values(
                array_filter(
                    array_map(
                        static fn ( $source ): string =>
                            is_array( $source )
                                ? sanitize_text_field(
                                    (string) (
                                        $source['name'] ?? ''
                                    )
                                )
                                : '',
                        $sources
                    )
                )
            ),

        'disabled_sources' =>
            $disabled_sources,

        'source_results' =>
            $source_results,

        'sources_checked' =>
            $sources_checked,

        'sources_failed' =>
            $sources_failed,

        'errors' =>
            $errors,

        'total_feed_items' =>
            array_sum(
                array_map(
                    static fn ( array $source ): int =>
                        (int) (
                            $source['items'] ?? 0
                        ),
                    $source_results
                )
            ),

        'duplicates_removed' =>
            $duplicates_removed,

        'invalid_removed' =>
            $invalid_removed,

        'ai_gate_filtered' =>
            $ai_gate_filtered,

        'hard_filtered' =>
            $hard_filtered,

        'scored_stories' =>
            count( $stories ),

        'qualified_stories' =>
            count( $qualified ),

        'top_scored' =>
            array_map(
                'revelations_editorial_scanner_format_story',
                array_slice(
                    $stories,
                    0,
                    15
                )
            ),

        'qualified_candidates' =>
            array_map(
                'revelations_editorial_scanner_format_story',
                array_slice(
                    $qualified,
                    0,
                    $qualified_limit
                )
            ),

        'candidates_created' =>
            0,

        'run_logs_created' =>
            0,
    );
}

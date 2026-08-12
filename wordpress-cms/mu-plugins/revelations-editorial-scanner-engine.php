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
 * Return strict future-tech signals for the target editorial sections.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_future_tech_signals(): array {
    return array(
        'robotics_embodied' => array(
            'humanoid robot', 'humanoid robots', 'service robot',
            'service robots', 'industrial robot', 'industrial robots',
            'embodied system', 'embodied systems', 'robotic', 'robotics',
            'robots', 'robot', 'humanoid',
        ),
        'autonomous_mobility' => array(
            'autonomous shuttle', 'autonomous transport',
            'autonomous delivery', 'autonomous vehicle',
            'autonomous vehicles', 'autonomous system',
            'autonomous systems', 'self-driving', 'driverless', 'robotaxi',
            'robotaxis', 'unmanned system', 'unmanned systems', 'autonomous',
        ),
        'smart_physical_infrastructure' => array(
            'intelligent transport system', 'intelligent infrastructure',
            'connected infrastructure', 'automated infrastructure',
            'smart building', 'smart airport', 'smart transport',
            'smart city', 'digital twin',
        ),
        'advanced_construction' => array(
            'additive construction', 'robotic construction',
            'construction robot', 'construction robots',
            'automated construction', 'autonomous construction',
            '3d-printed', '3d printed', '3d printing',
        ),
        'future_mobility' => array(
            'next-generation transport system', 'autonomous transit',
            'autonomous mobility', 'air taxi', 'evtol',
        ),
        'human_machine_environment' => array(
            'robot-operated hotel', 'robot-operated restaurant',
            'robot-operated facility', 'automated physical environment',
            'robotic service environment', 'autonomous facility',
            'intelligent physical environment',
        ),
    );
}
function revelations_editorial_scanner_future_tech_corroboration(): array {
    return array(
        'sensing_or_control' => array(
            'sensor', 'sensors', 'lidar', 'computer-controlled',
            'control system', 'control systems',
        ),
        'implementation' => array(
            'deployment', 'deployed', 'production', 'fleet',
            'infrastructure system',
        ),
    );
}

/**
 * Additional future-tech domains relevant to people and consequences.
 * They do not broaden the deployment-focused News, Places or Tech branch.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_scanner_future_tech_context_extensions(): array {
    return array(
        'advanced_computing' => array(
            'advanced computing', 'semiconductor', 'semiconductors',
            'chip architecture', 'chip architectures',
        ),
        'space_technology' => array(
            'space technology', 'space technologies', 'satellite system',
            'satellite systems', 'orbital system', 'orbital systems',
        ),
        'neurotechnology' => array(
            'brain-computer interface', 'brain computer interface', 'bci',
            'neurotechnology', 'neural implant', 'neural implants',
        ),
        'advanced_biotech' => array(
            'synthetic biology', 'gene editing', 'genome engineering',
            'biotech platform', 'biotechnology platform',
        ),
    );
}
function revelations_editorial_scanner_future_tech_gate( array $story ): array {
    $text = mb_strtolower(
        (string) ( $story['title'] ?? '' ) . ' ' .
        (string) ( $story['summary'] ?? '' ),
        'UTF-8'
    );
    $families = revelations_editorial_scanner_signal_matches(
        $text,
        array( 'future_tech' => revelations_editorial_scanner_future_tech_signals() )
    );
    if (
        preg_match( '~(?<![\\p{L}\\p{N}])additive manufacturing(?![\\p{L}\\p{N}])~iu', $text ) === 1 &&
        preg_match( '~(?<![\\p{L}\\p{N}])(building|buildings|construction|housing|homes|built environment)(?![\\p{L}\\p{N}])~iu', $text ) === 1
    ) {
        $families['future_tech'][] = 'advanced_construction';
    }
    $technical = revelations_editorial_scanner_signal_matches(
        $text,
        array( 'corroboration' => revelations_editorial_scanner_future_tech_corroboration() )
    );
    $actions = revelations_editorial_scanner_signal_matches(
        $text,
        array(
            'action' => revelations_editorial_scanner_ai_signals()['action'] + array(
                'open' => array( 'opens', 'opened' ),
                'install' => array( 'installs', 'installed' ),
                'operate' => array( 'begins operation', 'begin operation', 'operational', 'begins operating', 'begin operating' ),
                'service' => array( 'starts service', 'begin passenger service', 'begins passenger service' ),
                'complete' => array( 'completes', 'completed' ),
                'rollout' => array( 'rolls out', 'rolled out' ),
                'pilot' => array( 'begins testing', 'begin testing', 'begins pilot', 'pilot begins' ),
            ),
        )
    );
    $family_matches = array_values( array_unique( $families['future_tech'] ?? array() ) );
    $corroboration_matches = $technical['corroboration'] ?? array();
    $action_matches = $actions['action'] ?? array();
    $generic = preg_match(
        '~(?<![\p{L}\p{N}])(future|futuristic|smart|next-generation|technology|innovation)(?![\p{L}\p{N}])~iu',
        $text
    ) === 1;
    $qualified = array() !== $family_matches &&
        array() !== $action_matches &&
        array() !== $corroboration_matches;
    $code = $qualified ? '' : (
        array() === $family_matches
            ? ( $generic ? 'generic_futurism_only' : 'no_ai_or_future_tech_signal' )
            : ( array() === $action_matches ? 'future_tech_without_action' : 'future_tech_without_corroboration' )
    );

    return array(
        'qualified' => $qualified,
        'future_tech_families' => $family_matches,
        'future_tech_corroboration' => $corroboration_matches,
        'future_tech_action_matches' => $action_matches,
        'rejection_code' => $code,
        'reason' => $qualified
            ? 'Future-tech subject has action and corroboration.'
            : $code,
    );
}

/**
 * People and Unspoken require future-tech context, not a deployment event.
 * Their scorers apply the required person-significance or Unspoken-angle gate.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_future_tech_context_gate(
    array $story
): array {
    $text = mb_strtolower(
        (string) ( $story['title'] ?? '' ) . ' ' .
        (string) ( $story['summary'] ?? '' ),
        'UTF-8'
    );
    $matches = revelations_editorial_scanner_signal_matches(
        $text,
        array(
            'future_tech' => array_merge(
                revelations_editorial_scanner_future_tech_signals(),
                revelations_editorial_scanner_future_tech_context_extensions()
            ),
        )
    );
    if (
        preg_match( '~(?<![\\p{L}\\p{N}])additive manufacturing(?![\\p{L}\\p{N}])~iu', $text ) === 1 &&
        preg_match( '~(?<![\\p{L}\\p{N}])(building|buildings|construction|housing|homes|built environment)(?![\\p{L}\\p{N}])~iu', $text ) === 1
    ) {
        $matches['future_tech'][] = 'advanced_construction';
    }

    $families = array_values(
        array_unique( $matches['future_tech'] ?? array() )
    );

    return array(
        'qualified' => array() !== $families,
        'future_tech_families' => $families,
        'future_tech_corroboration' => array(),
        'future_tech_action_matches' => array(),
        'rejection_code' => array() === $families
            ? 'no_ai_or_future_tech_signal'
            : '',
        'reason' => array() !== $families
            ? 'Future-tech context is present; section criteria remain required.'
            : 'no_ai_or_future_tech_signal',
    );
}

function revelations_editorial_scanner_ai_gate(
    array $story,
    string $section = ''
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

    $rejection_code = '';

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
        if (
            array() === $direct_matches &&
            array() === $contextual_matches
        ) {
            $rejection_code =
                array() !== $technical_matches ||
                array() !== $action_matches
                    ? 'broad_signal_without_technical_context'
                    : 'no_ai_signal';
        } elseif ( array() === $action_matches ) {
            $rejection_code =
                'no_meaningful_ai_action';
        } elseif (
            array() === $direct_matches &&
            array() !== $contextual_matches
        ) {
            $rejection_code =
                'ambiguous_product_without_ai_context';
        } else {
            $rejection_code =
                'insufficient_ai_context';
        }

        $reason =
            'AI lacks the action and technical context required '
            . 'to be the dominant subject.';
    }

    $ai_result = array(
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

        'rejection_code' =>
            $rejection_code,
    );

    $section = mb_strtolower( trim( $section ), 'UTF-8' );
    if ( ! in_array( $section, array( 'news', 'places', 'tech', 'people', 'unspoken' ), true ) || ! empty( $ai_result['qualified'] ) ) {
        $ai_result['gate_branch'] = ! empty( $ai_result['qualified'] ) ? 'ai' : '';
        return $ai_result;
    }

    $future_result = in_array( $section, array( 'people', 'unspoken' ), true )
        ? revelations_editorial_scanner_future_tech_context_gate( $story )
        : revelations_editorial_scanner_future_tech_gate( $story );
    if ( ! empty( $future_result['qualified'] ) ) {
        return array_merge( $ai_result, $future_result, array( 'qualified' => true, 'gate_branch' => 'future_tech', 'rejection_code' => '', 'reason' => $future_result['reason'] ) );
    }

    $ai_result['future_tech_families'] = $future_result['future_tech_families'];
    $ai_result['future_tech_corroboration'] = $future_result['future_tech_corroboration'];
    $ai_result['future_tech_action_matches'] = $future_result['future_tech_action_matches'];
    $ai_result['gate_branch'] = '';
    if ( array() === $ai_result['direct_matches'] && array() === $ai_result['contextual_matches'] ) {
        $ai_result['rejection_code'] = $future_result['rejection_code'];
        $ai_result['reason'] = $future_result['reason'];
    }
    return $ai_result;
}

/**
 * Return the stable machine-readable rejection vocabulary.
 *
 * @return array{global_ai_gate:string[],section:string[]}
 */
function revelations_editorial_scanner_rejection_codes(): array {
    return array(
        'global_ai_gate' => array(
            'no_ai_signal',
            'insufficient_ai_context',
            'ambiguous_product_without_ai_context',
            'broad_signal_without_technical_context',
            'no_meaningful_ai_action',
            'no_ai_or_future_tech_signal',
            'future_tech_without_action',
            'future_tech_without_corroboration',
            'generic_futurism_only',
        ),
        'section' => array(
            'opinion_or_advice',
            'speculation_or_prediction',
            'promotional',
            'insufficient_section_signal',
            'insufficient_event_signal',
            'insufficient_evidence',
            'insufficient_significance',
            'insufficient_person_significance',
            'insufficient_future_tech_significance',
            'insufficient_unspoken_angle',
            'generic_negative_news',
            'planned_not_implemented',
            'person_not_central',
            'place_not_central',
            'harm_not_central',
            'allegation_without_attribution',
            'headline_only_sensationalism',
            'stale_story',
            'below_threshold',
            'unspecified_section_rejection',
        ),
    );
}

/**
 * Resolve a safe code for a section avoid-list match.
 *
 * @param string[] $matches Matched normalized phrases.
 */
function revelations_editorial_scanner_avoid_rejection_code(
    array $matches
): string {
    $opinion = array(
        'opinion:',
        'commentary:',
        'editorial:',
        'review:',
        'how to',
        'guide',
        'tips',
        'lessons for',
        'what leaders should',
        'five types',
        '5 types',
        'list of',
    );
    $promotional = array(
        'sponsored',
        'advertisement',
        'partner content',
        'press release',
        'webinar',
        'course',
    );
    $unsupported = array(
        'anonymous sources',
        'unnamed sources',
        'rumor',
        'rumour',
    );

    if ( array_intersect( $matches, $opinion ) ) {
        return 'opinion_or_advice';
    }

    if ( array_intersect( $matches, $promotional ) ) {
        return 'promotional';
    }

    if ( array_intersect( $matches, $unsupported ) ) {
        return 'allegation_without_attribution';
    }

    return 'insufficient_section_signal';
}

/**
 * Return a hard-rejection result consumed by the shared engine.
 *
 * @param array<string, int|float|null> $scores Optional scores.
 * @return array<string, mixed>
 */
function revelations_editorial_scanner_rejection(
    string $code,
    string $reason,
    array $scores = array()
): array {
    $allowed =
        revelations_editorial_scanner_rejection_codes()[
            'section'
        ];
    $code = sanitize_key( $code );

    if ( ! in_array( $code, $allowed, true ) ) {
        $code =
            'unspecified_section_rejection';
    }

    return array_replace(
        $scores,
        array(
            'qualified' => false,
            'hard_rejected' => true,
            'rejection_code' => $code,
            'rejection_reason' =>
                sanitize_text_field( $reason ),
        )
    );
}

/**
 * Increment one rejection aggregate.
 *
 * @param array<string, array<string, int>> $counts Counts.
 */
function revelations_editorial_scanner_count_rejection(
    array &$counts,
    string $scope,
    string $code
): void {
    $scope = 'global_ai_gate' === $scope
        ? 'global_ai_gate'
        : 'section';
    $allowed =
        revelations_editorial_scanner_rejection_codes()[
            $scope
        ];
    $code = sanitize_key( $code );

    if ( ! in_array( $code, $allowed, true ) ) {
        $code = 'global_ai_gate' === $scope
            ? 'insufficient_ai_context'
            : 'unspecified_section_rejection';
    }

    if ( ! isset( $counts[ $scope ] ) ) {
        $counts[ $scope ] = array();
    }

    $counts[ $scope ][ $code ] =
        (int) ( $counts[ $scope ][ $code ] ?? 0 ) + 1;
}

/**
 * Retain a bounded dry-run sample without a summary/source snapshot.
 *
 * @param array<string, array<string, array<int, array<string, mixed>>>> $samples
 * @param array<string, mixed> $story Story.
 * @param array<string, mixed> $diagnostic Diagnostic fields.
 */
function revelations_editorial_scanner_add_rejection_sample(
    array &$samples,
    string $scope,
    string $code,
    array $story,
    array $diagnostic = array()
): void {
    $scope = 'global_ai_gate' === $scope
        ? 'global_ai_gate'
        : 'section';
    $code = sanitize_key( $code );

    if (
        count(
            $samples[ $scope ][ $code ] ?? array()
        ) >= 5
    ) {
        return;
    }

    $sample = array(
        'title' => sanitize_text_field(
            (string) ( $story['title'] ?? '' )
        ),
        'source' => sanitize_text_field(
            (string) ( $story['source_name'] ?? '' )
        ),
        'url' => esc_url_raw(
            (string) ( $story['source_url'] ?? '' )
        ),
        'published_at' => sanitize_text_field(
            (string) ( $story['published_at'] ?? '' )
        ),
        'rejection_code' => $code,
    );

    foreach (
        array(
            'rejection_reason', 'direct_matches', 'contextual_matches', 'technical_matches', 'action_matches', 'future_tech_families', 'future_tech_corroboration', 'future_tech_action_matches', 'gate_branch', 'section_signals', 'section_angle_categories',
            'total_score',
            'freshness_score',
            'implementation_score',
            'relevance_score',
            'impact_score',
            'fit_score',
        ) as $key
    ) {
        if ( array_key_exists( $key, $diagnostic ) ) {
            $sample[ $key ] =
                $diagnostic[ $key ];
        }
    }

    $samples[ $scope ][ $code ][] =
        $sample;
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

        'gate_branch' => sanitize_key( (string) ( $story['gate_branch'] ?? '' ) ),
        'matched_ai_signals' => array_values( array_unique( array_merge( $story['direct_matches'] ?? array(), $story['contextual_matches'] ?? array() ) ) ),
        'matched_future_tech_families' => array_values( $story['future_tech_families'] ?? array() ),
        'action_signals' => array_values( array_unique( array_merge( $story['action_matches'] ?? array(), $story['future_tech_action_matches'] ?? array() ) ) ),
        'technical_signals' => array_values( array_unique( array_merge( $story['technical_matches'] ?? array(), $story['future_tech_corroboration'] ?? array() ) ) ),

        'section_signals' => array_values(
            is_array( $story['section_signals'] ?? null )
                ? $story['section_signals']
                : array()
        ),

        'section_angle_categories' => array_values(
            is_array( $story['section_angle_categories'] ?? null )
                ? $story['section_angle_categories']
                : array()
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

        'rejection_code' =>
            sanitize_key(
                (string) (
                    $story['rejection_code'] ?? ''
                )
            ),

        'secondary_section' =>
            sanitize_key(
                (string) (
                    $story['secondary_section'] ?? ''
                )
            ),

        'single_source_allegation' =>
            true === (
                $story[
                    'single_source_allegation'
                ] ?? false
            ),

        'requires_reputational_review' =>
            true === (
                $story[
                    'requires_reputational_review'
                ] ?? false
            ),

        'evidence_type' =>
            sanitize_key(
                (string) (
                    $story['evidence_type'] ?? ''
                )
            ),

        'evidence_signals' =>
            array_values(
                array_filter(
                    array_map(
                        'sanitize_text_field',
                        is_array(
                            $story['evidence_signals']
                            ?? null
                        )
                            ? $story[
                                'evidence_signals'
                            ]
                            : array()
                    )
                )
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
    if ( ! function_exists( 'fetch_feed' ) ) {
        require_once ABSPATH . WPINC . '/feed.php';
    }

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
    $rejection_counts   = array(
        'global_ai_gate' => array(),
        'section' => array(),
    );
    $rejection_samples  = array(
        'global_ai_gate' => array(),
        'section' => array(),
    );
    $below_threshold_scores = array();
    $closest_rejected = array();

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

                'age_hours' =>
                    $published_timestamp > 0
                        ? max(
                            0,
                            ( time() - $published_timestamp ) /
                            HOUR_IN_SECONDS
                        )
                        : null,

                'summary' =>
                    $summary,

                'duplicate_key' =>
                    $duplicate_key,
            );

            $ai_gate =
                revelations_editorial_scanner_ai_gate(
                    $story,
                    $section
                );

            if (
                empty(
                    $ai_gate['qualified']
                )
            ) {
                $ai_gate_filtered++;

                $rejection_code = sanitize_key(
                    (string) (
                        $ai_gate['rejection_code']
                        ?? 'insufficient_ai_context'
                    )
                );

                revelations_editorial_scanner_count_rejection(
                    $rejection_counts,
                    'global_ai_gate',
                    $rejection_code
                );

                revelations_editorial_scanner_add_rejection_sample(
                    $rejection_samples,
                    'global_ai_gate',
                    $rejection_code,
                    $story,
                    array(
                        'rejection_reason' =>
                            (string) (
                                $ai_gate['reason'] ?? ''
                            ),
                        'direct_matches' => $ai_gate['direct_matches'] ?? array(),
                        'contextual_matches' => $ai_gate['contextual_matches'] ?? array(),
                        'technical_matches' => $ai_gate['technical_matches'] ?? array(),
                        'action_matches' => $ai_gate['action_matches'] ?? array(),
                        'future_tech_families' => $ai_gate['future_tech_families'] ?? array(),
                        'future_tech_corroboration' => $ai_gate['future_tech_corroboration'] ?? array(),
                        'future_tech_action_matches' => $ai_gate['future_tech_action_matches'] ?? array(),
                        'gate_branch' => $ai_gate['gate_branch'] ?? '',
                    )
                );
                $closest = revelations_editorial_scanner_format_story(
                    array_merge(
                        $story,
                        $ai_gate,
                        array(
                            'scoring_reason' => (string) (
                                $ai_gate['reason'] ?? ''
                            ),
                        )
                    )
                );
                unset( $closest['summary'], $closest['duplicate_key'] );
                $closest['_diagnostic_depth'] = 1;
                $closest_rejected[] = $closest;

                continue;
            }

            $scores =
                call_user_func(
                    $score_story,
                    $story
                );

            if ( null === $scores ) {
                $hard_filtered++;

                revelations_editorial_scanner_count_rejection(
                    $rejection_counts,
                    'section',
                    'unspecified_section_rejection'
                );

                revelations_editorial_scanner_add_rejection_sample(
                    $rejection_samples,
                    'section',
                    'unspecified_section_rejection',
                    $story
                );

                continue;
            }

            if ( ! is_array( $scores ) ) {
                $hard_filtered++;

                revelations_editorial_scanner_count_rejection(
                    $rejection_counts,
                    'section',
                    'unspecified_section_rejection'
                );

                revelations_editorial_scanner_add_rejection_sample(
                    $rejection_samples,
                    'section',
                    'unspecified_section_rejection',
                    $story
                );

                continue;
            }

            if ( ! empty( $scores['hard_rejected'] ) ) {
                $hard_filtered++;

                $rejection_code = sanitize_key(
                    (string) (
                        $scores['rejection_code']
                        ?? 'unspecified_section_rejection'
                    )
                );

                revelations_editorial_scanner_count_rejection(
                    $rejection_counts,
                    'section',
                    $rejection_code
                );

                revelations_editorial_scanner_add_rejection_sample(
                    $rejection_samples,
                    'section',
                    $rejection_code,
                    $story,
                    $scores
                );

                $closest = revelations_editorial_scanner_format_story(
                    array_merge(
                        $story,
                        $ai_gate,
                        $scores,
                        array(
                            'scoring_reason' => (string) (
                                $scores['rejection_reason'] ?? ''
                            ),
                        )
                    )
                );
                unset( $closest['summary'], $closest['duplicate_key'] );
                $closest['_diagnostic_depth'] = 2;
                $closest_rejected[] = $closest;

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
            $scores['direct_matches'] = $ai_gate['direct_matches'] ?? array();
            $scores['contextual_matches'] = $ai_gate['contextual_matches'] ?? array();
            $scores['technical_matches'] = $ai_gate['technical_matches'] ?? array();
            $scores['action_matches'] = $ai_gate['action_matches'] ?? array();
            $scores['future_tech_families'] = $ai_gate['future_tech_families'] ?? array();
            $scores['future_tech_corroboration'] = $ai_gate['future_tech_corroboration'] ?? array();
            $scores['future_tech_action_matches'] = $ai_gate['future_tech_action_matches'] ?? array();
            $scores['gate_branch'] = $ai_gate['gate_branch'] ?? '';

            if ( empty( $scores['qualified'] ) ) {
                $rejection_code = sanitize_key(
                    (string) (
                        $scores['rejection_code']
                        ?? 'below_threshold'
                    )
                );

                $scores['rejection_code'] =
                    $rejection_code;

                revelations_editorial_scanner_count_rejection(
                    $rejection_counts,
                    'section',
                    $rejection_code
                );

                $below_threshold_story =
                    revelations_editorial_scanner_format_story(
                        array_merge(
                            $story,
                            $scores
                        )
                    );

                /*
                 * Diagnostics retain title/source/score, but never
                 * duplicate the RSS summary or source snapshot.
                 */
                unset(
                    $below_threshold_story['summary']
                );

                $below_threshold_scores[] =
                    $below_threshold_story;
                $below_threshold_story['_diagnostic_depth'] = 3;
                $closest_rejected[] = $below_threshold_story;
            }

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
    usort(
        $closest_rejected,
        static function ( array $left, array $right ): int {
            $depth =
                (int) ( $right['_diagnostic_depth'] ?? 0 )
                <=>(int) ( $left['_diagnostic_depth'] ?? 0 );

            return 0 !== $depth
                ? $depth
                : (float) ( $right['scores']['total'] ?? 0 )
                    <=>(float) ( $left['scores']['total'] ?? 0 );
        }
    );

    usort(
        $below_threshold_scores,
        static fn (
            array $left,
            array $right
        ): int =>
            (float) (
                $right['scores']['total'] ?? 0
            )
            <=>
            (float) (
                $left['scores']['total'] ?? 0
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

    $qualified_items = array_map(
        static function ( array $story ): array {
            $item = revelations_editorial_scanner_format_story( $story );
            unset( $item['summary'], $item['duplicate_key'] );
            return $item;
        },
        array_slice( $qualified, 0, $qualified_limit )
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

        'rejection_counts' =>
            $rejection_counts,

        'rejection_samples' =>
            $rejection_samples,

        'below_threshold_scores' =>
            array_slice(
                $below_threshold_scores,
                0,
                20
            ),

        'closest_rejected' => array_slice( $closest_rejected, 0, 10 ),

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

        'qualified_items' => $qualified_items,

        'candidates_created' =>
            0,

        'run_logs_created' =>
            0,
    );
}

<?php
/**
 * Plugin Name: REVELATIONS Editorial Unspoken Scanner
 * Description: Strict dry-run RSS scoring for documented AI harms and failures.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the configured Unspoken scanner profile.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_unspoken_profile(): array {
    return function_exists(
        'revelations_editorial_get_scanner_profile'
    )
        ? revelations_editorial_get_scanner_profile(
            'unspoken'
        )
        : array();
}

/**
 * Return active Unspoken RSS sources.
 *
 * @return array<int, array{name:string,url:string}>
 */
function revelations_editorial_unspoken_sources(): array {
    $profile =
        revelations_editorial_unspoken_profile();

    return isset( $profile['active_sources'] ) &&
        is_array( $profile['active_sources'] )
            ? array_values(
                $profile['active_sources']
            )
            : array();
}

/**
 * Return disabled Unspoken RSS sources.
 *
 * @return array<int, array{name:string,reason:string}>
 */
function revelations_editorial_unspoken_disabled_sources(): array {
    $profile =
        revelations_editorial_unspoken_profile();

    return isset( $profile['disabled_sources'] ) &&
        is_array( $profile['disabled_sources'] )
            ? array_values(
                $profile['disabled_sources']
            )
            : array();
}

/**
 * Return normalized editable Unspoken keyword groups.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_unspoken_keywords(): array {
    $profile =
        revelations_editorial_unspoken_profile();

    $keywords = isset( $profile['keywords'] ) &&
        is_array( $profile['keywords'] )
            ? $profile['keywords']
            : array();

    $result = array();

    foreach (
        array(
            'relevance',
            'implementation',
            'speculative',
            'impact',
            'product_launch',
            'avoid',
        ) as $group
    ) {
        $result[ $group ] =
            isset( $keywords[ $group ] ) &&
            is_array( $keywords[ $group ] )
                ? array_values(
                    $keywords[ $group ]
                )
                : array();
    }

    return $result;
}

/**
 * Return strict Unspoken scoring thresholds.
 *
 * @return array<string, array<string, float>>
 */
function revelations_editorial_unspoken_thresholds(): array {
    $profile =
        revelations_editorial_unspoken_profile();

    return isset( $profile['thresholds'] ) &&
        is_array( $profile['thresholds'] )
            ? $profile['thresholds']
            : array(
                'unspoken_signal' => array(
                    'total_score' => 5.2,
                    'freshness_score' => 4.0,
                    'relevance_score' => 4.0,
                    'implementation_score' => 4.0,
                    'impact_score' => 2.5,
                    'strong_relevance_score' => 6.0,
                ),
            );
}

/**
 * Find whole-word and whole-phrase matches.
 *
 * @param string[] $keywords Keywords.
 * @return string[]
 */
function revelations_editorial_unspoken_keyword_matches(
    string $text,
    array $keywords
): array {
    $matches = array();

    foreach ( $keywords as $keyword ) {
        $keyword = trim(
            mb_strtolower(
                (string) $keyword,
                'UTF-8'
            )
        );

        if ( '' === $keyword ) {
            continue;
        }

        $pattern =
            '~(?<![\p{L}\p{N}])' .
            preg_quote( $keyword, '~' ) .
            '(?![\p{L}\p{N}])~iu';

        if ( 1 === preg_match( $pattern, $text ) ) {
            $matches[] = $keyword;
        }
    }

    return array_values(
        array_unique( $matches )
    );
}

/**
 * Return canonical signal families for the five allowed tracks.
 *
 * These mandatory policy signals are not editable in Settings.
 *
 * @return array<string, string[]>
 */
function revelations_editorial_unspoken_track_signals(): array {
    return array(
        'legal_or_governance_conflict' => array(
            'lawsuit',
            'court',
            'legal filing',
            'regulator',
            'regulatory',
            'investigation',
            'complaint',
            'antitrust',
            'governance',
            'conflict of interest',
            'banned',
            'settled',
        ),

        'labor_or_social_cost' => array(
            'layoffs',
            'laid off',
            'job losses',
            'cut jobs',
            'workers',
            'labor',
            'displacement',
            'surveillance',
            'exploitation',
            'social cost',
        ),

        'economic_model_failure' => array(
            'unprofitable',
            'losses',
            'hidden cost',
            'cost overrun',
            'revenue decline',
            'business model',
            'bankruptcy',
            'financial failure',
        ),

        'failure_or_reversal' => array(
            'failure',
            'failed',
            'withdraws',
            'withdrew',
            'shut down',
            'shutdown',
            'suspended',
            'recalled',
            'rollback',
            'reversal',
            'abandoned',
            'outage',
            'cancelled',
            'canceled',
        ),

        'documented_harm' => array(
            'harm',
            'harmed',
            'bias',
            'discrimination',
            'privacy breach',
            'data breach',
            'misinformation',
            'unsafe',
            'injury',
            'rights violation',
        ),
    );
}

/**
 * Resolve one explicit editorial track without a catch-all.
 */
function revelations_editorial_unspoken_track(
    string $text
): ?string {
    foreach (
        revelations_editorial_unspoken_track_signals()
        as $track => $signals
    ) {
        if (
            array() !==
            revelations_editorial_unspoken_keyword_matches(
                $text,
                $signals
            )
        ) {
            return $track;
        }
    }

    return null;
}

/**
 * Detect the overlooked consequence that makes a story Unspoken.
 *
 * @return array{category:string,signals:string[]}
 */
function revelations_editorial_unspoken_angle( string $text ): array {
    $angles = array(
        'hidden_labor' => array(
            'hidden labor', 'manual labor', 'remote operator',
            'remote operators', 'human operator', 'human operators',
            'content moderation', 'labeling', 'supervision',
        ),
        'limitation' => array(
            'limitation', 'limitations', 'requires human intervention',
            'requires human supervision', 'manual intervention',
            'works worse', 'scaling problem', 'safety failure',
            'abandoned', 'failed deployment',
        ),
        'human_effect' => array(
            'emotional relationship', 'emotional relationships',
            'ai companion', 'ai companions', 'loneliness', 'dependency',
            'loss of skills', 'behavior change', 'behaviour change',
            'behavioral changes', 'behavioural changes', 'relationships',
            'emotional dependence', 'ai relationships', 'unexpected usage',
            'unexpected usage patterns', 'social adaptation',
        ),
        'trust_identity' => array(
            'synthetic media', 'deepfake', 'deepfakes', 'authenticity',
            'visual evidence', 'digital identity', 'no longer trust',
            'synthetic identity', 'impersonation', 'evidence erosion',
            'trust erosion', 'identity consequences',
        ),
        'privacy_control' => array(
            'brain data', 'neural data', 'mental privacy',
            'cognitive privacy', 'neural privacy', 'biometric signals',
            'neural signals', 'harvest your thoughts', 'harvesting thoughts',
            'ownership of thoughts', 'ownership of neural data',
            'control of neural data',
        ),
        'power_control' => array(
            'surveillance', 'monitoring', 'tracking', 'profiling',
            'behavioral control', 'behavioural control', 'algorithmic control',
            'manipulation', 'consent', 'ownership of personal data',
        ),
        'infrastructure_cost' => array(
            'energy demand', 'water use', 'water demand',
            'datacenter constraint', 'datacenter constraints',
            'data center constraint', 'data center constraints',
            'chip bottleneck', 'chip bottlenecks', 'physical infrastructure',
        ),
        'autonomy_gap' => array(
            'edge case', 'edge cases', 'unclear responsibility',
            'human supervision', 'remote operator', 'unexpected city behavior',
            'unexpected city behaviour',
        ),
        'second_order_effect' => array(
            'unintended use', 'unintended social use', 'unexpected behavior',
            'unexpected behaviour', 'second-order effect',
            'creates additional manual work', 'new dependency',
            'social adaptation',
        ),
    );

    foreach ( $angles as $category => $signals ) {
        $matches = revelations_editorial_unspoken_keyword_matches(
            $text,
            $signals
        );
        if ( array() !== $matches ) {
            return array( 'category' => $category, 'signals' => $matches );
        }
    }

    return array( 'category' => '', 'signals' => array() );
}

/**
 * Detect attribution/evidence suitable for preview qualification.
 *
 * Presence means the source snapshot names evidence; it does not
 * establish that the underlying claim is true.
 *
 * @return array{type:string,signals:string[]}
 */
function revelations_editorial_unspoken_evidence(
    string $raw_text
): array {
    $text = mb_strtolower(
        $raw_text,
        'UTF-8'
    );

    $types = array(
        'court_filing' => array(
            'court filing',
            'legal filing',
            'lawsuit filed',
            'court records',
        ),

        'regulator_statement' => array(
            'regulator said',
            'regulator announced',
            'regulatory filing',
            'watchdog said',
            'authority said',
        ),

        'published_research' => array(
            'published research',
            'peer-reviewed',
            'researchers found',
            'study found',
            'research report',
        ),

        'official_document' => array(
            'official document',
            'official report',
            'public filing',
            'internal memo',
            'audit report',
        ),

        'official_company_response' => array(
            'company statement',
            'company said',
            'company responded',
            'official response',
            'spokesperson said',
        ),
    );

    foreach ( $types as $type => $signals ) {
        $matches =
            revelations_editorial_unspoken_keyword_matches(
                $text,
                $signals
            );

        if ( array() !== $matches ) {
            return array(
                'type' => $type,
                'signals' => $matches,
            );
        }
    }

    $has_quote =
        1 === preg_match(
            '/(?:“[^”]{8,}”|"[^"]{8,}")/u',
            $raw_text
        );
    $has_quote_attribution =
        1 === preg_match(
            '/\b(?:said|told|stated|according to)\b/iu',
            $raw_text
        );

    if (
        $has_quote &&
        $has_quote_attribution
    ) {
        return array(
            'type' =>
                'direct_attributed_quote',
            'signals' =>
                array( 'attributed direct quote' ),
        );
    }

    $has_named_subject =
        1 === preg_match(
            '/\b(?:OpenAI|Anthropic|Google|Microsoft|Meta|' .
            'Apple|Amazon|Nvidia|xAI)\b/u',
            $raw_text
        ) ||
        1 === preg_match(
            '/\b\p{Lu}[\p{L}\p{M}.-]+\s+' .
            '(?:\p{Lu}[\p{L}\p{M}.-]+|' .
            'Inc\.?|Corp\.?|Corporation|Ltd\.?|LLC|' .
            'Company)\b/u',
            $raw_text
        );

    if ( $has_named_subject ) {
        return array(
            'type' =>
                'named_participant',
            'signals' =>
                array( 'named company or person' ),
        );
    }

    return array(
        'type' => '',
        'signals' => array(),
    );
}

/**
 * Detect whether the story contains allegation language.
 */
function revelations_editorial_unspoken_is_allegation(
    string $text
): bool {
    return array() !==
        revelations_editorial_unspoken_keyword_matches(
            $text,
            array(
                'alleged',
                'alleges',
                'allegation',
                'accused',
                'accuses',
                'claim',
                'claims',
                'complaint',
                'lawsuit',
                'fraud',
                'misled',
                'wrongdoing',
            )
        );
}

/**
 * Return a non-authoritative overlap suggestion.
 */
function revelations_editorial_unspoken_secondary_section(
    string $text
): string {
    $place_signals = array(
        'hotel',
        'restaurant',
        'museum',
        'airport',
        'hospital',
        'campus',
        'venue',
        'physical location',
    );

    if (
        array() !==
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $place_signals
        )
    ) {
        return 'places';
    }

    $people_signals = array(
        'ceo',
        'founder',
        'executive',
        'researcher',
        'director',
        'resigned',
        'steps down',
    );

    if (
        array() !==
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $people_signals
        )
    ) {
        return 'people';
    }

    $tech_signals = array(
        'model',
        'system',
        'tool',
        'software',
        'algorithm',
        'robot',
        'infrastructure',
        'benchmark',
        'capability',
    );

    if (
        array() !==
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $tech_signals
        )
    ) {
        return 'tech';
    }

    $news_signals = array(
        'announced',
        'regulator',
        'court',
        'deal',
        'investigation',
        'policy',
    );

    return array() !==
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $news_signals
        )
            ? 'news'
            : '';
}

/**
 * Return Unspoken freshness on the common 0–10 scale.
 *
 * Missing dates and stories older than seven days are rejected.
 *
 * @return array{score:float,age_hours:float|null}
 */
function revelations_editorial_unspoken_freshness(
    int $published_timestamp
): array {
    if ( $published_timestamp < 1 ) {
        return array(
            'score' => 0.0,
            'age_hours' => null,
        );
    }

    $age_hours = max(
        0,
        (
            time() -
            $published_timestamp
        ) / HOUR_IN_SECONDS
    );

    if ( $age_hours <= 24 ) {
        $score = 10.0;
    } elseif ( $age_hours <= 48 ) {
        $score = 8.0;
    } elseif ( $age_hours <= 72 ) {
        $score = 6.0;
    } elseif ( $age_hours <= 168 ) {
        $score = 4.0;
    } else {
        $score = 0.0;
    }

    return array(
        'score' => $score,
        'age_hours' => round( $age_hours, 1 ),
    );
}

/**
 * Score one Unspoken story after the shared global AI gate.
 *
 * High aggregate scores cannot compensate for a missing Unspoken angle,
 * substantive evidence or freshness gate.
 *
 * @param array<string, mixed> $story Story.
 * @return array<string, mixed>|null
 */
function revelations_editorial_score_unspoken_story(
    array $story
): ?array {
    $keywords =
        revelations_editorial_unspoken_keywords();
    $thresholds =
        revelations_editorial_unspoken_thresholds();

    $title =
        (string) ( $story['title'] ?? '' );
    $summary =
        (string) ( $story['summary'] ?? '' );
    $raw_text = trim(
        $title . ' ' . $summary
    );
    $text = mb_strtolower(
        $raw_text,
        'UTF-8'
    );

    $avoid_matches =
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $keywords['avoid']
        );

    if ( array() !== $avoid_matches ) {
        return revelations_editorial_scanner_rejection(
            revelations_editorial_scanner_avoid_rejection_code(
                $avoid_matches
            ),
            'Unspoken avoid-list rule matched.'
        );
    }

    if (
        mb_strlen( trim( $summary ), 'UTF-8' ) < 80 &&
        1 === preg_match(
            '/\b(?:shocking|nightmare|terrifying|' .
            'disaster|scandal)\b/iu',
            $title
        )
    ) {
        return revelations_editorial_scanner_rejection(
            'headline_only_sensationalism',
            'Sensational headline lacks a usable evidence summary.'
        );
    }

    $harm_matches =
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $keywords['relevance']
        );
    $event_matches =
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $keywords['implementation']
        );
    $speculative_matches =
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $keywords['speculative']
        );
    $impact_matches =
        revelations_editorial_unspoken_keyword_matches(
            $text,
            $keywords['impact']
        );

    $angle = revelations_editorial_unspoken_angle( $text );
    $track = revelations_editorial_unspoken_track( $text );
    $legacy_substantive =
        count( $harm_matches ) >= 2 ||
        count( $event_matches ) >= 2;

    $explicit_no_event =
        1 === preg_match(
            '/\b(?:no|without)\b.{0,60}\b' .
            '(?:confirmed\s+|new\s+|documented\s+)?' .
            '(?:event|evidence|filing|statement|research)\b/iu',
            $text
        );

    if (
        array() !== $speculative_matches &&
        $explicit_no_event
    ) {
        return revelations_editorial_scanner_rejection(
            'speculation_or_prediction',
            'Speculative story explicitly lacks a confirmed event.'
        );
    }

    if (
        '' === $angle['category'] &&
        ( null === $track || ! $legacy_substantive )
    ) {
        return revelations_editorial_scanner_rejection(
            array() !== $harm_matches
                ? 'generic_negative_news'
                : 'insufficient_unspoken_angle',
            'No hidden, overlooked or second-order technology angle was found.',
            array(
                'section_signals' => array_merge(
                    $harm_matches,
                    $event_matches
                ),
                'section_angle_categories' => array(),
            )
        );
    }

    $evidence =
        revelations_editorial_unspoken_evidence(
            $raw_text
        );

    if ( '' === $evidence['type'] ) {
        return revelations_editorial_scanner_rejection(
            'insufficient_evidence',
            'No attribution or evidence type was found.',
            array(
                'section_signals' => $angle['signals'],
                'section_angle_categories' => array_filter(
                    array( $angle['category'] )
                ),
            )
        );
    }

    $is_allegation =
        revelations_editorial_unspoken_is_allegation(
            $text
        );

    /*
     * Allegations remain preview-only and require one of the explicit
     * evidence types above. Anonymous source phrases were rejected by
     * the hard avoid gate before scoring.
     */
    if (
        $is_allegation &&
        array() === $evidence['signals']
    ) {
        return revelations_editorial_scanner_rejection(
            'allegation_without_attribution',
            'Allegation lacks an approved attribution signal.'
        );
    }

    $freshness =
        revelations_editorial_unspoken_freshness(
            absint(
                $story['published_timestamp'] ?? 0
            )
        );
    $freshness_score =
        (float) $freshness['score'];

    if ( $freshness_score <= 0 ) {
        return revelations_editorial_scanner_rejection(
            'stale_story',
            'Story is outside the Unspoken freshness window.',
            array(
                'freshness_score' =>
                    round( $freshness_score, 1 ),
            )
        );
    }

    $relevance_score = min(
        10,
        4 + count( $angle['signals'] ) * 2 +
        max( 0, count( $harm_matches ) - 1 )
    );

    $implementation_score = min(
        10,
        max(
            0,
            4 +
            max(
                0,
                count( $event_matches ) - 1
            ) * 2 -
            count( $speculative_matches ) * 1.5
        )
    );

    $impact_score = min(
        10,
        count( $impact_matches ) * 2.5
    );

    $fit_score = 10.0;

    $total_score = (
        $freshness_score +
        $relevance_score * 2 +
        $implementation_score * 1.5 +
        $impact_score * 1.2 +
        $fit_score
    ) / 6.7;

    $unspoken_thresholds = isset(
        $thresholds['unspoken_signal']
    ) && is_array(
        $thresholds['unspoken_signal']
    )
        ? $thresholds['unspoken_signal']
        : array();

    $base_qualified =
        $total_score >= (float) (
            $unspoken_thresholds['total_score']
            ?? 5.2
        ) &&
        $freshness_score >= (float) (
            $unspoken_thresholds['freshness_score']
            ?? 4.0
        ) &&
        $relevance_score >= (float) (
            $unspoken_thresholds['relevance_score']
            ?? 4.0
        ) &&
        $implementation_score >= (float) (
            $unspoken_thresholds[
                'implementation_score'
            ] ?? 4.0
        );

    $significance_qualified =
        $impact_score >= (float) (
            $unspoken_thresholds['impact_score']
            ?? 2.5
        ) ||
        $relevance_score >= (float) (
            $unspoken_thresholds[
                'strong_relevance_score'
            ] ?? 6.0
        ) ||
        $implementation_score >= 6.0;

    $qualified =
        $base_qualified &&
        $significance_qualified;

    $reasons = array(
        'Unspoken angle: ' .
            str_replace( '_', ' ', $angle['category'] ?: (string) $track ),
        'Angle signals: ' .
            implode( ', ', array_slice( $angle['signals'], 0, 4 ) ),
        'Legacy harm/failure signals: ' .
            implode(
                ', ',
                array_slice(
                    $harm_matches,
                    0,
                    4
                )
            ),
        'Confirmed event signals: ' .
            implode(
                ', ',
                array_slice(
                    $event_matches,
                    0,
                    3
                )
            ),
        'Evidence type: ' .
            str_replace(
                '_',
                ' ',
                $evidence['type']
            ),
    );

    if ( array() !== $speculative_matches ) {
        $reasons[] =
            'Speculative signals penalized: ' .
            count( $speculative_matches );
    }

    if ( $is_allegation ) {
        $reasons[] =
            'Single-source allegation';
        $reasons[] =
            'Requires reputational review';
    }

    return array(
        'freshness_score' =>
            round( $freshness_score, 1 ),
        'implementation_score' =>
            round( $implementation_score, 1 ),
        'relevance_score' =>
            round( $relevance_score, 1 ),
        'impact_score' =>
            round( $impact_score, 1 ),
        'fit_score' =>
            $fit_score,
        'total_score' =>
            round( $total_score, 1 ),
        'age_hours' =>
            $freshness['age_hours'],
        'qualified' =>
            $qualified,
        'rejection_code' =>
            $qualified
                ? ''
                : (
                    $base_qualified &&
                    ! $significance_qualified
                        ? 'insufficient_significance'
                        : 'below_threshold'
                ),
        'editorial_track' =>
            $qualified
                ? ( $track ?? $angle['category'] )
                : null,
        'secondary_section' =>
            $qualified
                ? revelations_editorial_unspoken_secondary_section(
                    $text
                )
                : '',
        'single_source_allegation' =>
            $qualified && $is_allegation,
        'requires_reputational_review' =>
            $qualified && $is_allegation,
        'evidence_type' =>
            $evidence['type'],
        'evidence_signals' =>
            $evidence['signals'],
        'scoring_reason' =>
            implode( '. ', $reasons ),

        'section_signals' => array_values(
            array_unique(
                array_merge(
                    $angle['signals'],
                    $harm_matches,
                    $event_matches
                )
            )
        ),

        'section_angle_categories' => array_filter(
            array( $angle['category'] )
        ),
    );
}

/**
 * Run Unspoken scanning through the shared engine without writes.
 *
 * @return array<string, mixed>
 */
function revelations_editorial_unspoken_scan_dry_run(
    int $qualified_limit = 5
): array {
    if (
        ! function_exists(
            'revelations_editorial_scanner_run_dry_run'
        )
    ) {
        return array(
            'mode' => 'dry-run',
            'section' => 'unspoken',
            'thresholds' =>
                revelations_editorial_unspoken_thresholds(),
            'active_sources' =>
                array_column(
                    revelations_editorial_unspoken_sources(),
                    'name'
                ),
            'disabled_sources' =>
                revelations_editorial_unspoken_disabled_sources(),
            'source_results' => array(),
            'sources_checked' => array(),
            'sources_failed' =>
                array_column(
                    revelations_editorial_unspoken_sources(),
                    'name'
                ),
            'errors' => array(
                'Generic scanner engine is unavailable.',
            ),
            'total_feed_items' => 0,
            'duplicates_removed' => 0,
            'invalid_removed' => 0,
            'ai_gate_filtered' => 0,
            'hard_filtered' => 0,
            'rejection_counts' => array(
                'global_ai_gate' => array(),
                'section' => array(),
            ),
            'rejection_samples' => array(
                'global_ai_gate' => array(),
                'section' => array(),
            ),
            'below_threshold_scores' => array(),
            'scored_stories' => 0,
            'qualified_stories' => 0,
            'top_scored' => array(),
            'qualified_candidates' => array(),
            'candidates_created' => 0,
            'run_logs_created' => 0,
        );
    }

    return revelations_editorial_scanner_run_dry_run(
        'unspoken',
        revelations_editorial_unspoken_sources(),
        revelations_editorial_unspoken_disabled_sources(),
        'revelations_editorial_score_unspoken_story',
        revelations_editorial_unspoken_thresholds(),
        $qualified_limit,
        20
    );
}

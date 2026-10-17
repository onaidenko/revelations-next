<?php
/**
 * Plugin Name: REVELATIONS Editorial AI Generation Profiles
 * Description: Defines section-specific editorial instructions for AI generation.
 * Version: 0.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pure registry of supported AI generation sections.
 *
 * @return array<string, array<string, mixed>>
 */
function revelations_editorial_ai_generation_profiles(): array {
    return array(
        'news' => array(
            'label' => 'News',
            'instructions' =>
                'Cover a significant general AI-industry news event, ' .
                'such as an announcement, deal, investment, launch or ' .
                'other consequential industry development.',
        ),

        'tech' => array(
            'label' => 'Tech',
            'instructions' =>
                'Focus on a concrete technology, technical system, model, ' .
                'infrastructure, robotics development or verified technical ' .
                'implementation.',
        ),

        'people' => array(
            'label' => 'People',
            'instructions' =>
                'Center the article on a specific person and that person\'s ' .
                'decision, action, statement or demonstrated influence on ' .
                'the industry.',
        ),

        'places' => array(
            'label' => 'Places',
            'instructions' =>
                'Cover a physical place where AI or related technology is ' .
                'already implemented and affects the space\'s operation or ' .
                'user experience. Plans and concepts do not qualify.',
        ),

        'art' => array(
            'label' => 'Art',
            'minimum_article_words' => 250,
            'preferred_minimum_article_words' => 350,
            'instructions' =>
                'Cover meaningful developments in visual and contemporary art, ' .
                'artistic practice, exhibitions, institutions, installations, ' .
                'art markets when artistically significant, and digital, ' .
                'immersive or AI-enabled art. Treat the artwork, artist, ' .
                'artistic practice, institution or cultural development as the ' .
                'central subject. Technology may be central when it materially ' .
                'changes artistic practice or experience, but technology is not ' .
                'required. Avoid commercial announcements, event listings, ' .
                'celebrity culture and generic technology reporting.',
        ),

        'unspoken' => array(
            'label' => 'Unspoken',
            'instructions' =>
                'Cover the overlooked layer of an AI or future-tech story: ' .
                'second-order effects, hidden labor, limits, autonomy gaps, ' .
                'power or control dynamics, trust and identity questions, ' .
                'or infrastructure costs. It is not a harm-or-scandal desk. ' .
                'Use especially careful attribution and strict fact-checking.',
        ),
    );
}

/**
 * Resolve the profile for an exact source section, without a fallback.
 *
 * @return array<string, mixed>|null
 */
function revelations_editorial_ai_generation_profile(
    string $source_section
): ?array {
    $profiles =
        revelations_editorial_ai_generation_profiles();

    return $profiles[ $source_section ] ?? null;
}

/**
 * Resolve the hard and preferred article lengths for one selected profile.
 *
 * Existing sections retain the global settings-derived limits. Art alone
 * accepts concise, evidence-complete articles while retaining a 350-word
 * preferred floor when the material supports it.
 *
 * @param array<string, mixed> $profile Section profile.
 * @param array<string, mixed> $settings Editorial settings.
 * @return array{minimum: int, maximum: int, preferred_minimum: int, preferred_maximum: int}
 */
function revelations_editorial_ai_generation_word_limits(
    array $profile,
    array $settings
): array {
    $positive_integer = static function ( mixed $value ): int {
        return abs( (int) $value );
    };

    $configured_minimum = max(
        100,
        $positive_integer( $settings['article_length_min'] ?? 350 )
    );
    $minimum = max(
        100,
        $positive_integer(
            $profile['minimum_article_words']
            ?? $configured_minimum
        )
    );
    $maximum = max(
        $minimum,
        $positive_integer( $settings['article_length_max'] ?? 600 )
    );
    $preferred_minimum = isset(
        $profile['preferred_minimum_article_words']
    )
        ? min(
            $maximum,
            max(
                $minimum,
                $positive_integer(
                    $profile['preferred_minimum_article_words']
                )
            )
        )
        : min( $maximum, $minimum + 75 );

    return array(
        'minimum' => $minimum,
        'maximum' => $maximum,
        'preferred_minimum' => $preferred_minimum,
        'preferred_maximum' => max(
            $preferred_minimum,
            $maximum - 100
        ),
    );
}

/**
 * Check the server-enforced minimum for generated article body words.
 *
 * @param array{minimum: int, maximum: int, preferred_minimum: int, preferred_maximum: int} $word_limits
 */
function revelations_editorial_ai_generation_word_count_is_valid(
    int $word_count,
    array $word_limits
): bool {
    return $word_count >= $word_limits['minimum'];
}

/**
 * Format the selected profile for the generation prompt.
 *
 * @param array<string, mixed> $profile Section profile.
 */
function revelations_editorial_ai_generation_profile_prompt(
    string $source_section,
    array $profile
): string {
    return
        "Assigned REVELATIONS section: " .
        $source_section .
        "\nSection profile (" .
        $profile['label'] .
        "):\n" .
        $profile['instructions'] .
        "\n\nThe section profile supplements the global editorial " .
        "policy, tone, structure, banned-phrase and factual-safety rules. " .
        "The assigned source section is immutable server context and is " .
        "not part of the model output. Do not attempt to change it. " .
        "If the material clearly belongs elsewhere, use only the advisory " .
        "section mismatch fields.";
}

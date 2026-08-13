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
 * @return array<string, array<string, string>>
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
 * @return array<string, string>|null
 */
function revelations_editorial_ai_generation_profile(
    string $source_section
): ?array {
    $profiles =
        revelations_editorial_ai_generation_profiles();

    return $profiles[ $source_section ] ?? null;
}

/**
 * Format the selected profile for the generation prompt.
 *
 * @param array<string, string> $profile Section profile.
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

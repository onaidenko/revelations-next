<?php
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function add_action( string $hook, callable $callback ): void {}
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-ai-generation-profiles.php';
require_once dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-ai-generate.php';
$art_profile = revelations_editorial_ai_generation_profile( 'art' );
$art_prompt = is_array( $art_profile ) ? revelations_editorial_ai_generation_profile_prompt( 'art', $art_profile ) . "\n\n" . revelations_editorial_ai_final_prompt_section_context( 'art', $art_profile ) : '';
$legacy_guidance = 'Name the actual subject - company, technology, scientific development, policy, person, or system - and state concretely what it does, changes, reveals, or means, using concrete nouns and verbs.';
$art_guidance = 'artwork, artist, artistic practice, exhibition, museum, institution, or cultural development';
$generation_source = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-ai-generate.php' );
$ok = '' !== $art_prompt && str_contains( $art_prompt, 'Section profile (Art)' ) && str_contains( $art_prompt, $art_guidance ) && is_string( $generation_source ) && str_contains( $generation_source, '$revelation_subject_guidance . \' \' .' );
foreach ( array( 'news', 'people', 'places', 'tech', 'unspoken' ) as $section ) {
    $profile = revelations_editorial_ai_generation_profile( $section );
    $prompt = is_array( $profile ) ? revelations_editorial_ai_final_prompt_section_context( $section, $profile ) : '';
    $ok = $ok && str_contains( $prompt, $legacy_guidance ) && ! str_contains( $prompt, $art_guidance );
}
echo $ok ? "Art revelation prompt: passed\n" : "Art revelation prompt: failed\n";
exit( $ok ? 0 : 1 );

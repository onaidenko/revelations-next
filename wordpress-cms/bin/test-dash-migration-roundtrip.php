<?php
declare(strict_types=1);
require __DIR__ . '/revelations-dash-migration-lib.php';

$fixtures = array(
    'preload-and-void-tags' => '<!-- wp:html --><link rel="preload" href="https://cdn.example/a—b.jpg"><img src="https://cdn.example/a–b.jpg"/><img src="x" /><br/><br /><p>Founder—investor and future-facing.</p><!-- /wp:html -->',
    'gutenberg-quotes' => '<!-- wp:paragraph {"note":"keep—exact"} -->\n<p>“Direct—quotation,” founder\'s single \'quote\' and "double quotes".</p>\n<!-- /wp:paragraph -->',
    'nested-backslashes' => '<blockquote><p>Nested <strong>answer – with</strong> backslash \\ and AI-powered work.</p></blockquote><code>const x = "a—b";</code>',
);
$passed = 0;
foreach ($fixtures as $name => $before) {
    $result = revelations_dash_normalize($before, true);
    $invariant = revelations_dash_assert_invariant($before, $result);
    if (hash('sha256', $result['after']) !== hash('sha256', revelations_dash_normalize($before, true)['after'])) {
        throw new RuntimeException("{$name}: stored/read SHA-256 mismatch");
    }
    if ($result['after'] === $before || $invariant === '') throw new RuntimeException("{$name}: no effective fixture coverage");
    $passed++;
}
if (!str_contains($fixtures['preload-and-void-tags'], '<link rel="preload"')) throw new RuntimeException('Missing preload fixture');
if (!str_contains(revelations_dash_normalize($fixtures['preload-and-void-tags'], true)['after'], 'href="https://cdn.example/a—b.jpg"')) throw new RuntimeException('URL attribute changed');
if (!str_contains(revelations_dash_normalize($fixtures['gutenberg-quotes'], true)['after'], 'note":"keep—exact"')) throw new RuntimeException('Gutenberg comment changed');
if (!str_contains(revelations_dash_normalize($fixtures['nested-backslashes'], true)['after'], '<code>const x = "a—b";</code>')) throw new RuntimeException('Code changed');
echo json_encode(array('passed' => $passed, 'failed' => 0, 'fixtures' => array_keys($fixtures)), JSON_UNESCAPED_SLASHES), "\n";

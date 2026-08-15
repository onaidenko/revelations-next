<?php
require_once __DIR__ . '/import-editorial-taxonomy.php';

$passed = 0; $failed = 0;
function revelations_taxonomy_check( bool $condition, string $label ): void { global $passed, $failed; if ( $condition ) { $passed++; echo "PASS: $label\n"; } else { $failed++; echo "FAIL: $label\n"; } }

$map = revelations_taxonomy_import_load( dirname( __DIR__, 2 ) . '/data/seo/editorial-taxonomy-v2.json' );
$plan = revelations_taxonomy_import_plan( $map );
revelations_taxonomy_check( $plan['valid'], 'approved map dry-run plan is valid' );
revelations_taxonomy_check( 45 === $plan['assignments'] && 9 === $plan['topics'] && 4 === $plan['series'] && 5 === $plan['manual_sources'], 'approved map has expected totals' );
$invalid = $map; $invalid['assignments'][0]['primary_topic'] = 'missing';
revelations_taxonomy_check( ! revelations_taxonomy_import_plan( $invalid )['valid'], 'invalid topic blocks plan' );
$missing = revelations_taxonomy_import_plan( $map, array( 'only-one' => 1 ) );
revelations_taxonomy_check( ! $missing['valid'] && count( $missing['missing_slugs'] ) === 45, 'missing slugs block plan before writes' );
$source = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-taxonomy.php' );
$api = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-public-api.php' );
revelations_taxonomy_check( false !== strpos( $source, "wp_verify_nonce" ) && false !== strpos( $source, "current_user_can( 'edit_post'" ) && false !== strpos( $source, 'wp_is_post_revision' ), 'admin save has nonce capability and revision guards' );
revelations_taxonomy_check( false !== strpos( $source, "3 === count( \$ids )" ) && false !== strpos( $source, "\$id !== \$post_id" ), 'manual related save caps at three and excludes self' );
revelations_taxonomy_check( false !== strpos( $api, "'primary_topic'") && false !== strpos( $api, "'manual_related'") && false !== strpos( $api, "'taxonomy_status'" ), 'public API exposes additive taxonomy fields' );
revelations_taxonomy_check( false !== strpos( $source, "'publish' !== \$candidate->post_status" ), 'manual related API helper is published-only' );
echo "Editorial taxonomy diagnostics: $passed passed, $failed failed, " . ( $passed + $failed ) . " total.\n";
exit( $failed ? 1 : 0 );

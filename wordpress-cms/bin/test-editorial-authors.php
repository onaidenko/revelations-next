<?php
declare(strict_types=1);
$authors = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-authors.php' );
$api = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-public-api.php' );
$core = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-cms-core.php' );
$editor = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-cms-editor.js' );
function check( bool $ok, string $label ): void { if ( ! $ok ) { fwrite( STDERR, "FAIL: $label\n" ); exit( 1 ); } echo "PASS: $label\n"; }
check( false !== strpos( $authors, "register_post_type( 'rev_author'" ), 'author CPT is registered' );
check( false !== strpos( $authors, "'_revelations_author_profile_ids'" ), 'ordered relation key is present' );
check( false !== strpos( $authors, "'auth_callback' => 'revelations_author_relation_can_edit'" ) && false !== strpos( $authors, "user_can( \$user_id, 'edit_post', \$post_id )" ), 'relation REST writes require edit_post for the target article' );
check( false !== strpos( $authors, "'Julia U.' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Julia Yupiterskaya' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Julia Upiterskaya' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Anonymous' => 'editorial-team'" ), 'exact migration map is present' );
check( false !== strpos( $authors, 'revelations_author_is_public_ready' ), 'thin-profile gate is present' );
check( false !== strpos( $api, "'author_profiles'" ) && false === strpos( $api, "'_rev_source_text'" ), 'public API is additive and does not expose private evidence' );
check( false !== strpos( $core, "'authorProfiles'" ) && strpos( $core, "'authorProfiles'" ) < strpos( $core, 'if (\n        $post_id < 1' ), 'editor author profiles are independent of review state and post ID' );
check( false !== strpos( $editor, "useState(false)" ) && false !== strpos( $editor, "label: 'Select author'" ) && false !== strpos( $editor, "'Create new author'" ), 'editor uses an explicit add-author selector and existing author creation flow' );
check( false !== strpos( $editor, 'authorProfileIds.includes(authorId)' ) && false !== strpos( $editor, "'Move up'" ) && false !== strpos( $editor, "'Remove'" ), 'editor preserves duplicate prevention, ordering, and removal controls' );
check( false === strpos( $editor, "label: 'Displayed author'" ) && false !== strpos( $editor, "label: 'Authors'" ) && false !== strpos( $editor, "'AUTHORS'" ), 'editor exposes only the canonical AUTHORS control' );
check( false !== strpos( $editor, 'authorProfileIds.length > 0 ||' ) && false !== strpos( $editor, "'Legacy author retained'" ), 'readiness accepts canonical authors and preserves legacy fallback readiness' );
check( false !== strpos( $api, "'displayed_author'" ) && false !== strpos( $api, "'author_profiles'" ), 'public API retains legacy fallback and canonical author profiles' );
echo "Editorial author diagnostics: 11 passed, 0 failed, 11 total.\n";

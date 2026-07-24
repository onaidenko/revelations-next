<?php
declare(strict_types=1);
$authors = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-authors.php' );
$api = file_get_contents( dirname( __DIR__ ) . '/mu-plugins/revelations-public-api.php' );
function check( bool $ok, string $label ): void { if ( ! $ok ) { fwrite( STDERR, "FAIL: $label\n" ); exit( 1 ); } echo "PASS: $label\n"; }
check( false !== strpos( $authors, "register_post_type( 'rev_author'" ), 'author CPT is registered' );
check( false !== strpos( $authors, "'_revelations_author_profile_ids'" ), 'ordered relation key is present' );
check( false !== strpos( $authors, "'Julia U.' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Julia Yupiterskaya' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Julia Upiterskaya' => 'julia-upiterskaya'" ) && false !== strpos( $authors, "'Anonymous' => 'editorial-team'" ), 'exact migration map is present' );
check( false !== strpos( $authors, 'revelations_author_is_public_ready' ), 'thin-profile gate is present' );
check( false !== strpos( $api, "'author_profiles'" ) && false === strpos( $api, "'_rev_source_text'" ), 'public API is additive and does not expose private evidence' );
echo "Editorial author diagnostics: 5 passed, 0 failed, 5 total.\n";

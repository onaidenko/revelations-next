<?php
declare(strict_types=1);

define( 'ABSPATH', __DIR__ );
$editable_posts = array( 42 );

function add_action( string $hook, callable $callback ): void {}
function user_can( int $user_id, string $capability, int $post_id ): bool {
    global $editable_posts;
    return 7 === $user_id && 'edit_post' === $capability && in_array( $post_id, $editable_posts, true );
}

require dirname( __DIR__ ) . '/mu-plugins/revelations-editorial-authors.php';

function check( bool $ok, string $label ): void {
    if ( ! $ok ) {
        fwrite( STDERR, "FAIL: {$label}\n" );
        exit( 1 );
    }
    echo "PASS: {$label}\n";
}

check( revelations_author_relation_can_edit( false, REVELATIONS_AUTHOR_RELATION, 42, 7 ), 'editor with edit_post may update the relation' );
check( ! revelations_author_relation_can_edit( false, REVELATIONS_AUTHOR_RELATION, 42, 8 ), 'user without edit_post may not update the relation' );
check( ! revelations_author_relation_can_edit( false, 'unrelated_meta', 42, 7 ), 'callback does not authorize another meta key' );
echo "Editorial author permission diagnostics: 3 passed, 0 failed, 3 total.\n";

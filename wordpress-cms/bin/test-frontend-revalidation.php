<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define(
    'REVELATIONS_REVALIDATION_URL',
    'https://example.test/api/revalidate'
);
define(
    'REVELATIONS_REVALIDATION_SECRET',
    'diagnostic-only-secret'
);

$hooks = array();
$posts = array();
$categories = array();
$taxonomies = array();
$meta = array();
$requests = array();
$mode = 'ok';
$revision = array();
$autosave = array();
$json_fail = false;
$n = 0;

class WP_Post {
    public function __construct(
        public int $ID,
        public string $post_status,
        public string $post_name,
        public string $post_type = 'post'
    ) {}
}

class WP_Error {}

function add_action( $hook, $callback, $priority = 10, $args = 1 ): void {
    global $hooks;
    $hooks[ $hook ] = array( $callback, $priority, $args );
}

function get_post( $id ) {
    global $posts;
    return $posts[ $id ] ?? null;
}

function wp_get_post_categories( $id, $args = array() ) {
    global $categories;
    return $categories[ $id ] ?? array();
}

function wp_get_post_terms( $id, $taxonomy, $args = array() ) {
    global $taxonomies;
    return $taxonomies[ $id ][ $taxonomy ] ?? array();
}

function get_post_meta( $id, $key, $single = false ) {
    global $meta;
    return $meta[ $id ][ $key ] ?? '';
}

function revelations_editorial_sections(): array {
    return array(
        'news',
        'people',
        'tech',
        'places',
        'unspoken',
        'podcast',
    );
}

function wp_is_post_revision( $id ): bool {
    global $revision;
    return ! empty( $revision[ $id ] );
}

function wp_is_post_autosave( $id ): bool {
    global $autosave;
    return ! empty( $autosave[ $id ] );
}

function wp_parse_url( $url ) {
    return parse_url( $url );
}

function wp_json_encode( $value ) {
    global $json_fail;
    return $json_fail ? false : json_encode( $value );
}

function wp_generate_uuid4(): string {
    static $i = 0;
    return 'uuid-' . ++$i;
}

function is_wp_error( $value ): bool {
    return $value instanceof WP_Error;
}

function wp_remote_retrieve_response_code( $value ): int {
    return $value['response']['code'] ?? 0;
}

function wp_remote_post( $url, $args ) {
    global $requests, $mode;
    $requests[] = array( $url, $args );

    return 'error' === $mode
        ? new WP_Error()
        : array(
            'response' => array(
                'code' => '500' === $mode ? 500 : 200,
            ),
        );
}

require __DIR__ .
    '/../mu-plugins/revelations-frontend-revalidation.php';

function check( bool $value, string $name ): void {
    global $n;
    $n++;

    if ( ! $value ) {
        fwrite( STDERR, "FAIL: $name\n" );
        exit( 1 );
    }

    echo "PASS: $name\n";
}

function payload(): array {
    global $requests;

    return json_decode(
        $requests[ count( $requests ) - 1 ][1]['body'],
        true
    );
}

function save(
    int $id,
    string $old_status,
    string $new_status,
    string $old_slug,
    string $new_slug,
    string $old_section,
    string $new_section,
    bool $snapshot = true
): array {
    global $posts, $categories, $hooks;

    $posts[ $id ] = new WP_Post(
        $id,
        $old_status,
        $old_slug
    );
    $categories[ $id ] = array( $old_section );

    if ( $snapshot ) {
        $hooks['pre_post_update'][0]( $id, array() );
    }

    $posts[ $id ] = new WP_Post(
        $id,
        $new_status,
        $new_slug
    );
    $categories[ $id ] = array( $new_section );

    $hooks['wp_after_insert_post'][0](
        $id,
        $posts[ $id ],
        true,
        new WP_Post(
            $id,
            $old_status,
            $old_slug
        )
    );

    return payload();
}

check(
    $hooks['pre_post_update'][2] === 2 &&
    $hooks['wp_after_insert_post'][2] === 4 &&
    $hooks['before_delete_post'][2] === 2,
    'hook registrations'
);

foreach (
    array(
        null,
        array(),
        '',
        ' ',
        'http://x.test',
        '/x',
        'https://u@x.test',
        'https://:p@x.test',
        'https://x.test/#f',
    ) as $value
) {
    check(
        null === revelations_frontend_revalidation_validate_config(
            $value,
            'x'
        ),
        'config rejects unsafe input'
    );
}

check(
    array(
        'url'    => 'https://x.test',
        'secret' => 'x',
    ) === revelations_frontend_revalidation_validate_config(
        ' https://x.test ',
        ' x '
    ),
    'config valid trimmed HTTPS'
);

$before = count( $requests );
$posts[1] = new WP_Post( 1, 'draft', 'draft' );
$categories[1] = array( 'news' );
$hooks['wp_after_insert_post'][0](
    1,
    $posts[1],
    false,
    null
);
check(
    $before === count( $requests ),
    'draft skipped'
);

$p = save(
    2,
    'draft',
    'publish',
    'old',
    'new',
    'news',
    'tech'
);
check(
    'publish' === $p['action'] &&
    null === $p['old_slug'] &&
    'new' === $p['new_slug'],
    'draft publish'
);

$posts[3] = new WP_Post(
    3,
    'publish',
    'direct'
);
$categories[3] = array( 'places' );
$hooks['wp_after_insert_post'][0](
    3,
    $posts[3],
    false,
    null
);
$p = payload();
check(
    'publish' === $p['action'] &&
    null === $p['old_section'] &&
    'places' === $p['new_section'],
    'direct publish'
);

$p = save(
    4,
    'publish',
    'publish',
    'old',
    'new',
    'news',
    'news'
);
check(
    'update' === $p['action'] &&
    'old' === $p['old_slug'] &&
    'new' === $p['new_slug'],
    'slug-only update'
);

$p = save(
    5,
    'publish',
    'publish',
    'same',
    'same',
    'news',
    'tech'
);
check(
    'news' === $p['old_section'] &&
    'tech' === $p['new_section'],
    'section-only update'
);

foreach (
    array(
        'draft',
        'private',
        'pending',
        'future',
        'trash',
    ) as $status
) {
    $p = save(
        10 + strlen( $status ),
        'publish',
        $status,
        'old',
        'gone',
        'news',
        'tech'
    );
    check(
        'unpublish' === $p['action'] &&
        null === $p['new_slug'],
        'publish unpublish'
    );
}

$posts[30] = new WP_Post(
    30,
    'publish',
    'delete'
);
$categories[30] = array( 'news' );
$hooks['before_delete_post'][0](
    30,
    $posts[30]
);
$p = payload();
check(
    'delete' === $p['action'] &&
    'delete' === $p['new_status'] &&
    null === $p['new_slug'],
    'direct delete'
);

$posts[90] = new WP_Post(
    90,
    'publish',
    'taxonomy-story'
);
$categories[90] = array( 'news' );
$taxonomies[90] = array(
    'revelations_topic' => array( 'ai-data' ),
    'revelations_series' => array( 'future-files' ),
    'revelations_location' => array( 'dubai-uae' ),
    'post_tag' => array(
        'openai',
        'dolce-gabbana',
    ),
);
$meta[90] = array(
    '_revelations_public_topic_eligible' => '1',
    '_revelations_taxonomy_status' => 'approved',
);
$hooks['wp_after_insert_post'][0](
    90,
    $posts[90],
    false,
    null
);
$p = payload();
check(
    $p['new_taxonomy_paths'] === array(
        '/locations/dubai-uae',
        '/series/future-files',
        '/tags/dolce-gabbana',
        '/tags/openai',
        '/topics/ai-data',
    ),
    'new taxonomy paths are canonical and sorted'
);

$hooks['pre_post_update'][0]( 90, array() );
$taxonomies[90]['post_tag'] = array( 'openai' );
$hooks['wp_after_insert_post'][0](
    90,
    $posts[90],
    true,
    $posts[90]
);
$p = payload();
check(
    in_array(
        '/tags/dolce-gabbana',
        $p['old_taxonomy_paths'],
        true
    ) &&
    ! in_array(
        '/tags/dolce-gabbana',
        $p['new_taxonomy_paths'],
        true
    ),
    'old and new taxonomy paths preserve changes'
);

$meta[91] = array(
    '_revelations_public_topic_eligible' => '0',
    '_revelations_taxonomy_status' => 'needs-editorial-review',
);
$taxonomies[91] = array(
    'revelations_topic' => array( 'ai-data' ),
);
check(
    array() === revelations_frontend_revalidation_taxonomy_paths(
        91
    ),
    'review-required topic path is excluded'
);

$base = array(
    'version' => 1,
    'event_id' => 'd',
    'post_id' => 80,
    'post_type' => 'post',
    'action' => 'update',
    'old_status' => 'publish',
    'new_status' => 'publish',
    'old_slug' => 'a',
    'new_slug' => 'b',
    'old_section' => 'news',
    'new_section' => 'tech',
    'old_taxonomy_paths' => array(
        '/tags/openai',
    ),
    'new_taxonomy_paths' => array(
        '/topics/ai-data',
    ),
    'occurred_at' => '2026-07-19T00:00:00Z',
);
$before = count( $requests );
revelations_frontend_revalidation_send( $base );
revelations_frontend_revalidation_send( $base );
check(
    $before + 1 === count( $requests ),
    'exact duplicate suppressed'
);
$base['new_taxonomy_paths'][] =
    '/locations/dubai-uae';
revelations_frontend_revalidation_send( $base );
check(
    $before + 2 === count( $requests ),
    'different taxonomy fingerprint allowed'
);

$request = $requests[0][1];
$headers = $request['headers'];
check(
    'application/json' ===
        $headers['Content-Type'] &&
    ctype_digit(
        $headers['X-Revelations-Timestamp']
    ),
    'request headers'
);
check(
    hash_equals(
        $headers['X-Revelations-Signature'],
        'sha256=' .
            hash_hmac(
                'sha256',
                $headers['X-Revelations-Timestamp'] .
                    '.' .
                    $request['body'],
                'diagnostic-only-secret'
            )
    ),
    'exact timestamp.raw_body HMAC'
);
check(
    'body' === $request['data_format'] &&
    true === $request['blocking'] &&
    3 === $request['timeout'] &&
    0 === $request['redirection'] &&
    true === $request['sslverify'] &&
    true === $request['reject_unsafe_urls'],
    'request settings'
);

$keys = array(
    'version',
    'event_id',
    'post_id',
    'post_type',
    'action',
    'old_status',
    'new_status',
    'old_slug',
    'new_slug',
    'old_section',
    'new_section',
    'old_taxonomy_paths',
    'new_taxonomy_paths',
    'occurred_at',
);
sort( $keys );
$actual = array_keys( payload() );
sort( $actual );
check(
    $keys === $actual,
    'exact private payload fields'
);

$source = file_get_contents(
    __DIR__ .
    '/../mu-plugins/revelations-frontend-revalidation.php'
);

foreach (
    array(
        'wp_remote_post',
        'wp_json_encode',
        'hash_hmac',
        'pre_post_update',
        'wp_after_insert_post',
        'before_delete_post',
        'old_taxonomy_paths',
        'new_taxonomy_paths',
    ) as $required
) {
    check(
        false !== strpos( $source, $required ),
        'required sender source'
    );
}

foreach (
    array(
        'error_log',
        'wp_die',
        'wp_update_post',
        'update_post_meta',
        'wp_set_post_categories',
        'getenv',
        'Authorization',
        'Bearer',
        'post_title',
        'post_content',
        'post_excerpt',
        'post_author',
        'revelations.me/api/revalidate',
    ) as $forbidden
) {
    check(
        false === strpos( $source, $forbidden ),
        'forbidden sender source'
    );
}

echo "$n/$n passed\n";

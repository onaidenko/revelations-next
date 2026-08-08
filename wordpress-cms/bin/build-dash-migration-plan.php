<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || $argc !== 3) {
    fwrite(STDERR, "Usage: php build-dash-migration-plan.php WP_LOAD_PATH OUTPUT.json\n");
    exit(64);
}
require __DIR__ . '/revelations-dash-migration-lib.php';

$wp_load_path = $argv[1];
if (!is_file($wp_load_path) || basename($wp_load_path) !== 'wp-load.php') {
    fwrite(STDERR, "WP_LOAD_PATH must identify a readable wp-load.php file\n");
    exit(66);
}
require $wp_load_path;

global $wpdb;
$posts = $wpdb->get_results(
    "SELECT ID, post_name, post_status, post_title, post_excerpt, post_content FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID",
    ARRAY_A
);
$meta_fields = array(
    'displayed_author' => 'revelations_author',
    'seo_title' => 'revelations_seo_title',
    'seo_description' => 'revelations_seo_description',
    'revelation' => 'revelations_revelation',
    'source_note' => 'revelations_source_note',
    'editorial_note' => 'revelations_editorial_note',
    'disclosure' => 'revelations_disclosure',
    'public_sources' => 'revelations_public_sources',
);
$post_fields = array('title' => 'post_title', 'excerpt' => 'post_excerpt', 'content' => 'post_content');
$rows = array();
$blocked = array();
$field_values = array();
foreach ($posts as $post) {
    $id = (int) $post['ID'];
    foreach ($post_fields as $field => $column) $field_values[$id][$field] = (string) $post[$column];
    $meta_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key IN (" . implode(',', array_fill(0, count($meta_fields), '%s')) . ') ORDER BY meta_id',
        array_merge(array($id), array_values($meta_fields))
    ), ARRAY_A);
    $seen = array();
    foreach ($meta_rows as $meta_row) {
        $key = (string) $meta_row['meta_key'];
        if (isset($seen[$key])) { $blocked[] = array('record_id' => $id, 'field' => $key, 'reason' => 'duplicate-meta-row'); continue; }
        $seen[$key] = true;
        $field = array_search($key, $meta_fields, true);
        if ($field !== false) $field_values[$id][$field] = (string) $meta_row['meta_value'];
    }
    foreach ($meta_fields as $field => $_key) if (!isset($field_values[$id][$field])) $field_values[$id][$field] = '';

    foreach ($field_values[$id] as $field => $before) {
        if (!preg_match('/[\x{2013}\x{2014}]/u', $before)) continue;
        if ($field === 'public_sources') {
            $blocked[] = array('record_id' => $id, 'field' => $field, 'reason' => 'structured-json-requires-label-specific-review', 'before_sha256' => hash('sha256', $before));
            continue;
        }
        $result = revelations_dash_normalize($before, $field === 'content');
        if ($result['protected']) {
            $blocked[] = array('record_id' => $id, 'field' => $field, 'reason' => 'forbidden-dash-in-protected-technical-region', 'protected' => $result['protected'], 'before_sha256' => hash('sha256', $before));
            continue;
        }
        if (!$result['replacements']) continue;
        $invariant = revelations_dash_assert_invariant($before, $result);
        $contexts = array_map(fn(array $replacement): array => revelations_dash_context($before, $result['after'], $replacement), $result['replacements']);
        $rows[] = array(
            'record_id' => $id,
            'slug' => (string) $post['post_name'],
            'status' => (string) $post['post_status'],
            'field' => $field,
            'storage' => isset($post_fields[$field]) ? array('table' => 'posts', 'column' => $post_fields[$field]) : array('table' => 'postmeta', 'meta_key' => $meta_fields[$field]),
            'current_raw_sha256' => hash('sha256', $before),
            'planned_raw_sha256' => hash('sha256', $result['after']),
            'untouched_bytes_sha256' => $invariant,
            'em_dash_count' => substr_count($before, "\u{2014}"),
            'en_dash_count' => substr_count($before, "\u{2013}"),
            'total_replacements' => count($result['replacements']),
            'contexts' => $contexts,
            'required_write_path' => 'prepared-storage-level-update',
            'byte_preserving_reason' => 'The planned value is built from unchanged raw byte slices plus explicit dash/adjacent-space replacement tokens; the untouched-byte invariant passed.',
            'before' => $before,
            'after' => $result['after'],
        );
    }
}
$summary = array(
    'records_scanned' => count($posts),
    'affected_records' => count(array_unique(array_column($rows, 'record_id'))),
    'affected_fields' => count($rows),
    'em_dash_count' => array_sum(array_column($rows, 'em_dash_count')),
    'en_dash_count' => array_sum(array_column($rows, 'en_dash_count')),
    'total_replacements' => array_sum(array_column($rows, 'total_replacements')),
    'blocked_fields' => count($blocked),
);
$plan = array(
    'schema' => 'revelations-byte-preserving-dash-plan-v2',
    'created_at_gmt' => gmdate('c'),
    'source' => 'direct raw database rows',
    'normalization' => 'Editorial text only: horizontal whitespace adjacent to U+2013/U+2014 plus that dash becomes one ASCII space-hyphen-space; markup, block comments, URLs and code-like elements are protected.',
    'summary' => $summary,
    'rows' => $rows,
    'blocked' => $blocked,
);
$json = wp_json_encode($plan, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (file_put_contents($argv[2], $json, LOCK_EX) === false || !chmod($argv[2], 0600)) throw new RuntimeException('Unable to create protected plan');
echo wp_json_encode($summary), "\n";

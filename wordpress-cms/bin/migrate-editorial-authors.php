<?php
/**
 * Controlled CLI for REVELATIONS canonical author provisioning and article
 * relation migration. It never runs when included by diagnostics.
 *
 * Usage (all commands require --wordpress-root=/path/to/wordpress):
 *   --audit | --provision-dry-run | --provision --confirm |
 *   --repair-canonical-dry-run | --repair-canonical --confirm |
 *   --activate-julia-profile-dry-run | --activate-julia-profile --confirm |
 *   --migration-dry-run | --migrate --confirm | --verify [--json]
 */
declare(strict_types=1);

const REVELATIONS_AUTHOR_MIGRATION_EXIT_INVALID = 2;
const REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE = 3;
const REVELATIONS_AUTHOR_MIGRATION_EXIT_WRITE = 4;
const REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY = 5;

function revelations_author_migration_canonical_authors(): array {
    return array(
        'julia-upiterskaya' => array('name' => 'Julia Upiterskaya', 'slug' => 'julia-upiterskaya', 'schema_type' => 'person', 'same_as' => array('https://www.linkedin.com/in/julia-upiter/'), 'predecessors' => array(array('name' => 'Julia Yupiterskaya', 'slug' => 'julia-yupiterskaya'))),
        'alina-b' => array('name' => 'Alina B.', 'slug' => 'alina-b', 'schema_type' => 'person', 'same_as' => array()),
        'editorial-team' => array('name' => 'Editorial Team', 'slug' => 'editorial-team', 'schema_type' => 'organization', 'same_as' => array()),
    );
}

/** Exact values only. Missing meta and an actual empty string are the approved empty states. */
function revelations_author_migration_legacy_state(bool $exists, mixed $raw): array {
    if (!$exists) return array('kind' => 'missing_meta', 'raw' => null, 'key' => '', 'target' => 'alina-b');
    if (!is_string($raw)) return array('kind' => 'non_string', 'raw' => $raw, 'key' => null, 'target' => null);
    if ('' === $raw) return array('kind' => 'empty_string', 'raw' => '', 'key' => '', 'target' => 'alina-b');
    if ('' === trim($raw)) return array('kind' => 'whitespace_only', 'raw' => $raw, 'key' => null, 'target' => null);
    $map = array('Julia U.' => 'julia-upiterskaya', 'Julia Yupiterskaya' => 'julia-upiterskaya', 'Julia Upiterskaya' => 'julia-upiterskaya', 'Alina B.' => 'alina-b', 'Alina K.' => 'alina-b', 'Anonymous' => 'editorial-team', 'Editorial Team' => 'editorial-team');
    return array('kind' => isset($map[$raw]) ? 'approved' : 'unexpected', 'raw' => $raw, 'key' => $raw, 'target' => $map[$raw] ?? null);
}

/** Never normalize duplicate or malformed stored JSON into a safe relation. */
function revelations_author_migration_relation_state(bool $exists, mixed $raw): array {
    if (!$exists || null === $raw || '' === $raw) return array('kind' => 'empty', 'ids' => array(), 'raw' => $raw);
    if (!is_string($raw)) return array('kind' => 'malformed', 'ids' => array(), 'raw' => $raw);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) return array('kind' => 'malformed', 'ids' => array(), 'raw' => $raw);
    if (array() === $decoded) return array('kind' => 'empty', 'ids' => array(), 'raw' => $raw);
    $ids = array();
    foreach ($decoded as $id) {
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) return array('kind' => 'malformed', 'ids' => array(), 'raw' => $raw);
        $id = (int) $id;
        if ($id < 1 || in_array($id, $ids, true)) return array('kind' => 'malformed', 'ids' => array(), 'raw' => $raw);
        $ids[] = $id;
    }
    return array('kind' => 'present', 'ids' => $ids, 'raw' => $raw);
}

function revelations_author_migration_empty_summary(): array {
    return array('total_inspected' => 0, 'input_legacy_values' => array(), 'canonical_targets' => array(), 'already_correct' => 0, 'pending_writes' => 0, 'conflicts' => 0, 'unexpected_values' => 0, 'skipped' => 0, 'malformed_relations' => 0, 'missing_targets' => 0);
}

/** Pure planner used by both the CLI and diagnostics. */
function revelations_author_migration_plan_articles(array $articles, array $target_ids): array {
    $summary = revelations_author_migration_empty_summary(); $rows = array();
    foreach ($articles as $article) {
        $summary['total_inspected']++;
        $legacy = revelations_author_migration_legacy_state((bool) $article['legacy_exists'], $article['legacy_raw']);
        $legacy_label = 'missing_meta' === $legacy['kind'] ? '<MISSING>' : ('empty_string' === $legacy['kind'] ? '<EMPTY>' : (string) $legacy['raw']);
        $summary['input_legacy_values'][$legacy_label] = ($summary['input_legacy_values'][$legacy_label] ?? 0) + 1;
        $row = array('post_id' => (int) $article['id'], 'slug' => (string) $article['slug'], 'title' => (string) $article['title'], 'legacy' => $legacy, 'existing_relation' => null, 'target_slug' => $legacy['target'], 'target_id' => null, 'proposed_relation' => array(), 'action' => 'skip');
        if (null === $legacy['target']) { $summary['unexpected_values']++; $summary['skipped']++; $rows[] = $row; continue; }
        $summary['canonical_targets'][$legacy['target']] = ($summary['canonical_targets'][$legacy['target']] ?? 0) + 1;
        if (!isset($target_ids[$legacy['target']]) || (int) $target_ids[$legacy['target']] < 1) { $row['action'] = 'conflict'; $summary['conflicts']++; $summary['missing_targets']++; $rows[] = $row; continue; }
        $target = (int) $target_ids[$legacy['target']]; $row['target_id'] = $target; $row['proposed_relation'] = array($target);
        $relation = revelations_author_migration_relation_state((bool) $article['relation_exists'], $article['relation_raw']); $row['existing_relation'] = $relation;
        if ('malformed' === $relation['kind']) { $row['action'] = 'conflict'; $summary['conflicts']++; $summary['malformed_relations']++; $rows[] = $row; continue; }
        if ('empty' === $relation['kind']) { $row['action'] = 'set'; $summary['pending_writes']++; $rows[] = $row; continue; }
        if (array($target) === $relation['ids']) { $row['action'] = 'unchanged'; $summary['already_correct']++; $rows[] = $row; continue; }
        $row['action'] = 'conflict'; $summary['conflicts']++; $rows[] = $row;
    }
    ksort($summary['input_legacy_values']); ksort($summary['canonical_targets']);
    return array('articles' => $rows, 'summary' => $summary);
}

function revelations_author_migration_same_as(mixed $raw): array|false {
    if ('' === $raw || null === $raw) return array();
    if (!is_string($raw)) return false;
    $urls = json_decode($raw, true);
    if (!is_array($urls) || array_filter($urls, static fn($url): bool => !is_string($url))) return false;
    return array_values(array_unique($urls));
}

function revelations_author_migration_identity_state(array $record, array $desired): ?string {
    if ('rev_author' !== ($record['post_type'] ?? null)) return null;
    if ($desired['slug'] === ($record['slug'] ?? null) && $desired['name'] === ($record['name'] ?? null)) return 'canonical';
    foreach ($desired['predecessors'] ?? array() as $predecessor) if ($predecessor['slug'] === ($record['slug'] ?? null) && $predecessor['name'] === ($record['name'] ?? null)) return 'predecessor';
    return null;
}

function revelations_author_migration_identity_matches(array $records, array $desired): array {
    return array_values(array_filter($records, static function (array $record) use ($desired): bool {
        if ($desired['slug'] === ($record['slug'] ?? null) || $desired['name'] === ($record['name'] ?? null)) return true;
        foreach ($desired['predecessors'] ?? array() as $predecessor) if ($predecessor['slug'] === ($record['slug'] ?? null) || $predecessor['name'] === ($record['name'] ?? null)) return true;
        return false;
    }));
}

/** Planner records only safe, narrow changes and never alters bio, role, image, or active state. */
function revelations_author_migration_plan_provision(array $records): array {
    $summary = array('create' => 0, 'update' => 0, 'unchanged' => 0, 'conflicts' => 0); $rows = array(); $wanted = revelations_author_migration_canonical_authors();
    foreach ($wanted as $slug => $desired) {
        $matches = revelations_author_migration_identity_matches($records, $desired);
        $row = array('desired' => $desired, 'matching_id' => null, 'metadata_matches' => false, 'action' => 'create', 'conflicts' => array());
        if (count($matches) > 1) { $row['action'] = 'conflict'; $row['conflicts'][] = 'duplicate_slug'; }
        elseif (1 === count($matches)) {
            $record = $matches[0]; $row['matching_id'] = (int) ($record['id'] ?? 0);
            $identity_state = revelations_author_migration_identity_state($record, $desired);
            if ('rev_author' !== ($record['post_type'] ?? null)) $row['conflicts'][] = 'slug_wrong_post_type';
            if ('predecessor' === $identity_state) $row['conflicts'][] = 'canonical_predecessor_requires_repair';
            elseif ($slug !== ($record['slug'] ?? null)) $row['conflicts'][] = 'canonical_identity_duplicate';
            elseif ($desired['name'] !== ($record['name'] ?? null)) $row['conflicts'][] = 'canonical_name_mismatch';
            if (!in_array(($record['schema_type'] ?? null), array('', $desired['schema_type']), true)) $row['conflicts'][] = 'schema_type_mismatch';
            $same_as = revelations_author_migration_same_as($record['same_as'] ?? '');
            if (false === $same_as) $row['conflicts'][] = 'same_as_malformed';
            elseif ('julia-upiterskaya' === $slug && array() !== $same_as && !in_array($desired['same_as'][0], $same_as, true)) $row['conflicts'][] = 'same_as_conflict';
            if ($row['conflicts']) $row['action'] = 'conflict';
            else {
                $needs = ('publish' !== ($record['status'] ?? null)) || '' === ($record['schema_type'] ?? '') || ('julia-upiterskaya' === $slug && array() === $same_as);
                $row['action'] = $needs ? 'update' : 'unchanged'; $row['metadata_matches'] = !$needs;
            }
        }
        if ('conflict' === $row['action']) $summary['conflicts']++; else $summary[$row['action']]++;
        $rows[$slug] = $row;
    }
    return array('authors' => $rows, 'summary' => $summary);
}

/**
 * Repair is deliberately narrower than provisioning: it may reconcile only an
 * exact, unique canonical identity and only its approved schema type.
 */
function revelations_author_migration_plan_canonical_repair(array $records): array {
    $summary = array('repairs' => 0, 'unchanged' => 0, 'conflicts' => 0);
    $rows = array();
    foreach (revelations_author_migration_canonical_authors() as $slug => $desired) {
        $matches = revelations_author_migration_identity_matches($records, $desired);
        $row = array('desired' => $desired, 'matching_id' => null, 'current_schema_type' => null, 'identity_state' => null, 'action' => 'conflict', 'conflicts' => array());
        if (1 !== count($matches)) {
            $row['conflicts'][] = count($matches) ? 'ambiguous_canonical_identity' : 'canonical_entity_missing';
        } else {
            $record = $matches[0];
            $row['matching_id'] = (int) ($record['id'] ?? 0);
            $row['current_schema_type'] = (string) ($record['schema_type'] ?? '');
            $row['identity_state'] = revelations_author_migration_identity_state($record, $desired);
            if ('rev_author' !== ($record['post_type'] ?? null)) $row['conflicts'][] = 'slug_wrong_post_type';
            if (null === $row['identity_state']) $row['conflicts'][] = 'canonical_identity_mismatch';
            if (!$row['conflicts']) $row['action'] = ('canonical' === $row['identity_state'] && $desired['schema_type'] === $row['current_schema_type']) ? 'unchanged' : 'repair';
        }
        if ('repair' === $row['action']) $summary['repairs']++;
        elseif ('unchanged' === $row['action']) $summary['unchanged']++;
        else $summary['conflicts']++;
        $rows[$slug] = $row;
    }
    return array('authors' => $rows, 'summary' => $summary);
}

function revelations_author_migration_wp_author_records(): array {
    $records = array();
    $record = static fn(WP_Post $post): array => array('id'=>$post->ID,'slug'=>$post->post_name,'name'=>$post->post_title,'excerpt'=>$post->post_excerpt,'post_type'=>$post->post_type,'status'=>$post->post_status,'schema_type'=>(string)get_post_meta($post->ID,'revelations_author_schema_type',true),'same_as'=>get_post_meta($post->ID,'revelations_author_same_as',true),'role'=>(string)get_post_meta($post->ID,'revelations_author_role',true),'active'=>(string)get_post_meta($post->ID,'revelations_author_active',true));
    foreach (get_posts(array('post_type' => 'rev_author', 'post_status' => 'any', 'posts_per_page' => -1)) as $post) $records[] = $record($post);
    /* Exact canonical and predecessor slugs detect collisions in every post type. */
    foreach (revelations_author_migration_canonical_authors() as $desired) foreach (array_merge(array($desired), $desired['predecessors'] ?? array()) as $identity) foreach (get_posts(array('post_type' => 'any', 'post_status' => 'any', 'posts_per_page' => -1, 'name' => $identity['slug'])) as $post) $records[] = $record($post);
    $unique = array(); foreach ($records as $record) $unique[(string)$record['id']] = $record; return array_values($unique);
}

function revelations_author_migration_resolved_targets(array $provision): array {
    $ids = array(); foreach ($provision['authors'] as $slug => $row) if ('conflict' !== $row['action'] && $row['matching_id']) $ids[$slug] = (int) $row['matching_id']; return $ids;
}

function revelations_author_migration_wp_articles(): array {
    $rows = array(); foreach (get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC')) as $post) $rows[] = array('id'=>$post->ID,'slug'=>$post->post_name,'title'=>$post->post_title,'legacy_exists'=>metadata_exists('post',$post->ID,'revelations_author'),'legacy_raw'=>get_post_meta($post->ID,'revelations_author',true),'relation_exists'=>metadata_exists('post',$post->ID,'_revelations_author_profile_ids'),'relation_raw'=>get_post_meta($post->ID,'_revelations_author_profile_ids',true)); return $rows;
}

function revelations_author_migration_apply_provision(array $plan): void {
    if ($plan['summary']['conflicts']) throw new RuntimeException('Provisioning conflicts block writes.');
    foreach ($plan['authors'] as $slug => $row) {
        $desired = $row['desired']; $id = (int) $row['matching_id'];
        $created = 'create' === $row['action'];
        if ($created) { $id = wp_insert_post(array('post_type'=>'rev_author','post_status'=>'publish','post_name'=>$slug,'post_title'=>$desired['name']), true); if (is_wp_error($id)) throw new RuntimeException($id->get_error_message()); }
        elseif ('update' === $row['action']) { $result = wp_update_post(array('ID'=>$id,'post_status'=>'publish'), true); if (is_wp_error($result)) throw new RuntimeException($result->get_error_message()); }
        if ($created || !metadata_exists('post',$id,'revelations_author_schema_type')) update_post_meta($id,'revelations_author_schema_type',$desired['schema_type']);
        if ($desired['schema_type'] !== get_post_meta($id,'revelations_author_schema_type',true)) throw new RuntimeException('Canonical schema type did not persist for '.$slug);
        $same = revelations_author_migration_same_as(get_post_meta($id,'revelations_author_same_as',true)); if ('julia-upiterskaya' === $slug && array() === $same) update_post_meta($id,'revelations_author_same_as',wp_json_encode($desired['same_as']));
    }
}

function revelations_author_migration_apply_canonical_repair(array $plan): void {
    if ($plan['summary']['conflicts']) throw new RuntimeException('Canonical repair conflicts block writes.');
    foreach ($plan['authors'] as $slug => $row) if ('repair' === $row['action']) {
        $desired = $row['desired']; $id = (int) $row['matching_id'];
        if ('predecessor' === $row['identity_state']) {
            $result = wp_update_post(array('ID'=>$id,'post_name'=>$desired['slug'],'post_title'=>$desired['name']), true);
            if (is_wp_error($result)) throw new RuntimeException('Canonical identity repair failed for '.$slug.': '.$result->get_error_message());
        }
        if ($desired['schema_type'] !== get_post_meta($id,'revelations_author_schema_type',true) && false === update_post_meta($id,'revelations_author_schema_type',$desired['schema_type'])) throw new RuntimeException('Canonical schema repair failed for '.$slug);
        if ($desired['schema_type'] !== get_post_meta($id,'revelations_author_schema_type',true)) throw new RuntimeException('Canonical schema repair did not persist for '.$slug);
    }
}

function revelations_author_migration_julia_profile(): array {
    return array('slug'=>'julia-upiterskaya','name'=>'Julia Upiterskaya','role'=>'Founder of JULS and REVELATIONS','bio'=>'Julia Upiterskaya is a Dubai-based communications entrepreneur, founder of JULS and REVELATIONS, and an international technology moderator and speaker. With more than 10 years of experience across media, communications and events, she has worked with over 100 companies across emerging technology and innovation. Her current focus spans artificial intelligence, wellness and the technologies reshaping how people live, work and build businesses. Through JULS and REVELATIONS, Julia works at the intersection of strategic communications, founder positioning, technology, culture and global communities.','active'=>'1');
}

/** Activation may change only Julia's approved public profile fields after identity reconciliation. */
function revelations_author_migration_plan_julia_profile(array $records): array {
    $canonical = revelations_author_migration_canonical_authors()['julia-upiterskaya'];
    $profile = revelations_author_migration_julia_profile();
    $matches = revelations_author_migration_identity_matches($records, $canonical);
    $row = array('desired'=>$profile,'matching_id'=>null,'action'=>'conflict','changes'=>array(),'conflicts'=>array());
    if (1 !== count($matches)) $row['conflicts'][] = count($matches) ? 'ambiguous_julia_identity' : 'julia_entity_missing';
    else {
        $record = $matches[0]; $row['matching_id'] = (int) ($record['id'] ?? 0);
        if ('canonical' !== revelations_author_migration_identity_state($record,$canonical)) $row['conflicts'][] = 'julia_identity_requires_repair';
        if ('person' !== ($record['schema_type'] ?? null)) $row['conflicts'][] = 'julia_schema_type_mismatch';
        $same_as = revelations_author_migration_same_as($record['same_as'] ?? '');
        if (false === $same_as || $canonical['same_as'] !== $same_as) $row['conflicts'][] = 'julia_same_as_mismatch';
        if (!$row['conflicts']) {
            foreach (array('excerpt'=>'bio','role'=>'role','active'=>'active') as $current => $desired_key) if ((string) ($record[$current] ?? '') !== $profile[$desired_key]) $row['changes'][] = $desired_key;
            $row['action'] = $row['changes'] ? 'activate' : 'unchanged';
        }
    }
    return array('profile'=>$row,'summary'=>array('activations'=>'activate' === $row['action'] ? 1 : 0,'unchanged'=>'unchanged' === $row['action'] ? 1 : 0,'conflicts'=>$row['conflicts'] ? 1 : 0));
}

function revelations_author_migration_apply_julia_profile(array $plan): void {
    $row = $plan['profile']; if ($plan['summary']['conflicts']) throw new RuntimeException('Julia profile activation conflicts block writes.'); if ('activate' !== $row['action']) return;
    $profile = $row['desired']; $id = (int) $row['matching_id'];
    if (in_array('bio',$row['changes'],true)) { $result = wp_update_post(array('ID'=>$id,'post_excerpt'=>$profile['bio']), true); if (is_wp_error($result)) throw new RuntimeException('Julia bio update failed: '.$result->get_error_message()); }
    if (in_array('role',$row['changes'],true) && false === update_post_meta($id,'revelations_author_role',$profile['role'])) throw new RuntimeException('Julia role update failed.');
    if (in_array('active',$row['changes'],true) && false === update_post_meta($id,'revelations_author_active',$profile['active'])) throw new RuntimeException('Julia active-state update failed.');
}

function revelations_author_migration_apply_articles(array $plan): void {
    $s = $plan['summary']; if ($s['conflicts'] || $s['unexpected_values'] || $s['skipped'] || $s['missing_targets']) throw new RuntimeException('Migration preflight blocks writes.');
    foreach ($plan['articles'] as $row) if ('set' === $row['action']) {
        $before = get_post_meta($row['post_id'],'revelations_author',true); $encoded = wp_json_encode($row['proposed_relation']);
        if (false === update_post_meta($row['post_id'],'_revelations_author_profile_ids',$encoded)) throw new RuntimeException('Relation write failed for post '.$row['post_id']);
        if ($before !== get_post_meta($row['post_id'],'revelations_author',true) || $encoded !== get_post_meta($row['post_id'],'_revelations_author_profile_ids',true)) throw new RuntimeException('Post-write verification failed for post '.$row['post_id']);
    }
}

function revelations_author_migration_render(array $report, bool $json): void {
    if ($json) { echo wp_json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL; return; }
    echo "REVELATIONS author migration\n";
    foreach (array('provision','repair','profile','migration') as $section) if (isset($report[$section]['summary'])) {
        echo strtoupper($section)."\n";
        foreach ($report[$section]['summary'] as $key => $value) echo str_pad((string)$key, 24).(is_array($value) ? wp_json_encode($value) : (string)$value).PHP_EOL;
    }
    if (isset($report['migration']['articles'])) {
        $legacy = array(); foreach ($report['migration']['articles'] as $row) { $label = 'missing_meta' === $row['legacy']['kind'] ? 'MISSING' : ('empty_string' === $row['legacy']['kind'] ? 'EMPTY' : (string) $row['legacy']['raw']); $key = $label.'|'.($row['target_slug'] ?? 'UNEXPECTED'); $legacy[$key] = ($legacy[$key] ?? 0) + 1; }
        echo "Legacy value                 Count  Target\n"; foreach ($legacy as $key => $count) { [$label,$target] = explode('|',$key,2); echo str_pad($label,29).str_pad((string)$count,7).$target.PHP_EOL; }
    }
}

function revelations_author_migration_cli(): int {
    $allowed = array('--wordpress-root','--audit','--provision-dry-run','--provision','--repair-canonical-dry-run','--repair-canonical','--activate-julia-profile-dry-run','--activate-julia-profile','--migration-dry-run','--migrate','--verify','--confirm','--json');
    for ($i = 1; $i < count($_SERVER['argv'] ?? array()); $i++) {
        $argument = (string) $_SERVER['argv'][$i];
        if ('--wordpress-root' === $argument) { $i++; continue; }
        $name = explode('=', $argument, 2)[0];
        if (!in_array($name, $allowed, true)) { fwrite(STDERR, "Unknown argument: $argument\n"); return REVELATIONS_AUTHOR_MIGRATION_EXIT_INVALID; }
    }
    $options = getopt('', array('wordpress-root:','audit','provision-dry-run','provision','repair-canonical-dry-run','repair-canonical','activate-julia-profile-dry-run','activate-julia-profile','migration-dry-run','migrate','verify','confirm','json'));
    $modes = array_filter(array('audit','provision-dry-run','provision','repair-canonical-dry-run','repair-canonical','activate-julia-profile-dry-run','activate-julia-profile','migration-dry-run','migrate','verify'), static fn(string $mode): bool => array_key_exists($mode,$options));
    $root = rtrim((string)($options['wordpress-root'] ?? ''), '/');
    if (1 !== count($modes) || '' === $root || !is_file($root.'/wp-load.php')) { fwrite(STDERR,"Usage requires one mode and --wordpress-root=/path/to/wordpress.\n"); return REVELATIONS_AUTHOR_MIGRATION_EXIT_INVALID; }
    $mode = array_values($modes)[0]; if (in_array($mode,array('provision','repair-canonical','activate-julia-profile','migrate'),true) && !array_key_exists('confirm',$options)) { fwrite(STDERR,"Write modes require --confirm.\n"); return REVELATIONS_AUTHOR_MIGRATION_EXIT_INVALID; }
    define('WP_USE_THEMES', false); require_once $root.'/wp-load.php';
    $json = array_key_exists('json',$options); $provision = revelations_author_migration_plan_provision(revelations_author_migration_wp_author_records());
    if ('audit' === $mode) { $audit = revelations_author_migration_plan_articles(revelations_author_migration_wp_articles(), revelations_author_migration_resolved_targets($provision)); revelations_author_migration_render(array('mode'=>$mode,'provision'=>$provision,'migration'=>$audit,'canonical_authors'=>revelations_author_migration_canonical_authors()),$json); return ($provision['summary']['conflicts'] || $audit['summary']['unexpected_values']) ? REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE : 0; }
    if ('provision-dry-run' === $mode) { revelations_author_migration_render(array('mode'=>$mode,'provision'=>$provision,'canonical_authors'=>revelations_author_migration_canonical_authors()),$json); return $provision['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE : 0; }
    if ('provision' === $mode) { try { revelations_author_migration_apply_provision($provision); } catch (RuntimeException $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); return REVELATIONS_AUTHOR_MIGRATION_EXIT_WRITE; } $after=revelations_author_migration_plan_provision(revelations_author_migration_wp_author_records()); revelations_author_migration_render(array('mode'=>$mode,'provision'=>$after),$json); return $after['summary']['create']||$after['summary']['update']||$after['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY : 0; }
    $repair = revelations_author_migration_plan_canonical_repair(revelations_author_migration_wp_author_records());
    if ('repair-canonical-dry-run' === $mode) { revelations_author_migration_render(array('mode'=>$mode,'repair'=>$repair),$json); return $repair['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE : 0; }
    if ('repair-canonical' === $mode) { try { revelations_author_migration_apply_canonical_repair($repair); } catch (RuntimeException $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); return REVELATIONS_AUTHOR_MIGRATION_EXIT_WRITE; } $after=revelations_author_migration_plan_canonical_repair(revelations_author_migration_wp_author_records()); revelations_author_migration_render(array('mode'=>$mode,'repair'=>$after),$json); return $after['summary']['repairs']||$after['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY : 0; }
    $profile = revelations_author_migration_plan_julia_profile(revelations_author_migration_wp_author_records());
    if ('activate-julia-profile-dry-run' === $mode) { revelations_author_migration_render(array('mode'=>$mode,'profile'=>$profile),$json); return $profile['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE : 0; }
    if ('activate-julia-profile' === $mode) { try { revelations_author_migration_apply_julia_profile($profile); } catch (RuntimeException $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); return REVELATIONS_AUTHOR_MIGRATION_EXIT_WRITE; } $after=revelations_author_migration_plan_julia_profile(revelations_author_migration_wp_author_records()); revelations_author_migration_render(array('mode'=>$mode,'profile'=>$after),$json); return $after['summary']['activations']||$after['summary']['conflicts'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY : 0; }
    $targets = revelations_author_migration_resolved_targets($provision); $migration = revelations_author_migration_plan_articles(revelations_author_migration_wp_articles(),$targets);
    if ('migration-dry-run' === $mode) { revelations_author_migration_render(array('mode'=>$mode,'migration'=>$migration),$json); return ($migration['summary']['conflicts']||$migration['summary']['unexpected_values']||$migration['summary']['skipped']) ? REVELATIONS_AUTHOR_MIGRATION_EXIT_UNSAFE : 0; }
    if ('migrate' === $mode) { try { revelations_author_migration_apply_articles($migration); } catch (RuntimeException $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); return REVELATIONS_AUTHOR_MIGRATION_EXIT_WRITE; } $after=revelations_author_migration_plan_articles(revelations_author_migration_wp_articles(),revelations_author_migration_resolved_targets(revelations_author_migration_plan_provision(revelations_author_migration_wp_author_records()))); revelations_author_migration_render(array('mode'=>$mode,'migration'=>$after),$json); return $after['summary']['pending_writes']||$after['summary']['conflicts']||$after['summary']['unexpected_values']||$after['summary']['skipped'] ? REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY : 0; }
    revelations_author_migration_render(array('mode'=>$mode,'provision'=>$provision,'migration'=>$migration),$json); return ($provision['summary']['create']||$provision['summary']['update']||$provision['summary']['conflicts']||$migration['summary']['pending_writes']||$migration['summary']['conflicts']||$migration['summary']['unexpected_values']||$migration['summary']['skipped']) ? REVELATIONS_AUTHOR_MIGRATION_EXIT_VERIFY : 0;
}

if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) exit(revelations_author_migration_cli());

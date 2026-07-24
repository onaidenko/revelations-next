<?php
declare(strict_types=1);

$meta = array(); $writes = array(); $posts = array();
function get_post_meta(int $id, string $key, bool $single = true): mixed { global $meta; if (isset($meta[$id]) && array_key_exists($key,$meta[$id])) return $meta[$id][$key]; return 'revelations_author_schema_type' === $key ? 'person' : ''; }
function metadata_exists(string $type, int $id, string $key): bool { global $meta; return isset($meta[$id]) && array_key_exists($key,$meta[$id]); }
function update_post_meta(int $id, string $key, mixed $value): int { global $meta, $writes; $meta[$id][$key] = $value; $writes[] = array($id,$key,$value); return 1; }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function is_wp_error(mixed $value): bool { return false; }
function wp_insert_post(array $data, bool $error = false): int { global $posts; $id=count($posts)+100; $posts[$id]=$data+array('ID'=>$id); return $id; }
function wp_update_post(array $data, bool $error = false): int { global $posts; $posts[$data['ID']]=array_merge($posts[$data['ID']]??array(),$data); return $data['ID']; }
require_once __DIR__ . '/migrate-editorial-authors.php';
function check(bool $ok, string $label): void { if (!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); } echo "PASS: $label\n"; }

$missing = revelations_author_migration_plan_provision(array());
revelations_author_migration_apply_provision($missing);
check('person' === get_post_meta(100,'revelations_author_schema_type',true), 'fresh Julia persists person');
check('person' === get_post_meta(101,'revelations_author_schema_type',true), 'fresh Alina persists person');
check('organization' === get_post_meta(102,'revelations_author_schema_type',true), 'fresh Editorial Team persists organization after read-back');

$records = array(); $id = 10;
foreach (revelations_author_migration_canonical_authors() as $slug => $author) $records[] = array('id'=>$id++,'slug'=>$slug,'name'=>$author['name'],'post_type'=>'rev_author','status'=>'publish','schema_type'=>$author['schema_type'],'same_as'=>wp_json_encode($author['same_as']));
$schemaConflict = $records; $schemaConflict[2]['schema_type'] = 'person';
$provisionConflict = revelations_author_migration_plan_provision($schemaConflict);
check(1 === $provisionConflict['summary']['conflicts'], 'ordinary schema conflict remains blocked in provisioning');
try { revelations_author_migration_apply_provision($provisionConflict); check(false, 'ordinary schema conflict refuses write'); } catch (RuntimeException) { check(true, 'ordinary schema conflict refuses write'); }

$repairRecords = $records; $repairRecords[2]['schema_type'] = 'person';
$repair = revelations_author_migration_plan_canonical_repair($repairRecords);
check(1 === $repair['summary']['repairs'] && 'repair' === $repair['authors']['editorial-team']['action'], 'canonical repair dry-run proposes only Editorial Team schema correction');
$meta = array(10=>array('revelations_author_schema_type'=>'person'),11=>array('revelations_author_schema_type'=>'person'),12=>array('revelations_author_schema_type'=>'person')); $writes = array();
revelations_author_migration_apply_canonical_repair($repair);
check('organization' === get_post_meta(12,'revelations_author_schema_type',true) && 1 === count($writes), 'explicit canonical repair changes only Editorial Team schema');
$repairedRecords = $repairRecords; $repairedRecords[2]['schema_type'] = 'organization';
check(0 === revelations_author_migration_plan_canonical_repair($repairedRecords)['summary']['repairs'], 'second canonical repair is idempotent');

$wrongSlug = $repairRecords; $wrongSlug[2]['slug'] = 'editorial-team-wrong';
check(1 === revelations_author_migration_plan_canonical_repair($wrongSlug)['summary']['conflicts'], 'repair refuses wrong slug');
$wrongName = $repairRecords; $wrongName[2]['name'] = 'Editorial Team Wrong';
check(1 === revelations_author_migration_plan_canonical_repair($wrongName)['summary']['conflicts'], 'repair refuses wrong name');
$duplicate = $repairRecords; $duplicate[] = $repairRecords[2]; $duplicate[3]['id'] = 99;
check(1 === revelations_author_migration_plan_canonical_repair($duplicate)['summary']['conflicts'], 'repair refuses duplicate canonical entity');

$blockedTargets = revelations_author_migration_resolved_targets($provisionConflict);
$fixture = array(); foreach (array_fill(0,7,'Editorial Team') as $value) $fixture[] = array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$value,'relation_exists'=>false,'relation_raw'=>'');
$blockedMigration = revelations_author_migration_plan_articles($fixture,$blockedTargets);
check(7 === $blockedMigration['summary']['missing_targets'] && 7 === $blockedMigration['summary']['conflicts'], 'migration remains blocked until canonical provisioning is valid');
echo "Editorial author repair diagnostics: 12 passed, 0 failed, 12 total.\n";

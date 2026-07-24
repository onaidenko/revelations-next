<?php
declare(strict_types=1);

$meta = array(); $writes = array(); $posts = array();
function get_post_meta(int $id, string $key, bool $single = true): mixed { global $meta; return $meta[$id][$key] ?? ''; }
function update_post_meta(int $id, string $key, mixed $value): int { global $meta, $writes; $meta[$id][$key] = $value; $writes[] = array($id,$key,$value); return 1; }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function is_wp_error(mixed $value): bool { return false; }
function wp_insert_post(array $data, bool $error = false): int { global $posts; $id=count($posts)+100; $posts[$id]=$data+array('ID'=>$id); return $id; }
function wp_update_post(array $data, bool $error = false): int { global $posts; $posts[$data['ID']]=array_merge($posts[$data['ID']]??array(),$data); return $data['ID']; }
require_once __DIR__ . '/migrate-editorial-authors.php';
function check(bool $ok, string $label): void { if (!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); } echo "PASS: $label\n"; }

$expected = array('Julia U.'=>'julia-yupiterskaya','Julia Yupiterskaya'=>'julia-yupiterskaya','Alina B.'=>'alina-b','Alina K.'=>'alina-b','Anonymous'=>'editorial-team','Editorial Team'=>'editorial-team');
foreach ($expected as $raw=>$target) check($target===revelations_author_migration_legacy_state(true,$raw)['target'], "exact mapping $raw");
check('alina-b'===revelations_author_migration_legacy_state(false,'')['target'] && 'missing_meta'===revelations_author_migration_legacy_state(false,'')['kind'], 'missing meta is the approved empty state');
check('alina-b'===revelations_author_migration_legacy_state(true,'')['target'] && null===revelations_author_migration_legacy_state(true,' ')['target'], 'empty string is approved but whitespace is not');
check(null===revelations_author_migration_legacy_state(true,'Julia Unknown')['target'], 'unexpected byline is blocked');

$missing = revelations_author_migration_plan_provision(array());
check(3===$missing['summary']['create'] && 0===$missing['summary']['conflicts'], 'missing canonical authors are planned for creation');
$correct = array(); $id=10; foreach (revelations_author_migration_canonical_authors() as $slug=>$author) $correct[]=array('id'=>$id++,'slug'=>$slug,'name'=>$author['name'],'post_type'=>'rev_author','status'=>'publish','schema_type'=>$author['schema_type'],'same_as'=>wp_json_encode($author['same_as']));
$provision = revelations_author_migration_plan_provision($correct);
check(3===$provision['summary']['unchanged'] && 0===$provision['summary']['conflicts'], 'correct canonical authors are idempotent');
$slugConflict=$correct; $slugConflict[0]['post_type']='post'; check(1===revelations_author_migration_plan_provision($slugConflict)['summary']['conflicts'], 'slug collision blocks provisioning');
$identityConflict=$correct; $identityConflict[]=array('id'=>99,'slug'=>'julia-yupiterskaya-duplicate','name'=>'Julia Yupiterskaya','post_type'=>'rev_author','status'=>'publish','schema_type'=>'person','same_as'=>wp_json_encode(array('https://www.linkedin.com/in/julia-upiter/'))); check(1===revelations_author_migration_plan_provision($identityConflict)['summary']['conflicts'], 'duplicate canonical identity blocks provisioning');
$schemaConflict=$correct; $schemaConflict[1]['schema_type']='organization'; check(1===revelations_author_migration_plan_provision($schemaConflict)['summary']['conflicts'], 'schema mismatch blocks provisioning');
$sameConflict=$correct; $sameConflict[0]['same_as']=wp_json_encode(array('https://example.com/conflict')); check(1===revelations_author_migration_plan_provision($sameConflict)['summary']['conflicts'], 'conflicting Julia sameAs blocks provisioning');

$targets=array('julia-yupiterskaya'=>10,'alina-b'=>11,'editorial-team'=>12); $fixture=array();
foreach (array_fill(0,14,'Julia U.') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
foreach (array_fill(0,1,'Julia Yupiterskaya') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
foreach (array_fill(0,20,'Alina B.') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
foreach (array_fill(0,1,'Alina K.') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
foreach (range(1,10) as $_) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>false,'legacy_raw'=>'','relation_exists'=>false,'relation_raw'=>'');
foreach (array_fill(0,5,'Anonymous') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
foreach (array_fill(0,2,'Editorial Team') as $v) $fixture[]=array('id'=>count($fixture)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>$v,'relation_exists'=>false,'relation_raw'=>'');
$plan=revelations_author_migration_plan_articles($fixture,$targets);
check(53===$plan['summary']['total_inspected'] && 53===$plan['summary']['pending_writes'] && array('alina-b'=>31,'editorial-team'=>7,'julia-yupiterskaya'=>15)===$plan['summary']['canonical_targets'], 'known 53 article fixture plans exact canonical totals');
check(0===$plan['summary']['conflicts'] && 0===$plan['summary']['unexpected_values'], 'safe dry run produces no conflicts or writes');

$one=$fixture[0]; $one['relation_exists']=true; $one['relation_raw']='[10]'; check('unchanged'===revelations_author_migration_plan_articles(array($one),$targets)['articles'][0]['action'], 'exact existing relation is unchanged');
$one['relation_raw']='[11]'; check('conflict'===revelations_author_migration_plan_articles(array($one),$targets)['articles'][0]['action'], 'different existing relation is conflict');
$one['relation_raw']='[10,10]'; check('conflict'===revelations_author_migration_plan_articles(array($one),$targets)['articles'][0]['action'], 'duplicate relation JSON is not normalized into safety');
$one['relation_raw']='not-json'; check('conflict'===revelations_author_migration_plan_articles(array($one),$targets)['articles'][0]['action'], 'malformed relation blocks migration');
$bad=$fixture[0]; $bad['legacy_raw']='Unknown'; $badPlan=revelations_author_migration_plan_articles(array($bad),$targets); check(1===$badPlan['summary']['unexpected_values'] && 1===$badPlan['summary']['skipped'], 'unexpected author blocks migration');

$meta=array(1=>array('revelations_author'=>'Julia U.')); $writes=array(); $apply=revelations_author_migration_plan_articles(array($fixture[0]),$targets); revelations_author_migration_apply_articles($apply);
check('Julia U.'===get_post_meta(1,'revelations_author',true) && '[10]'===get_post_meta(1,'_revelations_author_profile_ids',true) && 1===count($writes), 'apply preserves legacy author and changes only relation meta');
$after=$fixture[0]; $after['relation_exists']=true; $after['relation_raw']='[10]'; $afterPlan=revelations_author_migration_plan_articles(array($after),$targets); check(0===$afterPlan['summary']['pending_writes'] && 1===$afterPlan['summary']['already_correct'], 'second dry run is idempotent');

$posts=array(); $meta=array(); revelations_author_migration_apply_provision($missing);
check(3===count($posts) && 3===count(array_filter($writes, static fn(array $write): bool => 'revelations_author_schema_type'===$write[1])), 'provision apply creates exactly three canonical entities with approved schema metadata');
echo "Editorial author migration diagnostics: 23 passed, 0 failed, 23 total.\n";

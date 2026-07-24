<?php
declare(strict_types=1);

$meta = array(); $writes = array(); $posts = array();
function get_post_meta(int $id, string $key, bool $single = true): mixed { global $meta; return $meta[$id][$key] ?? ('revelations_author_schema_type' === $key ? 'person' : ''); }
function metadata_exists(string $type, int $id, string $key): bool { global $meta; return isset($meta[$id]) && array_key_exists($key,$meta[$id]); }
function update_post_meta(int $id, string $key, mixed $value): int { global $meta,$writes; if (($meta[$id][$key] ?? null) === $value) return 0; $meta[$id][$key]=$value; $writes[]=array($id,$key,$value); return 1; }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value,$flags); }
function is_wp_error(mixed $value): bool { return false; }
function wp_update_post(array $data, bool $error = false): int { global $posts; $posts[$data['ID']] = array_merge($posts[$data['ID']] ?? array(),$data); return $data['ID']; }
function wp_insert_post(array $data, bool $error = false): int { global $posts; $id=count($posts)+100; $posts[$id]=$data+array('ID'=>$id); return $id; }
require_once __DIR__ . '/migrate-editorial-authors.php';
function check(bool $ok,string $label): void { if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);} echo "PASS: $label\n"; }

foreach (array('Julia U.','Julia Yupiterskaya','Julia Upiterskaya') as $value) check('julia-upiterskaya'===revelations_author_migration_legacy_state(true,$value)['target'], "legacy alias $value resolves to corrected Julia");
$records=array(
 array('id'=>300,'slug'=>'julia-yupiterskaya','name'=>'Julia Yupiterskaya','excerpt'=>'','post_type'=>'rev_author','status'=>'publish','schema_type'=>'person','same_as'=>'["https://www.linkedin.com/in/julia-upiter/"]','role'=>'','active'=>''),
 array('id'=>301,'slug'=>'alina-b','name'=>'Alina B.','excerpt'=>'','post_type'=>'rev_author','status'=>'publish','schema_type'=>'person','same_as'=>'[]','role'=>'','active'=>''),
 array('id'=>302,'slug'=>'editorial-team','name'=>'Editorial Team','excerpt'=>'','post_type'=>'rev_author','status'=>'publish','schema_type'=>'organization','same_as'=>'[]','role'=>'','active'=>''),
);
$provision=revelations_author_migration_plan_provision($records);
check(1===$provision['summary']['conflicts'] && 300===$provision['authors']['julia-upiterskaya']['matching_id'], 'predecessor is blocked for explicit reconciliation instead of duplicate creation');
$repair=revelations_author_migration_plan_canonical_repair($records);
check(1===$repair['summary']['repairs'] && 'predecessor'===$repair['authors']['julia-upiterskaya']['identity_state'], 'exact predecessor plans only canonical Julia identity repair');
$posts=array(300=>array('ID'=>300,'post_name'=>'julia-yupiterskaya','post_title'=>'Julia Yupiterskaya','post_excerpt'=>'')); $meta=array(300=>array('revelations_author_schema_type'=>'person','revelations_author_same_as'=>'["https://www.linkedin.com/in/julia-upiter/"]')); $writes=array();
revelations_author_migration_apply_canonical_repair($repair);
check('julia-upiterskaya'===$posts[300]['post_name'] && 'Julia Upiterskaya'===$posts[300]['post_title'] && 'person'===get_post_meta(300,'revelations_author_schema_type',true), 'identity repair updates ID 300 in place and preserves schema');
$corrected=$records; $corrected[0]['slug']='julia-upiterskaya'; $corrected[0]['name']='Julia Upiterskaya';
$repairAfter=revelations_author_migration_plan_canonical_repair($corrected);
check(0===$repairAfter['summary']['repairs'] && 3===$repairAfter['summary']['unchanged'], 'second identity repair is idempotent');
$targets=revelations_author_migration_resolved_targets(revelations_author_migration_plan_provision($corrected)); $articles=array(); foreach(range(1,15) as $_)$articles[]=array('id'=>count($articles)+1,'slug'=>'p','title'=>'P','legacy_exists'=>true,'legacy_raw'=>'Julia U.','relation_exists'=>true,'relation_raw'=>'[300]');
$relations=revelations_author_migration_plan_articles($articles,$targets);
check(15===$relations['summary']['already_correct'] && 0===$relations['summary']['pending_writes'], 'identity repair preserves all existing Julia relation IDs');
$profile=revelations_author_migration_plan_julia_profile($corrected);
check('activate'===$profile['profile']['action'] && array('bio','role','active')===$profile['profile']['changes'], 'Julia profile activation plans only approved profile fields');
revelations_author_migration_apply_julia_profile($profile);
check(revelations_author_migration_julia_profile()['bio']===$posts[300]['post_excerpt'] && 'Founder of JULS and REVELATIONS'===get_post_meta(300,'revelations_author_role',true) && '1'===get_post_meta(300,'revelations_author_active',true), 'profile activation persists approved bio role and active state');
$active=$corrected; $active[0]['excerpt']=$posts[300]['post_excerpt']; $active[0]['role']=get_post_meta(300,'revelations_author_role',true); $active[0]['active']=get_post_meta(300,'revelations_author_active',true);
check(0===revelations_author_migration_plan_julia_profile($active)['summary']['activations'], 'second profile activation is idempotent');
$duplicate=$corrected; $duplicate[]=$corrected[0]; $duplicate[3]['id']=999;
check(1===revelations_author_migration_plan_canonical_repair($duplicate)['summary']['conflicts'], 'identity repair refuses duplicate Julia entity');
echo "Editorial author identity diagnostics: 12 passed, 0 failed, 12 total.\n";

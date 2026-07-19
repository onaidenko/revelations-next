<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('REVELATIONS_REVALIDATION_URL', 'https://example.test/api/revalidate');
define('REVELATIONS_REVALIDATION_SECRET', 'diagnostic-only-secret');
$hooks=[];$posts=[];$terms=[];$requests=[];$mode='ok';$revision=[];$autosave=[];$json_fail=false;
class WP_Post { public function __construct(public int $ID, public string $post_status, public string $post_name, public string $post_type='post'){} }
class WP_Error {}
function add_action($h,$c,$p=10,$a=1){global $hooks;$hooks[$h]=[$c,$p,$a];}
function get_post($id){global $posts;return $posts[$id]??null;} function wp_get_post_categories($id,$a=[]){global $terms;return $terms[$id]??[];}
function revelations_editorial_sections(){return ['news','people','tech','places','unspoken','podcast'];}
function wp_is_post_revision($id){global $revision;return !empty($revision[$id]);} function wp_is_post_autosave($id){global $autosave;return !empty($autosave[$id]);}
function wp_parse_url($u){return parse_url($u);} function wp_json_encode($v){global $json_fail;return $json_fail?false:json_encode($v);}
function wp_generate_uuid4(){static $i=0;return 'uuid-'.++$i;} function is_wp_error($v){return $v instanceof WP_Error;}
function wp_remote_retrieve_response_code($v){return $v['response']['code']??0;} function wp_remote_post($u,$a){global $requests,$mode;$requests[]=[$u,$a];return $mode==='error'?new WP_Error():['response'=>['code'=>$mode==='500'?500:200]];}
require __DIR__.'/../mu-plugins/revelations-frontend-revalidation.php';
function check($v,$name){global $n;$n=($n??0)+1;if(!$v){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
function payload(){global $requests;return json_decode($requests[count($requests)-1][1]['body'],true);}
function save($id,$oldStatus,$newStatus,$oldSlug,$newSlug,$oldSection,$newSection,$snapshot=true){global $posts,$terms,$hooks;$posts[$id]=new WP_Post($id,$oldStatus,$oldSlug);$terms[$id]=[$oldSection];if($snapshot)$hooks['pre_post_update'][0]($id,[]);$posts[$id]=new WP_Post($id,$newStatus,$newSlug);$terms[$id]=[$newSection];$hooks['wp_after_insert_post'][0]($id,$posts[$id],true,new WP_Post($id,$oldStatus,$oldSlug));return payload();}
global $hooks,$posts,$terms,$requests,$revision,$autosave,$mode,$json_fail;
check($hooks['pre_post_update'][2]===2&&$hooks['wp_after_insert_post'][2]===4&&$hooks['before_delete_post'][2]===2,'hook registrations');
foreach([null,[], '', ' ', 'http://x.test','/x','https://u@x.test','https://:p@x.test','https://x.test/#f'] as $v)check(null===revelations_frontend_revalidation_validate_config($v,'x'),'config rejects unsafe input');
check(['url'=>'https://x.test','secret'=>'x']===revelations_frontend_revalidation_validate_config(' https://x.test ',' x '),'config valid trimmed HTTPS');
$before=count($requests);$posts[1]=new WP_Post(1,'draft','draft');$terms[1]=['news'];$hooks['wp_after_insert_post'][0](1,$posts[1],false,null);check($before===count($requests),'draft skipped');
$p=save(2,'draft','publish','old','new','news','tech');check($p['action']==='publish'&&$p['old_slug']===null&&$p['new_slug']==='new','draft publish');
$posts[3]=new WP_Post(3,'publish','direct');$terms[3]=['places'];$hooks['wp_after_insert_post'][0](3,$posts[3],false,null);$p=payload();check($p['action']==='publish'&&$p['old_section']===null&&$p['new_section']==='places','direct publish');
$p=save(4,'publish','publish','old','new','news','news');check($p['action']==='update'&&$p['old_slug']==='old'&&$p['new_slug']==='new','slug-only update');
$p=save(5,'publish','publish','same','same','news','tech');check($p['old_section']==='news'&&$p['new_section']==='tech','section-only update');
$p=save(6,'publish','publish','old','new','news','tech');check($p['old_slug']==='old'&&$p['new_section']==='tech','combined update');
foreach(['draft','private','pending','future','trash'] as $s){$p=save(10+strlen($s),'publish',$s,'old','gone','news','tech');check($p['action']==='unpublish'&&$p['new_slug']===null,'publish unpublish');}
$p=save(20,'trash','publish','old','restored','news','tech');check($p['action']==='publish'&&$p['old_slug']===null&&$p['new_section']==='tech','trash restore');
$posts[30]=new WP_Post(30,'publish','delete');$terms[30]=['news'];$hooks['before_delete_post'][0](30,$posts[30]);$p=payload();check($p['action']==='delete'&&$p['new_status']==='delete'&&$p['new_slug']===null,'direct delete');
foreach(['trash','draft','private'] as $s){$before=count($requests);$posts[31]=new WP_Post(31,$s,'x');$hooks['before_delete_post'][0](31,$posts[31]);check($before===count($requests),'non-public delete skipped');}
$posts[40]=new WP_Post(40,'publish','r');$terms[40]=['news'];$revision[40]=true;$before=count($requests);$hooks['wp_after_insert_post'][0](40,$posts[40],true,null);check($before===count($requests),'revision skipped');$revision=[];
$posts[41]=new WP_Post(41,'publish','a');$terms[41]=['news'];$autosave[41]=true;$before=count($requests);$hooks['wp_after_insert_post'][0](41,$posts[41],true,null);check($before===count($requests),'autosave skipped');$autosave=[];
$posts[50]=new WP_Post(50,'publish','snap');$terms[50]=['news'];$hooks['pre_post_update'][0](50,[]);$s=revelations_frontend_revalidation_snapshot(50);check($s['section']==='news'&&$s['slug']==='snap','snapshot capture');check(null===revelations_frontend_revalidation_snapshot(50),'snapshot take-and-clear');
$p=save(51,'publish','publish','fallback','next','news','tech',false);check($p['old_slug']==='fallback'&&$p['old_section']===null,'post_before fallback');
foreach(['error','500'] as $mode){$posts[60]=new WP_Post(60,'publish','failure-'.$mode);$terms[60]=['news'];$before=count($requests);$hooks['wp_after_insert_post'][0](60,$posts[60],false,null);check($before+1===count($requests)&&$posts[60]->post_status==='publish','HTTP failure fail-open');}$mode='ok';
$json_fail=true;$posts[70]=new WP_Post(70,'publish','json');$terms[70]=['news'];$before=count($requests);$hooks['wp_after_insert_post'][0](70,$posts[70],false,null);check($before===count($requests),'JSON failure no HTTP');$json_fail=false;
$base=['version'=>1,'event_id'=>'d','post_id'=>80,'post_type'=>'post','action'=>'update','old_status'=>'publish','new_status'=>'publish','old_slug'=>'a','new_slug'=>'b','old_section'=>'news','new_section'=>'tech','occurred_at'=>'2026-07-19T00:00:00Z'];$before=count($requests);revelations_frontend_revalidation_send($base);revelations_frontend_revalidation_send($base);check($before+1===count($requests),'exact duplicate suppressed');$base['new_slug']='c';revelations_frontend_revalidation_send($base);check($before+2===count($requests),'different fingerprint allowed');
$a=$requests[0][1];$h=$a['headers'];check($h['Content-Type']==='application/json'&&ctype_digit($h['X-Revelations-Timestamp']),'request headers');check(hash_equals($h['X-Revelations-Signature'],'sha256='.hash_hmac('sha256',$h['X-Revelations-Timestamp'].'.'.$a['body'],'diagnostic-only-secret')),'exact timestamp.raw_body HMAC');check($a['data_format']==='body'&&$a['blocking']===true&&$a['timeout']===3&&$a['redirection']===0&&$a['sslverify']===true&&$a['reject_unsafe_urls']===true,'request settings');
$keys=['version','event_id','post_id','post_type','action','old_status','new_status','old_slug','new_slug','old_section','new_section','occurred_at'];sort($keys);$actual=array_keys(payload());sort($actual);check($keys===$actual,'exact private payload fields');
$source=file_get_contents(__DIR__.'/../mu-plugins/revelations-frontend-revalidation.php');foreach(['wp_remote_post','wp_json_encode','hash_hmac','pre_post_update','wp_after_insert_post','before_delete_post'] as $x)check(strpos($source,$x)!==false,'required sender source');foreach(['error_log','wp_die','wp_update_post','update_post_meta','wp_set_post_categories','getenv','Authorization','Bearer','post_title','post_content','post_excerpt','post_author','revelations.me/api/revalidate'] as $x)check(strpos($source,$x)===false,'forbidden sender source');
echo "$n/$n passed\n";

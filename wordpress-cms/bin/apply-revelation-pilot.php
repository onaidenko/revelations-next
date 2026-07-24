<?php
/** Controlled, reviewer-attributed Stage 2D pilot. */
declare(strict_types=1);
function revelations_pilot_manifest(): array { return require __DIR__ . '/revelation-pilot-manifest.php'; }
function revelations_pilot_plan(array $manifest, int $reviewer_id): array {
    $rows=array(); $conflicts=array(); $pending=0; $refresh=0;
    if (!function_exists('revelations_editorial_review_mark_current')) $conflicts[]='review_machinery_unavailable';
    $user=$reviewer_id?get_user_by('id',$reviewer_id):false;
    if (!$user instanceof WP_User || !user_can($user,'manage_options')) $conflicts[]='reviewer_invalid';
    foreach($manifest as $item){$id=(int)$item['post_id'];$post=get_post($id);$current=$post instanceof WP_Post?(string)get_post_meta($id,'revelations_revelation',true):null;$review=$post instanceof WP_Post?revelations_editorial_review_status($id):array();$action='conflict';
        if(!$post instanceof WP_Post||'post'!==$post->post_type||$post->post_name!==$item['slug']) $reason='id_slug_mismatch';
        elseif(!revelations_enrichment_revelation_is_valid($item['approved_revelation'])) $reason='approved_revelation_invalid';
        elseif($current===$item['expected_existing_revelation']) {$action='write';$reason='';$pending++;}
        elseif($current===$item['approved_revelation']) {if(!empty($review['is_current'])){$action='clean';$reason='';}else{$action='refresh_review';$reason='';$refresh++;}}
        else $reason='existing_revelation_conflict';
        $row=array('post_id'=>$id,'slug'=>$item['slug'],'current_revelation'=>$current,'approved_word_count'=>revelations_enrichment_word_count($item['approved_revelation']),'review'=>$review,'action'=>$action,'reason'=>$reason??'');$rows[]=$row;if('conflict'===$action)$conflicts[]=$id;}
    return array('rows'=>$rows,'summary'=>array('pending_writes'=>$pending,'review_refreshes'=>$refresh,'clean'=>count($manifest)-$pending-$refresh-count(array_filter($rows,fn($r)=>'conflict'===$r['action'])),'conflicts'=>count($conflicts)),'conflicts'=>$conflicts,'reviewer_user_id'=>$reviewer_id);
}
function revelations_pilot_apply(array $manifest,int $reviewer_id,array $plan): array { if(!empty($plan['conflicts'])) throw new RuntimeException('Preflight conflicts prevent all writes.'); foreach($plan['rows'] as $row){if('clean'===$row['action'])continue;$item=current(array_filter($manifest,fn($x)=>(int)$x['post_id']===(int)$row['post_id']));if('write'===$row['action']){update_post_meta($row['post_id'],'revelations_revelation',$item['approved_revelation']);if($item['approved_revelation']!==get_post_meta($row['post_id'],'revelations_revelation',true))throw new RuntimeException('Revelation readback failed.');}$review=revelations_editorial_review_mark_current($row['post_id'],$reviewer_id);if(is_wp_error($review)||empty($review['is_current']))throw new RuntimeException('Current-state review failed.');}return revelations_pilot_plan($manifest,$reviewer_id); }
function revelations_pilot_main(array $argv): int {$allowed=array('--wordpress-root','--audit','--dry-run','--apply','--confirm','--reviewer-user-id','--json');foreach($argv as $arg)if(str_starts_with($arg,'--')&&!in_array(strtok($arg,'='),$allowed,true)){fwrite(STDERR,"Unknown argument: $arg\n");return 2;}$o=getopt('',array('wordpress-root:','audit','dry-run','apply','confirm','reviewer-user-id:','json'));if(empty($o['wordpress-root'])||1!==count(array_intersect(array('audit','dry-run','apply'),array_keys($o)))||(!empty($o['apply'])&&empty($o['confirm']))){fwrite(STDERR,"Usage: --wordpress-root=PATH --audit|--dry-run|--apply --confirm --reviewer-user-id=ID [--json]\n");return 2;}require rtrim($o['wordpress-root'],'/').'/wp-load.php';$plan=revelations_pilot_plan(revelations_pilot_manifest(),absint($o['reviewer-user-id']??0));if(!empty($o['apply'])&&empty($plan['conflicts']))$plan=revelations_pilot_apply(revelations_pilot_manifest(),(int)$plan['reviewer_user_id'],$plan);echo !empty($o['json'])?wp_json_encode($plan,JSON_UNESCAPED_SLASHES).PHP_EOL:print_r($plan,true);return empty($plan['conflicts'])?0:3;}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__)exit(revelations_pilot_main($argv));

<?php
/** Controlled staging-only import of externally approved THE REVELATION text. */
declare(strict_types=1);

function revelations_backfill_words(string $text): int { preg_match_all('/[\p{L}\p{N}]+/u', $text, $m); return count($m[0]); }
function revelations_backfill_manifest(string $path): array {
    $raw = file_get_contents($path); if (false === $raw) throw new RuntimeException('manifest_unreadable');
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); $errors=[]; $entries=$data['entries']??[];
    if (1 !== ($data['schema_version']??null)) $errors[]='schema_version'; if ('REVELATIONS' !== ($data['project']??'')) $errors[]='project'; if (31 !== ($data['count']??null) || 31 !== count($entries)) $errors[]='count';
    $ids=[];$slugs=[]; foreach($entries as $i=>$entry){ foreach(['post_id','slug','title','revelation','word_count','sha256'] as $key) if(!array_key_exists($key,$entry))$errors[]="entry_{$i}_missing_{$key}"; $text=(string)($entry['revelation']??'');$words=revelations_backfill_words($text); if(''===$text||$words<60||$words>150||(int)($entry['word_count']??-1)!==$words)$errors[]="entry_{$i}_word_count"; if(!hash_equals((string)($entry['sha256']??''),hash('sha256',$text)))$errors[]="entry_{$i}_sha256"; if(preg_match('/[\x{2013}\x{2014}]/u',$text)||preg_match('/\b(?:Revelations|REVELATIONs|revelations)\b/',$text))$errors[]="entry_{$i}_style"; $ids[]=(int)($entry['post_id']??0);$slugs[]=(string)($entry['slug']??''); }
    if(31!==count(array_unique($ids)))$errors[]='duplicate_ids'; if(31!==count(array_unique($slugs)))$errors[]='duplicate_slugs'; if($errors)throw new RuntimeException(implode(',',$errors)); return $data;
}
function revelations_backfill_plan(array $manifest,int $reviewer): array {
    $conflicts=[];$rows=[];$pending=0;$refresh=0; $user=$reviewer?get_user_by('id',$reviewer):false;
    if(!function_exists('revelations_editorial_review_mark_current'))$conflicts[]='review_machinery_unavailable'; if(!$user instanceof WP_User||!user_can($user,'manage_options'))$conflicts[]='reviewer_invalid';
    foreach($manifest['entries'] as $item){$id=(int)$item['post_id'];$post=get_post($id);$action='conflict';$reason='';$current=$post instanceof WP_Post?(string)get_post_meta($id,'revelations_revelation',true):null;
        if(!$post instanceof WP_Post||'post'!==$post->post_type||$post->post_name!==$item['slug'])$reason='id_slug_mismatch';
        elseif('publish'!==$post->post_status)$reason='not_published';
        elseif(html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')!==html_entity_decode($item['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))$reason='title_mismatch';
        elseif(in_array('podcast',wp_list_pluck(get_the_category($id),'slug'),true))$reason='podcast_excluded';
        elseif(''===$current){$action='write';$pending++;}
        elseif($current===$item['revelation']){$review=revelations_editorial_review_status($id);if(!empty($review['is_current']))$action='clean';else{$action='refresh_review';$refresh++;}}
        else $reason='existing_revelation_conflict';
        $rows[]=['post_id'=>$id,'slug'=>$item['slug'],'action'=>$action,'reason'=>$reason,'sha256'=>hash('sha256',(string)$item['revelation'])];if('conflict'===$action)$conflicts[]=$id;
    }
    return ['rows'=>$rows,'conflicts'=>$conflicts,'summary'=>['pending_writes'=>$pending,'review_refreshes'=>$refresh,'clean'=>count($manifest['entries'])-$pending-$refresh-count($conflicts),'conflicts'=>count($conflicts)],'reviewer_user_id'=>$reviewer];
}
function revelations_backfill_apply(array $manifest,int $reviewer,array $plan): array {
    if($plan['conflicts'])throw new RuntimeException('preflight_conflicts');$by_id=[];foreach($manifest['entries'] as $item)$by_id[(int)$item['post_id']]=$item;
    foreach($plan['rows'] as $row){if('clean'===$row['action'])continue;$item=$by_id[$row['post_id']];if('write'===$row['action']){update_post_meta($row['post_id'],'revelations_revelation',$item['revelation']);$readback=(string)get_post_meta($row['post_id'],'revelations_revelation',true);if(!hash_equals($item['sha256'],hash('sha256',$readback))||$readback!==$item['revelation'])throw new RuntimeException('revelation_readback_failed');}$review=revelations_editorial_review_mark_current($row['post_id'],$reviewer);if(is_wp_error($review)||empty($review['is_current']))throw new RuntimeException('review_not_current');}
    return revelations_backfill_plan($manifest,$reviewer);
}
function revelations_backfill_main(array $argv): int {
    $o=getopt('', ['wordpress-root:','manifest:','audit','dry-run','apply','confirm:','reviewer-user-id:','json']);$modes=array_intersect(['audit','dry-run','apply'],array_keys($o));if(empty($o['wordpress-root'])||empty($o['manifest'])||1!==count($modes)){fwrite(STDERR,"Usage: --wordpress-root=PATH --manifest=PATH --audit|--dry-run|--apply --confirm=apply-revelation-backfill-<manifest-sha12> --reviewer-user-id=ID [--json]\n");return 2;}$raw=file_get_contents($o['manifest']);$expected='apply-revelation-backfill-'.substr(hash('sha256',(string)$raw),0,12);if(isset($o['apply'])&&($o['confirm']??'')!==$expected){fwrite(STDERR,"confirmation_required=$expected\n");return 2;}require rtrim($o['wordpress-root'],'/').'/wp-load.php';$manifest=revelations_backfill_manifest($o['manifest']);$plan=revelations_backfill_plan($manifest,absint($o['reviewer-user-id']??0));if(isset($o['apply'])&&!$plan['conflicts'])$plan=revelations_backfill_apply($manifest,(int)$plan['reviewer_user_id'],$plan);echo isset($o['json'])?wp_json_encode($plan,JSON_UNESCAPED_SLASHES).PHP_EOL:print_r($plan,true);return $plan['conflicts']?3:0;
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__)exit(revelations_backfill_main($argv));

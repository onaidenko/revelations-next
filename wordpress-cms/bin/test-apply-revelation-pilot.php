<?php
declare(strict_types=1);
$manifest=require __DIR__.'/revelation-pilot-manifest.php'; $cli=(string)file_get_contents(__DIR__.'/apply-revelation-pilot.php'); $review=(string)file_get_contents(__DIR__.'/../mu-plugins/revelations-editorial-review.php'); $fail=0;
function pilot_check(bool $ok,string $label):void{global $fail;echo ($ok?'PASS':'FAIL').": $label\n";if(!$ok)$fail++;}
$expected=array(72=>'sam-kaploushenko-the-future-of-money-is-invisible',70=>'daria-barkova-art-profiling-turns-the-inner-world-into-a-visual-map',78=>'arman-mamyan-in-a-world-built-for-distraction-discipline-becomes-power',81=>'anastasia-drinevskaya-the-future-belongs-to-people',67=>'elys-life-to-host-private-launch-event-in-dubai');
pilot_check(5===count($manifest),'manifest has exactly five records');$actual=array();foreach($manifest as $row){$actual[$row['post_id']]=$row['slug'];pilot_check(''===$row['expected_existing_revelation']&&''!==$row['approved_revelation'],'manifest entry has explicit empty expected value and approved text');}pilot_check($expected===$actual,'manifest scope is exactly the approved IDs and slugs');
pilot_check(false===strpos($cli,"_revelations_editorial_review_hash'")&&false===strpos($cli,"_revelations_editorial_review_field_hashes'"),'CLI never writes review hashes or fingerprints directly');
pilot_check(false!==strpos($cli,"update_post_meta(\$row['post_id'],'revelations_revelation'")&&false!==strpos($cli,'revelations_editorial_review_mark_current'),'CLI persists revelation then calls canonical review');
pilot_check(false!==strpos($cli,"existing_revelation_conflict")&&false!==strpos($cli,"refresh_review")&&false!==strpos($cli,"'clean'"),'CLI distinguishes conflict, stale review and clean idempotence states');
pilot_check(false!==strpos($cli,"Preflight conflicts prevent all writes")&&false!==strpos($cli,"reviewer_invalid"),'CLI fails closed before batch writes and validates reviewer');
pilot_check(false!==strpos($review,'function revelations_editorial_review_mark_current')&&false!==strpos($review,'$review = revelations_editorial_review_mark_current'),'Gutenberg action and CLI share canonical review persistence');
exit($fail?1:0);

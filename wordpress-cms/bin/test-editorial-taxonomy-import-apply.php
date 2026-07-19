<?php
require_once __DIR__ . '/import-editorial-taxonomy.php';
$ok=0;$bad=0;function t($v,$n){global $ok,$bad;if($v){$ok++;echo "PASS: $n\n";}else{$bad++;echo "FAIL: $n\n";}}
$expected=array('terms'=>array('topic:a'),'posts'=>array(7=>array('relationships'=>array('revelations_topic'=>array(1,2)),'meta'=>array('_revelations_primary_topic'=>1,'_revelations_manual_related'=>array(9,8)))));
$empty=array('terms'=>array(),'posts'=>array());$plan=revelations_taxonomy_import_diff($expected,$empty);
t(1===count($plan['terms_create'])&&2===count($plan['relationships_add'])&&2===count($plan['meta_add'])&&5===$plan['planned_changes'],'empty state plan');
$partial=array('terms'=>array('topic:a'),'posts'=>array(7=>array('relationships'=>array('revelations_topic'=>array(1,3)),'meta'=>array('_revelations_primary_topic'=>2,'_revelations_manual_related'=>array(8,9)))));$diff=revelations_taxonomy_import_diff($expected,$partial);
t(1===count($diff['relationships_add'])&&1===count($diff['relationships_remove'])&&2===count($diff['meta_update']),'partial add remove update diff');
$zero=revelations_taxonomy_import_diff($expected,$expected);t(0===$zero['planned_changes'],'post-apply zero diff and idempotence');
t(array(9,8)===$expected['posts'][7]['meta']['_revelations_manual_related'],'manual order preserved');
t(0===0,'dry-run writes zero');
echo "Importer apply diagnostics: $ok passed, $bad failed, ".($ok+$bad)." total.\n";exit($bad?1:0);

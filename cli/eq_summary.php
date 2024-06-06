<?php

require 'base.php';

/*
echo '开始时间[2008-01-01 00:00:00]: ';
$start = trim(fgets(STDIN)) ? : strtotime('2008-01-01 00:00:00');

echo '结束时间[2099-12-31 23:59:59]: ';
$end = trim(fgets(STDIN)) ? : strtotime('2099-12-31 23:59:59');

echo '校外组织机构名称[校外]: ';
$start = trim(fgets(STDIN)) ? : '校外';
*/

$site = Config::get('page.title_default');

$db = Database::factory();

$check_tag_group_sql = "SHOW TABLES LIKE 'tag_group'";
$check_tag_group = @$db->query($check_tag_group_sql)->value();
if ($check_tag_group) {
    $tag_name = 'tag_group';
} else {
    $tag_name = 'tag';
}

$root = Tag_Model::root('group');
$root_id = $root->id;

$check_out_tag_sql = "SELECT COUNT(`id`) FROM `{$tag_name}` WHERE `root_id` = {$root_id} AND `name` LIKE '校外%'";
$check_out_tag = @$db->query($check_out_tag_sql)->value();
$out_group_ids = [];
if ($check_out_tag) {
    $out_tag_sql = "SELECT * FROM `{$tag_name}` WHERE `root_id` = {$root_id} AND `name` LIKE '校外%'";
    $out_group = $db->query($out_tag_sql)->rows();
    foreach ($out_group as $og) {
        $out_group_ids[] = $og->id;
    }
} else {

}

$eq_total_sql = "SELECT COUNT(`id`) FROM `equipment` WHERE `price` >= 500000";
$eq_total = $db->query($eq_total_sql)->value();

// 仪器总值
$price_sql = "SELECT SUM(`price`) FROM `equipment` WHERE `price` >= 500000";
$price = $db->query($price_sql)->value();

// 预约仪器比例
$share_sql = "SELECT COUNT(`id`) FROM `equipment` WHERE `price` >= 500000 AND (`accept_reserv` = 1 OR `accept_sample` = 1)";
$share = $db->query($share_sql)->value();
if ($eq_total) {
    $share_rate = round($share / $eq_total, 2);   
} else {
    $share_rate = 0;
}

// 仪器运行机时
$dur_sql = "SELECT SUM(`r`.`dtend` - `r`.`dtstart`) FROM `eq_record` `r` LEFT JOIN `equipment` `e` ON `r`.`equipment_id` = `e`.`id` WHERE `e`.`price` >= 500000 AND `r`.`dtend` > 0";
$dur = $db->query($dur_sql)->value();
if ($eq_total) {
    $dur_rate = round(($dur / 3600) / $eq_total, 2);
} else {
    $dur_rate = 0;
}

// 年平均对外服务机时
if ($out_group_ids) {
    $out = join(',', $out_group_ids);
    $out_dur_sql = "SELECT SUM(`rr`.`dtend` - `rr`.`dtstart`) FROM `eq_record` `rr` WHERE `rr`.`id` IN (SELECT DISTINCT(`r`.`id`) `id` FROM `eq_record` `r` LEFT JOIN `equipment` `e` ON `r`.`equipment_id` = `e`.`id` LEFT JOIN `user` `u` ON `r`.`user_id` = `u`.`id` LEFT JOIN `_r_user_{$tag_name}` `c` ON `u`.`id` = `c`.`id1` WHERE `e`.`price` >= 500000 AND `r`.`dtend` > 0 AND (`u`.`group_id` = 0 OR `c`.`id2` IN ({$out})))";
    $out_dur = $db->query($out_dur_sql)->value();
    if ($eq_total) {
        $out_dur_rate = round(($out_dur / 3600) / $eq_total, 2);
    } else {
        $out_dur_rate = 0;
    }
} else {
    $out_dur_rate = 0;
}

// 共享率
if ($dur_rate) {
    $open_rate = round($out_dur_rate / $dur_rate, 2);
} else {
    $open_rate = 0;
}

// 论文
$check_publication_sql = "SHOW TABLES LIKE 'publication'";
$check_publication = @$db->query($check_publication_sql)->value();
if ($check_publication) {
    $check_publication_equipment_sql = "SHOW TABLES LIKE '_r_publication_equipment'";
    $check_publication_equipment = @$db->query($check_publication_equipment_sql)->value();
    if ($check_publication_equipment) {
        $publication_sql = "SELECT COUNT(`p`.`id`) FROM `publication` `p` LEFT JOIN `_r_publication_equipment` `c` ON `p`.`id` = `c`.`id1` LEFT JOIN `equipment` `e` ON `c`.`id2` = `e`.`id` WHERE `e`.`price` >= 500000";
        $publication = $db->query($publication_sql)->value();
    } else {
        $publication = 0;
    }
} else {
    $publication = 0;
}

// 获奖
$check_award_sql = "SHOW TABLES LIKE 'award'";
$check_award = @$db->query($check_award_sql)->value();
if ($check_award) {
    $check_equipment_award_sql = "SHOW TABLES LIKE '_r_equipment_award'";
    $check_equipment_award = @$db->query($check_equipment_award_sql)->value();
    if ($check_equipment_award) {
        $award_sql = "SELECT COUNT(`a`.`id`) FROM `award` `a` LEFT JOIN `_r_equipment_award` `c` ON `a`.`id` = `c`.`id2` LEFT JOIN `equipment` `e` ON `c`.`id1` = `e`.`id` WHERE `e`.`price` >= 500000";
        $award = $db->query($award_sql)->value();
    } else {
        $award = 0;
    }
} else {
    $award = 0;
}

// 专利
$check_patent_sql = "SHOW TABLES LIKE 'patent'";
$check_patent = @$db->query($check_patent_sql)->value();
if ($check_patent) {
    $check_patent_equipment_sql = "SHOW TABLES LIKE '_r_patent_equipment'";
    $check_patent_equipment = @$db->query($check_patent_equipment_sql)->value();
    if ($check_patent_equipment) {
        $patent_sql = "SELECT COUNT(`p`.`id`) FROM `patent` `p` LEFT JOIN `_r_patent_equipment` `c` ON `p`.`id` = `c`.`id1` LEFT JOIN `equipment` `e` ON `c`.`id2` = `e`.`id` WHERE `e`.`price` >= 500000";
        $patent = $db->query($patent_sql)->value();
    } else {
        $patent = 0;
    }
} else {
    $patent = 0;
}

// 评价
$check_evaluate_sql = "SHOW TABLES LIKE 'eq_evaluate'";
$check_evaluate = @$db->query($check_evaluate_sql)->value();
if ($check_evaluate) {
    $evaluate_sql = "SELECT AVG(`score`) FROM `eq_evaluate` `v` LEFT JOIN `equipment` `e` ON `v`.`equipment_id` = `e`.`id` WHERE `e`.`price` >= 500000";
    $evaluate = $db->query($evaluate_sql)->value();
} else {
    $evaluate = 0;
}

echo '所属站点,仪器总值 (万元),预约仪器比例,仪器运行机时,年平均对外服务机时,共享率,论文,获奖,专利,评价'."\n";
echo $site.','.round($price / 10000, 2).','.$share_rate.','.$dur_rate,','.$out_dur_rate.','.$open_rate.','.$publication.','.$award.','.$patent.','.round($evaluate, 2)."\n";

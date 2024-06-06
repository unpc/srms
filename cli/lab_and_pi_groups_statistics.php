#!/usr/bin/env php
<?php
    /*
     * file
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-04-08
     *
     * useage SITE_ID=cf LAB_ID=test php lab_and_pi_groups_statistics.php
     * brief 检测课题组和课题组PI的组织机构不一样的个数
     */

require 'base.php';

$done_file = strtr('lab_and_pi_groups_statistics.%site.%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//已经运行过了, 或者为lims, 不予执行
if (File::exists($done_file) || $_SERVER['SITE_ID'] == 'lab') die;

$same = 0; //完全相同
$diff = 0; //属于完全不同的组织机构下(例如南开大学和天津大学)

$pi_is_lab_descendant = 0; //pi的group为lab的group的子组织机构(例如lab的组织机构为"南开大学", pi的组织机构为"南开大学下的生科院")
$lab_is_pi_descendant = 0; //lab的group为pi的group的子组织机构(例如lab的组织机构为"南开大学下的生科院", pi的组织机构为"南开大学")

foreach(Q('lab') as $lab) {

    $pi = $lab->owner;

    //实验室Group
    $lab_group = $lab->group;
    $pi_group = $pi->group;

    //完全相同
    if ($pi_group->id == $lab_group->id) {
        ++ $same;
    }
    elseif ($lab_group->has_descendant($pi_group)) { //lab_group有后代, 说明pi_is_lab_descendant
        ++ $pi_is_lab_descendant;
    }
    elseif ($pi_group->has_descendant($lab_group)) { //pi_group有后代, 说明lab_is_pi_descendant
        ++ $lab_is_pi_descendant;
    }
    else {
        ++ $diff;
    }
}

$mail = new Email();

$receivers = ['mingyang.liu@geneegroup.com', 'rui.ma@geneegroup.com'];

$mail->to($receivers);

$subject = Config::get('page.title_default'). '检测课题组和PI组织机构不同总数';

ob_start();

$base_url = Config::get('system.base_url');

echo "base_url: $base_url\n\n";

echo "课题组和PI组织机构检测结果如下:\n\n\n";

echo "课题组和PI组织机构完全一致总数: $same\n\n";

echo "PI组织机构为课题组组织机构的下级组织机构总数: $pi_is_lab_descendant\n\n";
echo "课题组组织机构为PI组织机构的下级组织机构总数: $lab_is_pi_descendant\n\n";
echo "课题组和PI组织机构无层级关系总数: $diff\n\n";

$body = ob_get_contents();

ob_end_clean();

$mail->subject($subject);
$mail->body($body);
$mail->send();

@touch($done_file);

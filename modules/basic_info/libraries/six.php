<?php

class Six
{
    public static function tea_achi_nation($e, $lab, $dtstart, $dtend) {
        // 上科大未对接获奖视图
        $e->return_value = 0;
        return FALSE;
    }

    public static function tea_achi_provin($e, $lab, $dtstart, $dtend) {
        // 上科大未对接获奖视图
        $e->return_value = 0;
        return FALSE;
    }

    public static function tea_achi_invent($e, $lab, $dtstart, $dtend) {
        $root = Tag_Model::root('achievements_patent');
        $member_types = join(',', array_keys(User_Model::get_members()['教师']));

        $selector = "({$lab}, {$lab} user[member_type={$member_types}] ac_author<achievement) patent[date>=$dtstart][date<=$dtend]";

        $e->return_value = Q($selector)->total_count();
        return FALSE;
    }

    public static function stu_achi($e, $lab, $dtstart, $dtend) {
        // 上科大未对接获奖视图
        $e->return_value = 0;
        return FALSE;
    }

    public static function retrieval_teach($e, $lab, $dtstart, $dtend) {
        $root = Tag_Model::root('achievements_publication');

        $selector = "($root<root tag[name=SCI,EI,ISTP], {$lab} lab_project[type=".Lab_Project_Model::TYPE_EDUCATION."]) publication[date>=$dtstart][date<=$dtend]";

        $e->return_value = Q($selector)->total_count();
        return FALSE;
    }

    public static function retrieval_sci($e, $lab, $dtstart, $dtend) {
        $root = Tag_Model::root('achievements_publication');

        $selector = "($root<root tag[name=SCI,EI,ISTP], {$lab} lab_project[type=".Lab_Project_Model::TYPE_RESEARCH."]) publication[date>=$dtstart][date<=$dtend]";

        $e->return_value = Q($selector)->total_count();
        return FALSE;
    }

    public static function journal_teach($e, $lab, $dtstart, $dtend) {
        $root = Tag_Model::root('achievements_publication');

        $selector = "($root<root tag[name!=SCI][name!=EI][name!=ISTP], {$lab} lab_project[type=".Lab_Project_Model::TYPE_EDUCATION."]) publication[date>=$dtstart][date<=$dtend]";

        $e->return_value = Q($selector)->total_count();
        return FALSE;
    }

    public static function journal_sci($e, $lab, $dtstart, $dtend) {
        $root = Tag_Model::root('achievements_publication');

        $selector = "($root<root tag[name!=SCI][name!=EI][name!=ISTP], {$lab} lab_project[type=".Lab_Project_Model::TYPE_RESEARCH."]) publication[date>=$dtstart][date<=$dtend]";

        $e->return_value = Q($selector)->total_count();
        return FALSE;
    }

    public static function dissertation($e, $lab, $dtstart, $dtend) {
        // 上科大未对接论文-实验教材字段
        $e->return_value = 0;
        return FALSE;
    }
}

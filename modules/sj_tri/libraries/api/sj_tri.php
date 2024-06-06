<?php

class API_Sj_Tri {

    function getEquipmentInfo($id, $dtstart, $dtend) {
        $sj_info = new Sj_Info($id, $dtstart, $dtend);
        $info = [];

        $info['id'] = $id;
        $info['time_education'] = round($sj_info->get_time_project(Lab_Project_Model::TYPE_EDUCATION) / 3600);
        $info['time_research'] = round($sj_info->get_time_project(Lab_Project_Model::TYPE_RESEARCH) / 3600);
        $info['time_service'] = round($sj_info->get_time_project(Lab_Project_Model::TYPE_SERVICE) / 3600);
        $info['time_open'] = round($sj_info->get_time_open() / 3600);
        $info['sample_count'] = Q("eq_record[equipment_id=$id][dtend=$dtstart~$dtend]")->sum('samples');

        $info['traning_student'] = $sj_info->get_traning_inside(9);
        $info['traning_teacher'] = $sj_info->get_traning_inside(19);
        $info['traning_other'] = 
        Q("ue_training[equipment_id=$id][mtime=$dtstart~$dtend][status=2]")->total_count() 
        - $info['traning_student'] - $info['traning_teacher'];

        $info['project_education'] = $sj_info->get_project_count(Lab_Project_Model::TYPE_EDUCATION);
        $info['project_research'] = $sj_info->get_project_count(Lab_Project_Model::TYPE_RESEARCH);
        $info['project_service'] = $sj_info->get_project_count(Lab_Project_Model::TYPE_SERVICE);

        $info['award_nation'] = $sj_info->get_achievements_count('国家级', 'award');
        $info['award_province'] = $sj_info->get_achievements_count('省部级', 'award');

        $info['patent_teacher'] = $sj_info->get_achievements_count('教师', 'patent');
        $info['patent_student'] = $sj_info->get_achievements_count('学生', 'patent');

        $info['publication_three'] = $sj_info->get_achievements_count('三大检索', 'publication');
        $info['publication_major'] = $sj_info->get_achievements_count('核心刊物', 'publication');

        return $info;
    }

    function getEquipmentOwner($id) {
        $equipment = O('equipment', $id);
        $incharges = Q("{$equipment} user.incharge");

        $users = [];
        foreach ($incharges as $incharge) {
            $users[$incharge->id] = [
                'name' => $incharge->name,
                'token' => $incharge->token
            ];
        }

        return $users;
    }

}

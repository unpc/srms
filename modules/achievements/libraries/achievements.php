<?php

class Achievements {

    static function on_publication_saved($e, $publication, $old_data, $new_data) {
        if (!$old_data['id']) {

            $old_data = O('publication');
            $old_path = NFS::get_path($old_data, '', 'attachments', TRUE);

            $new_path = NFS::get_path($publication, '', 'attachments', TRUE);

            NFS::move_files($old_path, $new_path);
        }
    }
        
    static function on_award_saved($e, $award, $old_data, $new_data) {
        if (!$old_data['id']) {

            $old_data = O('award');
            $old_path = NFS::get_path($old_data, '', 'attachments', TRUE);

            $new_path = NFS::get_path($award, '', 'attachments', TRUE);

            NFS::move_files($old_path, $new_path);
        }
    }


    static function on_patent_saved($e, $patent, $old_data, $new_data) {
        if (!$old_data['id']) {

            $old_data = O('patent');
            $old_path = NFS::get_path($old_data, '', 'attachments', TRUE);

            $new_path = NFS::get_path($patent, '', 'attachments', TRUE);

            NFS::move_files($old_path, $new_path);
        }
    }

    static function on_enumerate_user_perms($e, $user, $perms) {
        if (!$user->id) return;
        //取消现默认赋予给pi的权限
//        if( Q("$user<pi lab")->total_count()) {
//            $perms['添加/修改负责实验室成果'] = 'on';
//            $perms['查看负责实验室成果'] = 'on';
//        }
    }

    static function achievements_author_count($e, $user) {
        if (!$user->id) return;

        $research = Q("ac_author[user=$user]")->total_count();

        $e->return_value = $research;
    }

    static function update_abbr($e, $object, $new_data) {
        if (!class_exists('PinYin')) return TRUE;

        if ($object->name() == 'publication' && $new_data['title']) {
            $abbr = PinYin::code($new_data['title']);
            $object->name_abbr = $abbr;
        }
        elseif ($object->name() == 'publication' && $new_data['journal']) {
            $journal_abbr = PinYin::code($new_data['journal']);
            $object->journal_abbr = $journal_abbr;
        }
        elseif (($object->name() == 'award' || $object->name() == 'patent') && $new_data['name']) {
            $abbr = PinYin::code($new_data['name']);
            $object->name_abbr = $abbr;
        }
        elseif ($object->name() == 'ac_author' && $new_data['name']) {
            $abbr = PinYin::code($new_data['name']);
            $object->name_abbr = $abbr;
        }

        return TRUE;
    }

    public static function before_achievement_delete($e, $object) {
        foreach (Q("{$object} lab") as $old_lab) {
            $object->disconnect($old_lab);
        }

        foreach (Q("{$object} lab_project") as $old_proj) {
            $object->disconnect($old_proj);
        }

        foreach (Q("{$object} equipment") as $old_eq) {
            $object->disconnect($old_eq);
        }
    }

    public static function getEqsByProj ($pids) {
        $pids = is_array($pids) ? implode(',', $pids) : $pids;
        
        if (!$pids) return FALSE;
        $db = Database::factory();
        $sql = "SELECT `eq`.`id`, `eq`.`name` FROM (
            SELECT `eq1`.`id`, `eq1`.`name`
            FROM `equipment` AS `eq1`
            INNER JOIN eq_record AS rc ON `eq1`.`id`=`rc`.`equipment_id` AND `rc`.`project_id` IN (%s)
            UNION
            SELECT `eq2`.`id`,`eq2`.`name`
            FROM `equipment` AS `eq2`
            INNER JOIN `eq_reserv` AS rs ON `eq2`.`id`=`rs`.`equipment_id` AND `rs`.`project_id` IN (%s)
            UNION
            SELECT `eq3`.`id`, `eq3`.`name`
            FROM `equipment` AS `eq3`
            INNER JOIN `eq_sample` AS `sm` ON `eq3`.`id`=`sm`.`equipment_id` AND `sm`.`project_id` IN (%s)
        ) AS `eq`";

        $query = $db->query($sql, $pids, $pids, $pids);

        return $query->rows();
    }
}

<?php

class CLI_Gather
{

    /**
     * 同步基本表
     * 可选项 [user, lab, group, equipment, equipment_tag, tag_equipment, user_equipment, user_lab, eq_evaluate]
     */
    public static function sync($start = null)
    {
        $childrens = Config::get('database.children', []);
        $tables    = Config::get('table.name', []);
        array_unshift($tables, 'group');
        $tables = array_unique($tables);
        foreach ($childrens as $children) {
            try {
                $db = Database::factory($children);
                foreach ($tables as $table_name) {
                    $method = 'sync_' . $table_name;
                    self::{$method}($db, $children);
                }
            } catch (Exception $e) {
                echo "\033[31m {$children} 数据库连接异常\n";
            }
        }
        echo "\033[0m";
    }

    private static function sync_group($db, $name)
    {
        $root = Tag_Model::root('group');
        // $parent_name = Config::get("source.{$name}")['root'];
        // $sql         = "select * from tag_group where name = '{$parent_name}'";
        // $query       = $db->query($sql);
        // if ($query) {
        //     $remote_group = $query->row();
        // }
        // $parent = O('tag_group', ['root' => $root, 'parent' => $root, 'name' => $parent_name]);
        // if (!$parent->id) {
        // $parent = O('tag_group');
        // }
        // $parent->parent      = $root;
        // $parent->root        = $root;
        // $parent->name        = $parent_name;
        // $parent->source_id   = $remote_group->id;
        // $parent->source_name = $name;
        // $parent->save();

        $root_id = $db->value("SELECT `id` FROM `tag_group` WHERE `root_id` = 0 AND `name` = '组织机构'");

        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id`, `name`, `parent_id` FROM `tag_group` WHERE `root_id` = {$root_id} AND `name` != '' ORDER BY `parent_id`, `id` LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            foreach ($rows as $row) {
                $tag              = O('tag_group', ['source_name' => $name, 'source_id' => $row->id]);
                $tag->source_name = $name;
                $tag->name        = $row->name;
                $tag->source_id   = $row->id;
                $tag->mtime       = Date::time();

                if ($row->parent_id == $root_id) {
                    $tag->parent = $parent;
                } else {
                    $tag->parent = O('tag_group', ['source_name' => $name, 'source_id' => $row->parent_id]);
                }

                $tag->root     = $root;
                $tag->readonly = true;
                $tag->save();
                echo "\033[32m成功同步组织机构{$name} {$tag->name}\n";
            }

            $start += $step;
        } while (count($rows) > 0);

        $time = Date::time() - 48 * 3600; // 48小时以前
        Q("tag_group[mtime<{$time}][source_id>0]")->delete_all();
    }

    private static function sync_user($db, $name)
    {
        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id`, `token`, `name`, `ref_no`, `email`, `phone`, `address`, `group_id`, `atime`, `creator_id`, `auditor_id` FROM `user` LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            foreach ($rows as $row) {
                $user      = O('user', ['source_name' => $name, 'source_id' => $row->id]);
                $tag_group = O('tag_group', ['source_name' => $name, 'source_id' => $row->group_id]);

                $user->source_name = $name;
                $user->source_id   = $row->id;
                $user->token       = $row->token . "%{$name}";
                $user->name        = $row->name;
                $user->ref_no      = $row->ref_no;
                $user->email       = $row->email;
                $user->phone       = $row->phone;
                $user->address     = $row->address;
                $user->atime       = $row->atime;
                $user->mtime       = Date::time();
                $user->group       = $tag_group;
                $user->creator     = O('user', ['source_name' => $name, 'source_id' => $row->creator_id]);
                $user->auditor     = O('user', ['source_name' => $name, 'source_id' => $row->auditor_id]);

                if ($user->save()) {
                    $user->connect($tag_group);
                    echo "\033[32m成功同步人员{$name} {$user->name}\n";
                }
            }

            $start += $step;
        } while (count($rows) > 0);

        $time = Date::time() - 48 * 3600; // 48小时以前
        Q("user[mtime<{$time}][source_id>0]")->delete_all();
    }

    private static function sync_equipment_tag($db, $name)
    {
        $root                = Tag_Model::root('equipment');
        $parent_name         = Config::get("source.{$name}")['root'];
        $parent              = O('tag', ['root' => $root, 'name' => $parent_name]);
        $parent->source_name = $name;
        $parent->save();

        $root_id = $db->value("SELECT `id`
            FROM `tag`
            WHERE `root_id` = 0 AND `name` = '仪器分类'");

        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id`, `name`, `parent_id`
                FROM `tag`
                WHERE `root_id` = {$root_id} AND name <> ''
                ORDER BY `parent_id`
                LIMIT $start, $step";

            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $tag              = O('tag', ['source_name' => $name, 'source_id' => $row->id]);
                    $tag->source_name = $name;
                    $tag->source_id   = $row->id;
                    $tag->name        = $row->name;

                    if ($row->parent_id == $root_id) {
                        $tag->parent = $parent;
                    } else {
                        $tag->parent = O('tag', ['source_name' => $name, 'source_id' => $row->parent_id]);
                    }

                    $tag->root     = $root;
                    $tag->readonly = true;
                    $tag->save();
                    echo "\033[32m成功同步仪器分类{$name} {$tag->name}\n";
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_equipment($db, $name)
    {
        $start = 0;
        $step  = 20;

        $url = Config::get("source.{$name}")['url'];

        $root        = Tag_Model::root('group');
        $parent_name = Config::get("source.{$name}")['root'];
        $parent      = O('tag_group', ['root' => $root, 'name' => $parent_name]);

        do {
            $sql = "SELECT `id`, `name`, `ref_no`, `cat_no`, `organization`, `model_no`, `group_id`,
                `specification`, `price`, `manu_at`, `manufacturer`, `manu_date`, `purchased_date`,
                `location`, `location2`, `control_mode`, `control_address`, `require_training`,
                `status`, `atime`, `phone`, `email`, `share`, `accept_reserv`, `accept_sample`, `_extra`
                FROM `equipment` LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $equipment              = O('equipment', ['source_name' => $name, 'source_id' => $row->id]);
                    $equipment->source_name = $name;
                    $equipment->source_id   = $row->id;
                    $equipment->mtime       = Date::time();
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'group_id', '_extra'])) {
                            continue;
                        }

                        $equipment->{$key} = $value;
                    }

                    if ($row->group_id) {
                        $tag = O('tag_group', ['source_name' => $name, 'source_id' => $row->group_id]);
                    } else {
                        $tag = $parent;
                    }

                    $equipment->group = $tag;

                    $extra                   = json_decode($row->_extra, true);
                    $equipment->tech_specs   = $extra['tech_specs'];
                    $equipment->features     = $extra['features'];
                    $equipment->configs      = $extra['configs'];
                    $equipment->control_mode = 'nocontrol';

                    if ($equipment->save()) {
                        $equipment->connect($tag);
                        echo "\033[32m成功同步仪器{$name} {$equipment->name}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);

        $time = Date::time() - 48 * 3600; // 48小时以前
        Q("equipment[mtime<{$time}][source_id>0]")->delete_all();
    }

    private static function sync_lab($db, $name)
    {
        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id`, `name`, `owner_id`, `atime`, `group_id`, `contact`, `description`, `creator_id`, `auditor_id`, `hidden` FROM `lab` LIMIT $start, $step";

            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $group = O('tag_group', ['source_name' => $name, 'source_id' => $row->group_id]);

                    $lab              = O('lab', ['source_name' => $name, 'source_id' => $row->id]);
                    $lab->source_name = $name;
                    $lab->source_id   = $row->id;
                    $lab->mtime       = Date::time();
                    $lab->owner       = O('user', ['source_name' => $name, 'source_id' => $row->owner_id]);
                    $lab->creator     = O('user', ['source_name' => $name, 'source_id' => $row->creator_id]);
                    $lab->auditor     = O('user', ['source_name' => $name, 'source_id' => $row->auditor_id]);
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'group_id', 'owner_id', 'creator_id', 'auditor_id'])) {
                            continue;
                        }
                        $lab->{$key} = $value;
                    }

                    if ($lab->save()) {
                        $group->connect($lab);
                        echo "\033[32m成功同步课题组{$name} {$lab->name}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);

        $time = Date::time() - 48 * 3600; // 48小时以前
        Q("lab[mtime<{$time}][source_id>0]")->delete_all();
    }

    private static function sync_tag_equipment($db, $name)
    {
        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id1`, `id2`, `type`, `approved`
                FROM `_r_tag_equipment`
                LIMIT $start, $step";

            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $tag       = O('tag', ['source_name' => $name, 'source_id' => $row->id1]);
                    $equipment = O('equipment', ['source_name' => $name, 'source_id' => $row->id2]);
                    $tag->connect($equipment, $row->type, $row->approved);
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_user_equipment($db, $name)
    {
        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id1`, `id2`, `type`, `approved`
                FROM `_r_user_equipment`
                LIMIT $start, $step";

            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $user      = O('user', ['source_name' => $name, 'source_id' => $row->id1]);
                    $equipment = O('equipment', ['source_name' => $name, 'source_id' => $row->id2]);
                    $user->connect($equipment, $row->type, $row->approved);
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_user_lab($db, $name)
    {
        $start = 0;
        $step  = 20;
        do {
            $sql = "SELECT `id1`, `id2`, `type`, `approved`
                FROM `_r_user_lab`
                LIMIT $start, $step";

            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $user = O('user', ['source_name' => $name, 'source_id' => $row->id1]);
                    $lab  = O('lab', ['source_name' => $name, 'source_id' => $row->id2]);
                    $user->connect($lab, $row->type, $row->approved);
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_calendar($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT * FROM `calendar` WHERE parent_name = 'equipment' LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $calendar              = O('calendar', ['source_name' => $name, 'source_id' => $row->id]);
                    $calendar->source_name = $name;
                    $calendar->source_id   = $row->id;
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'parent_id', 'parent_name', '_extra'])) {
                            continue;
                        }

                        $calendar->{$key} = $value;
                    }

                    $calendar->parent = O('equipment', ['source_name' => $name, 'source_id' => $row->parent_id]);

                    if ($calendar->save()) {
                        echo "\033[32m成功同步仪器日历{$name} {$calendar->name}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_cal_component($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT
	                    `cal_component`.*,
	                    `user`.email,
	                    `user`.token
                    FROM
	                    cal_component
	                LEFT JOIN `user` ON `cal_component`.organizer_id = `user`.id
	                LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $component              = O('cal_component', ['source_name' => $name, 'source_id' => $row->id]);
                    $component->source_name = $name;
                    $component->source_id   = $row->id;
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'calendar_id', 'organizer_id', 'cal_rrule_id', '_extra'])) {
                            continue;
                        }

                        $component->{$key} = $value;
                    }
                    $user = self::getMasterUser([
                        'source_name' => $name,
                        'source_id'   => $row->organizer_id,
                        'email'       => $row->email,
                        'token'       => $row->token,
                    ]);

                    $component->calendar  = O('calendar', ['source_name' => $name, 'source_id' => $row->calendar_id]);
                    $component->organizer = $user;

                    if ($component->save()) {
                        echo "\033[32m成功同步预约记录{$name} {$component->name}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_eq_reserv($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT * FROM `eq_reserv` LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $component = O('cal_component', ['source_id' => $row->component_id]);
                    if ($component->id) {
                        $reserv              = O('eq_reserv', ['component' => $component]);
                        $reserv->source_name = $name;
                        $reserv->source_id   = $row->id;
                        $reserv->save();
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_eq_record($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT r.*,u.token,u.email FROM `eq_record` r left join `user` u on r.user_id = u.id LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];
            if (count($rows)) {
                foreach ($rows as $row) {
                    $record              = O('eq_record', ['source_name' => $name, 'source_id' => $row->id]);
                    $record->source_name = $name;
                    $record->source_id   = $row->id;
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'equipment_id', 'user_id', 'agent_id', 'duty_teacher_id', 'reserv_id', 'project_id', '_extra'])) {
                            continue;
                        }

                        $record->{$key} = $value;
                    }

                    $record->equipment    = O('equipment', ['source_name' => $name, 'source_id' => $row->equipment_id]);
                    $record->user         = self::getMasterUser(['source_name' => $name, 'source_id' => $row->user_id, 'token' => $row->token, 'email' => $row->email]);
                    $record->agent        = O('user', ['source_name' => $name, 'source_id' => $row->agent_id]);
                    $record->duty_teacher = O('user', ['source_name' => $name, 'source_id' => $row->duty_teacher_id]);
                    $record->reserv       = O('eq_reserv', ['source_name' => $name, 'source_id' => $row->reserv_id]);
                    if ($record->save()) {
                        echo "\033[32m成功同步使用记录{$name} {$record->id}\n";
                    } else {
                        echo 'error';
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_eq_sample($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT s.*,u.token,u.email FROM `eq_sample` s left join `user` u on u.id = s.sender_id  LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $sample              = O('eq_sample', ['source_name' => $name, 'source_id' => $row->id]);
                    $sample->source_name = $name;
                    $sample->source_id   = $row->id;
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'lab_id', 'equipment_id', 'sender_id', 'operator_id', 'record_id', 'project_id', '_extra'])) {
                            continue;
                        }

                        $sample->{$key} = $value;
                    }

                    $sample->lab       = O('lab', ['source_name' => $name, 'source_id' => $row->lab_id]);
                    $sample->equipment = O('equipment', ['source_name' => $name, 'source_id' => $row->equipment_id]);
                    $sample->sender    = self::getMasterUser(['source_name' => $name, 'source_id' => $row->user_id, 'token' => $row->token, 'email' => $row->email]);
                    $sample->operator  = O('user', ['source_name' => $name, 'source_id' => $row->operator_id]);
                    $sample->record    = O('eq_record', ['source_name' => $name, 'source_id' => $row->record_id]);

                    if ($sample->save()) {
                        echo "\033[32m成功同步送样记录{$name} {$sample->id}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_eq_charge($db, $name)
    {
        $start = 0;
        $step  = 20;

        do {
            $sql = "SELECT * FROM `eq_charge` LIMIT {$start}, {$step}";

            $res  = $db->query($sql);
            $rows = $res ? $res->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $charge                = O('eq_charge', ['platform_name' => $name, 'platform_id' => $row->id]);
                    $charge->platform_name = $name;
                    $charge->platform_id   = $row->id;
                    foreach ($row as $key => $value) {
                        if (in_array($key, ['id', 'user_id', 'lab_id', 'equipment_id', 'transaction_id', 'source_id', 'source_name', '_extra'])) {
                            continue;
                        }

                        $charge->{$key} = $value;
                    }

                    $charge->user      = O('user', ['source_name' => $name, 'source_id' => $row->user_id]);
                    $charge->lab       = O('lab', ['source_name' => $name, 'source_id' => $row->lab_id]);
                    $charge->equipment = O('equipment', ['source_name' => $name, 'source_id' => $row->equipment_id]);
                    if ($row->source_id && $row->source_name) {
                        $charge->source = O($row->source_name, ['source_id' => $row->source_id]);
                    }

                    if ($charge->save()) {
                        echo "\033[32m成功同步计费记录{$name} {$charge->id}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    private static function sync_eq_evaluate($db, $name)
    {
        $start = 0;
        $step  = 20;

        $url = Config::get("source.{$name}")['url'];

        do {
            $sql  = "SELECT * FROM `eq_evaluate` LIMIT $start, $step";
            $rows = $db->query($sql) ? $db->query($sql)->rows() : [];

            if (count($rows)) {
                foreach ($rows as $row) {
                    $eq_evaluate              = O('eq_evaluate', ['source_name' => $name, 'source_id' => $row->id]);
                    $eq_evaluate->source_name = $name;
                    $eq_evaluate->source_id   = $row->id;
                    $eq_evaluate->equipment   = O('equipment', ['source_name' => $name, 'source_id' => $row->equipment_id]);
                    $eq_evaluate->user        = O('user', ['source_name' => $name, 'source_id' => $row->user_id]);
                    $eq_evaluate->score       = $row->score;
                    $eq_evaluate->content     = $row->content;

                    if ($eq_evaluate->save()) {
                        echo "\033[32m成功同步评价{$name} {$eq_evaluate->source_id}\n";
                    }
                }
            }

            $start += $step;
        } while (count($rows) > 0);
    }

    public static function sync_total()
    {
        $children = Config::get('database.children');
        $time     = 0;
        $online   = 0;

        foreach ($children as $name) {
            try {
                $db = Database::factory($name);

                $time += self::sync_total_time($db, $name);
                $online += self::sync_total_online($db, $name);
            } catch (Exception $e) {
                echo "\033[31m {$name} 数据库连接异常\n";
            }
        }

        Lab::set('multi_stage.total.time', $time);
        Lab::set('multi_stage.total.online', $online);
        echo "\033[0m";
    }

    public static function sync_icon()
    {
        $equipments = Q('equipment');
        foreach ($equipments as $equipment) {
            if (!$equipment->source_name) continue;
            $url  = Config::get("source.{$equipment->source_name}")['url'];
            $file = @file_get_contents("{$url}/icon/index.equipment.{$equipment->source_id}", 'r');
            if ($file) {
                $equipment->save_icon(Image::load_from_data($file));
                $equipment->save_real_icon(Image::load_from_data($file));
            }
        }
    }

    private static function sync_total_time($db, $name)
    {
        $sql = "SELECT SUM(`dtend` - `dtstart`) FROM `eq_record`
        WHERE `dtend` > 1514736000";
        $time = $db->value($sql);
        Lab::set("multi_stage.{$name}.time", $time);
        return $time;
    }

    private static function sync_total_online($db, $name)
    {
        $sql = "SELECT COUNT(*) FROM `eq_reserv`
        WHERE `dtend` > 1514736000";
        $online = $db->value($sql);
        Lab::set("multi_stage.{$name}.online", $online);
        return $online;
    }

    private static function getMasterUser($params)
    {
        $user = O('user', ['source_name' => $params['source_name'], 'source_id' => $params['source_id']]);
        if ($user->id) {
            return $user;
        }

        $user = O('user', ['email' => $params['email']]);
        if ($user->id) {
            return $user;
        }

        $user = O('user', ['token' => $params['token']]);
        if ($user->id) {
            return $user;
        }

        return O('user');
    }

}

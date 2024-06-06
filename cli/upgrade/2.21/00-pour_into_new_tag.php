#!/usr/bin/env php
<?php
    /*
     * brief 由于tag抽离, 组织机构树迁移到 tag_group表, 仪器分类树迁移到tag_equipment表, 用于历史数据处理
     * 现有的数据流向
     * tag => tag_group
     * tag => tag_equipment
     * tag => tag_achievements_award
     * tag => tag_achievements_patent
     * tag => tag_achievements_publication
     * _r_tag_publication => _r_tag_achievements_publication_publication
     * _r_tag_patent => _r_tag_achievements_patent_patent
     * _r_tag_award => _r_tag_achievements_award_award
     * _r_user_tag => _r_user_tag_group
     * _r_tag_lab => _r_tag_group_lab
     * _r_tag_equipment => _r_tag_equipment_equipment, _r_tag_group_equipment
     * _r_tag_tag => _r_tag_group_tag
     *
     *
     * TODO: 后续还得补充, 做到再说
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    $db = Database::factory();
    // return (!!$db->value('SHOW TABLES LIKE "_r_user_tag_group"') && !$db->value('SELECT COUNT(*) FROM `_r_user_tag_group`'));
    return true;
};

//数据库备份
$u->backup = function () {
    return true;
};

$u->upgrade = function () {
    $db = Database::factory();
    // 建新的_r表
    $tables = ['_r_tag_group_lab', '_r_user_tag_group', '_r_tag_achievements_publication_publication',
        '_r_tag_achievements_patent_patent', '_r_tag_achievements_award_award',
        '_r_tag_equipment_equipment', '_r_tag_group_equipment', '_r_tag_group_tag'];
    foreach ($tables as $table) {
        $db->prepare_table(
            $table,
            array(
                //fields
                'fields' => array(
                        'id1'=>array('type'=>'bigint', 'null'=>false),
                        'id2'=>array('type'=>'bigint', 'null'=>false),
                        'type'=>array('type'=>'varchar(20)', 'null'=>false),
                        'approved'=>array('type'=>'int', 'null'=>false, 'default'=>0),
                    ),
                //indexes
                'indexes' => array(
                    'PRIMARY'=>array('type'=>'primary', 'fields'=>array('id1', 'id2', 'type')),
                    'id1'=>array('fields'=>array('id1', 'type')),
                    'id2'=>array('fields'=>array('id2', 'type')),
                    'approved'=>array('fields'=>array('approved')),
                )
            )
        );
    }

    $old_group_root_id = Lab::get('tag.group_id');
    $old_equipment_root_id = Lab::get('tag.equipment_id');
    $old_achievements_award_root_id = Lab::get('tag.achievements_award_id');
    $old_achievements_patent_root_id = Lab::get('tag.achievements_patent_id');
    $old_achievements_publication_root_id = Lab::get('tag.achievements_publication_id');

    $tag_schema = Config::get('schema.tag')['fields'];
    foreach ($tag_schema as $k => $v) {
        if ($v['type'] == 'object') {
            $tag_schema[$k.'_id'] = $k.'_id';
            unset($tag_schema[$k]);
        }
    }
    $tag_schema['id'] = 'id'; // 为了tag迁移前后id不变
    $tag_schema['_extra'] = '_extra';
    $tag_keys = array_keys($tag_schema);

    // 灌数据
    // tag => tag_xxx
    $query = "SELECT * FROM `tag`";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            $query = '';
            $data = [];
            foreach ($tag_keys as $key) {
                $data[$key] = $res->$key;
            }
            $data['_extra'] = str_replace('"', '\"', $data['_extra']);
            if ($res->root_id == $old_group_root_id) {
                $query = "INSERT INTO `tag_group` (`" .
                    join('`, `', array_keys($data)) .
                    "`) VALUES (\"" . join('","', $data) . "\")";
            } elseif ($res->root_id == $old_equipment_root_id) {
                $query = "INSERT INTO `tag_equipment` (`" .
                    join('`, `', array_keys($data)) .
                    "`) VALUES (\"" . join('","', $data) . "\")";
            } else
            if ($res->root_id == $old_achievements_award_root_id) {
                $query = "INSERT INTO `tag_achievements_award` (`" .
                    join('`, `', array_keys($data)) .
                    "`) VALUES (\"" . join('","', $data) . "\")";
            } elseif ($res->root_id == $old_achievements_patent_root_id) {
                $query = "INSERT INTO `tag_achievements_patent` (`" .
                    join('`, `', array_keys($data)) .
                    "`) VALUES (\"" . join('","', $data) . "\")";
            } elseif ($res->root_id == $old_achievements_publication_root_id) {
                $query = "INSERT INTO `tag_achievements_publication` (`" .
                    join('`, `', array_keys($data)) .
                    "`) VALUES (\"" . join('","', $data) . "\")";
            }
            if ($query) {
                $db->query($query);
            }
        }
    }

    // _r_tag_publication => _r_tag_achievements_publication_publication
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_publication` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id != $old_achievements_publication_root_id) {
                continue;
            }
            $query = "INSERT INTO `_r_tag_achievements_publication_publication` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }
    // _r_tag_patent => _r_tag_achievements_patent_patent
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_patent` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id != $old_achievements_patent_root_id) {
                continue;
            }
            $query = "INSERT INTO `_r_tag_achievements_patent_patent` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }
    // _r_tag_award => _r_tag_achievements_award_award
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_award` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id != $old_achievements_award_root_id) {
                continue;
            }
            $query = "INSERT INTO `_r_tag_achievements_award_award` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }

    // _r_user_tag => _r_user_tag_group
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_user_tag` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id2` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id != $old_group_root_id) {
                continue;
            }
            $query = "INSERT INTO `_r_user_tag_group` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }

    // _r_tag_lab => _r_tag_group_lab
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_lab` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id != $old_group_root_id) {
                continue;
            }
            $query = "INSERT INTO `_r_tag_group_lab` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }

    // _r_tag_equipment => _r_tag_equipment_equipment, _r_tag_group_equipment
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_equipment` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            if ($res->root_id == $old_group_root_id) {
                $query = "INSERT INTO `_r_tag_group_equipment` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
                $db->query($query);
            } elseif ($res->root_id == $old_equipment_root_id) {
                $query = "INSERT INTO `_r_tag_equipment_equipment` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id1}\", \"{$res->id2}\", \"{$res->type}\", \"{$res->approved}\")";
                $db->query($query);
            }
        }
    }

    // _r_tag_tag => _r_tag_group_tag
    $query = "SELECT `r`.`id1`, `r`.`id2`, `r`.`type`, `r`.`approved`, `t`.`root_id` FROM `_r_tag_tag` AS `r`" .
        " LEFT OUTER JOIN `tag` AS `t` ON (`r`.`id1` = `t`.`id`)";
    $q = $db->query($query);
    if ($q) {
        $results = $q->rows();
        foreach ((array)$results as $res) {
            $query = "INSERT INTO `_r_tag_group_tag` (`id1`, `id2`, `type`, `approved`) VALUES (\"{$res->id2}\", \"{$res->id1}\", \"{$res->type}\", \"{$res->approved}\")";
            $db->query($query);
        }
    }

    Upgrader::echo_success(" 数据升级成功! \n");
    return true;
};

//恢复数据
$u->restore = function () {
    return true;
};

$u->run();

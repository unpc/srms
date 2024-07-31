#!/usr/bin/env php
<?php
    /*
     * file create_orm_tables.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013/06/24
     *
     * useage SITE_ID=cf LAB_ID=test php create_orm_tables.php
     * brief 对系统中的ORM对象的schema进行遍历，根据schema创建ORM对象在数据库中的表结构
     */
require 'base.php';
$create_time = Date::time();

$db = Database::factory();

// 会有业务逻辑, ORM->connect之前就在Q selector中写道_r逻辑, 导致数据库报错, 故在此处prepare
Upgrader::echo_title("========== 创建 _r 表 ==========");
require 'create_relational_table.php';

Upgrader::echo_title("========== 创建 ORM_Model ==========");
/*
* Create ORM Table
*/
foreach(Config::$items['schema'] as $name => $item) {
    $schema = ORM_Model::schema($name);
    $schema['engine'] = $item['engine'] ? : null;
    // 增加eq_comment后部分字段没读取 ?
    /* if ($name == 'eq_sample') {
        $schema['fields']['feedback'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];
        $schema['indexes']['feedback'] = ['fields'=>['feedback']];
    } */
	if ($schema) {
		$ret = $db->prepare_table($name, $schema);
        if (!$ret) {
            echo $name."表更新失败\n";
        }
	}
}

Upgrader::echo_success("ORM_Model 创建完成 \n");


Upgrader::echo_title("========== 检查是否创建 _config 数据表 ==========");
/*
* Init _Config Table
*/

if (!$db->table_exists('_config')) {
    $fields=[
        'key'=>['type'=>'varchar(150)', 'null'=>TRUE, 'default'=>NULL],
        'val'=>['type'=>'text', 'null'=>TRUE, 'default'=>NULL],
    ];
    $indexes=[
        'primary'=>['type'=>'primary', 'fields'=>['key']],
    ];
    $db->create_table(
        '_config', 
        $fields, $indexes,
        Config::get('lab.config_engine')
    );
    Upgrader::echo_success("_Config 创建完成 \n");
}

if (!$db->table_exists('_config_local')) {
    $fields=[
        'key'=>['type'=>'varchar(150)', 'null'=>TRUE, 'default'=>NULL],
        'val'=>['type'=>'text', 'null'=>TRUE, 'default'=>NULL],
    ];
    $indexes=[
        'primary'=>['type'=>'primary', 'fields'=>['key']],
    ];
    $db->create_table(
        '_config_local',
        $fields, $indexes,
        Config::get('lab.config_engine')
    );
    Upgrader::echo_success("_Config_Local 创建完成 \n");
}

$_fuc = function($fuc, $param=null){
    return $fuc($param);
};


/**
 * Create Default Lab
 */
$_CreateLabActions = function($action) {
    switch ($action) {
        case 'getLab':
            return function() {
                $db = Database::factory();
                $defaultLabID = 0;
                $findDefaultLabSQL = "SELECT `val` FROM `_config` WHERE `key` = 'default_lab_id'";
                $ret = $db->value($findDefaultLabSQL);
                $ret and $defaultLabID = @unserialize($ret);

                $lab = O('lab', $defaultLabID);

                if (!$lab->id) {
                    $lab->ctime = $lab->mtime = $lab->atime = Date::time();
                    $lab->name = Config::get('lab.name');
                    $lab->description = T('系统默认创建!');
                    $lab->save();
                    $db->query('REPLACE INTO `_config` (`key`, `val`) VALUES ("default_lab_id", "%s")', @serialize($lab->id));
                }
                var_dump(3);
                return $lab;
            };
            break;
        case 'updateOwner':
            return function($lab) {
                if (!$lab->owner->id && Config::get('lab.pi', NULL)) {
                    $pi_token = Auth::normalize(Config::get('lab.pi'));
                    $pi = O('user', ['token'=>$pi_token]);
                    $pi->name = $pi->name ?: strtr("%name的管理员", ['%name' => $lab->name]);
                    $pi->atime = Date::time();
                    $pi->description = T('系统默认创建!');
                    $pi->save();

                    $lab->owner = $pi;
                    $lab->save();
                    $pi->connect($lab, 'pi');
                    $pi->connect($lab);
                    
                }
            };
            break;
        case 'updateName':
            return function($lab) {
                if (!Module::is_installed('labs') && $lab->name != Config::get('lab.name')) {
                    $lab->name = Config::get('lab.name');
                    $lab->save();
                }
            };
            break;
        default:
            break;
    }

};

// orm的表结构会常驻在ORM class的静态变量中。
// 2.17->2.18过程中更改了perm的表结构，导致perm->save()异常
// 故此处强制断开数据库连接，重新读取表结构
Database::shutdown();

$lab = $_fuc($_CreateLabActions('getLab'));
$_fuc($_CreateLabActions('updateOwner'), $lab);
$_fuc($_CreateLabActions('updateName'), $lab);


Upgrader::echo_title("========== 检查且创建仪器临时实验室 ==========");
/*
* Create Equipment Template Lab
*/
if (Module::is_installed('equipments')) {
    $db = Database::factory();
    $defaultLabID = 0;
    $findEquipmentTmpLabSQL = "SELECT `val` FROM `_config` WHERE `key` = 'equipment.temp_lab_id'";
    $ret = $db->value($findEquipmentTmpLabSQL);
    $ret and $equipTmpLabID = @unserialize($ret);

    $lab = O('lab', $equipTmpLabID);

    if (!$lab->id) {
        $lab->ctime = $lab->mtime = $lab->atime = Date::time();
        $lab->name = I18N::T('equipments', Config::get('equipment.temp_lab_name'));
        $lab->description = T('系统默认创建!');
        $lab->save();
        $db->query('REPLACE INTO `_config` (`key`, `val`) VALUES ("equipment.temp_lab_id", "%s")', @serialize($lab->id));
    }
    Upgrader::echo_success("仪器临时实验室 检查完成 \n");
}

Upgrader::echo_title("========== 检查且更新系统角色和权限信息到数据库 ==========");
/*
 *  Replace Config perms to Database
 */
if (Module::is_installed('roles')) {

        Upgrader::echo_title("===== 检查 默认角色 配置 =====");
        $default_roles = Config::get('roles.default_roles');

        foreach ($default_roles as $role_id => $role_description) {
            if ($role_id == ROLE_PAST_MEMBERS && ! $GLOBALS['preload']['people.enable_member_date']) {
                continue;
            }
            $role = O('role', ['name' => I18N::T('people', $role_description['name'])]);
            if (!$role->id) $role->name = I18N::T('people', $role_description['name']);
            $role->weight = (int)$role_id;
            $role->save();
            Upgrader::echo_success("{$role->name} 角色更新成功!\n");
        }

        $names = ['application'] + array_keys((array) Config::get('lab.modules'));
        foreach (Q("module") as $m) {
            if (!in_array($m->mid, $names)) {
                $m->delete();
            }
        }
        $weight = 0;
        foreach ($names as $name) {
            Upgrader::echo_title("===== 检查 {$name} 模块配置 =====");
            $m = O('module', ['mid' => $name]);
            // module 下没有任何perm配置，module应删除
            if (count((array)Config::get("perms.$name")) <= 0) {
                $m->delete();
                continue;
            }
            if (!$m->id) $m->mid = $name;
            $m->weight = $weight++;
            $m->save();
            if (!$m->id) break;
            else {
                Upgrader::echo_success("{$name} Module更新成功!");
            }
            // 2.18之前的perm表示没有module字段的，应删除
            foreach (Q("perm") as $p) {
                if (!$p->module->id) {
                    $p->delete();
                }
            }
            // perm 名称修改或删除，也应删除旧perm及与role的关联
            foreach (Q("$m perm") as $old_perm) {
                if (!array_key_exists($old_perm->name, (array) Config::get("perms.$name"))) {
                    foreach (Q("$old_perm role") as $role) {
                        $old_perm->disconnect($role);
                    }
                    $old_perm->delete();
                }
            }
            $perm_weight = 0;
            foreach ((array) Config::get("perms.$name") as $perm => $default) {
                switch($perm[0]) {
                    case '-':
                        $name = substr($perm, 1);
                        $sm = O('sub_module', ['module' => $m, 'name' => $name]);
                        if (!$sm->id) {
                            $sm->module = $m;
                            $sm->name = $name;
                        }
                        $sm->weight = $sm->weight ?: Q("sub_module[module={$m}]")->total_count() + 1;
                        $sm->save();
                        break;
                    case '#':
                        if ($perm == "#name") {
                            $m->name = $default;
                            $m->save();
                        }
                        if ($perm == "#icon") {
                            $m->icon = $default;
                            $m->save();
                        }
                        if ($perm == "#perm_in_uno") {
                            $m->perm_in_uno = $default;
                            $m->save();
                        }
                        break;
                    default:
                        $p = O('perm', ['module' => $m, 'name' => $perm]);
                        if (!$p->id) {
                            $p->module = $m;
                            $p->name = $perm;
                        }
                        if ($sm && $sm->id) {
                            $p->sub_module = $sm;
                        }
                        $p->weight = $perm_weight++;
                        $key = "lims-" . implode("", explode(" ", PinYin::code($m->name))) . "-" . implode("", explode(" ", PinYin::code($p->name)));
                        if (People::perm_in_uno() && $m->perm_in_uno) {
                            if (strlen($key) > 50)
                                $key = substr($key,-50);
                            $unoperms = Config::get('uno_perm');
                            $p->gapper_key = isset($unoperms[$p->name]['key']) ? $unoperms[$p->name]['key'] : $key;
                        }
                        $p->save();

                        if (People::perm_in_uno() && $m->perm_in_uno) {
                            $path = [I18N::T("gateway", "仪器共享")];
                            if ($m->name) {
                                $path[] =  $m->name;
                            }
                            if ($sm->name) {
                                $path[] =  $sm->name;
                            }
                            $path = join("/", $path);
                            $res = Gateway::postRemotePermission([
                                'key' => $p->gapper_key,
                                'name' => $p->name,
                                'path' => $path,
                                'group_type' => ["system", "organization", "lab", "area", "building", "room"]
                            ]);
                        }
                        foreach ($default_roles as $role_id => $role_description) {
                            $role = O('role', ['name' => I18N::T('people', $role_description['name'])]);
                            $default_perms = config::get('perms.default_roles')[$role->name]['default_perms']?:[];
                            if (in_array($perm, $default_perms) && $m->ctime >= $create_time) {
                                echo "$perm in {$role_description['name']} 默认权限\r\n";
                                $role->connect($p);
                            }
                        }
                        break;
                }
            }
            Upgrader::echo_success("{$name} 中 perm 权限更新成功!");
            unset($m);
            unset($sm);
        }
}

// 各模块自己完成初始化，注意不要重复初始化
//Event::trigger('create_orm_tables');

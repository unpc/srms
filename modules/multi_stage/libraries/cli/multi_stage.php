<?php
class CLI_Multi_Stage extends CLI_Frame {
    /*
    Usage:
        drop database lims2_tjmec;
        create database lims2_tjmec;
        SITE_ID=cf LAB_ID=tjmec php create_orm_tables.php
        SITE_ID=cf LAB_ID=tjmec php add_user.php 'genee|database'
        SITE_ID=cf LAB_ID=tjmec php cli.php multi_stage run
    */

    static function run() {

        $stage = new CLI_Multi_Stage($url, $platform_id);
        $stage->prepare_sites();
        foreach (self::$_sites as $site) {
            self::$_current_site = $site;
            Upgrader::echo_title("multi_stage info: start marge [{$site->lab_id}]");
            $stage->_stash_pointer();
            $stage->merge_tags();
            $stage->merge_equipments();
            $stage->merge_labs();
            $stage->merge_users();
            // $stage->fix_tags();
            $stage->fix_rs();
        }
    }

    private static $_sites = [];
    private $_pointer = [
        'equipment' => 0,
        'user' => 0,
        'lab' => 0,
        'tag' => 0
    ];

    private $_db;
    private static $_current_site;
    private $_db_name;
    private $_site_g;

    private function _stash_pointer() {
        $query = "SELECT MAX(`id`) as `p` FROM `{$this->_db_name}`.`equipment`;";
        $results = $this->_db->query($query);
        if (!$results) $this->fatal_error("multi_stage Error: Cannot stash equipment pointer]");
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            // if (!$row->p) $this->fatal_error("multi_stage Error: Cannot stash equipment pointer]");
            $this->_pointer['equipment'] = (int)$row->p;
            break;
        }

        $query = "SELECT MAX(`id`) as `p` FROM `{$this->_db_name}`.`lab`;";
        $results = $this->_db->query($query);
        if (!$results) $this->fatal_error("multi_stage Error: Cannot stash lab pointer]");
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            // if (!$row->p) $this->fatal_error("multi_stage Error: Cannot stash lab pointer]");
            $this->_pointer['lab'] = (int)$row->p;
            break;
        }

        $query = "SELECT MAX(`id`) as `p` FROM `{$this->_db_name}`.`user`;";
        $results = $this->_db->query($query);
        if (!$results) $this->fatal_error("multi_stage Error: Cannot stash user pointer]");
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            // if (!$row->p) $this->fatal_error("multi_stage Error: Cannot stash user pointer]");
            $this->_pointer['user'] = (int)$row->p;
            break;
        }

        $query = "SELECT MAX(`id`) as `p` FROM `{$this->_db_name}`.`tag`;";
        $results = $this->_db->query($query);
        if (!$results) $this->fatal_error("multi_stage Error: Cannot stash tag pointer]");
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            // if (!$row->p) $this->fatal_error("multi_stage Error: Cannot stash tag pointer]");
            $this->_pointer['tag'] = (int)$row->p;
            break;
        }
        if (count(self::$_sites)) Upgrader::echo_success("multi_stage debug: pointer stashed: ". json_encode($this->_pointer));
    }

    function __construct() {
        $this->_db = Database::factory();
        $this->_db_name = Config::get('database.prefix') . LAB_ID;
    }

    function do_prepare_sites() {
        $sites_config = Config::get('sites.children_stage');

        foreach ($sites_config as $site_id => $site_option) {
            $site = Site_Model::root($site_id);
            self::$_sites[$site_id] = $site;
        }
        if (count(self::$_sites)) Upgrader::echo_success("multi_stage info: prepare_sites success");
    }

    function undo_prepare_sites() {
        foreach (self::$_sites as $lab_id => $site) {
            $site->delete();
            unset(self::$_sites[$lab_id]);
        }
        if (!count(self::$_sites)) Upgrader::echo_success("multi_stage info: distory_sites success");
    }

    function do_merge_equipments() {
        $site = self::$_current_site;
        $db_name = Config::get('database.prefix') . $site->lab_id;
        $query = "SELECT * FROM `{$db_name}`.`equipment` ORDER BY `id` ASC;";

        $results = $this->_db->query($query);
        if (!$results) return FALSE;
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            $columns = [];
            $values = [];
            foreach(self::$eq_columns as $column) {
                if (!is_null($row->$column)) {
                    $columns[$column] = $column;
                    if (in_array($column, ['group_id'])) {
                        $values[$column] = $row->$column ?
                            $row->$column + $this->_pointer['tag'] :
                            $this->_site_g->id ;
                    }
                    elseif (in_array($column, ['id'])) {
                        $values[$column] = $row->$column + $this->_pointer['equipment'];
                    }
                    else {
                        $values[$column] = $row->$column;
                    }
                }
            }
            $query = "INSERT INTO `{$this->_db_name}`.`equipment` (`" . join('`, `', $columns) .
                "`) VALUES ('" . join("', '", $values) . "');";
            if ($this->_db->query($query)) {
                $new_id = ($row->id + $this->_pointer['equipment']);
                $equipment = O('equipment', $new_id);

                if (!$equipment->id) {
                    $this->fatal_error("multi_stage Error: Cannot get LAST_INSERT_ID when merge equipments[{$db_name}:{$row->id}]");
                }

                $old_data = O('old_data');
                $old_data->new_id = $equipment->id;
                $old_data->old_id = $row->id;
                $old_data->site_id = $site->site_id;
                $old_data->lab_id = $site->lab_id;
                $old_data->obj = $equipment;
                $old_data->save();
                $site->connect($equipment);
            }
            else {
                // TODO 保存失败，删掉_extra再来一次，无奈之举
                unset($columns['_extra']);
                unset($values['_extra']);
                $query = "INSERT INTO `{$this->_db_name}`.`equipment` (`" . join('`, `', $columns) .
                    "`) VALUES ('" . join("', '", $values) . "');";
                if ($this->_db->query($query)) {

                    $new_id = ($row->id + $this->_pointer['equipment']);
                    $equipment = O('equipment', $new_id);

                    if (!$equipment->id) {
                        $this->fatal_error("multi_stage Error: Cannot get LAST_INSERT_ID when merge equipments[{$db_name}:{$row->id}]");
                    }

                    $old_data = O('old_data');
                    $old_data->new_id = $equipment->id;
                    $old_data->old_id = $row->id;
                    $old_data->site_id = $site->site_id;
                    $old_data->lab_id = $site->lab_id;
                    $old_data->obj = $equipment;
                    $old_data->save();
                    $site->connect($equipment);
                }
                else {
                    Upgrader::echo_fail("multi_stage Warning: merge_equipments field [{$site->lab_id}]:$row->id");
                }
            }
        }
        foreach (Q('equipment') as $equipment) {
            $tag = $equipment->group;
            while ($tag->parent->id) {
                $equipment->connect($tag);
                $tag=$tag->parent;
            }
        }
        $total = Q("{$site} equipment")->total_count();
        Upgrader::echo_success("multi_stage info: merge_equipments[{$total}] done");
    }

    function undo_merge_equipments() {
        $site = self::$_current_site;
        // Q("{$site} equipment<obj old_data")->delete_all();
        foreach (Q("{$site} equipment") as $equipment) {
            $site->disconnect($equipment);
            $equipment->delete();
        }
        Upgrader::echo_success("multi_stage info: unmerge_equipments done");
    }

    function do_merge_labs() {
        $site = self::$_current_site;
        $db_name = Config::get('database.prefix') . $site->lab_id;
        $query = "SELECT * FROM `{$db_name}`.`lab` ORDER BY `id` ASC;";

        $results = $this->_db->query($query);
        if (!$results) return FALSE;
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            $columns = [];
            $values = [];
            foreach(self::$lab_columns as $column) {
                if (!is_null($row->$column)) {
                    $columns[] = $column;
                    if (in_array($column, ['owner_id'])) {
                        $values[] = $row->$column + $this->_pointer['user'];
                    }
                    elseif (in_array($column, ['group_id'])) {
                        $values[] = $row->$column ?
                            $row->$column + $this->_pointer['tag'] :
                            $this->_site_g->id ;
                    }
                    elseif (in_array($column, ['id'])) {
                        $values[] = $row->$column + $this->_pointer['lab'];
                    }
                    else {
                        $values[] = $row->$column;
                    }
                }
            }
            $query = "INSERT INTO `{$this->_db_name}`.`lab` (`" . join('`, `', $columns) .
                "`) VALUES ('" . join("', '", $values) . "');";
            if ($this->_db->query($query)) {
                $new_id = ($row->id + $this->_pointer['lab']);
                $lab = O('lab', $new_id);

                if (!$lab->id) {
                    $this->fatal_error("multi_stage Error: Cannot get LAST_INSERT_ID when merge lab[{$db_name}:{$row->id}]");
                }

                $old_data = O('old_data');
                $old_data->new_id = $lab->id;
                $old_data->old_id = $row->id;
                $old_data->site_id = $site->site_id;
                $old_data->lab_id = $site->lab_id;
                $old_data->obj = $lab;
                $old_data->save();
                $site->connect($lab);
            }
            else {
                Upgrader::echo_fail("multi_stage Warning: merge_lab field [{$site->lab_id}]:$row->id");
            }
        }
        foreach (Q('lab') as $lab) {
            $tag = $lab->group;
            while ($tag->parent->id) {
                $lab->connect($tag);
                $tag=$tag->parent;
            }
        }
        $total = Q("{$site} lab")->total_count();
        Upgrader::echo_success("multi_stage info: merge_labs[{$total}] done");
    }

    function undo_merge_labs() {
        $site = self::$_current_site;
        // Q("{$site} lab<obj old_data")->delete_all();
        foreach (Q("{$site} lab") as $lab) {
            $site->disconnect($lab);
            $lab->delete();
        }
        Upgrader::echo_success("multi_stage info: unmerge_labs done");
    }

    function do_merge_users() {
        $site = self::$_current_site;
        $db_name = Config::get('database.prefix') . $site->lab_id;
        $query = "SELECT * FROM `{$db_name}`.`user` ORDER BY `id` ASC;";

        $results = $this->_db->query($query);
        if (!$results) return FALSE;
        $rows = $results->rows();
        foreach ((array)$rows as $row) {
            $columns = [];
            $values = [];
            foreach(self::$user_columns as $column) {
                if (!is_null($row->$column)) {
                    $columns[] = $column;
                    if (in_array($column, ['creator_id', 'auditor_id', 'id'])) {
                        $values[] = $row->$column + $this->_pointer['user'];
                    }
                    elseif (in_array($column, ['group_id'])) {
                        $values[] = $row->$column ?
                            $row->$column + $this->_pointer['tag'] :
                            $this->_site_g->id ;
                    }
                    else {
                        $values[] = $row->$column;
                    }
                }
            }
            $query = "INSERT INTO `{$this->_db_name}`.`user` (`" . join('`, `', $columns) .
                "`) VALUES ('" . join("', '", $values) . "');";

            if ($this->_db->query($query)) {
                $new_id = ($row->id + $this->_pointer['user']);
                $user = O('user', $new_id);

                if (!$user->id) {
                    $this->fatal_error("multi_stage Error: Cannot get LAST_INSERT_ID when merge user[{$db_name}:{$row->id}]");
                }

                $old_data = O('old_data');
                $old_data->new_id = $user->id;
                $old_data->old_id = $row->id;
                $old_data->site_id = $site->site_id;
                $old_data->lab_id = $site->lab_id;
                $old_data->obj = $user;
                $old_data->save();
                $site->connect($user);
            }
            else {
                Upgrader::echo_fail("multi_stage Warning: merge_user field [{$site->lab_id}]:$row->id");
            }
        }
        foreach (Q('user') as $user) {
            $tag = $user->group;
            while ($tag->parent->id) {
                $user->connect($tag);
                $tag=$tag->parent;
            }
        }
        $total = Q("{$site} user")->total_count();
        Upgrader::echo_success("multi_stage info: merge_users[{$total}] done");
    }

    function undo_merge_users() {
        $site = self::$_current_site;
        // Q("{$site} user<obj old_data")->delete_all();
        foreach (Q("{$site} user") as $user) {
            $site->disconnect($user);
            $user->delete();
        }
        Upgrader::echo_success("multi_stage info: unmerge_users done");
    }

    function do_merge_tags() {
        $site = self::$_current_site;
        $db_name = Config::get('database.prefix') . $site->lab_id;
        $query = "SELECT * FROM `{$db_name}`.`tag`
            WHERE `root_id` = 0 AND `parent_id` =0 AND `readonly` = 1
            ORDER BY `id` ASC;";

        $results = $this->_db->query($query);
        if (!$results) return FALSE;

        // 在顶级创建校级组织机构，将校级的所有group[->parent]+指到此处
        $group_root = Tag_Model::root('group');
        $site_group_p = O('tag');
        $site_group_p->name = $site->name;
        $site_group_p->root = $group_root;
        $site_group_p->parent = $group_root;
        $site_group_p->save();
        $this->_site_g = $site_group_p;
        $this->_pointer['tag'] += 1;

        $rows = $results->rows();

        $pids = [];
        foreach ((array)$rows as $row) {
            // 留存组织机构等root tag，不merge
            if ($row->root_id == 0 && $row->parent_id == 0 && $row->readonly == 1) {
                $old_data = O('old_data');
                $new_tag = O('tag', [
                    'name' => $row->name,
                    'root_id' => 0,
                    'parent_id' => 0,
                    'readonly' => 1
                ]);

                if (in_array($column, ['name']) && strpos($column, 'equipment#')) {
                    $eqInfo = explode('#', $row->$column);
                    $old_e_data = O('old_data', [
                        'old_id' => $eqInfo[1],
                        'obj_name' => 'equipment',
                        'site_id' => $site->site_id,
                        'lab_id' => $site->lab_id,
                    ]);
                    $name = 'equipment#' . $old_e_data->new_id;
                }
                else {
                    $name = $row->name;
                }

                if (!$new_tag->id) {
                    $new_tag = O('tag');
                    $new_tag->name = $name;
                    $new_tag->readonly = 1;
                    $new_tag->save();
                    $this->_pointer['tag'] += 1;
                }

                $old_data->new_id = $new_tag->id;
                $old_data->old_id = $row->id;
                $old_data->site_id = $site->site_id;
                $old_data->lab_id = $site->lab_id;
                $old_data->obj = $new_tag;
                $old_data->save();
                $pids[] = $row->id;
                // Upgrader::echo_title("multi_stage info: [{$site->lab_id}]:{$row->name} is root_tag");
            }
        }

        while (TRUE) {
            if (!count($pids)) break;

            $parent_ids = join(',', $pids);
            $query = "SELECT * FROM `{$db_name}`.`tag`
                WHERE `root_id` <> 0 AND `readonly` <> 1
                AND `parent_id` IN ({$parent_ids})
                ORDER BY `id` ASC;";
            $results = $this->_db->query($query);
            if (!$results) break;
            $rows = $results->rows();

            $pids = [];
            foreach ((array)$rows as $row) {
                $old_p_data = O('old_data', [
                    'old_id' => $row->parent_id,
                    'obj_name' => 'tag',
                    'site_id' => $site->site_id,
                    'lab_id' => $site->lab_id,
                ]);
                if (!$old_p_data->obj->id ) {
                    Upgrader::echo_fail("multi_stage Warning: merge_tag field [{$site->lab_id}]:$row->id");
                    continue;
                }

                $columns = [];
                $values = [];
                foreach(self::$tag_columns as $column) {
                    if (!is_null($row->$column)) {
                        $columns[] = $column;
                        if (in_array($column, ['id'])) {
                            $values[] = $row->$column + $this->_pointer['tag'];
                        }
                        elseif (in_array($column, ['parent_id'])) {
                            // 在顶级创建校级组织机构，将校级的所有group[->parent]+指到此处
                            if ($old_p_data->obj->name == '组织机构'
                                && $old_p_data->obj->root_id == 0
                                && $old_p_data->obj->parent_id == 0
                                && $old_p_data->obj->readonly == 1
                            ) {
                                $values[] = $site_group_p->id;
                            }
                            else {
                                $values[] = $old_p_data->obj->id;
                            }
                        }
                        elseif (in_array($column, ['root_id'])) {
                            $values[] = $old_p_data->obj->root_id ? : $old_p_data->obj->id;
                        }
                        else {
                            $values[] = $row->$column;
                        }
                    }
                }

                $query = "INSERT INTO `{$this->_db_name}`.`tag` (`" . join('`, `', $columns) .
                    "`) VALUES ('" . join("', '", $values) . "');";

                if ($this->_db->query($query)) {
                    $new_id = ($row->id + $this->_pointer['tag']);
                    $tag = O('tag', $new_id);

                    if (!$tag->id) {
                        $this->fatal_error("multi_stage Error: Cannot get LAST_INSERT_ID when merge tag[{$db_name}:{$row->id}]");
                    }

                    $old_data = O('old_data');
                    $old_data->new_id = $tag->id;
                    $old_data->old_id = $row->id;
                    $old_data->site_id = $site->site_id;
                    $old_data->lab_id = $site->lab_id;
                    $old_data->obj = $tag;
                    $old_data->save();
                    $site->connect($tag);
                    $pids[] = $row->id;
                }
                else {
                    var_dump($query);
                    Upgrader::echo_fail("multi_stage Warning: merge_tag field [{$site->lab_id}]:$row->id");
                }
            }
        }
        Upgrader::echo_success("multi_stage info: merge_tags done");
    }

    function undo_merge_tags() {
        $site = self::$_current_site;
        // Q("{$site} tag<obj old_data")->delete_all();
        foreach (Q("{$site} tag") as $tag) {
            $site->disconnect($tag);
            $tag->delete();
        }
        Upgrader::echo_success("multi_stage info: unmerge_tags done");
    }

    function do_fix_rs() {
        $site = self::$_current_site;
        $db_name = Config::get('database.prefix') . $site->lab_id;
        $rs = [
            '_r_tag_lab',
            '_r_tag_equipment',
            '_r_user_equipment',
            '_r_user_lab',
            '_r_user_tag'
        ];

        foreach ($rs as $r) {
            $query = "SELECT * FROM `{$db_name}`.`{$r}`;";
            $mode = '/^_r_(?<obj1>\w+?)_(?<obj2>\w+?)$/';
            preg_match($mode, $r, $match);
            if (!$match['obj1'] || !$match['obj2']) continue;

		    $conn_table = '_r_'.$match['obj1'].'_'.$match['obj2'];

            $prepare = $this->_db->prepare_table($conn_table, [
                    //fields
                    'fields' => [
                        'id1'=>['type'=>'bigint', 'null'=>FALSE],
                        'id2'=>['type'=>'bigint', 'null'=>FALSE],
                        'type'=>['type'=>'varchar(20)', 'null'=>FALSE],
                        'approved'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
                    ],
                    //indexes
                    'indexes' => [
                        'PRIMARY'=>['type'=>'primary', 'fields'=>['id1', 'id2', 'type']],
                        'id1'=>['fields'=>['id1', 'type']],
                        'id2'=>['fields'=>['id2', 'type']],
                        'approved'=>['fields'=>['approved']],
                    ]
                ]
            );

            $results = $this->_db->query($query);
            if (!$results) {
                Upgrader::echo_success("multi_stage info: there's no data in [{$site->lab_id}]:{$r}");
                continue;
            }

            $rows = $results->rows();

            // 在merge _r 之前，已经做过组织机构的处理
            $group_root = Tag_Model::root('group');
            foreach ((array)$rows as $row) {
                if (
                    ($match['obj1'] == 'tag' && $group_root->id == $row->id1 )
                    || ($match['obj2'] == 'tag' && $group_root->id == $row->id2 )
                ) {
                    continue;
                }
                $query = "INSERT INTO `{$this->_db_name}`.`{$r}` (
                    `id1`,
                    `id2`,
                    `type`,
                    `approved`
                )
                VALUES (
                    '" . ($row->id1 + $this->_pointer[$match['obj1']]) . "',
                    '" . ($row->id2 + $this->_pointer[$match['obj2']]) . "',
                    '{$row->type}',
                    '{$row->approved}'
                );";
                $this->_db->query($query);
            }
        }
        Upgrader::echo_success("multi_stage info: fix_rs done");
    }

    function undo_fix_rs() {
        Upgrader::echo_fail("multi_stage TODO: unfix_rs");
    }

    private static $eq_columns = [
        'organization',
        'ref_no',
        'cat_no',
        'name',
        'name_abbr',
        'model_no',
        'specification',
        'price',
        'manu_at',
        'manufacturer',
        'manu_date',
        'purchased_date',
        'location',
        'location2',
        'control_mode',
        'control_address',
        'is_using',
        'require_training',
        'status',
        'is_monitoring',
        'is_monitoring_mtime',
        'ctime',
        'mtime',
        'atime',
        'access_code',
        'group_id',
        'tag_root_id',
        'phone',
        'email',
        'share',
        'domain',
        'accept_reserv',
        'accept_limit_time',
        'billing_dept_id',
        'allow_evaluate',
        'accept_sample',
        'yiqikong_id',
        'yiqikong_share',
        '_extra',
        'en_name',
        'server',
        'user_using_id',
        'connect',
        'using_abbr',
        'contacts_abbr',
        'location_abbr',
        'id'
    ];

    private static $lab_columns = [
        'creator_id',
        'auditor_id',
        'owner_id',
        'ref_no',
        'name',
        'description',
        'rank',
        'ctime',
        'mtime',
        'atime',
        'name_abbr',
        'contact',
        'group_id',
        'hidden',
        'nfs_size',
        'nfs_mtime',
        'nfs_used',
        '_extra',
        'id'
    ];

    private static $user_columns = [
        'token',
        'email',
        'name',
        'card_no',
        'card_no_s',
        'dfrom',
        'dto',
        'weight',
        'atime',
        'ctime',
        'mtime',
        'hidden',
        'name_abbr',
        'phone',
        'address',
        'address_abbr',
        'group_id',
        'member_type',
        'creator_id',
        'creator_abbr',
        'auditor_id',
        'auditor_abbr',
        'ref_no',
        'binding_email',
        'nfs_size',
        'nfs_mtime',
        'nfs_used',
        'gapper_id',
        'outside',
        'nl_cat_vis',
        '_extra',
        'id'
    ];

    private static $tag_columns = [
        'name',
        'name_abbr',
        'parent_id',
        'root_id',
        'readonly',
        'ctime',
        'mtime',
        'weight',
        '_extra',
        'id'
    ];
}

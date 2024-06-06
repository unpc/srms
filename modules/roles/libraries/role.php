<?php

class Role {

	static function user_ACL($e, $user, $perm, $object, $options) {
        //object 为role对象

        if (!$object->id) {
            $e->return_value = FALSE;
            return FALSE;
        }

        switch($perm) {
            case '查看' ;
                $privacy = (int) $object->privacy;
                if ($privacy == Role_Model::PRIVACY_ALL) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if ($privacy == Role_Model::PRIVACY_GROUP) {
                    if ($user->access('添加/修改下属机构成员的信息') || $user->access('添加/修改所有成员信息')) {
                        $e->return_value = TRUE;
                    }
                    return FALSE;
                }

                if ($privacy == Role_Model::PRIVACY_ADMIN) {
                    if ($user->access('添加/修改所有成员信息')) {
                        $e->return_value = TRUE;
                    }
                    return FALSE;
                }
                break;
            default :
                $e->return_value = FALSE;
                return FALSE;
                break;
        }
    }

    static function prerender_people_users_table($e, $table) {
        if (L('ME')->is_allowed_to('查看角色', 'user') && !People::perm_in_uno()) {
            $form = $table->form;
            /*if ($form['role']) {
                $value = join(', ', json_decode($form['role'], TRUE));
            }*/
            /*$table->add_column('role', [
                'title'=>I18N::T('roles', '用户角色'),
                'invisible' => TRUE,
                'suppressible' => TRUE,
                'weight' => 70,
                'filter'=>[
                    'form'=>Widget::factory('roles:role_selector', [
                        'name'=>'role',
                        'autocomplete'=> URI::url('!roles/autocomplete/role'),
                        'value'=> $form['role'],
                        'size'=>30,
                    ]),
                    'value'=> $value,
                ],
            ]);*/

            $table->add_column('role', [
                'title'=>I18N::T('roles', '用户角色'),
                'invisible' => TRUE,
                // 'suppressible' => TRUE,
                'weight' => 70,
                'filter'=>[
                    'form'=>Widget::factory('roles:role_selector_new', [
                        'active_role'=> $form['role'],
                    ]),
                    'value' => empty($form['role']) ? null : $form['role'],
                ],
            ]);

        }
    }

    static function people_role_selector($e, $form, $selector, $pre_selectors) {
        if ($form['role'] && L('ME')->is_allowed_to('查看角色', 'user')) {
            //开启事务,这段事务的目的是将pi和机主的关系插入到role_user中
            $db = Database::factory();
            $db->begin_transaction();
           
            foreach($form['role'] as $id) {
                $role = O('role', $id);
                if ($role->id && $role->weight == ROLE_LAB_PI) {
                    $db->query("delete from _r_user_role where id2 = $role->id");
                    $db->query("insert  into _r_user_role(id1,id2) select distinct(id1),$role->id as id2 from _r_user_lab where type = 'pi'");
                }
                else if ($role->id && $role->weight == ROLE_EQUIPMENT_CHARGE) {
                    $db->query("delete from _r_user_role where id2 = $role->id");
                    $db->query("insert  into _r_user_role(id1,id2) select distinct(id1),$role->id as id2 from _r_user_equipment where type = 'incharge'");
                }

                if($role->id) {
                    $role_ids[] = $role->id;
                }
            }
            $db->commit();
            $role_ids = implode(',',$role_ids);
            $pre_selectors[] = "role[id=$role_ids]";
        }

    }

    static function set_roles($e)
    {
        $roles = Q('role[weight>=0]:sort(weight A)');
        $role_num = $roles->length();
        $role_set = count($roles->to_assoc('weight', 'id'));
        if ($role_num != $role_set) {
            $first_role = $roles->current();
            $weight = $first_role->weight;
            foreach ($roles as $role) {
                if ($first_role->id != $role->id) {
                    $weight ++;
                    if ((int)$role->weight != $weight){
                        $role->weight = $weight;
                        $role->save();
                    }
                }
            }
        }

        Cache::L('ROLES', $roles);

        $default_roles = Q('role[weight<0]:sort(weight D)');

        foreach ($default_roles as $role_id => $role) {
            if ($role->weight == ROLE_PAST_MEMBERS && ! $GLOBALS['preload']['people.enable_member_date']) {
                continue;
            }
            $roles->prepend(['id' => $role->id, 'name' => $role->name, 'weight' => $role->weight]);
        }
    }

    static function role_perm_connect($e, $role){
        if ($role->id) {
            $role->connect_perms_time = Date::time();
            $role->save();
        }
    }

    public static function extra_roles ($e, $user, $user_roles) {

        if (Module::is_installed('summary')) {
            $db = Database::factory();
            $res = $db->query("
                select
                r.id, p.name 
                from
                role as r
                left join _r_user_role as rur on rur.id2 = r.id
                left join _r_role_perm as rrp on rrp.id1 = r.id
                left join perm as p on p.id = rrp.id2
                where p.id is not null and rur.id1 = {$user->id}
                    and (p.name = '[大数据体系]管理申报任务' or p.name = '[大数据体系]填报仪器数据' or p.name = '[大数据体系]审核仪器数据')
            ");

            if ($res) $roles = $res->rows() ?: [];

            foreach($roles as $role){
                $user_roles[$role->id] = $role->id;
            }

            $e->return_value = $user_roles;
        }

        return false;
    }
    public static function is_accessible($e, $name) {
		
		$me = L('ME');

		if (People::perm_in_uno()){
            $e->return_value = false;
            return false;
        }

		if (Event::trigger('db_sync.need_to_hidden', 'role')) {
			$e->return_value = false;
			return false;
		}
	}
}

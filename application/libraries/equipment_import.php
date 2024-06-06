<?php

class Equipment_Import {

    static function add_equipment($e, $row, $fields, $user_submit) {
        $error = [];
        $warning = [];
        $me = L('ME');

        $check_str_email = '/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/';

        foreach ($row as $key => $value) {
            if (!in_array($key, $fields)) continue;
            switch ($key) {
                case 'email':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        $form['email'] = $trim_value;
                    } else {
                        // $error[$key] = T('必须输入邮箱');
                        // goto output;
                        $form['email'] = '';
                    }
                    break;
                case 'name':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        $error[$key] = T('需填写仪器名称');
                        goto output;
                    }
                    $form['name'] =$trim_value;
                    break;
                // case 'ref_no':
                //     $trim_value = trim($value);
                //     if ($trim_value) {
                //         $tmp_equipment = O('equipment', ['ref_no' => $trim_value]);
                //         if ($tmp_equipment->id) {
                //             $error[$key] = T('已存在仪器编号');
                //             goto output;
                //         }

                //         $form['ref_no'] = $trim_value;
                //     }
                //     break;
                case 'incharge_user':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        // $error[$key] = T('必须输入负责人');
                        // goto output;
                    } else {
                        $incharge_user_ids = null;
                        $user_names = explode(',', $trim_value);
                        foreach ($user_names as $uname_key => $uname_value) {
                            $uname_value = trim($uname_value);
                            $tmp_users = Q("user[name={$uname_value}]");
                            $tmp_users_count = $tmp_users->total_count();
                            //用户存在且唯一
                            if ($tmp_users_count >= 1) {
                                $incharge_user_ids[] = $tmp_users->id;
                            // }
                            // //用户不唯一
                            // elseif ($tmp_users_count > 1) {
                            //     $warning[$key][] = T('存在多个姓名为%name的用户，默认选择第一个用户！', ['%name' => $uname_value]);
                            //     $incharge_user_ids[] = $tmp_users->id;
                            }
                            //用户不存在
                            else {
                                $warning[$key][] = T('姓名为%name的用户不存在', ['%name' => $uname_value]);
                            }
                        }
                    }
                    if (is_null($incharge_user_ids)) {
                        // $error[$key] = T('无有效负责人');
                        // goto output;
                        $warning[$key][] = T('无仪器负责人');
                    }
                    break;
                case 'contact_user':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        // $error[$key] = T('必须输入负责人');
                        // goto output;
                    } else {
                        $contact_user_ids = null;
                        $user_names = explode(',', $trim_value);
                        foreach ($user_names as $uname_key => $uname_value) {
                            $uname_value = trim($uname_value);
                            $tmp_users = Q("user[name={$uname_value}]");
                            $tmp_users_count = $tmp_users->total_count();
                            //用户存在且唯一
                            if ($tmp_users_count >= 1) {
                                $contact_user_ids[] = $tmp_users->id;
                            // }
                            // //用户不唯一
                            // elseif ($tmp_users_count > 1) {
                            //     $warning[$key][] = T('存在多个姓名为%name的用户，默认选择第一个用户！', ['%name' => $uname_value]);
                            //     $contact_user_ids[] = $tmp_users->id;
                            }
                            //用户不存在
                            else {
                                $warning[$key][] = T('姓名为%name的用户不存在', ['%name' => $uname_value]);
                            }
                        }
                    }
                    if (is_null($contact_user_ids)) {
                        // $error[$key] = T('无有效联系人');
                        // goto output;
                        $warning[$key][] = T('无仪器联系人');
                    }
                    break;
                default:
                    $form[$key] = trim($value) ? : '';
                    break;
            }
        }
        $root = Tag_Model::root('group');
        $cat_root = Tag_Model::root('equipment');
        if ($form['ref_no']) {
            $eq = O('equipment', ['ref_no' => $form['ref_no']]);
        } else {
            $eq = O('equipment');
        }
        $eq->name = $form['name'];
        $eq->en_name = $form['en_name'] ? :"";
        $eq->ref_no = $form['ref_no'] ? : NULL;
        $eq->phone = $form['phone'] ?: '';
        $eq->email = $form['email'] ?: '';
        $eq->specification = $form['specification'] ? : '';
        $eq->model_no = $form['model_no'] ?: '';
        $eq->price = $form['price'] ?: 0;
        $eq->manufacturer = $form['manufacturer'] ?: '';
        $eq->manu_at = $form['manu_at'] ?: '';
        $eq->purchased_date = (int)strtotime($form['purchased_date']);
        $eq->manu_date = (int)strtotime($form['manu_date']);
        $eq->cat_no = $form['cat_no'] ? : '';
        $eq->tech_specs = $form['tech_specs'];
        $eq->features = $form['features'];
        $eq->open_reserv = $form['open_reserv'];
        $eq->charge_info = $form['charge_info'];
        $eq->configs = $form['configs'];
        $eq->atime = $form['atime'] ? : 0; //入网时间

        if (!$eq->save()){
            $error[] = T('保存失败，请联系管理员');
            goto output;
        }

        if (Config::get('equipment.location_type_select')){
            if (isset($form['location'])) {
                $tags = Q("tag_location[name={$form['location']}]")->to_assoc('id','name');
                if (count($tags)) {
                    Tag_Model::replace_tags($eq, $tags, 'location');
                }else{
                    $equipment_root = Tag_Model::root('location');
                    $tags = Q("$equipment tag_location[root=$equipment_root]");
                    foreach ($tags as $t) {
                        $t->disconnect($eq);
                    }
                }
            }
        }

        if (!is_null($incharge_user_ids)) {
            foreach ($incharge_user_ids as $incharge_id) {
                $incharge = O('user', $incharge_id);
                $eq->connect($incharge, 'incharge');
                $incharge->follow($eq);
            }
        }
        if (!is_null($contact_user_ids)) {
            foreach ($contact_user_ids as $contact_id) {
                $contact = O('user', $contact_id);
                $eq->connect($contact, 'incharge');
                $eq->connect($contact, 'contact');
                $contact->follow($eq);
            }
        }
        if ($form['group']) {
            $tags = explode('-', $form['group']);
            foreach ($tags as $tag_key => $tag_value) {
                $tag_value = trim($tag_value);
                if ($tag_key == 0) {
                    $tmp_group = O('tag_group', [
                        'name' => $tag_value,
                        'root' => $root
                        ]);
                    if ($tmp_group->id) {
                        $eq->group = $tmp_group;
                        $parent = $tmp_group;
                    } else {
                        $parent = $root;
                        $tmp_group = O('tag_group');
                        $tmp_group->parent = $parent;
                        $tmp_group->root = $root;
                        $tmp_group->name = $tag_value;
                        $tmp_group->weight = $user_submit['weight'] ? : 0;
                        if ($tmp_group->save()) {
                            $eq->group = $tmp_group;
                            $parent = $tmp_group;
                            $warning['group'][] = T('找不到名称为 %s 的机构,自动创建成功', ['%s' => $tag_value]);
                        } else {
                            $warning['group'][] = T('找不到名称为 %s 的机构,且自动创建失败', ['%s' => $tag_value]);
                        }
                    }
                } else {
                    $tmp_group = O('tag_group', [
                        'name' => $tag_value,
                        'parent_id' => $parent->id
                        ]);
                    if ($tmp_group->id) {
                        $eq->group = $tmp_group;
                        $parent = $tmp_group;
                    } else {
                        $tmp_group = O('tag_group');
                        $tmp_group->parent = $parent;
                        $tmp_group->root = $root;
                        $tmp_group->name = $tag_value;
                        $tmp_group->weight = $user_submit['weight'] ? : 0;
                        if ($tmp_group->save()) {
                            $eq->group = $tmp_group;
                            $parent = $tmp_group;
                            $warning['group'][] = T('找不到名称为 %s 的机构,自动创建成功', ['%s' => $tag_value]);
                        } else {
                            $warning['group'][] = T('找不到名称为 %s 的机构,且自动创建失败', ['%s' => $tag_value]);
                        }
                    }
                }
                if ($eq->group->id) $eq->group->connect($eq);
                if (!$eq->save()) {
                    $warning['group'][] = T('仪器组织机构保存失败');
                }
            }
            
        }
        if ($form['cat_name']) {
            $tags = explode(',', $form['cat_name']);
            $tag_ids = [];
            foreach ($tags as $tag_key => $tag_value) {
                $tag_value = trim($tag_value);
                $tmp_group = O('tag_equipment', [
                    'name' => $tag_value,
                    'root' => $cat_root
                    ]);
                if ($tmp_group->id) {
                    $tag_ids[$tmp_group->id] = $tmp_group;
                } else {
                    $tmp_tag_obj = O('tag_equipment');
                    $tmp_tag_obj->name = $tag_value;
                    $tmp_tag_obj->root = $cat_root;
                    $tmp_tag_obj->parent = $cat_root;
                    if ($tmp_tag_obj->save()) {
                        $tag_ids[$tmp_tag_obj->id] = $tmp_tag_obj;
                        $warning['cat_name'][] = T('找不到名称为 %s 的仪器分类,自动创建成功', ['%s' => $tag_value]);
                    } else {
                        $warning['cat_name'][] = T('找不到名称为 %s 的仪器分类,且自动创建失败', ['%s' => $tag_value]);
                    }
                }
                foreach($tag_ids as $tag) {
                    $tag->connect($eq);
                }
                // Tag_Model::replace_tags($eq, (array)$tag_ids, 'equipment');
            }
        }

        output:
        $e->return_value = [
            'error' => $error,
            'warning' => $warning
            ];
        return;
    }
}
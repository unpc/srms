<?php

class Lab_Import {

    static function add_lab($e, $row, $fields, $user_submit) {
        $error = [];
        $warning = [];
        $me = L('ME');

        foreach ($row as $key => $value) {
            if (!in_array($key, $fields)) continue;
            switch ($key) {
                case 'name':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        $error[$key] = T('需填写课题组名称');
                        goto output;
                    }
                    $form['name'] = $trim_value;
                    break;
                 case 'owner':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        // $error[$key] = T('需填写课题组负责人');
                        // goto output;
                       $form['owner'] = null;
                        $warning[$key][] = T('未填写课题组负责人');
                    } else {
                        $user = O('user', ['name' => $trim_value]);
                        if ($user->id) {
                            $form['owner'] = $user;
                        } else {
                            // $error[$key] = T('输入的课题组负责人不存在');
                            // goto output;
                            $warning[$key][] = T('输入的课题组负责人不存在');
                        }
                    }
                    break;
                default:
                    $form[$key] = trim($value) ? : '';
                    break;
            }
        }

        $lab = $form['ref_no'] ? O('lab',['ref_no' => $form['ref_no']]) : O('lab');
        if ($lab->id) $lab->owner->disconnect($lab, 'pi');
        if (isset($form['owner'])) {
            $lab->owner = $form['owner'];
        } else {
            $lab->owner = O("user");
        }
        $lab->name = $form['name'];

        $lab->creator = L('ME');
        $lab->ref_no = $form['ref_no'];
        $lab->type = $form['type'] ? : $lab->type;
        if ($lab->contact || $form['contact']) $lab->contact = $form['contact'] ? : $lab->contact;
        $lab->subject = $form['subject'] ? : $lab->subject;
        $lab->util_area = $form['util_area'] ? : $lab->util_area;
        $lab->location = $form['location'] ? : $lab->location;
        $lab->location2 = $form['location2'] ? : $lab->location2;
        $lab->description = $form['description'] ? : $lab->description;
        $lab->atime = Date::time();
        if (!$lab->save()) {
            $error[] = T('保存失败，请联系管理员');
            goto output;
        }

        $lab->owner->connect($lab, 'pi');

        if ($form['group']) {
            $root = Tag_Model::root('group');
            $tags = explode('-', $form['group']);
            foreach ($tags as $tag_key => $tag_value) {
                if ($tag_key == 0) {
                    $tmp_group = O('tag_group', [
                        'name' => $tag_value,
                        'root' => $root
                        ]);
                    if ($tmp_group->id) {
                        $lab->group = $tmp_group;
                        $parent = $tmp_group;
                    } else {
                        $parent = $root;
                        $tmp_group = O('tag_group');
                        $tmp_group->parent = $parent;
                        $tmp_group->root = $root;
                        $tmp_group->name = $tag_value;
                        $tmp_group->weight = $user_submit['weight'];
                        if ($tmp_group->save()) {
                            $lab->group = $tmp_group;
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
                        $lab->group = $tmp_group;
                        $parent = $tmp_group;
                    } else {
                        $tmp_group = O('tag_group');
                        $tmp_group->parent = $parent;
                        $tmp_group->root = $root;
                        $tmp_group->name = $tag_value;
                        $tmp_group->weight = $user_submit['weight'];
                        if ($tmp_group->save()) {
                            $lab->group = $tmp_group;
                            $parent = $tmp_group;
                            $warning['group'][] = T('找不到名称为 %s 的机构,自动创建成功', ['%s' => $tag_value]);
                        } else {
                            $warning['group'][] = T('找不到名称为 %s 的机构,且自动创建失败', ['%s' => $tag_value]);
                        }
                    }
                }
                if ($lab->group->id) $lab->group->connect($lab);
                if (!$lab->save()) {
                    $warning['group'][] = T('用户组织机构保存失败');
                }
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
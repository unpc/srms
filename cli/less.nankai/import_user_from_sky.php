#!/usr/bin/env php
<?php
    /*
     * file import_user_from_sky.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-06-09
     *
     * useage SITE_ID=cf LAB_ID=nankai php import_user_from_sky.php lims2_nankai_sky
     * brief 用于对南开生科院的一卡通用户的数据导入到less.nankai中, 如果用户无数据
     */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'nankai';

require dirname(dirname(__FILE__)). '/base.php';

if ($argc < 2) {
    echo "Usage: SITE_ID=cf LAB_ID=nankai php import_user_from_sky.php lims2_nankai_sky [--dry-run]\n";
    die;
}

//dry-run, 只进行导出数据, 不进行数据导入
$dry_run = in_array('--dry-run', $argv);

Config::set('database.prefix', NULL);

$db = Database::factory($argv[1]);
$root = Tag_Model::root('group');

$start = 0;
$perpage = 50;

$user_csv = new CSV('sky_users.csv', 'w+');
$lab_csv = new CSV('sky_labs.csv', 'w+');

//user write head
$user_csv->write([
    '姓名',
    '登录账号',
    '实验室名称',
    '组织机构',
    '卡号',
    '卡号(s)',
    '所属时间(起始时间)',
    '所属时间(结束时间)',
    '是否隐藏',
    '联系电话',
    '联系地址',
    '用户类型',
    '学号/工号',
    '绑定邮箱',
    '激活时间',
    '电子邮箱',
]);

//lab write head
$lab_csv->write([
    '名称',
    '组织机构',
]);

$full_labs = [];

while($data = $db->query('SELECT * FROM `user` ORDER BY `id` ASC LIMIT %d, %d', $start, $perpage)->rows('assoc')) {

    foreach($data as $d) {

        //获取到用户信息
        //如果token包含ids.nankai
        //说明是南开一卡通用户, 需要进行转移

        if (strpos($d['token'], 'ids.nankai')) {
            //尝试进行数据转移
            $extra = $d['_extra'];

            foreach(@json_decode($extra) as $key => $value) {
                if (! isset($d[$key])) $d[$key] = $value;
            }

            //nk1120120329|ids.nankai%less.nankai => nk1120120329|ids.nankai
            $token = substr($d['token'], 0, strpos($d['token'], '%'));

            //不存在对应的user
            //尝试创建
            if (! O('user', ['token'=> $token])->id) {
                $user = O('user');
                $user->name = $d['name'];
                $user->token = $token;
                $user->card_no = $d['card_no'];
                $user->card_no_s = $d['card_no_s'];
                $user->dfrom = $d['dfrom'];
                $user->dto = $d['dto'];
                $user->hidden = $d['hidden'];
                $user->phone = $d['phone'];
                $user->address = $d['address'];
                $user->member_type = $d['member_type'];
                $user->ref_no = $d['ref_no'];
                $user->binding_email = $d['binding_email'];
                $user->email = $d['email'];

                $group = O('tag', [
                    'name'=> $db->value('SELECT `name` FROM `tag` WHERE id=%d', $d['group_id']),
                    'root'=> $root,
                ]);

                $user->group = $group->id ? $group : $root;

                //xxx实验室(sky)
                //xxx课题组(less.nankai)
                $sky_lab_name = $db->value('SELECT `name` FROM `lab` WHERE id=%d', $d['lab_id']);
                $less_lab_name = trim(str_replace('实验室', '课题组', $sky_lab_name));

                $lab = O('lab', [
                    'name'=> $less_lab_name,
                ]);

                $new_lab = FALSE;
                if (!$lab->id) {
                    $new_lab = TRUE;

                    $lab = O('lab');
                    $lab_data = current($db->query('SELECT * FROM `lab` WHERE id=%d', $d['lab_id'])->rows('assoc'));

                    $group_name = $db->value('SELECT `name` FROM `tag` WHERE id=%d', $lab_data['group_id']);

                    //如果能获取到对应的group_name
                    //尝试获取对应的lgroup
                    $lgroup = NULL;

                    if ($group_name) {
                        $lgroup = O('tag', [
                            'root'=> $root,
                            'name'=> $group_name,
                        ]);
                    }

                    $lab->group = $lgroup->id ? $lgroup : $root;
                    $lab->name = $less_lab_name;

                    if (!in_array($less_lab_name, $full_labs)) {
                        $lab_csv->write([
                            $less_lab_name,
                            $lab->group->id == $root->id ? '全部' : $lab->group->name,
                        ]);
                        $full_labs[] = $less_lab_name;
                    }

                    if (!$dry_run) $lab->save();

                }

                $user->lab = $lab;

                $user_csv->write([
                    $user->name,
                    $user->token,
                    $user->lab->name,
                    $user->group->name,
                    $user->card_no,
                    $user->card_no_s,
                    $user->dfrom,
                    $user->dto,
                    $user->hidden,
                    $user->phone,
                    $user->address,
                    $user->member_type,
                    $user->ref_no,
                    $user->binding_email,
                    $user->atime,
                ]);

                if (!$dry_run) {
                    $user->save();
                    $user->group->connect($user);
                }

                if ($new_lab) $lab->owner = $user;

                if (!$dry_run) {
                    $lab->save();
                    $lab->group->connect($lab);
                }
                echo '.';
            }
        }
    }

    $start += $perpage;
}

$user_csv->close();
$lab_csv->close();

if ($dry_run) {
    echo "\n文件导出为sky_users.csv sky_labs.csv\n";
}
else {
    echo "\n实验室、成员导入成功!\n";
}

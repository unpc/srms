<?php
class CLI_Labs {
    static function import_labs_and_users($file) {
        define('DISABLE_NOTIFICATION', 1);

        if (!($file && file_exists($file))) {
            die("usage: SITE_ID=cf LAB_ID=test php cli.php labs import_labs_and_users import.csv\n");
        }

        // $backend = 'e.jiangnan.edu.cn';
        $backend = '';
        $backends = Config::get('auth.backends');

        $group_root = Tag_Model::root('group');
        $now = Date::time();


        $member_types = [
                '本科生' => 0,
                '硕士研究生' => 1,
                '研究生' => 1,
                '硕士留学生' => 1,
                '硕士研究生' => 1,
                '博士研究生' => 2,
                '博士' => 2,
                '其他学生' => 3,
                '其他研究生' => 3,
                '课题负责人(PI)' => 10,
                '科研助理' => 11,
                'PI助理/实验室管理员' => 12,
                '实验室管理人员' => 12,
                '实验室管理员' => 12,
                '其他老师' => 13,
                '技术员' => 20,
                '实验员' => 20,
                '博士后' => 21,
                '其他' => 22,
                '课题组负责人' => 10,
                '课题组负责人（教师）' => 10,
                ];
        $groups = [];

        $user_total = 0;
        $user_new = 0;
        $user_old = 0;
        $user_failed = 0;
        $failed_users = [];
        $old_users = [];

        $lab_total = 0;
        $lab_new = 0;
        $lab_old = 0;
        $lab_failed = 0;
        $failed_labs = [];
        $old_labs = [];

        $csv = new CSV($file, 'r');

        $row_escaped = 1;
        for (;$row_escaped--;) {
            $csv->read();
        }

        while ($row = $csv->read()) {
            $user_total++;

            // 课题组名称,所属学科,组织机构代码,人员类型,人员名称,e江南账号,e-mail,电话,
            $lab_name = trim($row[0]);
            $lab_subject = trim($row[1]);
            $group_no = trim($row[2]);
            $member_type = trim($row[3]);
            $name = trim($row[4]);
            $token_name = trim($row[5]);
            $email = trim($row[6]);
            $phone = trim($row[7]);
            $password = trim($row[8]);

            // printf("正在处理%s\n", $name);
            echo '.';

            $group_name = $groups[$group_no];

            $group = O('tag_group', [
                        'root' => $group_root,
                        'name' => $group_name,
                        ]);

            if ($lab_name != $last_labs_name) {
                $lab_total++;

                $lab = O('lab', [
                            'name' => $lab_name,
                            ]);

                if (!$lab->id) {
                    $lab->name = $lab_name;
                    $lab->subject = $lab_subject;
                    $lab->group = $group;

                    $lab->atime = $now;
                    if ($lab->save()) {
                        $lab_new++;
                    }
                    else {
                        $lab_failed++;
                        $failed_labs[] = $row;
                    }
                }
                else {
                    $lab_old++;
                    $old_labs[] = $row;
                }
            }
            $last_labs_name = $lab_name;

            if ($backend) {
                $token = Auth::make_token($token_name, $backend);
            }
            else {
                $token = Auth::Normalize($token_name);
            }

            $auth = new Auth($token);
            if (!$auth->create($password)) {
                $user_failed++;
                $failed_users[] = $row;
                continue;
            }

            $user = O('user', [
                        'token' => $token,
                        ]);
            if (!$token_name || !$user->id) {
                $user->token = $token;
                $user->must_change_password = TRUE;
                $user->name = $name;
                $user->email = $email;
                $user->phone = $phone;
                $user->member_type = $member_type;
                $user->group = $group;

                $user->atime = $now;
                if ($user->save()) {
                    $user->connect($lab);
                    $user_new++;
                }
                else {
                    $user_failed++;
                    $failed_users[] = $row;

                    $auth->remove();
                }
            }
            else {
                $user_old++;
                $old_users[] = $row;
            }

            if (10 == $user->member_type && !$lab->owner->id) {
                $lab->owner = $user;
                $lab->save();
            }
        }

        foreach (Q('user') as $u) {
            $u->group->connect($u);
        }
        foreach (Q('lab') as $l) {
            $l->group->connect($l);
        }

        echo "\n";

        printf("=============\n");

        printf("共涉及%d个实验室\n", $lab_total);
        printf("新建立%d个实验室\n", $lab_new);
        printf("已有%d个实验室\n", $lab_old);
        if ($lab_old) {
            foreach ($old_labs as $ol) {
                echo join(',', [
                            $ol[0],
                            $ol[1],
                            $ol[2],
                            ]);
                echo "\n";
            }
        }
        printf("尝试导入，但失败%d实验室\n", $lab_failed);
        if ($lab_failed) {
            foreach ($failed_labs as $ol) {
                echo join(',', [
                            $ol[0],
                            $ol[1],
                            $ol[2],
                            ]);
                echo "\n";
            }
        }

        printf("共处理%d名用户\n", $user_total);
        printf("新导入%d名用户\n" , $user_new);
        printf("已有%d名用户\n", $user_old);
        if ($user_old) {
            foreach ($old_users as $ou) {
                echo join(',', $ou) . "\n";
            }
        }

        printf("尝试导入，但失败%d名用户\n", $user_failed);
        if ($user_failed) {
            foreach ($failed_users as $fu) {
                echo join(',', $fu) . "\n";
            }
        }
    }

    static function import_labs($filename=null) {
        if(!file_exists($filename)){
            die("usage: SITE_ID=cf LAB_ID=test php cli.php labs import_labs import.csv\n");
        }

        if (file_exists($filename)) {

            $csv = new CSV($filename, 'r');

            $lab = new Lab_Batch();

            $header = TRUE;

            while ($row = $csv->read()) {

                if ($header == TRUE) {
                    $header = FALSE;
                    continue;
                }

                if ($row[0]) {

                    $lab->add($row);
                }
            }

            $lab->count();

        }
    }

    //增加set_owner参数, 默认导入时候同步设定owner
    //为false则只导入labs, 不设置owner
    //SITE_ID=cf LAB_ID=nankai php cli.php labs create_labs import.csv 0

    static function create_labs($file = null, $set_owner = TRUE) {
        if(!file_exists($file)){
            die("usage: SITE_ID=cf LAB_ID=test php cli.php labs create_labs import.csv [set_owner]\n");
        }

        $csv = new CSV($file, 'r');
        $csv->read();
        $total_count = $sucess_count = 0;
        $group_root = Tag_Model::root('group');

        while ($row = $csv->read()) {
            $total_count ++;
            if (!$row[0]) continue;
            $lab = O('lab');
            $lab->name = $row[0];
            $lab->contact = $row[2];
            $lab->ref_no = trim($row[4]);
            $lab->type = $row[5];
            $lab->subject = $row[6];
            $lab->util_area = $row[7];
            $lab->location = $row[8];
            $lab->location2 = $row[9];
            $lab->description = trim($row[10]);
            $incharges = explode(',', $row[1]);

            foreach ($incharges as $incharge) {
                $user = O('user', ['name' => $incharge]);
                if ($user->id) break;
            }

            //如果允许设置pi，
            if (!$set_owner) {
                $lab->owner = $user;
            }

            $group = O('tag_group', ['root' => $group_root, 'name' => $row[3]]);
            $lab->group = $group;
            $lab->atime = time();

            if ($lab->save()) {
                $sucess_count ++;
                if ( $group->id ) $group->connect($lab);
                echo "\033[1;40;32m";
                echo $lab->name." => ".$user->name."[{$user->id}]" ."\n";
                echo "\033[0m";
            }
        }

        $csv->close();
        echo "\033[1;40;32m";
        echo sprintf("\n导入数据总数为:%s\t成功数为:%s \n", $total_count, $sucess_count);
        echo "\033[0m";
    }

    //设置实验室owner
    //导入文件和上述creaet_labs为同一csv文件
    static function set_labs_owner($file = null) {
        if(!file_exists($file)){
            die("usage: SITE_ID=cf LAB_ID=test php cli.php labs set_labs_owner import.csv [set_owner]\n");
        }

        $total_count = $sucess_count = 0;

        $csv = new CSV($file, 'r');

        //跳过头
        $csv->read();

        while ($row = $csv->read()) {
            $total_count ++;
            if (!$row[0]) continue;
            $lab = O('lab', ['name'=> $row[0]]);

            $incharges = explode(',', $row[1]);

            foreach ($incharges as $incharge) {
                $user = O('user', ['name' => $incharge]);
                if ($user->id) break;
            }

            $lab->owner = $user;

            if ($lab->save()) {
                $sucess_count ++;
                echo "\033[1;40;32m";
                echo $lab->name." => ".$user->name."[{$user->id}]" ."\n";
                echo "\033[0m";
            }
            else {
                echo "\033[1;40;31m";
                echo $lab->name." => ".$user->name."[{$user->id}]" ."\n";
                echo "\033[0m";
            }
        }

        $csv->close();
        echo "\033[1;40;32m";
        echo sprintf("\n导入数据总数为:%s\t成功数为:%s \n", $total_count, $sucess_count);
        echo "\033[0m";
    }
}


class Lab_Batch {

    private $total = 0;
    private $fail = 0;
    private $count = 0;

    public function add($row=[]) {
        $this->total++;
        $name = str_replace(' ','',$row[0]);
        $owner_name = str_replace(' ','',$row[1]);
        $owner = O('user', ['name'=>$owner_name]);

        if (Q("lab[name=$name][owner=$owner]")->total_count() > 0) {
            $lab = O('lab', ['name'=>$name, 'owner'=>$owner]);
        }
        else{
            //添加实验室
            $lab = O('lab');
            $lab->name = $name;
            $lab->owner = $owner;
        }

        if ($lab->save()) {
            $this->count++;
            echo "\033[1;40;32m";
            echo $name." => ".$owner->name."[{$owner->id}]" ."\n";
            echo "\033[0m";
        }
        else {
            $this->fail++;
            echo "\033[1;40;31m";
            echo $name.'导入失败'."\n";
            echo "\033[0m";
        }
    }

    public function count() {
        echo '共有'.$this->total.'条数据，'.'更新了'.$this->count.'条数据'."\n";
        if ($this->fail) {
            echo $this->fail.'条数据更新失败'."\n";
        }
    }
}

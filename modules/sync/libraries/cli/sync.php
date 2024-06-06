<?php
class CLI_Sync
{
    // 组织机构root, 自动生成的lab等特殊的orm , 需要在sync_objects 之前在所有站点跑一次, 不然同步时会生成多个
    public static function sync_objects_pre()
    {
        $tag = Tag_Model::root('group');
        $tag->uuid = "Tag_Model::root('group')";
        $tag->platform = LAB_ID;
        $tag->save();

        $lab = Lab_Model::default_lab();
        $lab->uuid = 'Lab_Model::default_lab()';
        $lab->platform = LAB_ID;
        $lab->save();
        $lab = Equipments::default_lab();
        $lab->uuid = 'Equipments::default_lab()';
        $lab->platform = LAB_ID;
        $lab->save();
    }
    
    public static function sync_objects()
    {

        $topic = Config::get('sync.topics');

        $group_root = Tag_Model::root('group');

        if(in_array('tag.save',$topic)){
            foreach (Q("{$group_root}<root tag_group") as $group) {
                var_dump($group->name);
                $group->ctime = $group->ctime + 1;
                $group->platform = LAB_ID;
                $group->save();
            }
        }

        if(in_array('user.save',$topic)){
            foreach (Q("user") as $user) {
                if ($user->token == 'genee|database') {
                    continue;
                }
                var_dump($user->name);
                $user->ctime = $user->ctime + 1;
                $user->platform = LAB_ID;
                $user->save();
            }
        }

        if(in_array('lab.save',$topic)){
            foreach (Q("lab") as $lab) {
                var_dump($lab->name);
                $lab->ctime = $lab->ctime + 1;
                $lab->platform = LAB_ID;
                $lab->save();
            }
        }

        if(in_array('equipment.save',$topic)){
            foreach (Q("equipment") as $eq) {
                var_dump($eq->name);
                $eq->ctime = $eq->ctime + 1;
                $eq->platform = LAB_ID;
                $eq->save();
                foreach (Q("{$eq}<incharge user") as $u) {
                    $eq->connect($u, 'incharge');
                }
                foreach (Q("{$eq}<contact user") as $u) {
                    $eq->connect($u, 'contact');
                }
            }
        }

        if(in_array('user.save',$topic)){
            foreach (Q("user") as $user) {
                var_dump($user->name);
                foreach (Q("{$user} lab") as $l) {
                    $user->connect($l);
                }
                foreach (Q("{$user}<pi lab") as $l) {
                    $user->connect($l, 'pi');
                }
                foreach (Q("{$user} tag") as $l) {
                    $user->connect($l);
                }
            }
        }
    }
}

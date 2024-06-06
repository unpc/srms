#!/usr/bin/env php
<?php
    /*
     * file fix_sky_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-06-09
     *
     * useage SITE_ID=cf-lite LAB_ID=nankai_sky
     * brief
     */


$_SERVER['SITE_ID'] = 'cf-lite';
$_SERVER['LAB_ID'] = 'nankai_sky';

require 'base.php';

define('DISABLE_NOTIFICATION');

$root = Tag_Model::root('group');
$nankai = O('tag', [
    'name'=> '南开大学',
    'root'=> $root,
    'parent'=> $root,
]);

$sky = O('tag', [
    'name'=> '生命科学学院',
    'root'=> $root,
    'parent'=> $nankai,
]);

function update_tag($old_tag, $new_tag) {

    //找到old_tag的数据 修正为new_tag

    //更新用户组织机构
    foreach(Q("user[group={$old_tag}]") as $user) {
        $user->group = $group;
        $user->save();
    }

    foreach(Q("lab[group={$old_tag}]") as $lab) {
        $lab->group = $group;
        $lab->save();
    }

    foreach(Q("equipment[group={$old_tag}]") as $eq) {
        $eq->group = $group;
        $eq->save();
    }
}

//1、微生物系删除, 修改原“南开大学 >> 微生物系”用户、组织机构、实验室到"南开大学 >> 生命科学学院 >> 微生物学系"下
//由于已存在"南开大学 >> 生命科学学院 >> 微生物学系", 故考虑进行删除即可
$tag1 = O('tag', [
    'name'=> '微生物系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$weishengwu = O('tag', [
    'name'=> '微生物学系',
    'root'=> $root,
    'parent'=> $sky,
]);

//更新旧tag数据到新tag上, 删除旧tag
update_tag($tag1, $weishengwu);

$tag1->delete();

//2、微生物学系删除, 原数据更新到"南开大学 >> 生命科学学院 >> 微生物学系" 下

$tag2 = O('tag', [
    'name'=> '微生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

update_tag($tag2, $weishengwu);
$tag2->delete();

//3、"南开大学 >>  化学学院高分子所" 数据更新到"南开大学 >> 化学学院"
//更新数据, 删除化学学院高分子所
$tag2 = O('tag', [
    'name'=> '化学学院高分子所',
    'root'=> $root,
    'parent'=> $nankai,
]);

$huaxue = O('tag', [
    'name'=> '化学学院',
    'root'=> $root,
    'parent'=> $nankai,
]);

update_tag($tag2, $huaxue);
$tag2->delete();


//4、"南开大学 >> 动物生物和发育生物学系" 修改为 "南开大学 >> 生命科学学院 >> 动物生物学和发育生物学系"
//更新结构, 无需更新对应数据
$tag4 = O('tag', [
    'name'=> '动物生物和发育生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag4->parent = $sky;
$tag4->name = '动物生物学和发育生物学系';
$tag4->save();

//5、"南开大学 >> 生物化学和分子生物学系" 修改为 "南开大学 >> 生命科学学院 >> 生物化学和分子生物学系"
//已存在"生物化学和分子生物学系", 需更新数据, 删除"南开大学 >> 生物化学和分子生物学系"
//更新数据

$tag5 = O('tag', [
    'name'=> '生物化学和分子生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag6 = O('tag', [
    'name'=> '生物化学和分子生物学系',
    'root'=> $root,
    'parent'=> $sky,
]);

update_tag($tag5, $tag6);
$tag5->delete();

//6、"南开大学 >> 植物生物学和生态学系" 修改为 "南开大学 >> 生命科学学院 >> 植物生物学和生态学系"
//更新结构即可
$tag7 = O('tag', [
    'name'=> '植物生物学和生态学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag7->parent = $sky;
$tag7->save();

//7、"南开大学 >> 动物生物学和发育生物学系" 修改为 "南开大学 >> 生命科学学院 >> 动物生物学和发育生物学系"
//更新结构即可
$tag8 = O('tag', [
    'name'=> '动物生物学和发育生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

//tag4已创建了对应的tag
//更新数据
update_tag($tag8, $tag4);
$tag8->delete();

//8、"南开大学 >> 遗传与细胞生物学系" 修改为 "南开大学 >> 生命科学学院 >> 遗传学与细胞生物学系"
//更新结构即可
$tag9 = O('tag', [
    'name'=> '遗传与细胞生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag9->parent = $sky;
$tag9->name = '遗传学与细胞生物学系';
$tag9->save();


//9、"南开大学 >> 遗传学与细胞生物学系" 修改为 "南开大学 >> 生命科学学院 >> 遗传学与细胞生物系"
//更新数据后, 删除tag
$tag10 = O('tag', [
    'name'=> '遗传学与细胞生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);
update_tag($tag10, $tag9);
$tag10->delete();

//10、"南开大学 >> 遗传学和细胞生物学系" 修改为 "南开大学 >> 生命科学学院 >> 遗传学与细胞生物学系"
//更新数据后, 删除tag
$tag11 = O('tag', [
    'name'=> '遗传学和细胞生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);
update_tag($tag11, $tag9);
$tag11->delete();

//11、"南开大学 >> 生物活性材料教育部重点实验室" 修改为 "南开大学 >> 生命科学学院 >> 生物活性材料研究实验室"
//更新数据后, 删除tag
$tag12 = O('tag', [
    'name'=> '生物活性材料教育部重点实验室',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag13 = O('tag', [
    'name'=> '生物活性材料研究实验室',
    'root'=> $root,
    'parent'=> $sky,
]);

update_tag($tag12, $tag13);
$tag12->delete();


//13、"南开大学 >> 物理学院生物物理系" 修改为 "南开大学 >> 物理学院 >> 生物物理科学与技术系"
//更新结构
$tag14 = O('tag', [
    'name'=> '物理学院生物物理系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$wuli = O('tag');
$wuli->name = '物理学院';
$wuli->root = $root;
$wuli->parent = $nankai;
$wuli->save();

$tag14->parent = $wuli;
$tag14->name = '生物物理科学与技术系';
$tag14->save();

//14、"南开大学 >> 药学院微生物系" 修改为 "南开大学, 药学院, 药学院科研综合"
//更新结构
//更新名称

$tag15 = O('tag', [
    'name'=> '药学院微生物系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$yao = O('tag', [
    'name'=> '药学院',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag15->parent = $yao;
$tag15->name = '药学院科研综合';
$tag15->save();

//15、"南开大学 >> 药学院糖化学生物学系" 修改为 "南开大学 >> 药学院 >> 药学院科研综合"
//更新数据
//删除tag
$tag16 = O('tag', [
    'name'=> '药学院糖化学生物学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

update_tag($tag16, $tag15);
$tag16->delete();

//16、"南开大学 >> 医学院微生物系" 修改为 "南开大学 >> 医学院 >> 医学院科研综合"
//更新结构, 更新名称
$tag17 = O('tag', [
    'name'=> '医学院微生物系',
    'root'=> $root,
    'parent'=> $nankai,
]);

$yi = O('tag', [
    'name'=> '医学院',
    'root'=> $root,
    'parent'=> $nankai,
]);

$tag17->parent = $yi;
$tag17->name = '医学院科研综合';
$tag17->save();

//17、"南开大学 >> 医学院免疫学系" 修改为 "南开大学 >> 医学院 >> 医学院科研综合"
//更新数据
//删除tag
$tag18 = O('tag', [
    'name'=> '医学院免疫学系',
    'root'=> $root,
    'parent'=> $nankai,
]);

update_tag($tag18, $tag17);
$tag18->delete();

//18 把遗传系数据放置到 南开大学 生命科学学院 遗传学与细胞生物学系
$tag19 = O('tag', [
    'name'=> '遗传系',
    'root'=> $root,
    'parent'=> $nankai,
]);

update_tag($tag19, $tag9);
$tag19->delete();

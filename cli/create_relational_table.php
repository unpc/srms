<?php
/**
 * SITE_ID=cf LAB_ID=sdu_phychem php cli/create_relational_table.php
 * 一校N区 创建关系表脚本
 */

require_once 'base.php';

/**
 * @todo 按是否启用模块将tables进行拆分，只生成当前站点启用的表
 */
$db = Database::factory();
$tables = [
    '_r_tag_group_lab',
    '_r_tag_group_equipment',
    '_r_user_tag_group',
    '_r_user_tag_equipment_user_tags',
    '_r_tag_equipment_user_tags_lab',
    '_r_user_billing_department',
    '_r_tag_group_tag_equipment_user_tags',
    '_r_role_perm',
    '_r_user_role',
    '_r_user_lab',
    '_r_user_door',
    '_r_equipment_door',
    '_r_patent_equipment',
    '_r_patent_lab',
    '_r_tag_achievements_patent_patent',
    '_r_equipment_award',
    '_r_lab_award',
    '_r_tag_achievements_award_award',
    '_r_publication_equipment',
    '_r_publication_lab',
    '_r_tag_achievements_publication_publication',
];

if (Module::is_installed('vidmon')) {
    $tables[] = '_r_vidcam_equipment';
    $tables[] = '_r_vidcam_user';
}

foreach ($tables as $table) {
    $db->prepare_table(
        $table,
        array(
            // fields
            'fields'  => array(
                'id1'      => array('type' => 'bigint', 'null' => false),
                'id2'      => array('type' => 'bigint', 'null' => false),
                'type'     => array('type' => 'varchar(20)', 'null' => false),
                'approved' => array('type' => 'int', 'null' => false, 'default' => 0),
            ),
            // indexes
            'indexes' => array(
                'PRIMARY'  => array('type' => 'primary', 'fields' => array('id1', 'id2', 'type')),
                'id1'      => array('fields' => array('id1', 'type')),
                'id2'      => array('fields' => array('id2', 'type')),
                'approved' => array('fields' => array('approved')),
            ),
        )
    );
}
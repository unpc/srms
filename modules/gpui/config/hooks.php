<?php

// 仪器平板相关
$config['controller[admin/index].ready'][] = 'Pad_Jarvis_Admin::setup';
$config['equipment.links'][] = 'Pad_Jarvis_Admin::equipment_links';
$config['equipments_edit_use_submit'][] = 'Pad_Jarvis_Equipment::edit_use';
$config['eq_record_model.saved'][] = 'Pad_Jarvis_Eq_Record::on_record_saved';

// api Hooks
$config['gpui.api.equipment.inforDetail'][] = "GPUI_API_Extra::equipment_detail_basic";
$config['gpui.api.equipment.inforDetail'][] = "GPUI_API_Extra::equipment_detail_stat";
$config['gpui.api.equipment.inforDetail'][] = "GPUI_API_Extra::equipment_detail_reserv";
$config['gpui.api.equipment.inforDetail'][] = "GPUI_API_Extra::equipment_detail_jarvis";

// gpui仪器平板, 可以使用人脸识别...这里用gapperId->userId
$config['get_user_from_sec_card'][] = 'Pad_Jarvis_User::get_user_from_sec_card';

<?php

$config['controller[admin/index].ready'][] = 'Watcher_Admin::setup';

$config['equipment.links'][] = 'Watcher_Admin::equipment_links';
$config['equipments_edit_use_submit'][] = 'Watcher_Equipment::edit_use';
$config['equipment_model.saved'][] = 'Watcher_Equipment::save';

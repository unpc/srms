<?php

$config['view[calendar/component_form].prerender'][] = ['callback'=>'Cal_Component_Model::prerender_component', 'weight'=>-100];
$config['view[calendar/component_info].prerender'][] = ['callback'=>'Cal_Component_Model::prerender_component', 'weight'=>-100];
$config['cache_header'][] = ['callback' => 'Application::cache_header', 'weight' => 100];

$config['view[calendar/permission_check].prerender'][] = ['callback'=>'Cal_Component_Model::prerender_permission_check', 'weight'=>-100];

<?php
$config['login.view'] = 'LoGapper::login_view';
$config['show.sidbar'][] = ['callback' => 'Exam::show_sidebar', 'weight' => -999];
$config['labs.get_remote_user'] = "Exam::get_remote_user";

$config['view[calendar/permission_check].prerender'][] = 'Exam::reserv_permission_check';

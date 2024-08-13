<?php

$config['analysis.init.table'][] = 'Analysis_Eq_Record::init';
$config['analysis.init.table'][] = 'Analysis_Eq_Sample::init';
$config['analysis.init.table'][] = 'Analysis_Eq_Reserv::init';
$config['analysis.init.table'][] = 'Analysis_Project_Publication::init';
$config['analysis.init.table'][] = 'Analysis_Project_Awards::init';
$config['analysis.init.table'][] = 'Analysis_Project_Patent::init';

$config['analysis.full.data'][] = 'Analysis_Eq_Record::full';
$config['analysis.full.data'][] = 'Analysis_Eq_Sample::full';
$config['analysis.full.data'][] = 'Analysis_Eq_Reserv::full';
$config['analysis.full.data'][] = 'Analysis_Project_Publication::full';
$config['analysis.full.data'][] = 'Analysis_Project_Awards::full';
$config['analysis.full.data'][] = 'Analysis_Project_Patent::full';


$config['analysis.increment.data'][] = 'Analysis_Eq_Record::increment';
$config['analysis.increment.data'][] = 'Analysis_Eq_Sample::increment';
$config['analysis.increment.data'][] = 'Analysis_Eq_Reserv::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Publication::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Awards::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Patent::increment';
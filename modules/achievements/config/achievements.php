<?php
$config['publication.collect.sites'] = [
	'pubmed'=>[
		//'url'=>'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmedi&retmode=xml&id=%id',
		'logo'=>'!achievements/logo/pubmed.png',
		'description'=>'',
	]
];
$config['equipments.require'] = FALSE;

$config['export_columns.publication'] = [
	'title' => '标题',
	'author' => '作者',
	'journal' => '期刊',
	'date' => '日期',
	'volume' => '卷',
	'issue' => '刊号',
	'tags' => '标签',
	'lab' => '课题组',
	'project' => '关联项目',
	'equipment' => '关联仪器'
];

$config['export_columns.award'] = [
	'name' => '名称',
	'level' => '获奖级别',
	'date' => '获奖日期',
	'people' => '人员',
	'lab' => '课题组',
	'project' => '关联项目',
	'equipment' => '关联仪器'
];

$config['export_columns.patent'] = [
	'name' => '名称',
	'ref_no' => '专利号',
	'date' => '日期',
	'type' => '专利类型',
	'people' => '人员表',
	'lab' => '课题组',
	'project' => '关联项目',
	'equipment' => '关联仪器'
];

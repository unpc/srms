<?php

/*
 * @file environment.php
 * @author Shulei Li<shulei.li@geneegroup.com>
 * @date 2014-11-11
 *
 * @brief 工作时间规则检测 
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_empower/environment
 */
if (!Module::is_installed('eq_empower')) return true;
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_empower模块\n\n";
require_once('empower_rule_test.php'); 
$test_model = new empower_rule_test();
$test_model->set_up();
$test_model->run();
$test_model->tear_down();

<?php

/*
 * @file tn_lockable.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 创建一个集成了Presentable_Model的类
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/treenote/tn_lockable
 */
if (!Module::is_installed('treenote')) return true;

class Tn_Lockable extends Presentable_Model
{
	public function lock() 
	{
	}

	public function unlock()
	{
	}
}

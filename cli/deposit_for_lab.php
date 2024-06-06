#!/usr/bin/env php
<?php
/**
 * SITE_ID=cf LAB_ID=nankai ./deposit.php @充值金额
 * 为所有实验室创建财务帐号并充值
 **/
require "base.php";
$income = null;
while (is_null($income))
{
	fwrite(STDOUT, '请输入默认的充值金额：');
	$income = fgets(STDIN);
}

$departments = Q('billing_department')->to_assoc('id', 'name');
if (count($departments)<1)
{
	fwrite(STDOUT, '系统中尚未创建财务中心！');
	exit;
}
foreach ($departments as $id=>$name)
{
	fwrite(STDOUT, "{$id}\t{$name}\n");
}

$id = null;
while (!isset($departments[$id]))
{
	fwrite(STDOUT, '请选择一个财务中心：');
	$id = (int)fgets(STDIN);
}

$department = O('billing_department', $id);

$failed_labs = [];
$labs = Q('lab');
foreach ($labs as $lab)
{
	$account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
	if (!$account->id)
	{
		$account = O('billing_account');
		$account->lab = $lab;
		$account->department = $department;
		$account->save();
	}
	if (!$account->id)
	{
		$failed_labs[] = $lab;
	}
	else
	{
		$transaction = O('billing_transaction');
		$transaction->account = $account;
		//$transaction->user = $user;
		if ($income < 0)
		{
			$transaction->outcome = abs($income);
			$transaction->description = "每个实验室扣费{$income}元。";
		}
		else
		{
			$transaction->income = $income;
			$transaction->description = "每个实验室充值{$income}元。";
		}
		$transaction->save();
		fwrite(STDOUT, "\t{$lab->name}\t充值成功。\n");
	}
}

foreach ($failed_labs as $lab)
{
	fwrite(STDOUT, "以下实验室充值失败：\n");
	fwrite(STDOUT, "{$lab->id}\t{$lab->name}\n");
}

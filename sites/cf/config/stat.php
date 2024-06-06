<?php

$config['metrics'] = [
	'get_n_active_user',
	'get_n_inactive_user',
	'get_n_new_active_user',
	'get_n_new_inactive_user',
	'get_n_logon_succ',
	[
		'func' => 'get_n_user_logon_succ',
		'opts' => [
			'user_ids' => [1],
			],
		],
	'get_n_active_lab',
	'get_n_inactive_lab',
	'get_n_new_active_lab',
	'get_n_new_inactive_lab',
	'get_n_billing_dept',
	'get_n_billing_acct',
	'get_sum_billing_acct_balance',
	'get_sum_billing_acct_income',
	'get_sum_billing_acct_new_income',
	'get_sum_billing_acct_outcome',
	'get_sum_billing_acct_new_outcome',
	'get_period_n_all_eq_modified',
	'get_period_n_each_eq_use',
	'get_sum_nfs_share',
	'get_n_roles',
	'get_period_n_role_modified',
];



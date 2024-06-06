<?php

class Billing_Account {

	static function setup($e) {
		Event::bind('department.index.tab', 'Billing_Account::department_accounts_tab', '0', 'accounts');
	}

	static function setup_lab() {
		Event::bind('lab.view.tab', 'Billing_Account::lab_view_tab', '1');
	}

	static function lab_view_tab($e, $tabs) {
		$lab = $tabs->lab;
		if (L('ME')->is_allowed_to('查看财务情况', $lab) && Q("billing_account[lab={$lab}]")->total_count() > 0) {
			Event::bind('lab.view.content', 'Billing_Account::lab_view_content_account', '0', 'billing_account');
			Event::bind('lab.view.tool_box', 'Billing_Account::lab_department_view_tool', '0','billing_account');
			$tabs->add_tab('billing_account', [
				'url'=>$lab->url('billing_account'),
				'title'=>I18N::T('billing', '财务')
			]);
		}
	}

	static function lab_view_content_account($e, $tabs) {
		$lab = $tabs->lab;
		$account_type = $tabs->account_type;

        $tabs->content = V('billing:lab/index');

        $tabs->content->tertiary_tabs = Widget::factory('tabs');

		if (L('ME')->is_allowed_to('查看财务概要', $lab)) {
			Event::bind('account.index.tab.content', 'Billing_Account::lab_index_list', 0, 'list');
            $tabs->content->tertiary_tabs
				->add_tab('list', [
					'url'=>$lab->url('billing_account.list'),
					'title'=>I18N::T('billing', '概要'),
				]);
		}

		if (L('ME')->is_allowed_to('列表收支明细', $lab)) {
			Event::bind('account.index.tab.content', 'Billing_Account::lab_index_transaction', 0, 'transaction');
            $tabs->content->tertiary_tabs
				->add_tab('transaction', [
					'url'=>$lab->url('billing_account.transaction'),
					'title'=>I18N::T('billing', '明细'),
				]);
		}

        $tabs->content->tertiary_tabs
			->set('class', 'fourth_tabs')
			->set('lab', $lab)
			->content_event('account.index.tab.content')
			->select($account_type);

	}

	static function lab_index_list($e, $tabs) {
		$me = L('ME');
		$lab = $tabs->lab;
		if ($GLOBALS['preload']['billing.single_department']) {
		#ifdef (billing.single_department)
			$department = Billing_Department::get();
			$account = Q("billing_account[lab=$lab][department=$department]:limit(1)")->current();
			$tabs->content = V('billing:lab/summary', [
				'department'=>$department,
				'account'=>$account
			]);
//		#endif
		}
		else {
		#ifndef (billing.single_department)
			$form = Lab::form();
			if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('billing_department')){
                $selector = "billing_department[site=". LAB_ID ."]<department billing_account[lab={$lab}]";
            }else{
                $selector = "billing_account[lab={$lab}]";
            }
			// if (me is just some deps' owner) only show my deps' acct

			// 若我只是某财务部门负责人，与实验室无关系，则只能查看自己部门下的财务帐号
			// if (!($me->is_allowed_to('查看实验室总体财务情况', $lab))) {
			// 	$selector = "{$me} billing_department<department " . $selector;
			// }
            if ($form['department_id']) {
                $billing_department = O('billing_department', $form['department_id']);
                if ($billing_department->id) {
                    $selector .= "[department={$billing_department}]";
                }
            }

			$sort_by = $form['sort'];
			$sort_asc = $form['sort_asc'];
			$sort_flag = $sort_asc ? 'A':'D';
			if ($form['sort']) {
				$selector .= ":sort({$sort_by} {$sort_flag})";
			}

			$accounts = Q($selector);
			$form_token = Session::temp_token('billing_transaction_',300);
			$form['selector'] = $selector;
			$_SESSION[$form_token] = $form;
			$pagination = Lab::pagination($accounts, (int)$form['st'], 30);

            $tabs->form_token = $form_token;

			$tabs->content = V('billing:accounts', [
					'object'=>$lab,
					'accounts'=>$accounts,
					'pagination'=>$pagination,
					'form'=>$form,
					'form_token'=>$form_token,
					'sort_asc'=>$sort_asc,
					'sort_by'=>$sort_by,
				]);
		#endif
		}
	}

	static function lab_index_transaction($e, $tabs) {

		$me = L('ME');
		$lab = $tabs->lab;
		$form_token = Input::form('form_token');

		if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
		}
		else {
			$form_token = Session::temp_token('billing_transaction_',300);
			$form = Lab::form(function(&$old_form, &$form){
                if ($form['dtstart']) {
                    unset($old_form['dtstart']);
                }

                if ($form['dtend']) {
                    unset($old_form['dtend']);
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
			});
		}

        $account_pre = [];

        if ($GLOBALS['preload']['billing.single_department']) {
        #ifdef (billing.single_department)
            $department = Billing_Department::get();
            $selector = "{$lab}<lab billing_account[department={$department}]";
            $account_pre[] = "{$lab}<lab";
            $account_selector = "billing_account[department={$department}]";
        #endif
        }
        else {
        #ifndef (billing.single_department)
            $account_selector = $selector = "billing_account[lab={$lab}]";
            // 若我只是某财务部门负责人，与实验室无关系，则只能查看自己部门下的财务帐号的收支明细
            // if (!($me->is_allowed_to('查看实验室总体财务情况', $lab))) {
            // 	$selector = "{$me} billing_department<department " . $selector;
            // 	$account_pre[] = "{$me} billing_department<department";
            // }

            if ($form['department_id']) {
                $department = O('billing_department', $form['department_id']);
                if ($department->id) {
                    $selector = "{$lab}<lab billing_account[department={$department}]";
                    $account_pre = [];
                    $account_pre[] = "{$lab}<lab";
                    $account_selector = "billing_account[department={$department}]";
                }
                else {
                    $form['department_id'] = NULL;
                }
            }
        #endif
        }

        if (!$form['transaction_type']) unset($form['sub_transaction_type']);

        $account_suf = "billing_transaction[income!=0|outcome!=0]";
        $selector .= '<account billing_transaction[income!=0|outcome!=0]';


        if (Module::is_installed('db_sync') && Db_Sync::is_slave() && DB_SYNC::is_module_unify_manage('billing_transaction')) {
            $selector .= '[site=' . LAB_ID . ']';
        }

        //按时间搜索
        if($form['dtstart']){
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[ctime>=$dtstart]";
            $account_suf .= "[ctime>=$dtstart]";

        }

        if($form['dtend']){
            $dtend = Q::quote($form['dtend']);
            $selector .= "[ctime>0][ctime<=$dtend]";
            $account_suf .= "[ctime>0][ctime<=$dtend]";
        }

        if(!$form['dtstart'] && !$form['dtend']) {
            $dtend_date = getdate(time());
            $form['dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['dtstart'] = $form['dtend'] - 2592000;
        }

        //按凭证号搜索
        if($form['certificate']) {
            $certificate = Q::quote(trim($form['certificate']));
            $selector    .= "[certificate*=$certificate]";
            $account_suf .= "[certificate*=$certificate]";
        }

        if(isset($form['code']) && $form['code']) {
            $code = Q::quote($form['code']);
            $selector    .= "[code*=$code]";
            $account_suf .= "[code*=$code]";
        }

        if(isset($form['project_type']) && $form['project_type'] != -1) {
            $project_type = Q::quote($form['project_type']);
            $selector    .= "[project_type=$project_type]";
            $account_suf .= "[project_type=$project_type]";
        }

        // 按明细编号搜索
        if( $form['transaction_id'] ) {
            $transaction_id = Q::quote(trim($form['transaction_id']));
            $selector .= "[id=$transaction_id]";
            $account_suf .= "[id=$transaction_id]";
        }

        //按类型搜索
        if( $form['transaction_type'] ) {
            if($form['transaction_type'] == Billing_Transaction::FIN_TYPE_IN) {
                $selector    .= "[income>0][outcome=0]";
                $account_suf .= "[income>0][outcome=0]";
            }
			elseif ($form['transaction_type'] == Billing_Transaction::FIN_TYPE_OUT) {
                $selector    .= "[income=0][outcome>0]";
                $account_suf .= "[income=0][outcome>0]";
            }
        }
		
		switch($form['transaction_type']) {
            case Billing_Transaction::FIN_TYPE_IN : //转入
                switch($form['sub_transaction_type']) {
                    case Billing_Transaction::FIN_TYPE_IN_REMOTE : //远程
                        $selector .= '[source!=local][!transfer]';
                        $account_suf .= '[source!=local][!transfer]';
                        break;
                    case Billing_Transaction::FIN_TYPE_IN_LOCAL : //本地
                        $selector .= '[source=local][!transfer]';
                        $account_suf .= '[source=local][!transfer]';
                        break;
                    case Billing_Transaction::FIN_TYPE_IN_TRANSFER : //调账
                        $selector .= '[transfer]';
                        $account_suf .= '[transfer]';
                        break;
                    case Billing_Transaction::FIN_TYPE_IN_ANY : //所有
                        break;
                    default :
                }

                $selector .= '[income]';
                $account_suf .= '[income]';
                break;
            case Billing_Transaction::FIN_TYPE_OUT : //转出
                switch($form['sub_transaction_type']) {
                    case Billing_Transaction::FIN_TYPE_OUT_REMOTE : //远程
                        $selector .= '[source!=local][!transfer][!manual]';
                        $account_suf .= '[source!=local][!transfer][!manual]';
                        break;
                    case Billing_Transaction::FIN_TYPE_OUT_LOCAL : //本地
                        $selector .= '[source=local][!transfer][manual]'; //扣除转账, 手动操作
                        $account_suf .= '[source=local][!transfer][manual]'; //扣除转账, 手动操作
                        break;
                    case Billing_Transaction::FIN_TYPE_OUT_USE : //使用 //非手动操作
                        $selector .= '[!manual]';
                        $account_suf .= '[!manual]';
                        break;
                    case Billing_Transaction::FIN_TYPE_OUT_TRANSFER : //调账
                        $selector .= '[transfer]'; //调账
                        $account_suf .= '[transfer]'; //调账
                        break;
                    case Billing_Transaction::FIN_TYPE_OUT_ANY : //所有
                        break;
                    default :

                }

                $selector .= '[outcome]';
                $account_suf .= '[outcome]';
            default :
		}
		
		$sort_by = $form['sort'] ? : 'ctime';
        $sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';

		switch ($sort_by) {
			case 'department':
				$selector .= ":sort(billing_account.department_id $sort_flag)";
				break;
            case 'date':
                $selector .= ":sort(ctime $sort_flag)";
				break;
			case 'lab_id':
				$selector .= ":sort(lab.name_abbr $sort_flag)";
				break;
			case 'income':
				$selector .= ":sort($sort_by $sort_flag)";
				break;
			case 'outcome':
				$selector .= ":sort($sort_by $sort_flag)";
				break;
			case 'certificate':
				$selector .= ":sort($sort_by $sort_flag)";
				break;
            default:
				$selector .= ':sort(ctime D, id D)';
                break;
		}

		$transactions = Q($selector);

		$type = Input::form('type');

		$_SESSION[$form_token] = $form;
		$_SESSION[$form_token]['Q_query'] = $selector;
		$account_pre[] = $account_suf.'<account';

		/*
		 * TASK #1398::LIMS-CF中的一些问题修正:
		 * 财务中心上的帐务数据显示异常，不能按照搜索条件正常显示。
		 * 结果：显示当前搜索结果下的账户数（非记录数）、总收入、总支出、总余额。
		 * (kai.wu@2011.08.31)
		 */
		if (count($account_pre)) {
			$account_selector = '('.implode(',', $account_pre).') '.$account_selector;
		}


		$account_count = Q($account_selector)->total_count();
		$total_income = $transactions->sum('income');
		$confirmed_status = Billing_Transaction_Model::STATUS_CONFIRMED;
		$confirmed_income = $transactions->filter("[status={$confirmed_status}]")->sum('income');
		$total_outcome = $transactions->sum('outcome');
		if($GLOBALS['preload']['billing.single_department']){
			$total_balance = $transactions->current()->account->balance;
		}
		else{
			$accounts = Q($account_selector);
			foreach ($accounts as $account) {
				$total_balance += $account->balance;
			}
		}

		$tabs->form_token = $form_token;

		$pagination = Lab::pagination($transactions, (int)$form['st'], 25);

		$tabs->content = V('billing:lab/transactions', [
			'lab'=>$lab,
			'transactions'=>$transactions,
			'pagination'=>$pagination,
			'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
			'form'=>$form,
			'form_token'=>$form_token,
			'account_count'=>$account_count,
			'total_income'=>$total_income,
			'confirmed_income'=>$confirmed_income,
			'total_outcome'=>$total_outcome,
			'total_balance'=>$total_balance,
		]);
	}

    static function lab_department_view_tool($e,$tabs){
        $object = $tabs->lab;
        if (empty($object)){
            $object = $tabs->department;
            $object_name = $object->name();
            $type = $object_name;
        }else{
            $object_name = $object->name();
            $type = $tabs->account_type;
        }

        $form = Lab::form();
        $me = L('ME');
        $column = [];
        switch ($type){
            case 'billing_department':
            case 'list':
                if ($type == 'billing_department'){
                    $form_token = $tabs->form_token;
                }else{
                    $form_token = $tabs->content->tertiary_tabs->form_token;
                    $lab = $object;
                }

                $panel_buttons = new ArrayIterator;

                $user_lab_refill_check = Billing_Account::user_lab_refill_check($me, $lab);
                $user_is_lab_deduction = Billing_Account::user_is_lab_deduction($me, $lab);

                $enable_refill = TRUE;
                if($GLOBALS['preload']['billing.single_department']){
                    $department = Billing_Department::get();
                    $account = O('billing_account', ['department'=>$department, 'lab'=>$lab]);
                    if($account->source != 'local' && $account->voucher) $enable_refill = FALSE;
                } else {
                    $local_accounts = Q("billing_account[source!='local'][!voucher][lab={$lab}]");
                    //如果远程账户都存在账号,则屏蔽充值按钮
                    if(!$local_accounts->total_count()) $enable_refill = FALSE;
                }

                if (($user_lab_refill_check || $user_is_lab_deduction) && Config::get('billing.enable_local_inpour')){
                    if (!Config::get('billing.disabled_manual_changes')) {
                        if ($user_lab_refill_check && $enable_refill){
                            $panel_buttons[] = [
                                'text' =>I18N::HT('billing', '充值'),
                                'tip'=>I18N::HT('billing', '充值'),
                                'extra' => 'class="blue top" q-object="account_credit" q-event="click" q-static="' .H(['lab_id'=>$lab->id]). '"
                                 q-src="'.URI::url('!billing/account').'"',
                            ];
                        }
    
                        if ($user_is_lab_deduction){
                            $panel_buttons[] = [
                                'text' =>I18N::HT('billing', '扣费'),
                                'tip'=>I18N::HT('billing', '扣费'),
                                'extra' => 'class="blue top" q-object="account_deduction" q-event="click" q-static="' .H(['lab_id'=>$lab->id]). '"
                q-src="'.URI::url('!billing/account').'"',
                            ];
                        }
                    }
                }

                if ($object_name == 'billing_department' && $me->is_allowed_to('添加财务帐号', $object)){
                    $panel_buttons[] = [
                        'text' =>I18N::HT('billing', '添加账号'),
                        'tip'=>I18N::HT('billing', '添加账号'),
                        'extra' => 'class="button button_add  top" q-object="add_account" q-event="click" q-static="' .H(['id'=>$object->id]). '"
            q-src="'.URI::url('!billing/account').'"',
                    ];
                }

                if ($me->is_allowed_to('导出','billings')){

                    $panel_buttons[] = [
                        'text' =>I18N::HT('billing', '导出'),
                        'tip'=>I18N::HT('billing', '导出Excel'),
                        'extra' => 'class="button button button_save top" q-object="export" q-event="click" q-static="' .H(['type' => 'csv','form_token' => $form_token, 'object_name'=>$object_name]). '"
            q-src="'.URI::url('!billing/department').'"',
                    ];
                    $panel_buttons[] = [
                        'text' =>I18N::HT('billing', '打印'),
                        'tip'=>I18N::HT('billing', '打印'),
                        'extra' => 'class="button button_print  top" q-object="export" q-event="click" q-static="' .H(['type' => 'print','form_token' => $form_token, 'object_name'=>$object_name]). '"
            q-src="'.URI::url('!billing/department').'"',
                    ];
                }

                  $has_remote_billing = (bool) count(Config::get('billing.sources', []));

                  if ($form['balance_start'] && $form['balance_end']) {
                      if ($form['balance_start'] > $form['balance_end']) {
                          $tmptime = $form['balance_start'];
                          $form['balance_start'] = $form['balance_end'];
                          $form['balance_end'] = $tmptime;
                      }
                      $form_balance = I18N::T('billing','%balance_start - %balance_end', ['%balance_start'=>Number::currency($form['balance_start']),'%balance_end'=>Number::currency($form['balance_end'])]);
                  } elseif ($form['balance_start']) {
                      $form_balance = I18N::T('billing','%balance_start 以上',['%balance_start'=>Number::currency($form['balance_start'])]);
                  } elseif ($form['balance_end']) {
                      $form_balance = I18N::T('billing','%balance_end 以下',['%balance_end'=>Number::currency($form['balance_end'])]);
                  }

                  $root = $root->id ? $root : Tag_Model::root('group');
                  $tag = O('tag_group', $form['group']);

                  $column = [
                    //   'id'=>[
                    //       'title'=>I18N::T('billing', '编号'),
                    //       'nowrap'=>TRUE,
                    //   ]
                  ];

                  if ($object_name == 'billing_department') {
                      $column += [
                          'lab_name'=>[
                              'title'=>I18N::T('billing', '实验室'),
                              'filter'=>[
                                  'form'=>V('billing:accounts_table/filters/lab_name', ['lab_name'=>$form['lab_name']]),
                                  'value'=>$form['lab_name'] ? H($form['lab_name']) : NULL
                              ],
                              'nowrap'=>TRUE,
                              'sortable'=>TRUE,
                          ]
                      ];
                  } elseif ($object_name == 'lab') {
                      $column += [
                          'department'=>[
                              'title'=>I18N::T('billing', '财务部门'),
                              'nowrap'=>TRUE,
                              'align'=> 'left',
                              'filter'=>[
                                  'form'=>V('billing:transactions_table/filters/department', ['department_id'=>$form['department_id']]),
                                  'value'=>$form['department_id'] ? O('billing_department', H($form['department_id']))->name : NULL
                               ],      
                               "input_type" => 'select'                    
                            ]
                      ];
                  }
                  if ($has_remote_billing) {
                      $tooltip = I18N::T('billing', '可用余额 = 有效远程充值 + 本地充值 + 转入调账 - 远程扣费 - 本地扣费 - 使用 - 转出调账');
                  } else {
                      $tooltip = I18N::T('billing', '可用余额 = 本地充值 + 转入调账 - 本地扣费 - 使用 - 转出调账');
                  }

                  $column += [
                      'group'=>[
                          'title'=>I18N::T('billing', '组织机构'),
                          'filter'=>[
                              'form'=> V('billing:transactions_table/filters/group', ['department' => $object, 'tag'=>$tag, 'root'=>$root]),
                              'value'=> $tag->id && $tag->id != $root->id ? H($tag->name) : NULL
                          ],
                          'nowrap'=>TRUE,
                          'invisible'=>TRUE
                      ],
                      'income'=> [
                          'title'=> I18N::T('billing', '转入'),
                          'nowrap'=> TRUE,
                          'align'=> 'center',
                      ],
                      'outcome'=> [
                          'title'=> I18N::T('billing', '转出'),
                          'nowrap'=> TRUE,
                          'align'=> 'center',
                      ],
                      'balance'=>[
                          'title'=>I18N::T('billing', '可用余额'),
                          'filter'=>[
                              'form'=>V('billing:accounts_table/filters/balance', [
                                  'balance_start'=>$form['balance_start'],
                                  'balance_end'=>$form['balance_end'],
                              ]),
                              'value'=>$form_balance ? H($form_balance) : NULL,
                              'field'=>'balance_start,balance_end'
                          ],
                          'sortable'=>TRUE,
                          'nowrap'=>TRUE,
                          'align'=>'right',
                          'tooltip'=>$tooltip,
                      ],
                      'credit_line'=>[
                          'title'=>I18N::T('billing', '信用额度'),
                          'sortable'=>TRUE,
                          'align'=>'right',
                          'nowrap'=>TRUE
                      ],
                      'rest'=>[
                          'title'=>I18N::T('billing', '操作'),
                          'nowrap'=>TRUE,
                          'align'=>'left',
                      ]
                  ];

                  if ($type == 'list') {
                      unset($column['group']['filter']);
                      unset($column['balance']['filter']);
                  }

                  $sub_columns_income = [];
                  $sub_columns_outcome = [];

                  if ($has_remote_billing) {
                      $sub_columns_income['income_remote'] = [
                          'title'=>I18N::T('billing', '远程充值'),
                          'align'=> 'right',
                          'nowrap'=>TRUE,
                          'sortable'=>TRUE,
                          ];

                      $sub_columns_income['income_remote_confirmed'] = [
                          'title'=> I18N::T('billing', '有效远程充值'),
                          'align'=> 'right',
                          'nowrap'=> TRUE,
                          'sortable'=>TRUE,
                          ];
                  }

                  $sub_columns_income['income_local'] = [
                      'title'=> I18N::T('billing', '本地充值'),
                      'align'=> 'right',
                      'nowrap'=> TRUE,
                      'sortable'=>TRUE,
                      ];

                  $sub_columns_income['income_transfer'] = [
                      'title'=> I18N::T('billing', '调账'),
                      'align'=> 'right',
                      'nowrap'=> TRUE,
                      'sortable'=>TRUE,
                      ];

                  if ($has_remote_billing) {
                      $sub_columns_outcome['outcome_remote'] = [
                          'title'=> I18N::T('billing', '远程扣费'),
                          'align'=> 'right',
                          'nowrap'=> TRUE,
                          'sortable'=>TRUE,
                          'extra_class'=> 'lmargin_1',
                          ];
                  }

                  $sub_columns_outcome['outcome_local'] = [
                      'title'=> I18N::T('billing', '本地扣费'),
                      'align'=> 'right',
                      'nowrap'=> TRUE,
                      'sortable'=>TRUE,
                      ];

                  if (!$has_remote_billing) {
                      $sub_columns_outcome['outcome_local']['extra_class'] = 'lmargin_1';
                  }

                  $sub_columns_outcome['outcome_use'] = [
                      'title'=> I18N::T('billing', '使用'),
                      'align'=> 'right',
                      'nowrap'=> TRUE,
                      'sortable'=>TRUE,
                      ];

                  $sub_columns_outcome['outcome_transfer'] = [
                      'title'=> I18N::T('billing', '调账'),
                      'align'=> 'right',
                      'nowrap'=> TRUE,
                      'sortable'=>TRUE,
                      ];

                  $sub_column = [
                      'income'=> $sub_columns_income,
                      'outcome'=> $sub_columns_outcome,
                  ];

                if ($object_name == 'billing_department'){
                    $tabs->search_box = V('application:search_box', [
                        'is_offset' => true,
                        'top_input_arr' => ['lab_name'],
                        'columns' => $column,
                        'panel_buttons' => $panel_buttons,
                        'extra_view' => V('department/info', ['form' => $form])
                    ]);
                } else {
                    $tabs->content->tertiary_tabs->search_box = V('application:search_box', ['is_offset' => true, 'top_input_arr' => ['department'], 'columns' => $column, 'panel_buttons' => $panel_buttons]);
                    $tabs->entries_search_box = V('application:search_box', ['panel_buttons' => $panel_buttons]);
                    foreach ($column as $k) {
                        if (isset($column[$k]['filter'])) {
                            $tabs->entries_search_box = $tabs->content->tertiary_tabs->search_box = V('application:search_box', ['is_offset' => true, 'top_input_arr' => ['department'], 'columns' => $column, 'panel_buttons' => $panel_buttons]);
                        }
                    }
                }

                $tabs->column = $column;
                $tabs->sub_column = $sub_column;

                break;
            case 'transaction':
                $form_token = $tabs->content->tertiary_tabs->form_token ? : $tabs->form_token;
                $list_lab_billing_info = $me->is_allowed_to('列表收支明细', $object);
                $user_lab_refill_check = Billing_Account::user_lab_refill_check($me, $object);
                $user_is_lab_deduction = Billing_Account::user_is_lab_deduction($me, $object);

                $enable_refill = TRUE;
                if($GLOBALS['preload']['billing.single_department']){
                    $department = Billing_Department::get();
                    $account = O('billing_account', ['department'=>$department, 'lab'=>$object]);
                    if($account->source != 'local' && $account->voucher) $enable_refill = FALSE;
                }
                else{
                    $local_accounts = Q("billing_account[source!='local'][!voucher][lab={$object}]");
                    //如果远程账户都存在账号,则屏蔽充值按钮
                    if(!$local_accounts->total_count()) $enable_refill = FALSE;
                }

                if ($list_lab_billing_info || $user_lab_refill_check || $user_is_lab_deduction){
                    $panel_buttons = new ArrayIterator;

                    if (!Config::get('billing.disabled_manual_changes')) {
                        if ($user_lab_refill_check && $enable_refill){
                            $panel_buttons[] = [
                                'text' =>I18N::HT('billing', '充值'),
                                'tip'=>I18N::HT('billing', '充值'),
                                'extra' => 'class="button button_add top" q-object="account_credit" 
                                q-event="click" 
                                q-static="' .H(['lab_id'=>$object->id]). '"
                                q-src="'.H(URI::url('!billing/account')).'"',
                            ];
                        }
    
                        if ($user_is_lab_deduction){
                            $panel_buttons[] = [
                                'text' =>I18N::HT('billing', '扣费'),
                                'tip'=>I18N::HT('billing', '扣费'),
                                'extra' => 'class="button icon-cut top" q-object="account_deduction" 
                                q-event="click" 
                                q-static="' .H(['lab_id'=>$object->id]). '"
                                q-src="'.H(URI::url('!billing/account')).'"',
                            ];
                        }
                    }

                    if ($list_lab_billing_info){

                        $panel_buttons[] = [
                            'text' =>I18N::HT('billing', '导出'),
                            'tip'=>I18N::HT('billing', '导出Excel'),
                            'extra' => 'class="button button button_save top" q-object="export" 
                             q-event="click" 
                             q-static="' .H(['type'=>'csv','form_token'=>$form_token,'lab_id'=>$object->id]). '"
                             q-src="'.H(URI::url('!billing/transactions') ).'"',
                        ];
                        $panel_buttons[] = [
                            'text' =>I18N::HT('billing', '打印'),
                            'tip'=>I18N::HT('billing', '打印'),
                            'extra' => 'class="button button_print  top" 
                            q-object="export" 
                            q-event="click" 
                            q-static="' .H(['type'=>'print','lab_id'=>$object->id,'form_token'=>$form_token,'source'=>'lab']). '"
            q-src="'.H(URI::url('!billing/transactions')).'"',
                        ];
                    }


                }

                if($form['dtstart'] || $form['dtend']) {
                    $form['date'] = true;
                }

                $sort_fields = Config::get('billing.lab.transactions.sortable_columns');

                $transaction_type = !$form['transaction_type'] ? NULL : I18N::T('billing', Billing_Transaction::$transaction_type[$form['transaction_type']]);
                $sub_transaction_type = !$form['sub_transaction_type'] ? NULL : ($form['transaction_type'] == Billing_Transaction::FIN_TYPE_IN ? I18N::T('billing', Billing_Transaction::$transaction_type_income[$form['sub_transaction_type']]) : I18N::T('billing', Billing_Transaction::$transaction_type_outcome[$form['sub_transaction_type']]));

                #ifdef (billing.single_department)
                if (!$GLOBALS['preload']['billing.single_department']) {
                    $column += [
                        'department'=>[
                            'title'=>I18N::T('billing', '财务部门'),
                            'filter'=>[
                                'form'=> V('billing:transactions_table/filters/department', [
                                    'department_id'=>$form['department_id'],
                                    'lab'=>$object
                                ]),
                                'value'=>$form['department_id'] ? H(O('billing_department', $form['department_id'])->name) : NULL,
                                'field'=>'department_id'
                            ],
                            'nowrap'=>TRUE,
                            'sortable' => in_array('department', $sort_fields),
                            'weight'=> 10,
                        ]
                    ];
                }
                #endif

                $column += [
                    'id'=>[
                        'title'=>I18N::T('billing', '编号'),
                        'nowrap'=>TRUE,
                        'weight'=> 5,
                    ],
                    'date'=>[
                        'title'=>I18N::T('billing', '日期'),
                        'filter'=> [
                            'form' => V('billing:transactions_table/filters/date', [
                                'dtstart'=>$form['dtstart'],
                                'dtend'=>$form['dtend']
                            ]),
                            'value' => $form['date'] ? H($form['date']) : NULL,
                            'field'=>'dtstart,dtend'
                        ],
                        'nowrap'=>TRUE,
                        'sortable' => in_array('date', $sort_fields),
                        'weight'=> 15,
                    ],
                    'income'=>[
                        'title'=>I18N::T('billing', '转入'),
                        'align'=>'right',
                        'nowrap'=>TRUE,
                        'sortable' => in_array('income', $sort_fields),
                        'weight'=> 20,
                    ],
                    'outcome'=>[
                        'title'=>I18N::T('billing', '转出'),
                        'align'=>'right',
                        'nowrap'=>TRUE,
                        'sortable' => in_array('outcome', $sort_fields),
                        'weight'=> 30,
                    ],
                    'description'=>[
                        'title'=>I18N::T('billing', '备注'),
                        'nowrap'=>TRUE,
                        'weight'=> 40,
                    ],
                    'transaction_id'=>[
                        'title'=>I18N::T('billing', '编号'),
                        'filter'=>[
                            'form'=>V('billing:transactions_table/filters/transaction_id', ['transaction_id'=>$form['transaction_id']]),
                            'value'=>$form['transaction_id'] ? Number::fill(H($form['transaction_id']), 6) : NULL,
                        ],
                        'weight'=> 50,
                        'invisible'=>TRUE,
                    ],
                    'certificate'=>[
                        'title'=>I18N::T('billing', '凭证号'),
                        'filter'=> [
                            'form' => V('billing:transactions_table/filters/certificate', ['certificate'=>$form['certificate']]),
                            'value'=>$form['certificate'] ? H($form['certificate']) : NULL
                        ],
                        'nowrap'=>TRUE,
                        'sortable' => in_array('certificate', $sort_fields),
                        'weight'=> 60,
                    ],
                    'transaction_type'=>[
                        'title'=>I18N::T('billing', '类别'),
                        'filter'=>[
                            'form'=>V('billing:transactions_table/filters/type', ['type'=>$form['transaction_type'], 'form'=> $form]),
                            'value'=> ($transaction_type || $sub_transaction_type) ? ( $transaction_type. H(' » ').$sub_transaction_type) : NULL,
                        ],
                        'invisible'=>TRUE,
                        'weight'=> 120,
                    ],
                    'rest'=>[
                        'title'=>I18N::T('billing', '操作'),
                        'nowrap'=>TRUE,
                        'weight'=> 130,
                        'align'=>'right',
                    ]

                ];

                $extra_column = Event::trigger('extra.transactions.column', $form);

                if ($extra_column) {
                    $column += $extra_column;
                }

                $tabs->column = $column;
                $tabs->entries_search_box = $tabs->content->tertiary_tabs->search_box=V('application:search_box', ['panel_buttons'=>$panel_buttons, 'top_input_arr' => ['transaction_id'], 'columns'=>$column]);
                break;
        }

    }

	static function department_accounts_tab($e, $tabs) {
		/*
		NO.TASK#300(guoping.zhang@2010.12.11)
		查看财务帐号的权限判断
		*/
		if (L('ME')->is_allowed_to('查看', $tabs->department)) {
			Event::bind('department.index.tab.content', 'Billing_Account::department_accounts_content', 0, 'accounts');
			Event::bind('department.index.tab.tool', 'Billing_Account::lab_department_view_tool', 0, 'accounts');
			$tabs->add_tab('accounts', [
					'url'=> $tabs->department->url('accounts'),
					'title'=>I18N::T('billing', '财务帐号'),
                    'weight' => '10',
				]);
		}
	}

	static function department_accounts_content($e, $tabs) {
		$me = L('ME');
		$form_token = Session::temp_token('billing_list_',300);//生成唯一一个SESSION的key
		$department = $tabs->department;

        $selector = $temp = "billing_account[department={$department}]";
        $billing_account_select[] = 'lab[hidden=0]';

        if (!$me->is_allowed_to('列表财务帐号', 'billing_department')) {
            $selector = "{$me}<group tag_group[parent] lab[hidden=0] {$temp}";
        }
		//搜索功能
		$form = Lab::form(function(&$old_form, &$form){
			if (isset($form['balance_filter'])) {
				if (!$form['balance_start']) {
					unset($old_form['balance_start']);
				}
				if (!$form['balance_end_check']) {
					unset($old_form['balance']);
				}
				unset($form['balance_filter']);
			}
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                }
                else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
                unset($form['date_filter']);
            }
		});

		if ($form['lab_name']) {
			$lab_name = Q::quote(trim($form['lab_name']));
			if (!$me->is_allowed_to('列表财务帐号', 'billing_department')) {
				$selector = "{$me}<group tag_group[parent] lab[hidden=0][name*=$lab_name|name_abbr*=$lab_name]<lab {$temp}";
			}
			else {
				$billing_account_select[] = "lab[hidden=0][name*=$lab_name|name_abbr*=$lab_name]<lab ";
			}
		}

        if ($form['group']) {
			$root = Tag_model::root('group');
			$group = O('tag_group', $form['group']);
			if ($group->id && $group->id != $root->id) {
				if (!$me->is_allowed_to('列表财务帐号', 'billing_department')) {
					!$me->group->is_itself_or_ancestor_of($group) and
						$group = $me->group and
						$form['group'] = $me->group->id;
					$selector = "{$group} lab[name*=$lab_name|name_abbr*=$lab_name]<lab {$temp}";
				}
				else {
					$billing_account_select[] = "{$group} lab";
				}
			}
		}
        $extra_billing_account_select = Event::trigger('extra.billing_account_select');
        if ($extra_billing_account_select) {
            $billing_account_select = array_merge($billing_account_select, $extra_billing_account_select);
        }

		if (count($billing_account_select)) {
			$selector = '('.implode(',', $billing_account_select).') '.$selector;
		}
		if($form['balance_start']) {
			$balance_start = $form['balance_start'];
			$selector_tmp = "[balance>=$balance_start]";
		}

		if($form['balance_end']) {
			$balance_end = $form['balance_end'];
			$selector_tmp .= "[balance<=$balance_end]";
		}

		$sebalance = ($balance_end && $balance_start > $balance_end) ? "[balance>=$balance_end][balance<=$balance_start]" : $selector_tmp;
        $selector = (string)Event::trigger('billing.account.extra.selector', $form, $selector) ? : $selector;
        $sebalance = (string)Event::trigger('billing.account.extra.sebalance', $form, $sebalance) === 'false' ?  '' : $sebalance;
		$selector .= $sebalance;
		//排序
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		if ($form['sort']) {
			if ($sort_by == 'lab_name') {
				$selector .= ":sort(lab.name_abbr {$sort_flag})";
			}
			else {
				$selector .= ":sort({$sort_by} {$sort_flag})";
			}
		}
        $accounts = Q($selector);
		//导出、打印相关的session
		$form['form_token'] = $form_token;
		$form['selector'] = $selector;
		$_SESSION[$form_token] = $form;
		LAB::store_form($form);

		//分页效果
		$pagination = Lab::pagination($accounts, (int)$form['st'], 25);

		$tabs->form_token = $form_token;

		$tabs->content = V('billing:accounts',[
			'object'=>$department,
			'accounts'=>$accounts,
			'sort_asc'=>$sort_asc,
			'sort_by'=>$sort_by,
			'pagination'=>$pagination,
			'form'=>$form,
			'form_token' => $form_token,
			'root' => !$me->is_allowed_to('列表财务帐号', 'billing_department') ? $me->group : Tag_Model::root('group')
		]);
	}


	static function before_transaction_save($e, $transaction, $new_data) {

		if ($new_data['income'] && $new_data['outcome']) {
			$new_margin = $transaction->income - $transaction->outcome;
			//transaction income 和 outcome 的转换处理
			if ($new_margin >= 0) {
				$transaction->income = $new_margin;
				$transaction->outcome = 0;
			}
			else {
				$transaction->outcome = -$new_margin;
				$transaction->income = 0;
			}
		}

	}

	static function on_transaction_saved($e, $transaction, $old_data, $new_data) {
		//transaction改变时，自动对account的处理
		if (isset($new_data['income'])
			|| isset($new_data['outcome'])
			|| isset($new_data['ctime'])
			|| isset($new_data['source'])
			|| isset($new_data['status'])
			|| isset($new_data['account'])
		) {
			self::update_balance_q($transaction, $old_data, $new_data);

			// 在不设置transaction更新的情况更新transaction相关的数据
			$data = $transaction->get_data();
			$transaction->set_data($data);
		}
	}

    static function on_transaction_deleted($e, $transaction) {
        $old_data = [
            'account' => O('billing_account', $transaction->account_id),
            'income' => $transaction->income,
            'outcome' => $transaction->outcome,
        ];
        self::update_balance_q($transaction, $old_data);
    }

	static function update_balance($account) {
        if ($account->lab->hidden == 1) {
            return;
        }
        //总收入
        //$account->amount = (double) Q("billing_transaction[account={$account}][income][!transfer]")->sum('income');

        //confirmed状态
        $confirmed_status = Billing_Transaction_Model::STATUS_CONFIRMED;

        $db = Database::factory();

        //远程充值的Source
        $remote_source = Config::get('billing.remote_source', 'billing.'.LAB_ID);
        //本地充值的Source
        $local_source = 'local';

        //有效远程充值
        $account->income_remote_confirmed = (double) $db->value("
        	SELECT SUM(income) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND income > 0
        	 AND status = %d
        	 AND source = '%s'", $account->id, $confirmed_status, $remote_source);

        //远程充值
        $account->income_remote = (double) $db->value("
        	SELECT SUM(income) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND income > 0
        	 AND source = '%s'", $account->id, $remote_source);

        //有效本地充值
        $account->income_local = (double) $db->value("
            SELECT SUM(income) FROM billing_transaction
             WHERE account_id = %d
             AND income > 0
             AND source = '%s'
             AND transfer = 0", $account->id, $local_source) +
            (double) $db->value("
            SELECT SUM(income) FROM billing_transaction
             WHERE account_id = %d
             AND income < 0
             AND source = '%s'
             AND transfer = 0", $account->id, $local_source);

        //转入调账
        $account->income_transfer = (double) $db->value("
        	SELECT SUM(income) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND transfer > 0", $account->id);

        //远程扣费
        $account->outcome_remote = (double) $db->value("
        	SELECT SUM(outcome) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND outcome > 0
        	 AND status = %d
        	 AND source = '%s'
        	 AND transfer = 0", $account->id, $confirmed_status, $remote_source);

        //本地扣费
        $account->outcome_local = (double) $db->value("
        	SELECT SUM(outcome) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND outcome > 0
        	 AND source = '%s'
        	 AND transfer = 0
        	 AND manual > 0", $account->id, $local_source);

        //使用
        $account->outcome_use = (double) $db->value("
        	SELECT SUM(outcome) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND outcome > 0
        	 AND source = '%s'
        	 AND transfer = 0
        	 AND manual = 0", $account->id, $local_source);

        //转出调账
        $account->outcome_transfer = (double) $db->value("
        	SELECT SUM(outcome) FROM billing_transaction
        	 WHERE account_id = %d
        	 AND outcome > 0
        	 AND transfer > 0 ", $account->id);

        /*
        *
        *	2016.1.30 Unpc
        *	All 'bogus' about the float in PHP
        *	PHP中这种直接运算因为float/double的精度会出现问题，需要强制转为相同位数进行更新才不会出现
        *	-3.6379788070917E-12类似数据，但是介于这里是财务，需要保留最原始的数据，故进行一下操作：
        *	& 采用公式运算，相减之间仅采用单个运算，不让其失精度 & Excel中同理使用
        **/
        $account->balance = (double) (($account->income_remote_confirmed +
                $account->income_local + $account->income_transfer) -
                ($account->outcome_remote + $account->outcome_local +
                $account->outcome_use + $account->outcome_transfer));

        $ret = $account->save();
        $action = $ret ? '成功' : '失败';
        $me = L('ME');
        Log::add(strtr('[billing] %user_name[%user_id]操作导致财务账号[%account_id]重新进行余额计算, 操作 %action, 当前余额为: %balance', [
                    '%user_name' => $me->name,
                    '%user_id' => (int)$me->id,
                    '%account_id' => $account->id,
                    '%action' => $action,
                    '%balance' => $account->balance
        ]), 'transaction');
	}

    static function update_balance_q ($transaction, $old_data = [], $new_data = []) {
        $account = $transaction->account;
        $old_account = $old_data['account'];
        if (!$account->id || $account->lab->hidden == 1) {
            return;
        }

        //confirmed状态
        $confirmed_status = Billing_Transaction_Model::STATUS_CONFIRMED;
        //远程充值的Source
        $remote_source = Config::get('billing.remote_source', 'billing.'.LAB_ID);
        //本地充值的Source
        $local_source = 'local';

        $caculate_queue = Lab::get('account.caculate_queue') ? : [];

        $old_account = $old_data['account'];
        $old_income = $old_data['income'];
        $old_outcome = $old_data['outcome'];
        $new_income = $new_data['income'];
        $new_outcome = $new_data['outcome'];

        $tran_keys_i = [];
        $tran_keys_o = [];
        //有效远程充值
        if ($transaction->income > 0
            && $transaction->status == $confirmed_status
            && $transaction->source == $remote_source
        ) {
            $tran_keys_i[] = 'income_remote_confirmed';
        }
        //远程充值
        if ($transaction->income > 0
            && $transaction->source == $remote_source
        ) {
            $tran_keys_i[] = 'income_remote';
        }
        //有效本地充值
        if ($transaction->income != 0
            && ($transaction->source == $local_source || $transaction->source == '')
            && $transaction->transfer == 0
        ) {
            $tran_keys_i[] = 'income_local';
        }
        //转入调账
        if ($transaction->income != 0
            && $transaction->transfer > 0
        ) {
            $tran_keys_i[] = 'income_transfer';
        }
        //远程扣费
        if ($transaction->outcome > 0
            && $transaction->status == $confirmed_status
            && $transaction->source == $remote_source
            && $transaction->transfer == 0
        ) {
            $tran_keys_o[] = 'outcome_remote';
        }
        //本地扣费
        if ($transaction->outcome > 0
            && ($transaction->source == $local_source || $transaction->source == '')
            && $transaction->transfer == 0
            && $transaction->manual > 0
        ) {
            $tran_keys_o[] = 'outcome_local';
        }
        //使用
        if ($transaction->outcome > 0
            && ($transaction->source == $local_source || $transaction->source == '')
            && $transaction->transfer == 0
            && $transaction->manual == 0
        ) {
            $tran_keys_o[] = 'outcome_use';
        }
        //转出调账
        if ($transaction->outcome != 0
            && $transaction->transfer > 0
        ) {
            $tran_keys_o[] = 'outcome_transfer';
        }

        $account->balance = $account->balance - $new_outcome + $new_income;
        foreach ($tran_keys_i as $tran_key) {
            $account->$tran_key += $new_income;
        }
        foreach ($tran_keys_o as $tran_key) {
            $account->$tran_key += $new_outcome;
        }
        array_push($caculate_queue, $account->id);

        if (!$old_account->id
            || ($old_account->id && $old_account->id == $account->id)) {
            $account->balance = $account->balance + $old_data['outcome'] - $old_data['income'];
            foreach ($tran_keys_i as $tran_key) {
                $account->$tran_key -= $old_income;
            }
            foreach ($tran_keys_o as $tran_key) {
                $account->$tran_key -= $old_outcome;
            }
        }
        elseif ($old_account->id) {
            $old_account->balance = $old_account->balance + $old_data['outcome'] - $old_data['income'];
            foreach ($tran_keys_i as $tran_key) {
                $old_account->$tran_key -= $old_income;
            }
            foreach ($tran_keys_o as $tran_key) {
                $old_account->$tran_key -= $old_outcome;
            }
            array_push($caculate_queue, $old_account->id);
        }
        Lab::set('account.caculate_queue', $caculate_queue);

        $ret = $account->save();
        if ($old_account->id) {
        	$old_account->save();
        }

        $action = $ret ? '成功' : '失败';
        $me = L('ME');
        Log::add(strtr('[billing] %user_name[%user_id]操作导致财务账号[%account_id]重新进行余额粗算, 操作 %action, 当前余额为: %balance', [
                    '%user_name' => $me->name,
                    '%user_id' => (int)$me->id,
                    '%account_id' => $account->id,
                    '%action' => $action,
                    '%balance' => $account->balance
        ]), 'transaction');
    }

	static function before_lab_delete($e, $lab) {
		if (Q("billing_account[lab={$lab}]")->total_count()) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '"%lab"在系统内已经建立了财务帐号，请删除其财务帐号后重试。', ['%lab'=>H($lab->name)]));
			$e->return_value = false;
			return false;
		}
	}

	//传入对象$object为lab
	static function lab_ACL($e, $me, $perm_name, $lab, $options) {
		switch ($perm_name) {
		case '列表财务帐号':
			if ($me->id == $lab->owner->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$me $lab")->total_count()
				&& $me->access('列表本实验室的财务帐号')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$me<pi $lab")->total_count()
				&& $me->access('列表负责实验室的财务帐号')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			// $lab是$me的下属机构
			if (($me->access('列表下属实验室的财务帐号'))
				&& $me->group->id && $lab->group->id
				&& $me->group->is_itself_or_ancestor_of($lab->group)) {
				//有“列表下属机构的财务帐号”的权限
					$e->return_value = TRUE;
					return FALSE;
			}
			// 是财务部门负责人，且实验室在负责部门下有帐号
			$users = Q("billing_account[lab=$lab]<department billing_department user")->to_assoc('id', 'id');
			if (in_array($me->id, $users)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
        default:
			return;
		}

		if ($me->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	/*
	充值/添加/修改/删除/修改充值人员：传入对象$object为billing_account
		"添加"是在save前调用
	*/
	/* 最后修改:(xiaopei.li@2011.03.21) */
	static function billing_account_ACL($e, $me, $perm_name, $account, $options) {
		$department = $account->department;

		switch ($perm_name) {
		case '充值':
		case '扣费':
		case '添加':
		case '修改':
		case '删除':
			if (Billing_Department::user_is_dept_incharge($me, $department)) {
				$e->return_value = TRUE;
				return FALSE;
			}
            if (($me->access('修改下属实验室的财务帐号') && $me->group->is_itself_or_ancestor_of($account->lab->group))
                || $GLOBALS['preload']['billing.single_department']) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;
		case '查看':
			if ( Q("$me lab")->total_count() /* 我是实验室成员 */
				  || ($me->access('列表下属实验室的财务帐号') && $me->group->is_itself_or_ancestor_of($account->lab->group))
	              || $GLOBALS['preload']['billing.single_department'] && Billing_Department::user_is_dept_incharge($me)){
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$me billing_department<department $account")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改充值人员':
		case '修改扣费人员':
			break;
		default:
			return;
		}

		if ($me->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	//用户可否对实验室进行充值
	static function user_lab_refill_check($user, $lab) {
		if (!$user || !$lab) return;
		if ($GLOBALS['preload']['billing.single_department']) {
			$department = Billing_Department::get();
			$account = O('billing_account', ['lab' => $lab, 'department' => $department]);
			if ($account->id && $user->is_allowed_to('充值', $account)) {
				return TRUE;
			}
		}
		else {
			$accounts = Q("billing_account[lab={$lab}]");
			foreach($accounts as $account) {
				if ($user->is_allowed_to('充值', $account)) {
					return TRUE;
				}
			}
		}
	}

	static function user_is_lab_deduction($user, $lab) {
		if (!$user || !$lab) return;
		if ($GLOBALS['preload']['billing.single_department']) {
			$department = Billing_Department::get();
			$account = O('billing_account', ['lab' => $lab, 'department' => $department]);
			if ($account->id && $user->is_allowed_to('扣费', $account)) {
				return TRUE;
			}
		}
		else {
			$accounts = Q("billing_account[lab={$lab}]");
			foreach($accounts as $account) {
				if ($user->is_allowed_to('扣费', $account)) {
					return TRUE;
				}
			}
		}
	}

    static function billing_total_fund($dept, $type, $source = NULL, $status = NULL) {
        $selector = "billing_account[department={$dept}]<account billing_transaction";

        /*if ($type) {
            $selector .= "[$type>0]";
        }*/

        if (!is_null($source)) {
            $selector .= "[source=$source]";
        }
        else {
            $selector .= "[source!=local]";
        }

        if (!is_null($status)) {
            $selector .= "[status=$status]";
        }

        /*if ($type == 'outcome' && Config::get('billing.correct_time')) {*/
            $time = Config::get('billing.correct_time') ? : 0;
            $selector .= "[ctime>$time]";
        //}

        return round(Q($selector)->sum($type), 2);
    }

    static function billing_total_use($dept) {
        $selector = "billing_account[department={$dept}]<account billing_transaction";
        return round(Q($selector. "[outcome>0][ctime>$time][manual=0]")->sum('outcome'), 2);
    }
    static function extra_form_validate ($e, $equipment, $type, $form) {
        $me = L('ME');
        $lab = self::_get_lab($form, $type);
        if($me->is_allowed_to('管理使用', $equipment)) {
            return TRUE;
        }

        if ($equipment->name() == 'equipment') {
            $department = $equipment->billing_dept;
            $account = O('billing_account',['lab'=>$lab,'department'=>$department]);
            if (!$account->id) return TRUE;

            if ($account->balance + $account->credit_line < 0  && !Config::get('billing.ignore_lab_balance_limit')) {
                $form->set_error('', I18N::T('billing', '实验室余额不足, 目前无法使用该设备。'));
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '实验室余额不足, 目前无法使用该设备。'));
                $e->return_value = TRUE;
            }
        }
        return TRUE;
    }

    private static function _get_lab($form, $type) {
		$me = L('ME');
        switch ($type) {
			case 'use':
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $lab = O('eq_charge',['source' => O('eq_record', $form['record_id'])])->lab;
                }
                else {
					$user = $form['user_id'] ? O('user', $form['user_id']) : $me;
                    $lab = Q("$user lab")->current();
                }
                break;
            case 'eq_sample':
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $lab = O('lab_project', $form['project'])->lab;
                }
                else {
					$user = $form['sender'] ? O('user', $form['sender']) : $me;
                    $lab = Q("$user lab")->current();
                }
                break;
            case 'eq_reserv':
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $lab = O('lab_project', $form['project'])->lab;
                }
                else {
					$user = $form['user_id'] ? O('user', $form['user_id']) : $me;
                    $lab = Q("$user lab")->current();
                }
                break;
        }
        return $lab;
    }
}


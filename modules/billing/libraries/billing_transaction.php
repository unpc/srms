<?php

class Billing_Transaction {

	const FIN_TYPE_ANY  = 0;
	const FIN_TYPE_IN   = 1;
	const FIN_TYPE_OUT = 2;

	static $transaction_type = [
		self::FIN_TYPE_ANY  => '--',
		self::FIN_TYPE_IN   => '转入',
		self::FIN_TYPE_OUT => '转出',
	];

    //转入
    const FIN_TYPE_IN_ANY = 0; //任意 --
    const FIN_TYPE_IN_REMOTE = 1; //远程充值
    const FIN_TYPE_IN_LOCAL = 2; //本地充值
    const FIN_TYPE_IN_TRANSFER = 3; //本地转账

    //转入
    static $transaction_type_income = [
        self::FIN_TYPE_IN_ANY => '--',
        self::FIN_TYPE_IN_REMOTE => '远程',
        self::FIN_TYPE_IN_LOCAL => '本地',
        self::FIN_TYPE_IN_TRANSFER => '调账',
    ];

    //转出
    const FIN_TYPE_OUT_ANY = 0; //任意
    const FIN_TYPE_OUT_REMOTE = 1; //远程扣费
    const FIN_TYPE_OUT_LOCAL = 2; //本地扣费
    const FIN_TYPE_OUT_USE = 3; //本地使用
    const FIN_TYPE_OUT_TRANSFER = 4; //本地转账

    //转出
    static $transaction_type_outcome = [
        self::FIN_TYPE_OUT_ANY => '--',
        self::FIN_TYPE_OUT_REMOTE => '远程',
        self::FIN_TYPE_OUT_LOCAL => '本地',
        self::FIN_TYPE_OUT_USE => '使用',
        self::FIN_TYPE_OUT_TRANSFER => '调账',
    ];

	static function setup($e) {
		Event::bind('department.index.tab', 'Billing_Transaction::department_transaction_tab', 0, 'trancactions');
	}

	static function department_transaction_tab($e, $tabs) {
		/*
		NO.TASK#300(guoping.zhang@2010.12.11)
		列表财务部门的收支明细的权限判断
		*/
		if (L('ME')->is_allowed_to('列表收支明细', $tabs->department)) {
			Event::bind('department.index.tab.content', 'Billing_Transaction::department_transaction_tab_content', 0, 'transactions');
			Event::bind('department.index.tab.tool', 'Billing_Transaction::department_transaction_tab_tool', 0, 'transactions');
			Event::bind('department.index.tab.content', 'Billing_Transaction::department_transaction_tab_content_check', 0, 'check');

			$tabs->add_tab('transactions', [
					'url'=>$tabs->department->url('transactions'),
					'title'=>I18N::T('billing', '明细'),
					'weight' => '20',
				])
				->add_tab('check', [
					'url'=>$tabs->department->url('check'),
					'title'=>I18N::T('billing', '对帐'),
					'weight' => '30',
				]);
		}
	}

	static function department_transaction_tab_content_check($e, $tabs) {

		$department = $tabs->department;
		$session_token = 'billing_check_' . $department->id;

		$form = Input::form();

		if ($form['submit']) {

			$file = Input::file('file');

			if ($file['tmp_name']) {
				if (File::extension($file['name']) != 'csv') {
					Lab::message(LAB::MESSAGE_ERROR, I18N::T('billing', '文件类型错误, 请上传csv文件'));
				}
				else {
					$file_name = tempnam(Config::get('system.tmp_dir'), 'billing_check_');

					File::check_path($file_name);

					if (move_uploaded_file($file['tmp_name'], $file_name)) {
						Lab::message(LAB::MESSAGE_NORMAL, I18N::T('billing','文件上传成功'));

						if ($form['dtstart_check']) {
							$dtstart = $form['dtstart'];
						}

						if ($form['dtend_check']) {
							$dtend = $form['dtend'];
						}

						$billing_check = [
							'dtstart' => $dtstart,
							'dtend' => $dtend,
							'file_name' => $file_name,
							'oringin_file_name' => $file['name'],
							];

						$_SESSION[$session_token] = $billing_check;
					}
					else {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '文件上传失败'));
					}
				}
			}
			else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '请选择您要上传的文件。'));
			}
		}
		else {
			if (isset($_SESSION[$session_token])) {
				$billing_check = $_SESSION[$session_token];
			}
		}

		if (isset($billing_check) && $billing_check['file_name'] && file_exists($billing_check['file_name'])) {
			$can_check = TRUE;
		}

		$tabs->content = V('billing:check/index', [
							   'form' => $form,
							   'billing_check' => $billing_check,
							   'can_check' => $can_check,
							   ]);
	}

	static function department_transaction_tab_content($e, $tabs) {

		
		$department = $tabs->department;

		/*
		NO.TASK#300(guoping.zhang@2010.12.11)
		列表财务部门的收支明细的权限判断
		*/
		$me = L('ME');
		if (!$me->is_allowed_to('列表收支明细', $department)) {
			URI::redirect('error/401');
		}

		//$account_selector_suffix = "billing_account[department={$department}]<account";

		//当前用户只可看到下属机构的lab的收支明细
		/* if (!$me->is_allowed_to('列表收支明细', 'billing_department')) { */
		/* 	$selector = "{$me}<group tag[parent] lab {$temp}"; */
		/* } */

		/*
		NO.BUG#328(guoping.zhang@2011.01.17)
		明细列表，添加日期搜索项
		*/

		if ($form_token && isset($_SESSION[$form_token])) {
			$form = $_SESSION[$form_token];
		}
		else {
			$form_token = Session::temp_token('billing_transaction_',300);

			$form = Lab::form(function(&$old_form, &$form){
				if (isset($form['date_filter'])) {
					if (!$form['dtstart']) {
						unset($old_form['dtstart']);
					}
					if (!$form['dtend']) {
						unset($old_form['dtend']);
					}
					else {
                        $form['dtend'] = Date::get_day_end($form['dtend']);
					}
					unset($form['date_filter']);
				}
			});

            if (!$form['transaction_type']) unset($form['sub_transaction_type']);

			$form['form_token'] = $form_token;
			$_SESSION[$form_token] = $form;


			//为了之后便于实验室和组织机构双重查询

            $selector_prefix = [];

            //按实验室搜索

            //group 和 lab 的检索
            //
            if ($form['lab_id']) {
                $lab = O('lab', Q::quote($form['lab_id']));
            }

            if ($form['group']) {
                $root = Tag_model::root('group');
                $group = O('tag_group', $form['group']);
            }

            if ($lab->id && $group->id) {
                //如果 lab 和 group 都进行检索

                if (!$me->is_allowed_to('列表收支明细', $department)) {
                    //用户无权限
                    $account_prefix = "{$group} tag[parent] {$lab}<lab ";
                }
                else {
                    if ($group->id && $group->id != $root->id) {
                        //用户有权限
                        $account_prefix = "{$group}<group {$lab}<lab ";
                    }
                    else {
                        $account_prefix = "{$lab}<lab ";
                    }
                }
            }
            elseif ($lab->id) {

                if (!$me->is_allowed_to('列表收支明细', $department)) {
                    $account_prefix = "{$me}<group tag[parent] {$lab}<lab ";
                }
                else {
                    $account_prefix = "{$lab}<lab ";
                }

            }
            elseif ($group->id) {
                if ($group->id && $group->id != $root->id) {
                    $account_prefix = "{$group} lab ";
                }
            }
            else {
                //清空请求
                $form['lab_id'] = NULL;
                $form['group'] = NULL;
            }

            if ($account_prefix) {
                $selector_prefix[] = $account_prefix;
            }
            $selector_prefix[] = "{$department}<department";

            if (Event::trigger('billing.show_supervised_labs_department_transactions', $department)){
                $selector_prefix[] = "{$me}<group tag_group[parent] lab[hidden=0]";
            }else{
                $selector_prefix[] = "lab[hidden=0]";
            }

            $selector = " billing_account<account billing_transaction[income!=0|outcome!=0]";

            //转账日期
            if($form['t_date_s_check'] == 'on') {
                $t_date_s = strtotime(date('Y-m-d',Q::quote($form['t_date_s'])).' 00:00:00');
                $selector .= "[transaction_date>=$t_date_s]";
            }

            if($form['t_date_e_check'] == 'on') {
                $t_date_e = strtotime(date('Y-m-d',Q::quote($form['t_date_e'])).' 23:59:59');
                $selector .= "[transaction_date<=$t_date_e]";
            }

            //按时间搜索
            if($form['dtstart']) {
                $dtstart = Date::get_day_start(Q::quote($form['dtstart']));
                $selector_tmp = "[ctime>=$dtstart]";
            }

			if($form['dtend']) {
				$dtend = Date::get_day_end(Q::quote($form['dtend']));
				$selector_tmp .= "[ctime>0][ctime<=$dtend]";
			}

			$sdtime = ($dtend && $dtstart > $dtend) ? "[ctime>=$dtend][ctime<=$dtstart]" : $selector_tmp;
			$selector .= $sdtime;

//			if(!$form['dtstart'] && !$form['dtend']) {
//				$dtend_date = getdate(time());
//				$form['dtend']   = mktime(23, 59, 59, $dtend_date['mon']  , $dtend_date['mday'], $dtend_date['year']);
//				$form['dtstart'] = mktime(0,  0,  1,  $dtend_date['mon']-1, $dtend_date['mday'], $dtend_date['year']);
//			}

			$new_selector = Event::trigger('billing.department.transactions.selector.add',$selector, $form);
			if($new_selector) {
				$selector = $new_selector;
			}

			if ($form['recharger_id']) {
				if (!O('user', $form['recharger_id'])->id) {
					$form['recharger_id'] = NULL;
				}
				else {
					$recharger_id = Q::quote($form['recharger_id']);
                    $recharger = O('user', $recharger_id);

					$selector .= "[user={$recharger}]";
				}
			}

			//按凭证号搜索
			if($form['certificate']) {
				$certificate = Q::quote($form['certificate']);
				$selector .= "[certificate*=$certificate]";
			}

			if(isset($form['code']) && $form['code']) {
				$code = Q::quote($form['code']);
				$selector .= "[code*=$code]";
			}

			if(isset($form['project_type']) && $form['project_type'] != -1) {
				$project_type = Q::quote($form['project_type']);
				$selector .= "[project_type=$project_type]";
			}

			//按明细编号搜索
			if( $form['transaction_id'] ) {
				$transaction_id = Q::quote( $form['transaction_id'] );
				$selector .= "[id=$transaction_id]";
			}

			//按类型搜索
            switch($form['transaction_type']) {
                case self::FIN_TYPE_IN : //转入
                    switch($form['sub_transaction_type']) {
                        case self::FIN_TYPE_IN_REMOTE : //远程
                            $selector .= '[source!=local][!transfer]';
                            break;
                        case self::FIN_TYPE_IN_LOCAL : //本地
                            $selector .= '[source=local][!transfer]';
                            break;
                        case self::FIN_TYPE_IN_TRANSFER : //调账
                            $selector .= '[transfer]';
                            break;
                        case self::FIN_TYPE_IN_ANY : //所有
                            break;
                        default :
                    }

                    $selector .= '[income]';
                    break;
                case self::FIN_TYPE_OUT : //转出
                    switch($form['sub_transaction_type']) {
                        case self::FIN_TYPE_OUT_REMOTE : //远程
                            $selector .= '[source!=local][!transfer][!manual]';
                            break;
                        case self::FIN_TYPE_OUT_LOCAL : //本地
                            $selector .= '[source=local][!transfer][manual]'; //扣除转账, 手动操作
                            break;
                        case self::FIN_TYPE_OUT_USE : //使用 //非手动操作
                            $selector .= '[!manual][source=local]';
                            break;
                        case self::FIN_TYPE_OUT_TRANSFER : //调账
                            $selector .= '[transfer]'; //调账
                            break;
                        case self::FIN_TYPE_OUT_ANY : //所有
                            break;
                        default :

                    }

                    $selector .= '[outcome]';
                default :
            }
        }

        //$selector = Event::trigger('billing.transactions.extra_selector', $form, $selector) ? : $selector;
        $selector_prefix[] = Event::trigger('billing.department_transactions_selector', $form);

        //过滤多余数据
        $selector_prefix = array_filter($selector_prefix);

        if (count($selector_prefix)) {
            $prefix = '('. implode(',', $selector_prefix). ')';
        }

        $selector = $prefix. $selector;

		$sort_by = $form['sort'] ? : 'ctime';
        $sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		
		switch ($sort_by) {
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
            default:
				$selector .= ':sort(ctime D, id D)';
                break;
		}
		
		$_SESSION[$form_token]['Q_query'] = $selector;

		$transactions = Q($selector);

		$type = strtolower(Input::form('type'));

		if ($type) {
			switch($type) {
				case 'csv': self::_export_csv($form, $transactions); break;
				default: break;
			}
		}
		else {
			/*
			 * TASK #1398::LIMS-CF中的一些问题修正:
			 * 财务中心上的帐务数据显示异常，不能按照搜索条件正常显示。
			 * 结果：显示当前搜索结果下的账户数（非记录数）、总收入、总支出、总余额。
			 * (kai.wu@2011.08.31)
			 */
			$total_income = $transactions->sum('income');
			$total_outcome = $transactions->sum('outcome');
			$total_balance = $total_income - $total_outcome;

			$pagination = Lab::pagination($transactions, (int)$form['st'], 30);

			$tabs->form_token = $form_token;
			$tabs->form = $form;

			$tabs->content = V('billing:department/transactions', [
				'department'=>$department,
				'transactions'=>$transactions,
				'pagination'=>$pagination,
				'sort_by' => $sort_by,
            	'sort_asc' => $sort_asc,
				'form'=>$form,
				'form_token'=>$form_token,
				'total_income'=>$total_income,
				'total_outcome'=>$total_outcome,
				'total_balance'=>$total_balance,
			]);
		}
	}

	static function department_transaction_tab_tool($e,$tabs){

        $department = $tabs->department;
        $form_token = $tabs->form_token;

        $form = $tabs->form;

        $panel_buttons = new ArrayIterator;

        $panel_buttons[] = [
            'text' =>I18N::HT('billing', '导出'),
            'tip'=>I18N::HT('billing', '导出Excel'),
            'extra' => 'class="button button button_save top" q-object="export" q-event="click" q-static="' .H(['type'=>'csv','form_token'=>$form_token]). '"
            q-src="'.URI::url('!billing/transactions').'"',
        ];
        $panel_buttons[] = [
            'text' =>I18N::HT('billing', '打印'),
            'tip'=>I18N::HT('billing', '打印'),
            'extra' => 'class="button button_print  top" q-object="export" q-event="click" q-static="' .H(['type'=>'print','form_token'=>$form_token, 'dept'=>$department->id]). '"
            q-src="'.URI::url('!billing/transactions').'"',
        ];

        $sort_fields = Config::get('billing.transactions.sortable_columns');

        if($form['dtstart'] && $form['dtend']) {
            if($form['dtstart'] > $form['dtend']) {
                $tmptime = $form['dtstart'];
                $form['dtstart'] = $form['dtend'];
                $form['dtend'] = $tmptime;
            }
            $form['date'] = H(date('Y/m/d',$form['dtstart'])).'-'.H(date('Y/m/d',$form['dtend']));
        }
        elseif($form['dtstart']) {
            $form['date'] = H(date('Y/m/d',$form['dtstart'])).'-'.I18N::T('billing','最末');
        }
        elseif($form['dtend']) {
            $form['date'] = I18N::T('billing','最初').'-'.H(date('Y/m/d',$form['dtend']));
        }

        $transaction_type = !$form['transaction_type'] ? NULL : I18N::T('billing', Billing_Transaction::$transaction_type[$form['transaction_type']]);

        $sub_transaction_type = (!$form['sub_transaction_type'])
            ?
            NULL :
            (
            $form['transaction_type'] == Billing_Transaction::FIN_TYPE_IN
                ?
                I18N::T('billing', Billing_Transaction::$transaction_type_income[$form['sub_transaction_type']])
                :
                I18N::T('billing', Billing_Transaction::$transaction_type_outcome[$form['sub_transaction_type']])
            );

        $transaction_type_value = NULL;

        if ($transaction_type) {
            $transaction_type_value =  $transaction_type;
            if ($sub_transaction_type) {
                $transaction_type_value .= H(' » ').$sub_transaction_type;
            }
        }

        $root = Tag_Model::root('group');
		$tag = O('tag_group', $form['group']);

        $column = [
            'transaction_id'=>[
                'title'=>I18N::T('billing', '编号'),
                'filter'=>[
                    'form'=>V('billing:transactions_table/filters/transaction_id', ['transaction_id'=>$form['transaction_id']]),
                    'value'=>$form['transaction_id'] ? H($form['transaction_id']) : NULL,
                ],
//                'invisible'=>TRUE,
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
                'weight'=> 10,
            ],
            'lab_id'=>[
                'title'=>I18N::T('billing', '实验室'),
                'filter'=>[
					'form'=>V('billing:transactions_table/filters/lab_name', ['department' => $department, 'lab_id'=>$form['lab_id']]),
                     'value'=>$form['lab_id'] ? H(O('lab', $form['lab_id'])->name) : NULL
                ],
                'nowrap'=>TRUE,
                'sortable' => in_array('lab_id', $sort_fields),
                'weight'=> 20,
            ],
            'group'=>[
                'title'=>I18N::T('billing', '组织机构'),
                'filter'=>[
                    'form'=> V('billing:transactions_table/filters/group', ['department' => $department, 'tag'=>$tag, 'root'=>$root]),
                    'value'=> $tag->id && $tag->id != $root->id ? H($tag->name) : NULL
                ],
                'nowrap'=>TRUE,
                'invisible'=>TRUE,
                'weight'=> 30,
            ],
            'recharger_id'=>[
                'title'=>I18N::T('billing', '充值人'),
                'filter'=>[
                    'form'=>V('billing:transactions_table/filters/recharger', ['department'=>$department, 'recharger_id'=>$form['recharger_id']]),
                    'value'=>$form['recharger_id'] ? H(O('user', $form['recharger_id'])->name) : NULL
                ],
                'nowrap'=>TRUE,
                'invisible'=>TRUE,
                'weight'=> 40,
            ],
            'income'=>[
                'title'=>I18N::T('billing', '转入'),
                'align'=>'right',
                'nowrap'=>TRUE,
                'sortable' => in_array('income', $sort_fields),
                'weight'=> 50,
            ],
            'outcome'=>[
                'title'=>I18N::T('billing', '转出'),
                'align'=>'right',
                'nowrap'=>TRUE,
                'sortable' => in_array('outcome', $sort_fields),
                'weight'=> 60,
            ],
            'description'=>[
                'title'=>I18N::T('billing', '备注'),
                'weight'=> 80,
            ],
            'certificate'=>[
                'title'=>I18N::T('billing', '凭证号'),
                'filter'=> [
                    'form' => V('billing:transactions_table/filters/certificate', ['certificate'=>$form['certificate']]),
                    'value'=>$form['certificate'] ? H($form['certificate']) : NULL
                ],
                'nowrap'=>TRUE,
                'weight'=> 100,
            ],
            'transaction_type'=>[
                'title'=>I18N::T('billing', '类别'),
                'filter'=>[
                    'form'=>V('billing:transactions_table/filters/type', ['type'=>$form['transaction_type'], 'form'=> $form]),
                    'value'=> $transaction_type_value,
                ],
                'invisible'=>TRUE,
                'weight'=> 120,
            ],
            'rest'=>[
                'title'=>I18N::T('billing', '操作'),
                'nowrap'=>TRUE,
                'align'=>'right',
                'weight'=> 130,
            ],
        ];
		$new_column = Event::trigger('extra.transactions.column.change', $column, $form);
		if ($new_column) {
			$column = $new_column;
		}
        $extra_column = Event::trigger('extra.transactions.column', $form);

        if ($extra_column) {
            $column += $extra_column;
        }

        $tabs->column = $column;
        $tabs->search_box=V('application:search_box', ['panel_buttons'=>$panel_buttons, 'top_input_arr' => ['transaction_id'], 'columns'=>$column]);
    }

    //lab 查看财务情况
    static function lab_ACL($e, $me, $perm, $lab, $options) {
	    if ($me->access('查看财务中心') && ($perm == '查看财务情况' || $perm == '查看财务概要')) {
            $e->return_value = TRUE;
            return FALSE;
        }
        switch($perm) {
            case '列表收支明细' :
            case '查看财务情况' :
            case '查看财务概要' :
				if (!$lab->id) return;
				//实验室负责人
				if (Q("$me<pi $lab")->total_count() && $me->access('列表本实验室的收支明细')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				//实验室财务管理人员
				if (Q("$me $lab")->total_count()
					&& $me->access('列表本实验室的收支明细')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				//组织机构负责人
				if ($me->access('列表下属实验室的收支明细')
					&& $me->group->id
					&& $lab->group->id
					&& $me->group->is_itself_or_ancestor_of($lab->group)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				// if lab has account in departments that I'm in charge
				$users = Q("billing_account[lab={$lab}]<department billing_department user")->to_assoc('id', 'id');
				if (in_array($me->id, $users)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
            default :
                return;
        }
		if ($me->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}
    }

    //billing_account相关billing_transaction ACL
    static function billing_account_ACL($e, $me, $perm, $account, $options) {
        switch($perm) {
            case '列表收支明细' :
				if (!$account->id) return;
				//财务部门负责人可以查看本财务部门财务帐号的收支情况
				$department = $account->department;
				if (Billing_Department::user_is_dept_incharge($me, $department)) {
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

    //billing_department相关billing_transaction ACL
    static function billing_department_ACL($e, $me, $perm, $department, $options) {
        switch($perm) {
            case '列表收支明细' :
				if ($department->id) {
					if (Billing_Department::user_is_dept_incharge($me, $department)) {
						/* !billing/department/index.[id].transactions */
						$e->return_value = TRUE;
						return FALSE;
					}
				}
                if ($me->access('列表下属实验室的收支明细')
                    && Q("{$me}<group tag[parent] lab[hidden=0] billing_account[department={$department}]")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
				break;
			default :
				return;
        }

		if ($me->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}
    }

	static function _export_csv($form, $transactions) {
		$csv = new CSV('php://output', 'w');

		$csv->write([
			I18N::T('billing', '编号'),
			I18N::T('billing', '日期'),
			I18N::T('billing', '实验室'),
			I18N::T('billing', '收入'),
			I18N::T('billing', '支出'),
			I18N::T('billing', '备注'),
			I18N::T('billing', '凭证号')
		]);

		if ($transactions->total_count() > 0) {

			foreach ($transactions as $transaction) {
				$description = preg_replace('/\<[\/]?a[^\>]*\>/', '', $transaction->description());
				$description = preg_replace('/\<br\>-----\<br\>/', '', $description);
				$csv->write([
					Number::fill($transaction->id, 6),               												//编号
					Date::format($transaction->ctime),									//日期
					$transaction->account->lab->name,												//课题组
					$transaction->income ? $transaction->income : '',  			//收入
					$transaction->outcome ? $transaction->outcome : '',			//支出
					$description,                                                                   //备注
					$transaction->certificate
				]);
			}

		}

		$csv->close();

	}

	static function before_user_save_message($e, $user) {

		if (Q("billing_transaction[user={$user}]")->total_count()) {
			$e->return_value = I18N::T('billing', '该用户关联了相应的收费记录!');
			return FALSE;
		}
	}

	static function transaction_ACL($e, $user, $perm, $transaction, $params) {


		switch ($perm) {
			case '修改':

                //非手动创建transaction, 不予修改
                if (!$transaction->manual) {
                    $e->return_value = FALSE;
                    return FALSE;
                }
				if ($transaction->is_locked()) {
					$e->return_value = FALSE;
					return FALSE;
				}
				if ($user->is_allowed_to('充值', $transaction->account)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '查看':
                if ($user->access('查看财务中心')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
				$account = $transaction->account;
				if ( Q("$user lab $account")->total_count() /* 我是实验室成员 */
					 || ($user->access('列表下属实验室的财务帐号') && $user->group->is_itself_or_ancestor_of($account->lab->group))
					 || $GLOBALS['preload']['billing.single_department'] && Billing_Department::user_is_dept_incharge($user)){
					$e->return_value = TRUE;
					return FALSE;
				}
				if (Q("$user billing_department<department $account")->total_count()) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
		}

		if ($user->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}

	}

	/*
	static function on_transaction_saved($e, $transaction, $old_data, $new_data) {

		if (!$old_data['status'] && $new_data['status']) {
			$object = O('eq_charge', array('transaction'=>$transaction));
			if (!$object->id) {
				$object = O('eq_sample', array('transaction'=>$transaction));
			}

			if ($object->id) {
				$object->is_locked = 1;
				$object->save();
			}
		}
	}
	*/

	static function transaction_description($e, $transaction) {
		if ($transaction->description['module'] == 'billing') {
			$description = new Markup(I18N::T('billing', $transaction->description['template'], [
				'%user'=>$transaction->description['%user'],
				'%account'=>$transaction->description['%account'],
				'%from_account'=> $transaction->description['%from_account'],
			]), TRUE);
			if ($transaction->description['amend']) $description .= '<br>-----<br>'.$transaction->description['amend'];
			$e->return_value = $description;
			return FALSE;
		}
	}

	static function show_supervised_labs_department_transactions($e, $department)
    {
        $me = L('ME');
        if ($me->access('管理财务中心')) {
            $e->return_value = FALSE;
            return FALSE;
        }
        if ($department->id) {
            if (Billing_Department::user_is_dept_incharge($me, $department)) {
                $e->return_value = FALSE;
                return FALSE;
            }
        }
        if ($me->access('列表下属实验室的收支明细')) {
            $e->return_value = TRUE;
            return FALSE;
        }
    }
}

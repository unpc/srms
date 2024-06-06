<?php

class Grant_Controller extends Base_Controller {

	function index($id=0, $tab=NULL) {
		
		$grant = O('grant', $id);
		if (!$grant->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('查看', $grant)) URI::redirect('error/401');
		$content  = V('grants:grant/view', ['grant'=>$grant]);

		Event::bind('grant.index.tab.content', [$this, '_index_summary'], 0, 'summary');
		Event::bind('grant.index.tab.content', [$this, '_index_expenses'], 0, 'expenses');

		$content->secondary_tabs
			= Widget::factory('tabs')
				->set('grant', $grant)
				->tab_event('grant.index.tab')
				->content_event('grant.index.tab.content')
				->add_tab('summary', [
					'url' => $grant->url('summary'),
					'title' => I18N::T('grants', '经费概况'),
				])
				->add_tab('expenses', [
					'url' => $grant->url('expenses'),
					'title' => I18N::T('grants', '支出明细'),
				])
				;
		
		$primary_tabs = $this->layout->body->primary_tabs;

		$primary_tabs
			->add_tab('view', [
				'url'=> $grant->url(NULL, NULL, NULL, 'view'),
				'title'=> H($grant->project),
			])
			->select('view');


		$primary_tabs->content = $content;

		$content->secondary_tabs->select($tab);

		$this->add_css('grants:common');
		
	}

	function _get_summary_portions($grant, $parent=null) {
		$portions = Q("grant_portion[grant={$grant}][!parent]:sort(id A)");
		return $portions;
	}

	function _index_summary($e, $tabs) {
		$grant = $tabs->grant;
		$portions = $this->_get_summary_portions($grant);
		$tabs->content
			= V('grants:grant/summary', [
				'grant' => $grant,
				'portions' => $portions,
			]);
	}

	function _index_expenses($e, $tabs) {
		$grant = $tabs->grant;
		// 根据搜索条件生成expenses
		$form = Lab::form(function(&$old_form, &$form) {
				if (isset($form['date_filter'])) {
					if ($form['dtstart_check']) {
                        $form['dtstart'] = Date::get_day_start($form['dtstart']);
					}
					else {
						unset($old_form['dtstart_check']);
					}
					if ($form['dtend_check']) {
                        $form['dtend'] = Date::get_day_end($form['dtend']);
					}
					else {
						unset($old_form['dtend_check']);
					}
					unset($form['date_filter']);
				}

				if (isset($form['tag'])) {
					$tag = O('tag', $form['tag']);
					if ($tag->id) {
						$tags = (array)@json_decode($form['tags'], TRUE);
						$tags[$tag->id] = $tags->name;
						$form['tags'] = json_encode($tags);
					}
					unset($form['tag']);
				}
			});
		$selector = "grant_expense[grant=$grant]";
		$pre_selector = [];
		
		if ($form['summary']) {
			$summary = Q::quote($form['summary']);
			$selector .= "[summary*=$summary]";
		}

		if ($form['id']) {
			$id = Q::quote($form['id']);
			$selector .= "[id=$id]";
		}
		
		if ($form['dtstart_check']){
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[ctime>=$dtstart]";
		}
		if ($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
			$selector .= "[ctime<=$dtend]";
		}
		if ($form['invoice']) {
			$invoice = Q::quote($form['invoice']);
			$selector .= "[invoice_no*=$invoice]";
		}
		if ($form['tags']) {
			$root = Tag_Model::root('grant_expense');
			$tag_names = @json_decode($form['tags'], TRUE);

			foreach ($tag_names as $name => $foo) {
				$tag = O('tag', ['name' => $name, 'root' => $root]);

				if ($tag->id) {
					$pre_selector[] = $tag;
				}
			}
		}
		
		if ($form['portion']) {
			$grant_portion = O('grant_portion', $form['portion']);
			$selector .= "[portion_id=" . implode(',', $grant_portion->childrens()) . "]";
		}
		
		//排序
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		
		$sort = ":sort(ctime D)";
		
		if ($form['sort']) {
			$sort = ":sort({$sort_by} {$sort_flag})";
		}

		if (count($pre_selector) > 0) {
			$selector = '(' . implode(', ', $pre_selector). ') ' . $selector;
		}
		
		$selector .= $sort;
		//生成 session token
		$form_token = Session::temp_token('grant_expenses_',300);
		//将搜索条件存入session
		$form['selector'] = $selector;
		$_SESSION[$form_token] = $form;
		
		$expenses = Q($selector);
		$pagination = Lab::pagination($expenses, (int)$form['st'], 15);

		$tabs->content
			= V('grants:expenses', [
				'expenses' => $expenses,
				'sort_asc'=>$sort_asc,
				'sort_by'=>$sort_by,
				'pagination'=>$pagination,
				'form'=>$form,
				'form_token'=>$form_token,
				'grant'=>$grant
			]);
	}
	
	function expenses($id=0) {
		$form = Input::form();
		$grant = O('grant', $id);
		if (!$grant->id) URI::redirect('error/404');
		$type = strtolower($form['type']);
		unset($form['type']);
				
		if (isset($type) && $type == 'print') {
			$this->_index_print($grant, $form);
		}
		elseif (isset($type) && $type == 'csv') {
			$this->_index_csv($grant, $form);
		}
	}
	
	private function _index_print($grant, $form) {
		$form_token = $form['form_token'];
		$visible_columns = $form['columns'];
		$form = $_SESSION[$form_token];
		$selector = $form['selector'];
		$expenses = Q($selector);
		$valid_columns = Config::get('grants.export_columns.expenses');
		
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$this->layout = V('grant_expenses_print',['valid_columns'=>$valid_columns]);
		$this->layout->expenses = $expenses;
		$this->layout->grant = $grant;
		$this->layout->dtstart = $form['dtstart'] ?: null;
		$this->layout->dtend = $form['dtend'] ?: null;
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[grants] %user_name[%user_id]打印了经费%grant_project[%grant_id]的支出明细', [		
				'%user_name' => $me->name,
				'%user_id' => $me->id,
				'%grant_project' => $grant->project,
				'%grant_id' => $grant->id,
		]), 'journal');
	}
	
	private function _index_csv($grant, $form) {
		$form_token = $form['form_token'];
		// $form = $_SESSION[$form_token];
		
		$form_token = $form['form_token'];
		$old_form = (array) $_SESSION[$form_token];
		$new_form = (array) $form;
		if (isset($new_form['columns'])) {
		    unset($old_form['columns']);
		}

		$form = $_SESSION[$form_token] = $new_form + $old_form;

		$selector = $form['selector'];
		$expenses = Q($selector);		
		$valid_columns = Config::get('grants.export_columns.expenses');
		$visible_columns = $form['columns'];
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$csv = new CSV('php://output', 'w');
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[grants] %user_name[%user_id]以CSV导出了经费%grant_project[%grant_id]的支出明细', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,
					'%grant_project' => $grant->project,
					'%grant_id' => $grant->id,
		]), 'journal');
		
		$csv->write(I18N::T('grants',$valid_columns));
		
		if ($expenses->total_count() > 0) {
			$start = 0;
			$per_page = 100;
			while (1) {
				$tmp_expenses = $expenses->limit($start, $per_page);
				if ($tmp_expenses->length() == 0) break;
				foreach ($tmp_expenses as $expense) {
					$data = [];
					foreach ($valid_columns as $key => $value) {
						switch ($key) {
							case 'ref_no':
								$data[] = str_pad($expense->id,6,'0',STR_PAD_LEFT);
								break;
							case 'date':
								$data[] = Date::format($expense->ctime);
								break;
							case 'portion':
								$data[] = str_replace('&#187;','>>',V('grants:portion_name',['portion'=>$expense->portion]) );
								break;
							case 'amount':
								$data[] = $expense->amount;
								break;
							case 'summary':
								$data[] = $expense->pre_summary ? new Markup($expense->pre_summary, FALSE) . ':' . $expense->summary : $expense->summary;
								break;		
							case 'invoice_no':
								$data[] = $expense->invoice_no;
								break;						
						}
					}
					$csv->write($data);
				}
				$start += $per_page;	
			}
		}
		
		$csv->close();	
	}

	function summary($id=0) {
		$form = Input::form();
		$visible_columns = $form['columns'];
		$valid_columns = Config::get('grants.export_columns.summary');
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$grant = O('grant', $id);
		if (!$grant->id) URI::redirect('error/404');
		$gp_form = $this->_portion_form($grant);
		$this->layout = V('grant_summary_print', ['valid_columns'=>$valid_columns]);
		$this->layout->grant = $grant;
		$this->layout->gp_form = $gp_form;
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[grants] %user_name[%user_id]打印了经费%grant_project[%grant_id]的概况', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,
					'%grant_project' => $grant->project,
					'%grant_id' => $grant->id,
		]), 'journal');
		
	}

	function add() {
		$me = L('ME');

		if (!$me->is_allowed_to('添加', 'grant')) URI::redirect('error/401');	
		$grant = O('grant');

		$form = Form::filter(Input::form());
		if ($form['submit']) {

			$form
				->validate('project', 'not_empty', I18N::T('grants', '课题名称不可为空!'))
				->validate('source', 'not_empty', I18N::T('grants', '请输入经费来源！'))
				->validate('amount', 'not_empty', I18N::T('grants','经费总额不可为空!'));
			if ($form['amount']<=0) {
				$form->set_error('amount',I18N::T('grants','数额必须大于0'));
			}

            //如果有效时间设定不为int值或者小于0
            if (!is_numeric($form['remind_time']) || $form['remind_time'] < 0) $form->set_error('remind_time', I18N::T('grants', '提醒时间设定有误!'));

						
			$grant->project = $form['project'];
			$grant->source = $form['source'];
			$grant->ref = $form['ref'];
			$grant->amount = $form['amount'];
			$grant->description = $form['description'];

            if ($form['dtstart'] > $form['dtend']) {
                list($form['dtend'], $form['dtstart']) = [$form['dtstart'], $form['dtend']];
            }

			$grant->dtstart = $form['dtstart'];
			$grant->dtend = Date::get_day_end($form['dtend']);

            $grant->remind_time = $form['remind_time'];

			$user = O('user', $form['user']);

			/* BUG #927::添加经费时，负责人随意填写，也可添加成功
			   解决：验证用户的id。(kai.wu@2011.08.01) */
			if (!$user->id) {
				$form->set_error('user', I18N::T('grants', '请选择课题负责人!'));
			}
			
			$grant->user = $user;
			$this->_portion_checking($form);
			
			if ($form->no_error) {
							
				$grant->ctime = time();
				$grant->expense = 0;
				$grant->balance = round($form['amount'], 2);
				
                if ($grant->touch()->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('grants', '经费添加成功！'));
                    /* 记录日志 */
                    Log::add(strtr('[grants] %user_name[%user_id]添加了经费%grant_project[%grant_id]', [
                    			'%user_name' => $me->name,
                    			'%user_id' => $me->id,
                    			'%grant_project' => $grant->project,
                    			'%grant_id' => $grant->id,
                    ]), 'journal');

                    /**
                     * @ 在添加经费的时候就直接添加默认的分类
                     */
                    $this->_portion_save($grant, $form);
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('grants', '经费添加失败！'));
                }
				URI::redirect($grant->url(NULL, NULL, NULL, 'edit'));
			}
			
			// 保存 grant 出错时， 要保持住 avail_balance
			$grant->avail_balance = $form['avail_balance'];
		}

		$primary_tabs = $this->layout->body->primary_tabs;

		$primary_tabs
			->add_tab('add', [
				'url'=> URI::url('!grants/grant/add'),
				'title'=> I18N::T('grants', '添加经费'),
			])
			->select('add');

		$primary_tabs->content = V('grants:grant/add'
			, ['grant'=>$grant, 'form'=>$form]);

		$this->add_css('grants:common');
	}


	function edit($id=0) {
		$me = L('ME');

		$grant = O('grant', $id);
		if (!$grant->id) URI::redirect('!grants/grant/add');
		if (!$me->is_allowed_to('修改', $grant)) URI::redirect('error/401');
		$form = Form::filter(Input::form());
		if ($form['submit']) {
			$form
				->validate('source', 'not_empty', I18N::T('grants', '请输入经费来源！'))
				->validate('amount', 'not_empty', I18N::T('grants','经费总额不可为空!'))
				->validate('project', 'not_empty', I18N::T('grants', '课题名称不可为空!'));

            if (!is_numeric($form['remind_time']) || $form['remind_time'] < 0) $form->set_error('remind_time', I18N::T('grants', '提醒时间设定有误!'));

			$user = O('user', $form['user']);

			/* BUG #927::添加经费时，负责人随意填写，也可添加成功
			   解决：验证用户的id。(kai.wu@2011.08.01) */
			if (!$user->id) {
				$form->set_error('user', I18N::T('grants', '请选择课题负责人!'));
			}
			$this->_portion_checking($form);
			
			if ($form->no_error) {
				$grant->project = $form['project'];
				$grant->source = $form['source'];
				$grant->ref = $form['ref'];
				$grant->amount = $form['amount'];
				$grant->description = $form['description'];

                if ($form['dtstart'] > $form['dtend']) {
                    list($form['dtend'], $form['dtstart']) = [$form['dtstart'], $form['dtend']];
                }

				$grant->dtstart = $form['dtstart'];
				$grant->dtend = Date::get_day_end($form['dtend']);

                $grant->remind_time = $form['remind_time'];

				$grant->user = $user;
				if ($grant->touch()->save()) {
                    /* 记录日志 */
                    Log::add(strtr('[grants] %user_name[%user_id]修改了经费%grant_project[%grant_id]', [
                    			'%user_name' => $me->name,
                    			'%user_id' => $me->id,
                    			'%grant_project' => $grant->project,
                    			'%grant_id' => $grant->id,
                    ]), 'journal');

                    $gp_form = $this->_portion_save($grant, $form);
                    if (!isset($gp_form['has_error'])) {
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('grants', '经费修改成功！'));
                    }
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('grants', '经费修改失败！'));
                }
			}
			else {
				$gp_form = $this->_portion_error($grant, $form);
			}
			
			// 保存 grant 出错时， 要保持住 avail_balance
			$grant->avail_balance = $form['avail_balance'];
		}
		else {
			$gp_form  = $this->_portion_form($grant);
		}

		$primary_tabs = $this->layout->body->primary_tabs;

		$primary_tabs
			->add_tab('edit', [
				'*' => [
					[
						'url'=> $grant->url(NULL, NULL, NULL, 'view'),
						'title'=> H($grant->project),
					],
					[
						'url'=> $grant->url(NULL, NULL, NULL, 'edit'),
						'title'=> I18N::T('grants', '修改') ,
					]
				]
			])
			->select('edit');


		$primary_tabs->content = V('grants:grant/edit'
			, ['grant'=>$grant, 'form'=>$form, 'gp_form'=>$gp_form]);

		$this->add_css('grants:common');
	}

	function delete($id=0){
		$me = L('ME');
	
		$grant = O('grant', $id);
		if (!$me->is_allowed_to('删除', $grant)) {
			URI::redirect('error/401');
		}

		if (Q("grant_portion[grant={$grant}]<portion grant_expense")->total_count() > 0) {
			//存在关联的花费 不能删除grant
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('grants','经费中存在支出, 不能删除!'));
		}
		elseif($grant->id && $grant->delete()) {
			/* 记录日志 */
			Log::add(strtr('[grants] %user_name[%user_id]删除了经费%grant_name[%grant_id]', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%grant_name' => $grant->name,
						'%grant_id' => $grant->id,
			]), 'journal');
			
			
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('grants','经费删除成功!'));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR,I18N::T('grants','经费删除失败!'));
		}
		URI::redirect( URI::url('!grants/grants') );
	}

	private function &_portion_form($grant) {
		$gp_form = [];
		foreach (Q("grant_portion[grant={$grant}][!parent]:sort(id A)") as $portion) {
			$gp_form[$portion->id] = [
				'indent' => 0,
				'portion' => $portion,
			];
	
			$this->_add_children_to_assoc($portion, $gp_form, 1);
		}
		return $gp_form;
	}

	// 将 grant_portions 转换为合适的数据结果
	// portion 顶层 grant_portion
	// gp_form 返回的 级联结构 数据
	// indent 层数
	
	private function _add_children_to_assoc($portion, &$gp_form, $indent) {

		foreach (Q("grant_portion[parent=$portion]:sort(id A)") as $child) {
			$gp_form[$child->id] = [
				'indent' => $indent,
				'portion' => $child,
			];
			$this->_add_children_to_assoc($child, $gp_form, $indent+1);
		}
	}

	private function _portion_checking(&$form) {
		$portion_names = $form['portion_name'] ?: [];
		$portion_amounts = $form['portion_amount'] ?: [];
		$portion_parents = $form['portion_parent'] ?: [];
		
		// 检测错误
		foreach($portion_parents as $k => $portion_parent){
			// $portion_name 不可以为空
			$portion_name = trim($portion_names[$k]);
			if(empty($portion_name)){
				$form->validate("portion_name[$k]", 'not_empty', I18N::T('grants', '分类名称不可为空!'));
			}
	
			if(!(float)$portion_amounts[$k]){
				$form->validate("portion_amount[$k]", 'not_empty', I18N::T('grants', '分类金额应为大于 0 的数字!'));
			}
			
			// 只验证数据库中存在的记录
			if($k > 0){
				$portion = O('grant_portion', $k);
				if($portion_amounts[$k] < $portion->expense) {
					$form->errors["portion_amount[$k]"][] = I18N::T('grants', '使用份额应小于分配分额');
					$form->no_error = FALSE;
				}
			}
		
			if($portion_parent){
				$sum[$portion_parent] += $portion_amounts[$k];
			}else{
				$sum_top += $portion_amounts[$k];
			}
		}
	
		//验证分配的amount 小于grant的amount
		if($sum_top > $form['amount']){
			$form->errors["amount"][] = I18N::T('grants', '父分类应该大于子分类');
			$form->no_error = FALSE;
		}

		foreach($portion_amounts as $k=>$amount){
		
			//子amount和父expense之和不可大于父amount
			if($amount < $sum[$k] + $portion_expenses[$k]) {
			
				$form->errors["portion_amount[$k]"][] = I18N::T('grants', '父分类应该大于子分类');
				$form->no_error = FALSE;
			}
		}
	}
	
	
	// 检测 grant_portion 的错误，若无错误则保存
	private function & _portion_save($grant, & $form){
		
		$portion_names = $form['portion_name'] ?: [];
		$portion_amounts = $form['portion_amount'] ?: [];
		$portion_parents = $form['portion_parent'] ?: [];
		
		// 检测错误
		try{
			foreach($portion_parents as $k => $portion_parent){
				
				// $portion_name 不可以为空
				$portion_name = trim($portion_names[$k]);
				if(empty($portion_name)){
			
					$form->validate("portion_name[$k]", 'not_empty', I18N::T('grants', '分类名称不可为空!'));
			
					throw new Exception;
				}
		
				if(!(float)$portion_amounts[$k]){
			
					$form->validate("portion_amount[$k]", 'not_empty', I18N::T('grants', '分类金额应为大于 0 的数字!'));
		
					throw new Exception;
				}
				
				// 只验证数据库中存在的记录
				if($k > 0){
					$portion = O('grant_portion', $k);
					if($portion_amounts[$k] < $portion->expense) {
						$form->errors["portion_amount[$k]"][] = I18N::T('grants', '使用份额应小于分配分额');
						$form->no_error = FALSE;
						throw new Exception;
					}
				}
			
				if($portion_parent){
			
					$sum[$portion_parent] += $portion_amounts[$k];
				}else{
			
					$sum_top += $portion_amounts[$k];
				}
			
			}
		
			//验证分配的amount 小于grant的amount
			if($sum_top > $grant->amount){
				$form->errors["amount"][] = I18N::T('grants', '父分类应该大于子分类');
				$form->no_error = FALSE;
				throw new Exception;
			}

			foreach($portion_amounts as $k=>$amount){
			
				//子amount和父expense之和不可大于父amount
				if($amount < $sum[$k] + $portion_expenses[$k]) {
				
					$form->errors["portion_amount[$k]"][] = I18N::T('grants', '父分类应该大于子分类');
					$form->no_error = FALSE;
					throw new Exception;
				}
			}

		}
		catch(Exception $e) { //出错退出
		
			return $this->_portion_error($grant, $form);
		}
		
		//Q("grant_portion[grant={$grant}]")->delete_all();	
		$old_portions = Q("grant_portion[grant=$grant]");
		
		$key2object = [];
		foreach($portion_parents as $id => $portion_parent){
				
			$old_portion = $old_portions[$id];
			if (isset($old_portion)) {
				$portion = $old_portions[$id];
				unset($old_portions[$id]);
			}
			else {
				$portion = O('grant_portion');
		
			}
			
			if($key2object[$portion_parent]){
				$portion->parent = $key2object[$portion_parent];
			}
			else{
			
				$portion->parent = NULL;
			}
			$portion->name = Output::safe_html($portion_names[$id]);
			$portion->amount = $portion_amounts[$id];
			$portion->grant = $grant;
			
			if($portion->save()){
				$key2object[$id] = $portion;
			}
	
		}

		$old_portions->delete_all();

		if(!$portion_parents){
			$portion = O('grant_portion');
			$portion->name = I18N::T('grants', '其他');
			$portion->amount = $grant->amount;
			$portion->grant = $grant;
			$portion->save();
		}

		$grant->recalculate();

		return $this->_portion_form($grant);
	}
	
	// 转换表单传过来的 grant_portion 数据 为 合适的格式，并传到 view 中
	private function &  _portion_error($grant, &$form){

		$scanned_portion = [];
		$portion_indent = [];
		
		$old_portions = Q("grant_portion[grant=$grant]");
		$form['portion_parent'] = (array) $form['portion_parent'];
		foreach ($form['portion_parent'] as $id => $parent_id) {
			
			// isset 函数只接受变量 ， 不能接受 $old_portions[$id]
			$old_portion = $old_portions[$id];
			if (isset($old_portion)) {
			
				$portion = $old_portions[$id];
			}
			else {
				$portion = O('grant_portion');
				$portion->id = $id;
			}
			
			$scanned_portion[$portion->id] = $portion;
			$portion->amount = $form['portion_amount'][$id];
			$portion->name = $form['portion_name'][$id];
			
			// 要保持住 grant_portion, 需要赋值 balance 和 avail_balance
			$portion->balance = $form['portion_balance'][$id];
			$portion->avail_balance = $form['portion_avail_balance'][$id];
			
			$scanned_portion_parent = $scanned_portion[$parent_id];
			if (isset($scanned_portion_parent)) {
			
				$portion->parent = $scanned_portion_parent;
				$indent = $portion_indent[$parent_id] + 1;
			}
			else {
			
				$portion->parent = 0;
				$indent = 0;
			}		
			
			$portion_indent[$id] = $indent;
				
			$gp_form[$id]['indent'] = $indent;
			$gp_form[$id]['portion'] = $portion;
            $gp_form['has_error'] = TRUE;
			
		}
		
		return $gp_form;
	}
	
}

class Grant_AJAX_Controller extends AJAX_Controller {

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$grant_id = $form['grant_id'];
		$columns = Config::get('grants.export_columns.expenses');
		switch ($type) {
			case 'csv':
				$title = I18N::T('grants', '请选择要导出CSV的列');
				break;
			case 'print':
				$title = I18N::T('grants', '请选择要打印的列');
				break;
		}
		JS::dialog(V('export_form', [
						'type' => $type,
						'form_token' => $form_token,
						'columns' => $columns,
						'grant_id' => $grant_id
					]),[
						'title' => $title
					]);
	}

	function index_export_summary_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$grant_id = $form['grant_id'];
		$columns = Config::get('grants.export_columns.summary');
		switch ($type) {
			case 'csv':
				$title = I18N::T('grants', '请选择要导出CSV的列');
				break;
			case 'print':
				$title = I18N::T('grants', '请选择要打印的列');
				break;
		}
		JS::dialog(V('export_summary_form', [
						'type' => $type,
						'form_token' => $form_token,
						'columns' => $columns,
						'grant_id' => $grant_id
					]),[
						'title' => $title
					]);
	}

	function index_portion_list_children_click($id=0) {
		$form = Input::form();
		$portion_id = $form['id'];
		$element_id = $form['element_id'];
		$max_width = $form['max_width'];
		$grant = O('grant', $id);
		$portion = O('grant_portion', $portion_id);
		if (!$portion->id || !$grant->id || $portion->grant->id!=$grant->id) return;
		
		$portions = $portion->children();
			$view = (string) V('grants:grant/summary.portions', [
				'portions'=>$portions,
				'max_width'=>$max_width,
				'random_id'=>$element_id
			]);
			Output::$AJAX["#{$element_id}"] = [
				'data'=>$view,
				'mode'=>'after',
			];
	}

	function index_add_expense_click($id=0) {

		$form = Input::form();

		$grant = O('grant', $form['id']);
		if (!$grant->id) {
			URI::redirect('error/404');
		}
		
		if (!L('ME')->is_allowed_to('修改支出', $grant)) {
			URI::redirect('error/401');
		}

		JS::dialog(V('expense/add', ['grant'=>$grant]));
	}

	function index_add_expense_submit($id=0) {
		$form = Form::filter(Input::form());
		$me = L('ME');

		$grant = O('grant', $form['id']);
		if (!$grant->id) {
			URI::redirect('error/404');
		}

		if (!$me->is_allowed_to('修改支出', $grant)) {
			URI::redirect('error/401');
		}

		$form
			->validate('summary', 'not_empty', I18N::T('grants', '请填写说明!'))
			->validate('amount', 'number(>0)', I18N::T('grants', '数额必须大于0!'));

		$portion = O('grant_portion', $form['portion_id']);
		if (!$portion->id || $portion->grant->id != $grant->id) {
			$form->set_error('portion_id', I18N::T('grants', '经费选择有误!'));
		}
        else {
            if ($portion->avail_balance < $form['amount']) {
                $form->set_error('portion_id', I18N::T('grants', '经费分配不足!'));
            }
        }

		if($form->no_error){
			$expense = O('grant_expense');
			$expense->summary = $form['summary'];
			$expense->amount = $form['amount'];
			$expense->invoice_no = $form['invoice_no'];
			$expense->user = O('user', $form['user_id']);
			$expense->ctime = $form['date'];
			$expense->portion = $portion;
			$expense->grant = $portion->grant;
			if ($expense->save()) {
				/* 记录日志 */
				Log::add(strtr('[grants] %user_name[%user_id]添加了经费%grant_project[%grant_id]的支出%expense[%expense_id]', [
							'%user_name' => $me->name,
							'%user_id' => $me->id,
							'%grant_project' => $expense->grant->project,
							'%grant_id' => $expense->grant->id,
							'%expense' => $expense->summary,
							'%expense_id' => $expense->id,					
				]), 'journal');
				
				$tags = @json_decode($form['tags'], TRUE);
				if (count($tags)) {
					Tag_Model::replace_tags($expense, $tags, 'grant_expense', TRUE);
				}
			}
			JS::refresh();
		}
		else {
			JS::dialog(V('expense/add', ['grant'=>$grant, 'form'=>$form]));
		}

	}

	function index_delete_expense_click($id=0) {
		$me = L('ME');
		$form = Input::form();
		$expense = O('grant_expense', $form['id']);
		if (!$expense->id) {
			URI::redirect('error/404');
		}

		if (!$me->is_allowed_to('修改支出', $expense->grant)) {
			URI::redirect('error/401');
		}

		if (JS::confirm(I18N::T('grants', '您确定删除该支出吗?'))) {
			$expense->delete();
			/* 记录日志 */
			Log::add(strtr('[grants] %user_name[%user_id]删除了经费%grant_project[%grant_id]的支出%expense[%expense_id]', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%grant_project' => $expense->grant->project,
						'%grant_id' => $expense->grant->id,
						'%expense' =>  $expense->summary,
						'%expense_id' => $expense->id,
			]), 'journal');
			
			JS::refresh();
		}
	}

	function index_edit_expense_click($id=0) {
		$form = Input::form();
		$expense = O('grant_expense', $form['id']);
		if (!$expense->id) {
			URI::redirect('error/404');
		}

		if (!L('ME')->is_allowed_to('修改支出', $expense->grant)) {
			URI::redirect('error/401');
		}

		JS::dialog(V('expense/edit', ['expense'=>$expense]));
	}

	function index_edit_expense_submit($id=0) {
		$me = L('ME');
		$form = Form::filter(Input::form());

		$expense = O('grant_expense', $form['id']);
		if (!$expense->id) {
			URI::redirect('error/404');
		}

		if (!$me->is_allowed_to('修改支出', $expense->grant)) {
			URI::redirect('error/401');
		}

		$form
			->validate('summary', 'not_empty', I18N::T('grants', '请填写说明!'))
			->validate('amount', 'number(>0)', I18N::T('grants', '数额必须大于0!'));


		$portion = O('grant_portion', $form['portion_id']);
		if (!$portion->id || $portion->grant->id != $expense->grant->id) {
			$form->set_error('portion_id', I18N::T('grants', '经费选择有误!'));
		}
        else {
            //剩余经费加修改明细的经费
            if ($portion->avail_balance + $expense->amount < $form['amount']) {
                $form->set_error('portion_id', I18N::T('grants', '经费分配不足!'));
            }
        }

		if ($form->no_error) {

			$expense->summary = $form['summary'];
			$expense->amount = $form['amount'];
			$expense->invoice_no = $form['invoice_no'];
			$expense->user = O('user', $form['user_id']);
			$expense->ctime = (int) $form['date'];
			$expense->portion = $portion;
			$expense->grant = $portion->grant;
			if ($expense->save()) {
				/* 记录日志 */
				Log::add(strtr('[grants] %user_name[%user_id]修改了经费%grant_project[%grant_id]的支出%expense[%expense_id]', [
							'%user_name' => $me->name,
							'%user_id' => $me->id,
							'%grant_project' => $expense->grant->project,
							'%grant_id' => $expense->grant->id,
							'%expense' => $expense->summary,
							'%expense_id' => $expense->id,
				]), 'journal');

				$tags = @json_decode($form['tags'], TRUE);
				if (count($tags)) {
					Tag_Model::replace_tags($expense, $tags, 'grant_expense', TRUE);
				}else{
					$root = Tag_Model::root('grant_expense');
					$tags = Q("$expense tag[root=$root]");
					foreach ($tags as $tag) {
						$tag->disconnect($expense);
					}
				}
			}
			JS::refresh();
		}
		else {
			JS::dialog(V('expense/edit', ['expense'=>$expense, 'form'=>$form]));
		}

	}

}

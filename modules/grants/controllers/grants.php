<?php

class Grants_Controller extends Base_Controller {

	function index() {
		$me = L('ME');		
		$type = strtolower(Input::form('type'));
		$form_token = Input::form('form_token');

		if ($form_token && isset($_SESSION[$form_token])) {
			$form = $_SESSION[$form_token];	
		}
		else { 
			if (!$me->is_allowed_to('列表', 'grant')) URI::redirect('error/401');	
			
			$form_token = Session::temp_token('grant_list_',300);//生成唯一一个SESSION的key
			//多栏搜索	
			$form = Lab::form(function(&$old_form, &$form){
				if (isset($form['date_filter'])) {
					if (!$form['dtstart_check']) {
						unset($old_form['dtstart_check']); 
					}
					else {
						$form['dtstart'] = strtotime('midnight', $form['dtstart']);
					}
	
					if (!$form['dtend_check']) {
						unset($old_form['dtend_check']);
					}
					else {
						$form['dtend'] = strtotime('tomorrow midnight', $form['dtend']) - 1;
					}
					unset($form['date_filter']);
				}
			});

			$form['form_token'] = $form_token;
			$_SESSION[$form_token] = $form;
			
			$pre_selector = [];
			$selector = "grant";
		
			if($form['project']){
				$project = Q::quote($form['project']);
				$selector .= "[project*=$project]";
			}
			
			if($form['source']){
				$source = Q::quote($form['source']);
				$selector .= "[source*=$source]";
			}
			if($form['ref']){
				$ref = Q::quote($form['ref']);
				$selector .= "[ref*=$ref]";
			}
			if($form['user']){
				$user_name = Q::quote($form['user']);
				$pre_selector['user'] = "user[name*=$user_name|name_abbr*=$user_name]";
			}
			
			if($form['dtstart_check']){
				$dtstart = Q::quote($form['dtstart']);
				$selector .= "[dtstart>=$dtstart]";
			}
			if($form['dtend_check']){
				$dtend = Q::quote($form['dtend']);
				$selector .= "[dtend>0][dtend<=$dtend]";
			}
		
			//排序
			$sort_asc = $form['sort_asc'];
			$sort = $form['sort'];
			$sort_flag = $sort_asc ? 'A':'D';
			$sort_by = $sort ?: 'project';
			
			/*
				TODO 按照汉字排序会有问题， 仅仅只是算作归类查看。
				需要有进一步的方案来处理。
			*/
			switch($sort){
				case 'user':
				
					if($pre_selector['user']){
						$pre_selector['user'] .= ":sort(name {$sort_flag})";
					
					}
					else{
						$pre_selector['user'] = "user:sort(name {$sort_flag})";
					}
				
					$selector = '('.implode(', ', $pre_selector).') ' . $selector;
					break;
				default:
					
					if(count($pre_selector)){
					
						$selector = '('.implode(', ', $pre_selector).') ' . $selector;
					}
					
					$selector .= ":sort({$sort_by} {$sort_flag})";
					break;
			}
			
		
		}		
		
			//打印、导出功能
		$export_types = ['print','csv'];
		if ( in_array($type,$export_types) ) {
				$grants = Q($form['selector']);
				call_user_func([$this, '_export_'.$type], $grants, $form);
		}
		else {
			
			$form['selector'] = $selector;
			$_SESSION[$form_token] = $form;
						
			$grants = Q($selector);
			
			$start = (int) $form['st'];
			$per_page = 15;
			$start = $start - ($start % $per_page);
	
			$pagination = Lab::pagination($grants, $start, $per_page);

			$panel_buttons = new ArrayIterator;
		
			if ($me->is_allowed_to('添加', 'grant')) {
				$panel_buttons[] = [
					'url' => URI::url('!grants/grant/add'),
					'text'  => I18N::T('grants', '添加经费'),
					'extra' => 'class="button button_add"'
				];
			}
				
			if ( $me->is_allowed_to('导出','grant') ) {
				$panel_buttons[] = [
					//'url' => URI::url(),
					'text'  => I18N::T('grants','打印'),
					'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!grants/index') .
							'" q-static="' . H(['type'=>'print','form_token' => $form_token]) .
							'" class="button button_print "'
				];

				$panel_buttons[]  = [
				//'url' => URI::url(),
				'text'  => I18N::T('grants','导出CSV'),
				'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!grants/index') .
						'" q-static="' . H(['type'=>'csv','form_token' => $form_token]) .
						'" class="button button_save "'
			
				];
            }
				
			$primary_tabs = $this->layout->body->primary_tabs->select('list');
			$primary_tabs->content = V('grants',
							[
								'grants'=>$grants,
								'pagination'=>$pagination,
								'lab'=>$lab,
								'form'=>$form,
								'sort_asc'=>$sort_asc,
								'sort_by'=>$sort_by,
								'panel_buttons' => $panel_buttons,
							]);
			$this->add_css('grants:common');
		}	
	}
	
	
	//选择完打印列，点击“确定”，执行该打印事件
	function _export_print($grants,$form) {
		$valid_columns = Config::get('grants.export_columns.grant');
		$visible_columns = Input::form('columns');
		
		
		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$this->layout = V('grant_print',[
			'grants' => $grants,
			'valid_columns' => $valid_columns,
			
		]);
		
		//记录日志
		$me = L('ME');
		Log::add(strtr('%user_name[%user_id]打印了经费管理列表', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,
		]), 'journal');
			
	
	}
	
	//选择完导出的列，点击“确定”，执行该导出事件
	function _export_csv($grants, $form) {
		$form_token = $form['form_token'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) Input::form();
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('grants.export_columns.grant');
		$visible_columns = $form['columns'];
		
		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$csv =new CSV('php://output','w');
		//导出统计结果
		 $tamount = $grants->sum('amount');//保存所有项目总额
 		 $tbalance = $grants->sum('balance'); //保存所有项目总余额
	 	 
	 	 $statics = [];
	 	 $statics[] = I18N::T('grants', '当前实验室所有课题的经费总额为%tamount, 余额总计为%tbalance',
								[
									'%tamount' => Number::currency($tamount),
									'%tbalance' => Number::currency($tbalance),
								]
					);
	 	 $csv->write($statics);
		//导出titile
		$title = [];
		foreach ($valid_columns as $p => $p_name) {
			$title[] = I18N::T('grants',$valid_columns[$p]);
		}
		$csv->write($title);
		//导出内容
		if ($grants->total_count()) {
			foreach ($grants as $grant) {
				$data = [];
				if (array_key_exists('project', $valid_columns)) {
					$data[] = H($grant->project)?:'-';
				}
				if (array_key_exists('source', $valid_columns)) {
					$data[] = H($grant->source)?:'-';
				}
				if (array_key_exists('ref', $valid_columns)) {
					$data[] = H($grant->ref)?:'-';
				}
				if (array_key_exists('amount', $valid_columns)) {
					$data[] = $grant->amount ? : '-';
				}
				if (array_key_exists('balance', $valid_columns)) {
					$data[] = $grant->balance ? : '-';
				}
				if (array_key_exists('incharge', $valid_columns)) {
					$incharge = Q("$grant<user user");
					$data[] = H($incharge->name)?:'-';
				}
				
				$csv->write($data);
			}
		
		}
		
		$csv->close();
		//记录日志
		$me = L('ME');
		Log::add(strtr('%user_name[%user_id]以CSV导出了经费管理列表', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,
		]), 'journal');
	}

}

<?php
class Index_Controller extends Base_Controller {

	function index() {
		/* filter */
		$me = L('ME');
		$type = strtolower(Input::form('type'));
		$form_token = Input::form('form_token');
		$export_types = ['print','csv'];

		if ($form_token && isset($_SESSION[$form_token]) && in_array($type,$export_types) ) {

			$form = $_SESSION[$form_token];
			$form['form_token'] = $form_token;
					
			$selector = 'stock[!is_collection]';
			if(trim($form['ref_no'])){
				$ref_no = Q::quote($form['ref_no']);
				$selector .= "[ref_no*=$ref_no]";
			}
			if($form['barcode']){
				$barcode = Q::quote($form['barcode']);
				$selector .= "[barcode=$barcode]";
			}
			if($form['product_name']){
				$product_name = Q::quote($form['product_name']);
				$selector .= "[product_name*=$product_name]";
			}
			if($form['manufacturer']){
				$manufacturer = Q::quote($form['manufacturer']);
				$selector .= "[manufacturer*=$manufacturer]";
			}
			if($form['catalog_no']){
				$selector .= '[catalog_no*='.Q::quote($form['catalog_no']).']';
			}
			if($form['vendor']){
				$vendor = Q::quote($form['vendor']);
				$selector .= "[vendor*=$vendor]";
			}
			if($form['location']){
				$location = Q::quote($form['location']);
				$selector .= "[location*=$location]";
			}
			if($form['expire_status']){
				$expire_status = Q::quote($form['expire_status']);
				$selector .= "[expire_status=$expire_status]";
			}
			if($form['status']){
				$status = Q::quote($form['status']);
				$selector .= "[status=$status]";
			}

            if ($form['_type'] && $form['_type'] != -1) {
                $_type = Q::quote($form['_type']);
                $selector .= "[type={$_type}]";
            }

			$sort_by = $form['sort'];
			$sort_asc = $form['sort_asc'];
			$sort_flag = $sort_asc ? 'A':'D';

			switch($sort_by){
				case 'product_name':
					$selector .= ":sort(quantity D, parent_id D, product_name {$sort_flag})";
					break;
				case 'manufacturer':
					$selector .= ":sort(quantity D, parent_id D, manufacturer {$sort_flag})";
					break;
				case 'vendor':
					$selector .= ":sort(quantity D, parent_id D, vendor {$sort_flag})";
					break;
				case 'location':
					$selector .= ":sort(quantity D, parent_id D, location {$sort_flag})";
					break;
				case 'status':
					$selector .= ":sort(quantity D, parent_id D, status {$sort_flag})";
					break;
				case 'ref_no':
					$selector .= ":sort(quantity D, parent_id D, ref_no {$sort_flag})";
					break;
				default:
					$selector .= ":sort(quantity D, parent_id D, mtime {$sort_flag})";
			}


			if (count($pre_selector)){
				$selector = '('.implode(', ', $pre_selector).') ' . $selector;
			}
			$stocks = Q($selector);
			call_user_func([$this, '_export_'.$type], $stocks, $form);
		}
		else {
			$form = Lab::form(function(&$old_form, &$form) {
					if (isset($form['sort'])) {
						if ($old_form['sort'] == $form['sort']) {
							$form['sort_asc'] = !$old_form['sort_asc'];
						}
						else {
							$form['sort_asc'] = TRUE;
						}
					}

					if (isset($form['tag'])) {
						$tag = O('tag', $form['tag']);
						if ($tag->id) {
							$tags = (array)@json_decode($form['tags'], TRUE);
							$tags[$tag->id] = $tag->name;
							$form['tags'] = json_encode($tags);
						}
						unset($form['tag']);
					}

				});
			$db = Database::factory();

			$sql = "SELECT DISTINCT ";

			$pre_selector = [];

			$sql_where = [];
			if(trim($form['ref_no'])){
				$sql_where[] = 'ref_no LIKE "%%'.$db->escape($form['ref_no']).'%%"';
			}
			if($form['barcode']){
				$barcode = Q::quote($form['barcode']);
				$sql_where[] = "barcode=$barcode";
			}
			if($form['product_name']){
				$sql_where[] = 'product_name LIKE "%%'.$db->escape($form['product_name']).'%%"';
			}
			if($form['manufacturer']){
				$sql_where[] = 'manufacturer LIKE "%%'.$db->escape($form['manufacturer']).'%%"';
			}
			if($form['catalog_no']){
				$sql_where[] = 'catalog_no LIKE "%%'.$db->escape($form['catalog_no']).'%%"';
			}
			if($form['vendor']){
				$sql_where[] = 'vendor LIKE "%%'.$db->escape($form['vendor']).'%%"';
			}
			if($form['location']){
				$sql_where[] = 'location LIKE "%%'.$db->escape($form['location']).'%%"';
			}

            if ($form['_type'] && $form['_type'] != -1) {
                $sql_where[] = 'type LIKE "%%'. $db->escape($form['_type']). '%%"';
            }

			if($form['expire_status']){
				$expire_status = Q::quote($form['expire_status']);
				$sql_where[] .= "expire_status=$expire_status";
			}

			if($form['status']){
				$status = Q::quote($form['status']);
				$sql_where[] = "status=$status";
			}

			$sort_by = $form['sort'];
			$sort_asc = $form['sort_asc'];
			$sort_flag = $sort_asc ? 'ASC':'DESC';

			$sql_orderby = [];
			switch($sort_by){
				case 'product_name':
					$sql_orderby[] = "product_name $sort_flag, is_collection DESC";
					break;
				case 'manufacturer':
					$sql_orderby[] = "manufacturer $sort_flag, is_collection DESC";
					break;
				case 'vendor':
					$sql_orderby[] = "vendor $sort_flag, is_collection DESC";
					break;
				case 'location':
					$sql_orderby[] = "location $sort_flag, is_collection DESC";
					break;
				case 'status':
					$sql_orderby[] = "status $sort_flag, is_collection DESC";
					break;
				case 'ref_no':
					$sql_orderby[] = "ref_no $sort_flag, is_collection DESC";
					break;
				default:
					$sql_orderby[] = "mtime $sort_flag, is_collection DESC";
					break;
			}

			if ($form['tags']) {	/* TASK#331(xiaopei.li@2011.03.14) */
				$root = Tag_Model::root('inventory'); /* TODO fix inventory tag */
				$tag_names = @json_decode($form['tags'], TRUE);
				foreach ($tag_names as $id => $name) {

					$tag = O('tag', ['name'=>$name,'root'=>$root]);

					if ($tag->id) {
						$pre_selector[] = $tag;
					}
				}
			}
			if ($form['tag']) {		/* TASK#331(xiaopei.li@2011.03.14) */
				$root = Tag_Model::root('inventory'); /* TODO fix inventory tag */
				$tag = O('tag', ['id'=>$form['tag'], 'root'=>$root]);
				if ($tag->id) {
					$pre_selector[] = $tag;
				}
			}

			$num = count($pre_selector);
			if ($num == 0) {
				$t_name = ' `t'.$num.'`';
				$sql .= $t_name.".`parent_id` FROM  (SELECT * FROM stock ORDER BY is_collection DESC) AS".$t_name;
				if (count($sql_where) == 0) {
					$sql .=  ' WHERE'.$t_name.".`parent_id`!='0' ";
				}
				else {
					$wheres = [];	
					foreach ($sql_where as $where) {
						$wheres[] = $t_name.'.'.$where;
					}
					$sql .= ' WHERE ('.$t_name.".`parent_id`!='0' AND ".implode(' AND', $wheres).')';
				}
				$sql .= ' GROUP BY '.$t_name.'.`parent_id` ORDER BY ';
				foreach ($sql_orderby as $orderby) {
					$sql .= $t_name.'.'.$orderby.' ';
				}
			}
			else {
				$n = 0;
				$join = [];
				$s_name = ' `t'.$num.'`';
				$sql .= $s_name.".`parent_id` FROM (SELECT * FROM stock ORDER BY is_collection DESC) AS".$s_name;
				foreach ($pre_selector as $tag) {
					$tags[] = $tag->id;
					$t_name = '`t'.$n.'`';
					$r_name = '`r'.$n.'`';
					$join = ' INNER JOIN (`_r_tag_stock` '.$r_name.', `tag` '.$t_name.') ';
					$on = 'ON ('.$t_name.".`id`='".$tag->id."' AND ";
					$condition = $s_name.".`parent_id`!='0' AND ";
					if (count($sql_where)) {
						$wheres = [];
						foreach ($sql_where as $where) {
							$wheres[] = $s_name.'.'.$where;
						}
						$condition .= implode(' AND ', $wheres).' AND '.$r_name.'.`type`="" AND '.$r_name.'.`id1`='.$t_name.'.`id` AND '.$r_name.'.`id2`='.$s_name.'.id) ';
					}
					else {
						$condition .= $r_name.'.`type`="" AND '.$r_name.'.`id1`='.$t_name.'.`id` AND '.$r_name.'.`id2`='.$s_name.'.id) ';
					}
					$inner_join[] = $join.$on.$condition;
					$n++;
				}
				$sql .= implode(' ', $inner_join);
				$sql .= ' GROUP BY '.$s_name.'.`parent_id` ORDER BY';
				foreach ($sql_orderby as $orderby) {
					$sql .= $s_name.'.'.$orderby.' ';
				}
			}

			$stocks = null;
			if ($db->query($sql)) {
				$stocks = $db->query($sql)->rows();
			}

			/* pagination */
			$start = (int) $form['st'];
			$per_page = 20;
			$start = $start - ($start % $per_page);
			$num = count($stocks);
			if ($start > 0) {
				$last = floor($num/ $per_page) * $per_page;
				if ($last == $num) {
					$last = max(0, $last - $per_page);
				}
				if ($start > $last) {
					$start = $last;
				}
				$sql .= 'LIMIT '.$start.','.$per_page;
				$result = $db->query($sql);
			}
			else {
				$sql .= 'LIMIT 0,'.$per_page;
				$result = $db->query($sql);
			}

			if ($num > 0) {
				$stocks = $result->rows();
			}

			$form['sql'] = $sql;
			$form_token = Session::temp_token('stock_', 900);
			$_SESSION[$form_token] = $form;
			$panel_buttons = new ArrayIterator;
					
			if ( $me->is_allowed_to('添加', 'stock') ) {
				$panel_buttons[] = [
					'url' => URI::url('!inventory/stock/add'),
					'text' => I18N::T('inventory', ''),
					'tip' => I18N::T('inventory', '添加存货'),
					'extra' => 'class="button button_add"'
				];
			}
			
			if ( $me->is_allowed_to('领用/归还', 'stock') || $me->is_allowed_to('代人领用/归还', 'stock')) {
				$panel_buttons[] = [
					'url' => URI::url('!inventory/use/'),
					'text' => I18N::T('inventory', ''),
					'tip' => I18N::T('inventory', '领用 / 归还'),
					'extra' => 'class="button button_scan"'
				];

			}
			$panel_buttons[] =Event::trigger('add_apply_button');

			if ( $me->is_allowed_to('导出','stocks') ) {
				
				$panel_buttons[]  = [
				//'url' => URI::url(),
				'tip' => I18N::T('inventory','导出Excel'),
				'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!inventory/index') .
						'" q-static="' . H(['type'=>'csv','form_token' => $form_token]) .
						'" class="button button_save "'

				];

				$panel_buttons[] = [
					//'url' => URI::url(),
					'tip' => I18N::T('inventory','打印'),
					'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!inventory/index') .
							'" q-static="' . H(['type'=>'print','form_token' => $form_token]) .
							'" class="button button_print "'
				];
			}
			$pagination = Widget::factory('pagination');
			$pagination->set([
								 'start' => $start,
								 'per_page' => $per_page,
								 'total' => $num,
								 ]);

            $columns = self::get_stock_field($form);
            $search_box = V('application:search_box', ['is_offset' => true,'top_input_arr'=>['ref_no','product_name'], 'columns' => $columns]);

			$this->layout->body->primary_tabs
				->select('stocks')
				->set('content', V('stocks', [
									   'stocks'=>$stocks,
									   'pagination'=>$pagination,
									   'form'=>$form,
									   'sort_asc'=>$sort_asc,
									   'sort_by'=>$sort_by,
									   'panel_buttons' => $panel_buttons,
                                       'search_box'=>$search_box,
                                       'columns'=>$columns
									   ]));
		}
	}


	function get_stock_field($form){

        $tag_names = [];
        if ($form['tags']) {
            foreach (json_decode($form['tags']) as $temp_tag_id => $temp_tag_name) {
                $tag_names[$temp_tag_id] = $temp_tag_name;
            }
        }

        $columns=[
            'ref_no' => [
                'title'=>I18N::T('inventory', '自定义编号'),
                'sortable' => TRUE,
                'nowrap' => TRUE,
                'align' => 'left',
                'filter'=>[
                    'form'=>V('inventory:stocks_table/filters/ref_no', ['ref_no'=>$form['ref_no']]),
                    'value' => $form['ref_no'] ? H($form['ref_no']) : NULL
                ],
            ],
            'product_name'=>[
                'title'=>I18N::T('inventory', '产品名称'),
                'sortable'=>TRUE,
                'filter'=>[
                    'form'=>V('inventory:stocks_table/filters/product_name', ['name'=>$form['product_name']]),
                    'value' => $form['product_name'] ? H($form['product_name']) : NULL
                ],
                'nowrap'=>TRUE
            ],
            'manufacturer'=>[
                'title'=>I18N::T('inventory', '生产商'),
                'sortable'=>TRUE,
                'filter'=>[
                    'form'=>V('inventory:stocks_table/filters/manufacturer', ['manufacturer'=>$form['manufacturer']]),
                    'value' => $form['manufacturer'] ? H($form['manufacturer']) : NULL
                ],
                'invisible'=>TRUE,
                'nowrap'=>TRUE
            ],
            'catalog_no'=>[
                'title'=>I18N::T('inventory', '目录号'),
                'filter'=>[
                    'form'=>V('inventory:stocks_table/filters/catalog_no', ['catalog_no'=>$form['catalog_no']]),
                    'value' => $form['catalog_no'] ? H($form['catalog_no']) : NULL
                ],
                'invisible'=>TRUE,
                'nowrap'=>TRUE
            ],
            'vendor'=>[
                'title'=>I18N::T('inventory', '供应商'),
                'sortable'=>TRUE,
                'filter'=>[
                    'form'=> V('inventory:stocks_table/filters/vendor', ['vendor'=>$form['vendor']]),
                    'value' => $form['vendor'] ? H($form['vendor']) : NULL
                ],
                'nowrap'=>TRUE
            ],
            'quantity'=>[
                'title'=>I18N::T('inventory', '存量'),
                'align'=>'right',
                'nowrap'=>TRUE
            ],
        ];

        if (Module::is_installed('extra')) {
            $columns += [
                '_type'=> [
                    'title'=> I18N::T('inventory', '类型'),
                    'align'=> 'right',
                    'nowrap'=> TRUE,
                    'filter'=> [
                        'form'=> V('inventory:stocks_table/filters/type', ['type'=> $form['_type']]),
                        'value'=> $form['_type'] != '-1' ? H($form['_type']) : NULL
                    ],
                ],
            ];
        }

        $columns += [
            'expire_status' => [
                'title' => I18N::T('inventory', '过期状态'),
                'align' => 'center',
                'nowrap' => TRUE,
                'filter'=> [
                    'form'=>Form::dropdown('expire_status', [0=>'--'] + I18N::T('inventory', Stock_Model::$expire_status), ($form['expire_status'] ? : 0)),
                    'value' => isset($form['expire_status']) ? I18N::T('inventory', Stock_Model::$expire_status[$form['expire_status'] ?: 0]) : NULL,
                ],	],
            'location'=>[
                'title'=>I18N::T('inventory', '存放位置'),
                'filter'=>[
                    'form'=> V('inventory:stocks_table/filters/location', ['location'=>$form['location']]),
                    'value' => $form['location'] ? H($form['location']) : NULL
                ]
            ],
            'barcode'=>[
                'title'=>I18N::T('inventory', '条形码'),
                'nowrap'=>TRUE,
                'align' => 'center',
                'invisible' => TRUE,
                'filter'=>[
                    'form'=>V('inventory:stocks_table/filters/barcode', ['barcode'=>$form['barcode']]),
                    'value' => $form['barcode'] ? H($form['barcode']) : NULL
                ],
            ],
            'status'=>[
                'title'=>I18N::T('inventory', '库存状态'),
                'invisible'=>TRUE,
                'filter'=> [
                    'form'=>Form::dropdown('status', [0=>'--'] + I18N::T('inventory', Stock_Model::$stock_status), (int) $form['status']),
                    'value' => isset($form['status']) ? I18N::T('inventory', Stock_Model::$stock_status[$form['status'] ?: 0]) : NULL,
                ],
            ],
            'tags'=>[
                'title'=>I18N::T('inventory', '标签'),
                'filter'=> [
                    'form'=>V('inventory:stocks_table/filters/tag', ['tag_values'=>$form['tags']]),
                    'value' => count($tag_names) ? join(', ', $tag_names) : NULL,
                ],
                'nowrap'=>TRUE,
                'invisible'=>TRUE,
            ],
            'rest'=>[
                'title'=>'操作',
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];

        return $columns;
    }


	function old_index() {
		/*
		NO.BUG#195(guoping.zhang@2010.12.05)
		*/
		if (!L('ME')->is_allowed_to('列表存货', 'lab')) {
			URI::redirect('error/401');
		}

		//多栏搜索
		$form = Lab::form();

		$selector = 'stock';

		if($form['catalog_no']){
			$catalog_no = Q::quote($form['catalog_no']);
			$selector .= "[catalog_no*=$catalog_no]";
		}

		if($form['name']){
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
		}
		if($form['location']){
			$location = Q::quote($form['location']);
			$selector .= "[location*=$location]";
		}
		if($form['barcode']){
			$barcode = Q::quote($form['barcode']);
			$selector .= "[barcode*=$barcode]";
		}
		if($form['lab']){
			$lab = O('lab', Q::quote($form['lab']));
			if ($lab->id) {
				$lab_selector = "lab[id={$lab->id}] ";
			}

		}
		if($form['manufacturer']){
			$manufacturer = Q::quote($form['manufacturer']);
			$selector .= "[manufacturer*=$manufacturer]";
		}
		if($form['vendor']){
			$vendor = Q::quote($form['vendor']);
			$selector .= "[vendor*=$vendor]";
		}

		//排序
		$sort_by = $form['sort'] ?: 'name';
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';

		switch($sort_by){
			case 'lab':
				if($lab_selector){
					$selector = $lab_selector .":sort(name {$sort_flag}) ".$selector;

				}
				else{
					$selector = "lab:sort(name {$sort_flag}) " . $selector;
				}
				break;
			case 'vendor':
			case 'manufacturer':
			case 'name':
				//在不排序 lab 的情况下，处理搜索 lab;
				$selector = $lab_selector . $selector;
				$selector .= ":sort({$sort_by} {$sort_flag})";
				break;
		}

		$pre_selectors = [];

		$group = O('tag', $form['group_id']);
		$root_name = Config::get('tag.inventory', '订单存货标签');
		$group_root = O('tag', ['root_id'=>0, 'name'=>$root_name]);

		if ($group->id && $group->root->id == $group_root->id) {
			$pre_selectors[] = $group;
		}
		else{
			$group = null;
		}

		if (count($pre_selectors)>0) $selector = '('.implode(',', $pre_selectors).') '.$selector;

		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);

		$stocks = Q($selector);

		$pagination = Lab::pagination($stocks, $start, $per_page);

		$this->layout->body->primary_tabs
			->select('index')
			->set('content', V('stocks',
									[
										'stocks'=>$stocks,
										'pagination'=>$pagination,
										'form'=>$form,
										'sort_asc'=>$sort_asc,
										'sort_by'=>$sort_by,
										'group'=>$group,
										'group_root'=>$group_root,
									]));

	}

	//选择完打印列，点击“确定”，执行该打印事件
	function _export_print($stocks,$form) {
		$valid_columns = Config::get('inventory.export_columns.shocks');
		$visible_columns = Input::form('columns');
		
		
		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$max_print_count = Config::get('print.max.print_count', 500);
		if($stocks->total_count() > $max_print_count){
			$form_token = Input::form('form_token');
			$form['@columns'] = $valid_columns;
			$_SESSION[$form_token] = $form;
		   
			$csv_link = I18N::T('inventory', '导出Excel');
			
			$return_url = I18N::T('inventory', '搜索条件');
			$title = I18N::T('inventory', '存货统计列表');
			$this->layout = V('stocks_excessive', [
				'max_print_count' => $max_print_count,
				'title' => $title, 
				'csv_link' => $csv_link,
				'return_url' => $return_url]);
		}
		else {
			$this->layout = V('inventory:stocks_print',[
				'stocks' => $stocks,
				'valid_columns' => $valid_columns,
				'form' => $form,		
			]);
		}
		
		//记录日志
		$me = L('ME');
		Log::add(strtr('[inventory] %user_name[%user_id]打印了存货列表', [
				'%user_name' => $me->name,
				'%user_id' => $me->id
				]),'journal');
			
	
	}

	static function stocks_csv_columns($column_name ,$stock) {
		switch ($column_name) {
			case 'product_name':
				$data = H($stock->product_name)?:'-';
				break;
			case 'ref_no':
				$data = H($stock->ref_no) ?: '-';
				break;
			case 'catalog_no':
				$data = H($stock->catalog_no)?:'-';
				break;
			case 'vendor':
				$data = H($stock->vendor)?:'-';
				break;
			case 'manufacturer':
				$data = H($stock->manufacturer)?:'-';
				break;
			case 'barcode':
				$data = H($stock->barcode)?:'-';
				break;
			case 'model':
				$data = H($stock->model)?:'-';
				break;
			case 'spec':
				$data = H($stock->spec)?:'-';
				break;
			case 'unit_price':
				$data = H($stock->unit_price)?:'-';
				break;
			//库存信息
			case 'quantity':
				$data = H($stock->quantity)?:'-';
				break;
			case 'location':
				$data = H($stock->location)?:'-';
				break;
			case 'status':			
				$status = [
					'1' => '不详',
					'2' => '充足',
					'3' => '紧张',
					'4' => '用罄',
				];
				$data = H($status[$stock->status])?:'-';
				break;
			case 'tags':
				$root = Tag_Model::root('inventory');
				$tags = (array) Q("$stock tag[root=$root]")->to_assoc('name','name');
             	$data = H(implode(',',$tags));								
				break;
			case 'note':
				$data = H($stock->note)?:'-';
				break;
		}
		return  $data;
	}
	
	//选择完导出的列，点击“确定”，执行该导出事件
	function _export_csv($stocks, $form) {
	
		$form_token = $form['form_token'];
	    $old_form = (array) $_SESSION[$form_token];
	    $new_form = (array) Input::form();
	    if (isset($new_form['columns'])) {
	        unset($old_form['columns']);
	    }

	    $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('inventory.export_columns.shocks');
		$visible_columns = $form['columns'] ? : $form['@columns'];
		
		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		
		$csv =new CSV('php://output','w');
		$title = [];
		foreach ($valid_columns as $p => $p_name) {
			$title[] = I18N::T('inventory',$valid_columns[$p]);
		}
		$csv->write($title);
		if (count($stocks)) {
			foreach ($stocks as $stock) {
				$data = [];
				if (array_key_exists('product_name', $valid_columns)) {
					$data[] = H($stock->product_name)?:'-';
				}
				if (array_key_exists('ref_no', $valid_columns)) {
					$data[]= H($stock->ref_no) ?: '-';
				}
				if (array_key_exists('catalog_no', $valid_columns)) {
					$data[] = H($stock->catalog_no)?:'-';
				}
				if (array_key_exists('vendor', $valid_columns)) {
					$data[] = H($stock->vendor)?:'-';
				}
				if (array_key_exists('manufacturer', $valid_columns)) {
					$data[] = H($stock->manufacturer)?:'-';
				}
				if (array_key_exists('barcode', $valid_columns)) {
					$data[] = H($stock->barcode)?:'-';
				}
				if (array_key_exists('model', $valid_columns)) {
					$data[] = H($stock->model)?:'-';
				}
				if (array_key_exists('spec', $valid_columns)) {
					$data[] = H($stock->spec)?:'-';
				}
				if (array_key_exists('unit_price', $valid_columns)) {
					$data[] = H($stock->unit_price)?:'-';
				}
                if (array_key_exists('type', $valid_columns)) {
                    $data[] = H($stock->type) ? : '-';
                }
				if (array_key_exists('quantity', $valid_columns)) {
					$data[] = H($stock->quantity)?:'-';
				}
				if (array_key_exists('location', $valid_columns)) {
					$data[] = H($stock->location)?:'-';
				}
				if (array_key_exists('status', $valid_columns)) {
					$status = [
						'1' => '不详',
						'2' => '充足',
						'3' => '紧张',
						'4' => '用罄',
					];
					$data[] = I18N::HT('inventory', $status[$stock->status]) ? : '-';
				}
				if (array_key_exists('tags', $valid_columns)) {
					$root = Tag_Model::root('inventory');
					$tags = (array) Q("$stock tag[root=$root]")->to_assoc('name','name');
					$data[] = H(implode(',',$tags))?:'-';
	             }						
				if (array_key_exists('note', $valid_columns)) {
					$data[] = H($stock->note)?:'-';
				}
				$csv->write($data);
			}
		
		}
		
		$csv->close();
		//记录日志
		$me = L('ME');
		Log::add(strtr('[inventory] %user_name[%user_id]以CSV导出了存货列表', [
				'%user_name' => $me->name,
				'%user_id' => $me->id
				]),'journal');
	}

}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_add_child_tr_click() {
		$form_token = Input::form('form_token');
		if ($form_token && isset($_SESSION[$form_token])) {
			$form = $_SESSION[$form_token];
			$stock_id = Input::form('stock_id');
			$stock = O('stock', $stock_id);

			if ($form['tags']) {
				$root = Tag_Model::root('inventory');
				$tag_names = @json_decode($form['tags'], TRUE);
				foreach ($tag_names as $id => $name) {

					$tag = O('tag', ['name'=>$name,'root'=>$root]);

					if ($tag->id) {
						$pre_selector[] = $tag;
					}
				}
			}
			if ($form['tag']) {
				$root = Tag_Model::root('inventory');
				$tag = O('tag', ['id'=>$form['tag'], 'root'=>$root]);
				if ($tag->id) {
					$pre_selector[] = $tag;
				}
			}

			$selector = "stock[is_collection=0][parent={$stock}]";
			if(trim($form['ref_no'])){
				$ref_no = Q::quote($form['ref_no']);
				$selector .= "[ref_no*=$ref_no]";
			}
			if($form['barcode']){
				$barcode = Q::quote($form['barcode']);
				$selector .= "[barcode=$barcode]";
			}
			if($form['product_name']){
				$product_name = Q::quote($form['product_name']);
				$selector .= "[product_name*=$product_name]";
			}
			if($form['manufacturer']){
				$manufacturer = Q::quote($form['manufacturer']);
				$selector .= "[manufacturer*=$manufacturer]";
			}
			if($form['catalog_no']){
				$selector .= '[catalog_no*='.Q::quote($form['catalog_no']).']';
			}
			if($form['vendor']){
				$vendor = Q::quote($form['vendor']);
				$selector .= "[vendor*=$vendor]";
			}
			if($form['location']){
				$location = Q::quote($form['location']);
				$selector .= "[location*=$location]";
			}
			if($form['expire_status']){
				$expire_status = Q::quote($form['expire_status']);
				$selector .= "[expire_status=$expire_status]";
			}
			if($form['status']){
				$status = Q::quote($form['status']);
				$selector .= "[status=$status]";
			}

			$sort_by = $form['sort'];
			$sort_asc = $form['sort_asc'];
			$sort_flag = $sort_asc ? 'A':'D';

			switch($sort_by){
				case 'product_name':
					$selector .= ":sort(quantity D, parent_id D, product_name {$sort_flag})";
					break;
				case 'manufacturer':
					$selector .= ":sort(quantity D, parent_id D, manufacturer {$sort_flag})";
					break;
				case 'vendor':
					$selector .= ":sort(quantity D, parent_id D, vendor {$sort_flag})";
					break;
				case 'location':
					$selector .= ":sort(quantity D, parent_id D, location {$sort_flag})";
					break;
				case 'status':
					$selector .= ":sort(quantity D, parent_id D, status {$sort_flag})";
					break;
				case 'ref_no':
					$selector .= ":sort(quantity D, parent_id D, ref_no {$sort_flag})";
					break;
				default:
					$selector .= ":sort(quantity D, parent_id D, mtime {$sort_flag})";
			}


			if (count($pre_selector)){
				$selector = '('.implode(', ', $pre_selector).') ' . $selector;
			}
			$page = Input::form('page');
			$per_page = 10;
			$child_stocks = Q($selector)->limit($page, $per_page+1);
			$stock_num = count($child_stocks);
			if ($stock_num > 0) {
				$load_end = FALSE;
				if ($stock_num <= $per_page) {
					$load_end = TRUE;
				}
				$id = 'load_view_'.$stock_id;
				$next_page = $page + $per_page;
			    Output::$AJAX['#'. $id] = [
			        'data'=> (string) V('stocks_table/data/child_tr', ['child_stocks'=>$child_stocks, 'stock'=>$stock, 'page'=>$next_page, 'per_page'=>$per_page, 'load_end'=>$load_end]),
			        'mode'=>'replace',
			        'page'=> $next_page,
			    ];
			}
		}
		else {
			JS::refresh();
		}
	}

		//导出、打印。点击导出、打印链接会触发该事件
	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('inventory.export_columns.shocks');
		
		if ($type=='csv') {
			$title = I18N::T('inventory','请选择要导出Excel的列');		
		}
		elseif ($type=='print')
		{
			$title = I18N::T('inventory', '请选择要打印的列');
		}
		JS::dialog(V('export_stocks_form',[
			'form_token' => $form_token,
			'columns' => $columns,
			'type' => $type
		]),[
			'title' => I18N::T('inventory',$title)
		]);

	}

    function index_export_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('inventory', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;
                
        $selector = 'stock[!is_collection]';
        if(trim($form['ref_no'])){
            $ref_no = Q::quote($form['ref_no']);
            $selector .= "[ref_no*=$ref_no]";
        }
        if($form['barcode']){
            $barcode = Q::quote($form['barcode']);
            $selector .= "[barcode=$barcode]";
        }
        if($form['product_name']){
            $product_name = Q::quote($form['product_name']);
            $selector .= "[product_name*=$product_name]";
        }
        if($form['manufacturer']){
            $manufacturer = Q::quote($form['manufacturer']);
            $selector .= "[manufacturer*=$manufacturer]";
        }
        if($form['catalog_no']){
            $selector .= '[catalog_no*='.Q::quote($form['catalog_no']).']';
        }
        if($form['vendor']){
            $vendor = Q::quote($form['vendor']);
            $selector .= "[vendor*=$vendor]";
        }
        if($form['location']){
            $location = Q::quote($form['location']);
            $selector .= "[location*=$location]";
        }
        if($form['expire_status']){
            $expire_status = Q::quote($form['expire_status']);
            $selector .= "[expire_status=$expire_status]";
        }
        if($form['status']){
            $status = Q::quote($form['status']);
            $selector .= "[status=$status]";
        }

        if ($form['_type'] && $form['_type'] != -1) {
            $_type = Q::quote($form['_type']);
            $selector .= "[type={$_type}]";
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';

        switch($sort_by){
            case 'product_name':
                $selector .= ":sort(quantity D, parent_id D, product_name {$sort_flag})";
                break;
            case 'manufacturer':
                $selector .= ":sort(quantity D, parent_id D, manufacturer {$sort_flag})";
                break;
            case 'vendor':
                $selector .= ":sort(quantity D, parent_id D, vendor {$sort_flag})";
                break;
            case 'location':
                $selector .= ":sort(quantity D, parent_id D, location {$sort_flag})";
                break;
            case 'status':
                $selector .= ":sort(quantity D, parent_id D, status {$sort_flag})";
                break;
            case 'ref_no':
                $selector .= ":sort(quantity D, parent_id D, ref_no {$sort_flag})";
                break;
            default:
                $selector .= ":sort(quantity D, parent_id D, mtime {$sort_flag})";
        }


        if (count($pre_selector)){
            $selector = '('.implode(', ', $pre_selector).') ' . $selector;
        }

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

		if ('csv' == $type) {
			$pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('inventory', '导出等待')
            ]);
		}
    }

	function _export_csv($selector, $form, $file_name) {
		$me = L('ME');
		$valid_columns = Config::get('inventory.export_columns.shocks');
		$visible_columns = $form['columns'] ? : $form['@columns'];
		
		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		
		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

		Log::add(strtr('[inventory] %user_name[%user_id]以CSV导出了存货列表', [
				'%user_name' => $me->name,
				'%user_id' => $me->id
				]),'journal');

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_inventory export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;
        return $pid;
	}
}


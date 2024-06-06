<?php

class Stock {

	static function setup_people($e) {
		Event::bind('profile.follow.tab', [__CLASS__, 'index_follow_stock_tab'], 100, 'stock');
		Event::bind('profile.follow.content', [__CLASS__, 'index_follow_stock_content'], 100, 'stock');		
	}
	
	static function index_follow_stock_tab($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');
		
		/*
		NO.TASK#274(guoping.zhang@2010.11.26)
		应用权限判断新规则
		*/
		if ($me->is_allowed_to('列表关注的存货', $user)){
			$count = $user->get_follows_count('stock');
			if ($count > 0) {
				$tabs
				->add_tab('stock', [
					'url'=> $user->url('follow.stock'),
					'title'=>I18N::T('inventory', '存货 (%d)', ['%d'=>$count]),
				]);
			}
		}
	}

	static function index_follow_stock_content($e, $tabs) {
		$user = $tabs->user;
		$follows = $user->followings('stock');
		$pagination = Lab::pagination($follows, Input::form('st'), 20);

		$tabs->content =  V('inventory:follow/stocks', [
								   'follows'=>$follows,
								   'pagination'=>$pagination,
									'tabs'=>$tabs,
								]);
	}

	static function on_enumerate_user_perms($e, $user, $perms) {
		if (!$user->id) return;
        //取消现默认赋予给pi的权限
//		if (Q("$user<pi lab")->total_count()) {
//			$perms['管理负责实验室存货'] = 'on';
//		}
	}

	static function setup_update() {
		Event::bind('update.index.tab', 'Stock::_update_index_tab');
	}
	
	static function user_ACL($e, $user, $perm, $object, $options) {
		if ($user->access('管理存货')) {
			$e->return_value = TRUE;
		}
		if (in_array($perm, ['列表文件', '下载文件'])) {
			$e->return_value = TRUE;
		}
		return FALSE;
	}
	
	static function _update_index_tab($e, $tabs) {
		$tabs->add_tab('stock', [
			'url'=>URI::url('!update/index.stock'),
			'title'=>I18N::T('inventory', '存货更新')
		]);
	}
	
	static function get_update_parameter($e, $object, array $old_data = [], array $new_data = []) {
		if ($object->name() != 'stock' || !$old_data) return;
		$difference = array_diff_assoc($new_data,$old_data);
	  	$old_difference = array_diff_assoc($old_data, $new_data);
	  	$data = $e->return_value;
	  	
	  	if(!count($difference)) {
	  		return;
	  	}
	  	$delta = [];
	  	$subject = L('ME');
  		$delta['subject'] = $subject;
	  	$delta['object'] = $object;
	  	$delta['action'] = 'edit_info';
	  	$arr = array_keys($difference);
	  	if (in_array('lab', $arr)) {
	  		$lab = $difference['lab'];
	  		if ($lab->id) {
	  			$difference['lab'] = Markup::encode_Q($lab);
	  		}
	  		else {
	  			unset($difference['lab']);
	  		}
	  	}
/*
 * location在update中不显示，注释解决rui.ma@geneegroup.com 2012.02.03
 *          if (in_array('location', $arr)) {
 *              $tag = $difference['location'];
 *              $difference['location'] = $tag->name;
 *        }
 */


	  	$delta['new_data'] = $difference;
	  	$delta['old_data'] = $old_difference;
  		
  		$key = Misc::key((string)$subject, $delta['action'], (string)$object);
  		$data[$key] = (array)$data[$key];
  		
  		Misc::array_merge_deep($data[$key], $delta);
  		
  		$e->return_value = $data;		
	}
	
	static $properties = [
		'manufacturer'=>'生产商',
		'vendor'=>'供应商',
		'product_name'=>'存货名称',
		'catalog_no'=>'目录号',
		'unit_price'=>'单价',
		'quantity'=>'数量',
		'unit'=>'单位',
		'note'=>'备注',
		'lab'=>'实验室',
		'location'=>'存放位置',
		'barcode'=>'条形码'
	];
	
	static function get_update_message($e, $update) {
		if ($update->object->name() != 'stock') return;
		$me = L('ME');
		$subject = $update->subject->name;
		$old_data = json_decode($update->old_data, TRUE);
		$object = H($old_data['product_name'] ? $old_data['product_name'] : $update->object->product_name);
		/*
		if ($me->id == $update->subject->id) {
			$subject = I18N::T('inventory', '我');
		}
		*/

		$config = 'inventory.stock.info.msg.model';
		$opt = Lab::get($config, Config::get($config));
		$msg = I18N::T('inventory', $opt['body'], [
						'%subject'=>URI::anchor($update->subject->url(), $subject, 'class="blue label"'),
						'%date'=>'<strong>'.Date::fuzzy($update->ctime, 'TRUE').'</strong>',
						'%stock'=>URI::anchor($update->object->url(), $object, 'class="blue label"')
						]);
		$e->return_value = $msg;
		return FALSE;
	}
	
	static function get_update_message_view($e, $update) {
        $e->return_value = V('inventory:update/stock_show_msg', ['update'=>$update]);
		return FALSE;
	}

	static function before_labnote_edit($e, $note) {
		$e->return_value =  V('inventory:stock/note_stock', ['note'=>$note]);
	}

    static function update_stock_status($e, $stock) {

        if ($stock->auto_update_status)  {

        	$inadequate_percent = $stock->percent_inadequate ?: Config::get('stock.default_inadequate_percent');
        	$adequate_percent = $stock->percent_adequate ?: Config::get('stock.default_adequate_percent');
        	$summation = $stock->summation;
        	$adequate = ($adequate_percent * $summation / 100 );
        	$inadequate = ($inadequate_percent * $summation / 100 );

            if ($stock->quantity >= $adequate && $stock->quantity <= $summation) {
                $stock->status = Stock_Model::ADEQUATE; 
            }
            elseif ($stock->quantity < $adequate && $stock->quantity >= $inadequate) {
                $stock->status = Stock_Model::INADEQUATE; 
            }
            elseif ($stock->quantity < $inadequate) {
                $stock->status = Stock_Model::EXHAUSTED;
            }
            else {
                $stock->status = Stock_Model::UNKNOWN; 
            }
            $stock->save();
        }
    }

	static function on_stock_deleted($e, $stock) {
		if ($stock->id) {
			Q("follow[object={$stock}]")->delete_all();
            Q("stock_use[stock=$stock]")->delete_all();
		}
	}

    static function on_stock_use_saved($e, $stock_use) {
        Event::trigger('update_stock_status', $stock_use->stock);
    }

    static function inventory_newsletter_content($e, $user) {

		$dtstart = strtotime(date('Y-m-d')) - 86400;
		$dtend = strtotime(date('Y-m-d'));
		$templates = Config::get('newsletter.template');
		$db = Database::factory();

		$template = $templates['finance']['stock_use_user_count'];
		$sql = "SELECT COUNT(*) FROM (SELECT DISTINCT user_id FROM `stock_use` WHERE ctime>%d AND ctime<%d) as total";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('inventory:newsletter/stock_use_user_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['stock_use_count'];
		$sql = "SELECT COUNT(*) FROM `stock_use` WHERE ctime>%d AND ctime<%d AND quantity>0";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('inventory:newsletter/stock_use_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['stock_return_count'];
		$sql = "SELECT SUM(quantity) FROM `stock_use` WHERE ctime>%d AND ctime<%d AND quantity<0";
		$count = abs($db->value($sql, $dtstart, $dtend));
		if ($count > 0) {
			$str .= V('inventory:newsletter/stock_return_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['stock_inadequate'];
		$sql = "SELECT COUNT(*) FROM `stock` WHERE status=".Stock_Model::INADEQUATE;
		$count = $db->value($sql);
		if ($count > 0) {
			$str .= V('inventory:newsletter/stock_inadequate', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['stock_exhausted'];
		$sql = "SELECT COUNT(*) FROM `stock` WHERE status=".Stock_Model::EXHAUSTED;;
		$count = $db->value($sql);
		if ($count > 0) {
			$str .= V('inventory:newsletter/stock_exhausted', [
				'count' => $count,
				'template' => $template,
			]);
		}
		//存货过期和即将过期的newsletter
		$template = $templates['finance']['stock_expired'];
		$sql = "SELECT COUNT(*) FROM `stock` WHERE is_collection=0 AND expire_status=%d";
		$count = $db->value($sql, Stock_Model::$has_expired);
		if ( $count > 0 ) {
			$str .= V('inventory:newsletter/stock_expired_count', [
				'count' => $count,
				'template' => $template,
			]);
		}
		
		$template = $templates['finance']['stock_almost_expired'];
		$sql = "SELECT COUNT(*) FROM `stock` WHERE is_collection=0 AND expire_status=%d";
		$count = $db->value($sql, Stock_Model::$almost_expired);
		if ( $count > 0 ) {
			$str .= V('inventory:newsletter/stock_almost_expired_count', [
				'count' => $count,
				'template' => $template,
			]);
		}
			
		if (strlen($str) > 0) {
			$view = V('inventory:newsletter/view', [
					'str' => $str,
				]);
			$e->return_value .= $view;
		}
    }
    
    static function markup_name($e, $stock) {
	    $e->return_value = $stock->product_name;
	    return TRUE;
    }
    
    //根据传递的存货和比较的时间，决定存货的过期状态
    static function get_stock_expire_status($stock, $rel_time = '') {
    	/*When the initial status of the stock is never_expired, but someone wants to change it, it can not be changed*/
    	//if ( $stock->expire_status == Stock_Model::$never_expired ) return Stock_Model::$never_expired;
    	
    	if ( !$rel_time ) {
	    	$rel_time = Date::time();
    	}
    	
	    if ( $stock->expire_time ) {
		    $expire_time = $stock->expire_time;
		    if ( $expire_time <= $rel_time ) {
			    return Stock_Model::$has_expired;
		    }
		    $expire_notice_time = $stock->expire_notice_time;

		    if ( ($expire_time - Date::get_day_end($rel_time)) <= $expire_notice_time  ) {
			    return Stock_Model::$almost_expired;
		    }
		    else {
			    return Stock_Model::$not_expired;
		    }
	    }
	    else {
			return Stock_Model::$never_expired;
	    }
    }

    static function type_rename($e, $extra, $old, $new) {
        if ($extra->type == 'stock') {
            $stocks = Q("stock[type={$old}]");
            if ($stocks->total_count()) {
                foreach($stocks as $s) {
                    $s->type = $new;
                    $s->save();
                }
            }
        }
    }

    static function type_delete($e, $extra, $name) {

        if ($extra->type == 'stock') {
            $stocks = Q("stock[type={$name}]");
            if ($stocks->total_count()) {
                foreach($stocks as $s) {
                    $s->type = '';
                    $s->save();
                }
            }
        }
    }
    
}

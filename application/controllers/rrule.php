<?php

class Rrule_AJAX_Controller extends AJAX_Controller {
	
	function index_rtype_change() {
		$form = Form::filter(Input::form());
		
		$flexform_index = $form['flexform_index'] ?: 0;
		$rrule_uniqid = $form['rrule_uniqid'];
						
		$item_rel = '#'.$rrule_uniqid;

		$switch = $form['rtype'][$flexform_index];
		
		switch ($switch) {
			
			case TM_RRule::RRULE_DAILY:   //每天
				$path = 'application:widgets/rule_date/daily/day';
				break;
			
			case TM_RRule::RRULE_WEEKLY:  //每周
				$path = 'application:widgets/rule_date/weekly/week';
				break;
				
			case TM_RRule::RRULE_MONTHLY:  //每月
				$path = 'application:widgets/rule_date/monthly/month';
				break;
				
			case TM_RRule::RRULE_YEARLY:   //每年
				$path = 'application:widgets/rule_date/yearly/year';
				break;
			
			default:
				$path = '';
				break;
		}
		
		if ($path) {
			$view = V($path, ['flexform_index'=>$flexform_index]);
		}
		else {
			$view = '';
		}
		
		Output::$AJAX["$item_rel"] = [
			'data'=> (string) $view,
			'mode'=>'html',
		];
		
	}
	
	function index_wtrtype_change() {
		$form = Form::filter(Input::form());
		
		$flexform_index = $form['flexform_index'] ?: 0;
		$rrule_uniqid = $form['rrule_uniqid'];
						
		$item_rel = '#'.$rrule_uniqid;

		$switch = $form['rtype'][$flexform_index];
		
		switch ($switch) {
			case WT_RRule::RRULE_DAILY:   //每天
				break;
			case WT_RRule::RRULE_WEEKDAY:	//每工作日
				$path = 'application:widgets/rule_date/weekly/wt_weekday';
				break;
			case WT_RRule::RRULE_WEEKEND_DAY:		//每周末
				$path = 'application:widgets/rule_date/weekly/wt_weekend';
				break;
			case WT_RRule::RRULE_WEEKLY:  //每周
				$path = 'application:widgets/rule_date/weekly/wt_week';
				break;
				
			case WT_RRule::RRULE_MONTHLY:  //每月
				$path = 'application:widgets/rule_date/monthly/wt_month';
				break;
			case WT_RRule::RRULE_YEARLY:   //每年
				$path = 'application:widgets/rule_date/yearly/wt_year';
				break;
			default:
				$path = '';
				break;
		}
		
		if ($path) {
			$view = V($path, ['flexform_index'=>$flexform_index]);
		}
		else {
			$view = '';
		}
		
		Output::$AJAX["$item_rel"] = [
			'data'=> (string) $view,
			'mode'=>'html',
		];
		
	}

	function index_monthly_type_change() {
		
		$form = Form::filter(Input::form());
		$flexform_index = $form['flexform_index'] ?: 0;
		$monthly_uniqid = $form['monthly_uniqid'];
			
		$item_rel = '#'.$monthly_uniqid;
		
		$switch = $form['monthly_type'][$flexform_index];
		
		switch ($switch) {
			case 'day':
				
				$view = V('application:widgets/rule_date/monthly/month_day', ['flexform_index'=>$flexform_index]);
				Output::$AJAX["$item_rel"] = [
					'data'=> (string) $view,
					'mode'=>'html',
				];
				
				break;
			case 'week':
				
				$view = V('application:widgets/rule_date/monthly/month_week', ['flexform_index'=>$flexform_index]);
				Output::$AJAX["$item_rel"] = [
					'data'=> (string) $view,
					'mode'=>'html',
				];
								
				break;
			default:
				Output::$AJAX["$item_rel"] = [
					'data'=> '',
					'mode'=>'html',
				];
				break;
		}
	}
	
	function index_yearly_type_change() {
		$form = Form::filter(Input::form());
		$flexform_index = $form['flexform_index'] ? $form['flexform_index'] : 0;
		$yearly_uniqid = $form['yearly_uniqid'];
		
		$item_rel = '#'.$yearly_uniqid;
		
		$switch = $form['yearly_type'][$flexform_index];
		
		switch ($switch) {
			case 'day':
				
				$view = V('application:widgets/rule_date/yearly/year_month_day', ['flexform_index'=>$flexform_index]);
				Output::$AJAX["$item_rel"] = [
					'data'=> (string) $view,
					'mode'=>'html',
				];
				
				break;
			case 'week':
				
				$view = V('application:widgets/rule_date/yearly/year_month_week', ['flexform_index'=>$flexform_index]);
				Output::$AJAX["$item_rel"] = [
					'data'=> (string) $view,
					'mode'=>'html',
				];
				
				break;
			default:
				
				Output::$AJAX["$item_rel"] = [
					'data'=> '',
					'mode'=>'html',
				];
				
				break;
		} 
	}

	/*
	  NO.TASK#262 (xiaopei.li@2010.11.20)
	*/
	function index_cal_rtype_change() {
		 $form = Form::filter(Input::form());
		 $rrule_uniqid = $form['rrule_uniqid'];
		 $item_rel = '#'.$rrule_uniqid;
		 $switch = $form['cal_rtype'];
		 switch ($switch) {
		 case TM_RRule::RRULE_DAILY:   //每天
			  $path = 'application:widgets/cal_rule_date/daily/day';
			  break;
		 case TM_RRule::RRULE_WEEKLY:  //每周
			  $path = 'application:widgets/cal_rule_date/weekly/week';
			  break;
		 default:
			  $path = '';
			  break;
		 }
		 if ($path) {
			  $view = V($path);
		 }
		 else {
			  $view = '';
		 }
		 Output::$AJAX["$item_rel"] = [
			  'data'=> (string) $view,
			  'mode'=>'html',
			  ];
	}

}


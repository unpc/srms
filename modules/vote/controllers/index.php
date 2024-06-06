<?php 

class Index_Controller extends Base_Controller {

	function index() {
		$form = Lab::form();
		$start = $form['start'] ? $form['start'] : 0;
		$sort_by = $form['sort'] ? $form['sort'] : 'ctime';
		
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A' : 'D';
		
		$selector = "vote_activity";

		
		if ( $form['creater'] != null ) {
			$creater = Q::quote($form['creater']);		
			$selector = "(user[name*=".$creater."|name_abbr*=".$creater."]) vote_activity.creater";
		}
		
		if ( $form['topic'] !=null ) {
			$topic = Q::quote($form['topic']);
			$selector .= "[topic*={$topic}]";		
		}
		
		
		switch ($sort_by) {
			case 'ctime' :
				$selector .= ":sort(ctime $sort_flag)";
				break;
			case 'dtstart':
				$selector .= ":sort(dtstart $sort_flag)";
				break;
			case 'dtend':
				$selector .= ":sort(dtend $sort_flag)";
				break;
			default :
				$selector .= ":sort(ctime D)";
				break;
		}	
			
		$activities = Q($selector);	
		$start = (int) $form['st'];
		$per_page = 10;
		$start = $start - ($start % $per_page);
		$pagination = Lab::pagination($activities, $start, $per_page);		
		

	
		$this->layout->body->primary_tabs
			->select($tabs)
			->content=V('vote:index',[
									'form' => $form,
									'pagination' => $pagination,
									'st' => $start,
									'activities' => $activities,
			
			]);
			
	}
	
	function add() {
	
		$me = L('ME');
		$form = Form::filter(Input::form());

		if ( $form['submit'] ) {		
		
			$form->validate('topic','not_empty',I18N::T('vote','投票主题不能为空！'));
			
			if ( $form['btime'] >= $form['etime'] ) {
				$form->set_error('btime', I18N::T('vote', '投票开始时间应小于投票截止时间！'));
			}
			
			if ( count($form['special_tags'])<2 ) {
				$form->set_error('special_tags',I18N::T('vote','每个投票活动至少有两个选项！'));
			}
			else {
				foreach ( $form['special_tags'] as $special_tag ) {
		
					if ( is_null($special_tag) || empty($special_tag) || trim($special_tag)=='' ) {
						 $form->set_error('special_tags',I18N::T('vote','请不要添加空选项！'));
						 break;
					}
					
				}
				
			}
			
			if ( $form->no_error ) {
				$vote_activity = O('vote_activity');
				$vote_activity->creater = $me;
				$vote_activity->topic = trim( $form['topic'] );
				$vote_activity->radio = $form['radio'];
				$vote_activity->dtstart = $form['btime'];
				$vote_activity->dtend = $form['etime'];
				$vote_activity->remark = trim( $form['remark'] );
				$vote_activity->choices = json_encode($form['special_tags']);
				echo $vote_activity->url(NULL, NULL, NULL, 'activity');
				if ( $vote_activity->save() ) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vote','添加成功!'));	
					URI::redirect($vote_activity->url(NULL, NULL, NULL, 'edit'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('vote','添加失败！'));
				}				
			
			}
		
			
		}
		
		
		if ( !$me->is_allowed_to('创建','vote_activity') ) URI::redirect('error/401');
		$this->layout->body->primary_tabs
			->add_tab('add',[
				'url' => URI::url('!vote/add'),
				'title' => I18N::T('vote','发起投票'),
			])
			->select('add')
			->content = V('vote:add',['form' => $form]);
			
	}
	
	function edit($activity_id=0) {
		$activity = Q('vote_activity#'.$activity_id);
		
		$vote_activity = O('vote_activity',$activity_id);
	
	
	
		$form = Form::filter(Input::form());
		if ( $form['submit'] ) {		
		
			$form->validate('topic','not_empty',I18N::T('vote','投票主题不能为空！'));
			
			if ( $form['btime'] >= $form['etime'] ) {
				$form->set_error('btime', I18N::T('vote', '投票开始时间应小于投票截止时间！'));
			}
			
			if ( count($form['special_tags'])<2 ) {
				$form->set_error('special_tags',I18N::T('vote','每个投票活动至少有两个选项！'));
			}
			else {
				foreach ( $form['special_tags'] as $special_tag ) {
		
					if ( is_null($special_tag) || empty($special_tag) || trim($special_tag)=='' ) {
						 $form->set_error('special_tags',I18N::T('vote','请不要添加空选项！'));
						 break;
					}
					
				}
				
			}

			
			if ( $form->no_error ) {
				$vote_activity->topic = $form['topic'];
				$vote_activity->radio = $form['radio'];
				$vote_activity->dtstart = $form['btime'];
				$vote_activity->dtend = $form['etime'];
				$vote_activity->remark = $form['remark'];
				$vote_activity->choices = json_encode($form['special_tags']);
									
				if ( $vote_activity->save() ) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vote',' 更新成功!'));	
				
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('vote','更新失败！'));
				}				
			//URI::redirect($happyhour->url(NULL, NULL, NULL, 'activites'));
			}
		
			
		}
		$breadcrumb = [
			[
				'url' => $vote_activity->url(NULL,NULL,NULL,'activity'),
				'title' => I18N::T('vote', '%topic', ['%topic' => H($vote_activity->topic)]),

			],
			[
				'url' => $vote_activity->url(NULL,NULL,NULL,'edit'),
				'title' => I18N::T('vote', '修改'),
			
			]
		];
		$this->layout->body->primary_tabs
			->add_tab('edit',[
				'*' => $breadcrumb
			])
			 ->select('edit')
			 ->content = V('vote:edit', [
			 				'form' => $form,
			 				'activity' =>  $activity,
			 				'choices' => json_decode($activity->choices)
							
						]);	
						
								
		
	}
	
}
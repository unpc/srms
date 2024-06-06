<?php

class Vote_Controller extends Base_Controller {

	function index() {
	
	}
	
	function vote($activity_id) {			
		$me = L('ME');	
				
		$selector = 'vote_activity#'.$activity_id;
		$vote_activity = Q($selector);
		
		$choices = json_decode($vote_activity->choices);
		
		$form = Form::filter(Input::form());
		
		$submit = true;//决定是否进入提交表的if块

		//检查投票是否已过期限
		$cur_time = time();	
		if ( $cur_time<$vote_activity->dtstart ) {
			$form->set_error('early',I18N::T('vote','投票尚未开始，您不能投票！'));
			$submit = false;
		}
		elseif ( $cur_time>$vote_activity->dtend ) {
			$form->set_error('expire',I18N::T('vote','已经超过了投票期限，您不能再投票了！'));
			$submit = false;
		}
		
		if ( $form['submit'] && $submit ) {			
			if ( !isset($form['choice']) ) {
				$form->set_error('choice',I18N::T('vote','您没有选择任何选项!'));
			}
						
			if ( $form->no_error ) {
				//检查是否已经投票,若已经投票则删除所投的票
				$check_selector = 'vote_behavior[creater_id='.$me->id.'][vote_activity_id='.$activity_id.']';
				$vote_records = Q($check_selector);
				$has_vote = FALSE;
				if ( (int)$vote_records->total_count() ) {
					 foreach ( $vote_records as $vote_record ) {
					 	$vote_record->delete();
					 }
					 $has_vote = TRUE;
				}
				$flag = 0;
				foreach ( $form['choice'] as $choice ) {
					 $vote_behavior = O('vote_behavior');
					 $vote_behavior->creater = $me;
					 $vote_behavior->vote_activity = $vote_activity;
					 $vote_behavior->choice = $choice;
					 $vote_behavior->save();
					 $flag++;
					 
				}	
				
				if ( $flag==count($form['choice']) ) {
					$has_vote ? Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vote','修改成功!')) : Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vote','投票成功!'));
			
				}
				else {
					Lab::messaage(Lab::MESSAGE_ERROR,I18N::T('vote','投票失败!'));
				}
		
			}
		
		}
		
		//统计投票，已投选项为从数据库查出来的数据，未投向其票数为0
		$vote_choices = (array)json_decode($vote_activity->choices);
		$db = Database::factory();
		$sql = 'select count(*) votecount,choice from vote_behavior where vote_activity_id = %d group by choice';
		$execute = $db->query($sql,$activity_id);
		$result = [];
		if ( $execute ) {
			$results = $execute->rows();
		}

		$vote_result = [];
		$has_vote = false;
		foreach ( $vote_choices as $choice) {
			$has_vote = false;
			foreach ($results as $result) {
				if ( $choice == $result->choice) {
					$vote_result[$choice] = $result->votecount;
					$has_vote = true;
					break;
					
				} 
			}
			if ( !$has_vote) {
				$vote_result[$choice] = 0;
			}
		}

	
		$this->layout->body->primary_tabs
			->add_tab('vote',[
				'url' => URI::url(),
				'title' => I18N::T('vote','请投票吧'),

			])->select('vote')
			->content =V('vote:vote',[
				'activity' => $vote_activity,
				'choices' => $choices,
				'form' => $form,
				'submit' => $submit,
				'vote_result' => $vote_result
			]);
	}
}
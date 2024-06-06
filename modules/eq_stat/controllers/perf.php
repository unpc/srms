<?php

class Perf_Controller extends Base_Controller {
	
	function index($id=0, $tab='') {
		$perf = O('eq_perf', $id);
		$me = L('ME');
		if (!$perf->id) URI::redirect('error/404');
		if (!$me->is_allowed_to('查看', $perf)) URI::redirect('error/401');
		$this->layout->body->primary_tabs
			->add_tab('view', [
					'url'=>$perf->url(),
					'title'=>H($perf->name),
				])
			->select('view');
		
		$this->add_css('preview');
		$this->add_js('preview');
		
		$content = V('perf/view');
		$content->perf = $perf;
		
		$content->secondary_tabs
			= Widget::factory('tabs')
				->set('perf', $perf)
				->tab_event('perf.index.tab')
				->content_event('perf.index.tab.content');
				
		$content->secondary_tabs->select($tab);

		$this->layout->body->primary_tabs->content = $content;
	}
	
	function edit($id=0, $tab='info') {
		$perf = O('eq_perf', $id);
		$me = L('ME');
		if (!$perf->id) URI::redirect('error/404');
		if (!$me->is_allowed_to('修改', $perf)) URI::redirect('error/401');

        $breadcrumb = [
            [
                'url'=> $perf->url(),
                'title'=> H($perf->name),
            ],
            [
                'url'=> $perf->url(NULL, NULL, NULL, 'edit'),
                'title'=> I18N::T('eq_stat', '修改'),
            ],
        ];

		$this->layout->body->primary_tabs
			->add_tab('edit', ['*'=> $breadcrumb])
			->select('edit');

		$content = V('perf/edit')->set('perf', $perf);

		Event::bind('perf.edit.content', [$this, '_edit_info'], 0, 'info');
		Event::bind('perf.edit.content', [$this, '_edit_rating'], 0, 'rating');
		Event::bind('perf.edit.content', [$this, '_edit_stat'], 0, 'stat');

		$content->secondary_tabs
				= Widget::factory('tabs')
					->set('class', 'secondary_tabs')
					->set('perf', $perf)
					->tab_event('perf.edit.tab')
					->content_event('perf.edit.content')
					->add_tab('info', [
						'url'=> $perf->url('info', NULL, NULL, 'edit'),
						'title'=>I18N::T('eq_stat', '基本信息'),
					])
					->add_tab('rating', [
						'url'=> $perf->url('rating', NULL, NULL, 'edit'),
						'title'=>I18N::T('eq_stat', '用户评分'),
					])
					->add_tab('stat', [
						'url'=> $perf->url('stat', NULL, NULL, 'edit'),
						'title'=>I18N::T('eq_stat', '公式设置'),
					])
					->select($tab);
					
		
		
		$this->layout->body->primary_tabs->content = $content;
	}
	
	function _edit_info($e, $tabs) {
		$perf = $tabs->perf;
		if (!$perf->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('修改', $perf)) URI::redirect('error/401');
		$cat_root = Tag_Model::root('equipment');
		$group_root = Tag_Model::root('group');
		
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
			$form
				->validate('name', 'not_empty', I18N::T('eq_stat', '请输入评估名称！'))
				->validate('dfrom', 'is_numeric', I18N::T('eq_stat', '起始时间不合法。'))
				->validate('dto', 'is_numeric', I18N::T('eq_stat', '结束时间不合法。'))
				->validate('dto', 'compare(>=dfrom)', I18N::T('eq_stat', '结束时间不能在起始时间之前!'));
			
			if ($form->no_error) {
				$tag_type = $form['tag'];
				if ($tag_type == 'cat') {
					$tag = O('tag_equipment', $form['cat_id']);
				}
				elseif ($tag_type == 'group'){
					$tag = O('tag_group', $form['group_id']);
				}
				
				$perf->name = $form['name'];
				$perf->collection = $tag;
				$perf->dfrom = Date::get_day_start($form['dfrom']);
				$perf->dto = Date::get_day_end($form['dto']);
				
				if ($perf->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_stat', '绩效评估更新成功！'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '绩效评估更新失败！'));
				}
			}
		}
		
		$tabs->content = V('perf/edit.info', [
				'cat_root'=>$cat_root,
				'group_root'=>$group_root,
				'perf'=>$perf,
				'form'=>$form
			]);
	}
	
	function _edit_rating($e, $tabs) {
		$perf = $tabs->perf;
		if (!$perf->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('修改', $perf)) URI::redirect('error/401');
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
			$form
				->validate('dto', 'compare(>=dfrom)', I18N::T('eq_stat', '结束时间不能在起始时间之前!'));
				
			if ($form->no_error) {
				$items = (array)$form['items'];
				$perf->rating_items = @json_encode($items);
				$perf->rating_from = Date::get_day_start($form['dfrom']);
				$perf->rating_to = Date::get_day_end($form['dto']);
				$perf->score_value = $form['score_value'];
				if ($perf->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_stat', '评分问题更新成功！'));
					URI::redirect(URI::url(''));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '评分问题更新失败！'));
				}
			}
		}
		$tabs->content = V('perf/edit.rating', [
								'perf'=>$perf,
								'form'=>$form
							]);
	}
	
	function _edit_stat($e, $tabs) {
		$perf = $tabs->perf;
		if (!$perf->id) URI::redirect('error/401');
		if (!L('ME')->is_allowed_to('修改', $perf)) URI::redirect('error/401');
		$form = Form::filter(Input::form());
		if ($form['submit']) {
			$options = Config::get('eq_stat.perf_opts');
			$options = array_keys(Perf::filter_opts($options));
			array_unshift($options, 'user_score');
			foreach ((array)$options as $key) {
				$form
					->validate($key, 'is_numeric', I18N::T('eq_stat', '请填写正确的数字格式！'));
			}
			
			if ($form->no_error) {
				$formula = [];
				foreach ((array)$options as $key) {
					$formula[$key] = $form[$key] ?: 0;
				}
				$perf->formula = $formula;
				
				if ($perf->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_stat', '评分公式更新成功！'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '评分公式更新失败！'));
				}
			}
		}
		$tabs->content = V('perf/edit.stat', [
								'perf'=>$perf,
								'form'=>$form	
							]);
	}
	
	function delete($id=0) {
		$perf = O('eq_perf', $id);
		if (!$perf->id) URI::redirect('error/404');
		if (!L('ME')->is_allowed_to('删除', $perf)) URI::redirect('error/401');
		$ratings = Q("eq_perf_rating[perf={$perf}]");
		if ($perf->delete()) {
			foreach($ratings as $rating) {
				$rating->delete();
			}
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_stat', '评估删除成功!'));
			URI::redirect('!eq_stat/perfs');
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '评估删除失败!'));
		}
	}
	
	function rating($id=0) {
		$perf = O('eq_perf', $id);
		$me = L('ME');
		if (!$perf->id || !$me->id || $perf->can_grade != 1) URI::redirect('error/404');

        $this->layout->body->primary_tabs
            ->delete_tab('chart')
            ->delete_tab('list')
            ->delete_tab('perfs');

        $tag = $perf->collection;
        $dfrom = $perf->dfrom;
        $dto = $perf->dto;
        $equipments = Q("{$tag} equipment");
        foreach($equipments as $e) {
            if (Q("eq_record[equipment={$e}][dtstart<={$dto}][dtend>={$dfrom}]:limit(1)")->total_count()) {
                $rating = Q("eq_perf_rating[perf={$perf}][equipment={$e}][user={$me}]:limit(1)")->current();
                if ($rating->id) continue;
                $real_eq = $e;
                break;
            }
        }
        if (!$real_eq->id) URI::redirect('error/404');
		
		$this->layout->body->primary_tabs
			->add_tab('rating', [
					'url'=>URI::url('!eq_stat/perf/rating.'.$perf->id),
					'title'=>I18N::T('eq_stat', '给 %e_name 评分', ['%e_name'=>$real_eq->name])
				])
			->select('rating');
		
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
			$items = json_decode($perf->rating_items);
			$max = $perf->score_value ?: 10;
			foreach($items as $key => $v) {
				$name = $key.(string)$real_eq;
				$form
					->validate($name, 'is_numeric', I18N::T('eq_stat', '请填写正确的数字格式！'));
				if ($form[$name] < 0 || $form[$name] > $max) {
					$form->set_error($name, I18N::T('eq_stat', '请输入0～%max的数字！', ['%max'=>$max]));
				}
			}
			
			if ($form->no_error) {
				$list = [];
				$total = 0;
				$num = 0;
				foreach($items as $key => $v) {
					$name = $key.(string)$real_eq;
					$list[$key] = $form[$name];
					$total += $form[$name];
					$num ++;
				}
				$rating = O('eq_perf_rating');
				$rating->perf = $perf;
				$rating->equipment = $real_eq;
				$rating->user = $me;
				$rating->scores = $total;
				$rating->average = round($total/$num);
				$rating->score_list = $list;
				if ($rating->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('eq_stat', '对 %name 评分成功！', ['%name'=>$real_eq->name]));
					$perfs = Q("eq_perf[can_grade=1]");

					foreach ($perfs as $perf) {

                        $dfrom = $perf->dfrom;
                        $dto = $perf->dto;
                        $tag = $perf->collection;
                        $equipments = Q("{$tag} equipment");

                        foreach($equipments as $e) {
                            if (Q("eq_record[equipment={$e}][dtstart<={$dto}][dtend>={$dfrom}]:limit(1)")->total_count()) {
                                $rating = Q("eq_perf_rating[perf={$perf}][equipment={$e}][user={$me}]:limit(1)")->current();
                                if ($rating->id) continue;
								URI::redirect('!eq_stat/perf/rating.'.$perf->id);
                            }
                        }
					}

					URI::redirect("/");
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '对 %name 评分失败！', ['%name'=>$real_eq->name]));
				}
			}
		}
		
		$this->layout->body->primary_tabs
			->content = V('perf/user.rating', [
								'equipment'=>$real_eq,
								'perf'=>$perf,
								'form'=>$form
							]);
	}
}

class Perf_AJAX_Controller extends AJAX_Controller {

	function index_preview_click() {
		$form = Input::form();
		$equipment = O('equipment', $form['e_id']);
		$perf = O('eq_perf', $form['p_id']);
		if (!$equipment->id || !$perf->id) return;
		Output::$AJAX['preview'] = (string)V('eq_stat:perf/preview', ['equipment'=>$equipment, 'perf'=>$perf]);
	}
}

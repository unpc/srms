<?php
class Position_Controller extends Base_Controller {

	function index($id=0) {

		if( !L('ME')->is_allowed_to('查看', 'position') ) {
			URI::redirect('error/401');
		}

		$form = Lab::form();
		$selector = 'position';

		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*={$name}]";
		}

		if ($form['department']) {
			$department = Q::quote($form['department']);
			$selector .= "[department={$department}]";
		}

		if ($form['minsalary'] && $form['maxsalary']) {
			$minsalary = Q::quote($form['minsalary']);
			$maxsalary = Q::quote($form['maxsalary']);
			$selector .= "[minsalary>={$minsalary}]";
			$selector .= "[maxsalary<={$maxsalary}]";
		}

		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';

		switch ($sort_by) {
		case 'name':
			$selector .= ":sort(name $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'department':
			$selector .= ":sort(department $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'salary':
			$selector .= ":sort(minsalary $sort_flag)";
			$form['sort'] = NULL;
			break;
		}

		$positions = Q($selector);
		$pagination = Lab::pagination($positions, $form['st'], 25);

		$panel_buttons = [];
		if( L('ME')->is_allowed_to('添加', 'position') ) {
			$panel_buttons[] = [
				'text'  => I18N::T('resume', '添加职位'),
				'extra' => 'q-object="add" q-event="click" class="button button_add"'
			];
		}

		$content = V('position/index', [
						 'positions'=>$positions,
						 'form'=>$form,
						 'pagination' => $pagination,
						 'sort_asc' => $sort_asc,
						 'sort_by' => $sort_by,
						 'panel_buttons' =>$panel_buttons
						 ]);
		$this->layout->body->primary_tabs
			->select('position')
			->set('content', $content);
	}
}

class Position_AJAX_Controller extends AJAX_Controller {
	function index_add_click() {
		JS::dialog(V('position/add'));
	}

	function index_add_position_submit()
	{
		$form = Form::filter(Input::form());
		$position = O('position');
		if ($form['submit']) {
			$position->name = $form['name'];
			$position->department = $form['department'];
			$position->maxsalary = $form['maxsalary'];
			$position->minsalary = $form['minsalary'];
			if ($position->save()) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '职位添加成功!'));
				JS::refresh();
			}
		}
	}

	function index_edit_click($id=0) {
		$form = Input::form();
		$position = O('position', $form['id']);

		if (!$position->id) {
			URI::redirect('error/404');
		}

		JS::dialog(V('position/edit', ['position' => $position]));
	}

	function index_edit_position_submit($id=0)
	{
		$form = Form::filter(Input::form());

		$position = O('position', $form['id']);
		if ($form['submit']) {
			$position->name = $form['name'];
			$position->department = $form['department'];
			$position->maxsalary = $form['maxsalary'];
			$position->minsalary = $form['minsalary'];
			if ($position->save()) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '职位修改成功!'));
				JS::refresh();
			}
		}
	}
}

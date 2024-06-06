<?php

class Calendar_Model extends Presentable_Model {

	protected $object_page = [
		'view'=>'!calendars/calendar/%id[.%arguments]',
		'edit'=>'!calendars/calendar/edit.%id[.%arguments]',
	];

	function delete(){
		Q("{$this} cal_component")->delete_all();
		return parent::delete();
	}

	function list_empty_message() {
		return Event::trigger('calendar.list_empty_message', $this);
	}

	function & list_row($component) {
		$row = new ArrayIterator;
	/*	$row['@'] = [
			'view' => (string) $component->organizer->icon('32'),
			'extra' => 'style="padding:4px"',
		];*/
		$row['name'] = H($component->name);
		$row['organizer'] = (string) V('calendar/organizer', ['organizer' => $component->organizer]);
		$row['date'] = H(Date::range($component->dtstart, $component->dtend));

		//links()位于calendars模块下models下cal_componet.php中
		$row['links'] = Widget::factory('application:links', ['links'=>$component->links()]);

		if (FALSE === Event::trigger('calendar.list_row', $this, $component, $row)) {
			return NULL;
		}

		return (array) $row;
	}

	function & list_columns($form) {

		$columns = new ArrayIterator;

		$columns['organizer'] = [
			'align' => 'left',
			'title' => I18N::HT('calendars', '组织者'),
			'nowrap' => TRUE,
		];

		$columns['name'] = [
			'align' => 'left',
			'title' => I18N::HT('calendars', '主题'),
			'nowrap' => TRUE,
			'weight' => 10,
		];

		if ($form['dtstart'] || $form['dtend']) {
			$date_value = true;
		}

		$columns['date'] = [
			'align' => 'left',
			'title' => I18N::HT('calendars', '时间'),
			'nowrap' => TRUE,
			'filter' => [
				'form' => V('calendars:calendar_table/filters/date', ['form' => $form]),
				'value'=> $date_value,
				'field'=> 'dtstart,dtend'
			],
			'input_type' => 'select',
			'weight' => 20,
		];
		Event::trigger('calendar.list_columns', $this, $columns, $form);

		$columns['links'] = [
            'title' => I18N::HT('calendars', '操作'),
			'align' => 'right',
			'nowrap' => TRUE,
			'weight' => 1000,
		];
		
		return (array) $columns;
	}

}

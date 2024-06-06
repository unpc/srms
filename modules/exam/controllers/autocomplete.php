<?php

class Autocomplete_Controller extends AJAX_Controller {

	function exams() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;

		$currentPage = ($n==10) ? 1 : ((int)(($start-10) / $n) + 2);

		$exams = (new HiExam())->get('exams/list', ['q'=> $s, 'currentPage'=> $currentPage, 'pageSize'=> $n]);
		if ($exams) $exams = $exams['list'];

		if ($start == 0 && empty($exams)) {
			Output::$AJAX[] = array(
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			);
		}
		else {
			foreach ($exams as $exam) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/exams', array('exam'=>$exam)),
					'alt' => $exam['id'],
					'text' => H($exam['title']),
					'data' => []
				);
			}


			if ($start == 95) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				);
			}
		}
	}
}

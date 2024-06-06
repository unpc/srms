<?php
/*
NO.TASK#282(guoping.zhang@2010.12.01
搜索送样时，输入user关键字自动完成
*/
class Autocomplete_Controller extends AJAX_Controller {
	
	function user() {
		$s = Q::quote(Input::form('s'));
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if ($s) {
			$selector = "user[!hidden][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		}
		else {
			$selector = "user[!hidden]:limit({$start},{$n})";
		}

		$users = Q($selector);
		$users_count = $users->length();

		if (!$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('eq_sample:autocomplete/user', ['user' => $user]),
					'alt' => $user->name,
					'text' => $user->friendly_name(),
				];
			}
			// $rest = $users->total_count() - $users_count;			
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function sender($lab_id=0) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}

		$n = 5;
		if($start == 0) $n = 10;

		if($start >= 100) return;

		if ($s) {
			$s = Q::quote($s);
			$selector = "user[!hidden][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		}
		else {
			$selector = "user[!hidden]:limit({$start},{$n})";
		}
		$lab = O('lab', $lab_id);
		if ($lab->id) {
            $pre_selector = "{$lab} ";
        }
        $selector = $pre_selector.$selector;
		$users = Q($selector);
		$users_count = $users->total_count();

		if ($start == 0 && !$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('eq_sample:autocomplete/sender', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
				];
			}

			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	/*
	function sample($id=0) {
		$s = Q::quote(Input::form('s'));
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$equipment = O('equipment', $id);

        $sample_status = Event::trigger('eq_sample.get_sample_status_for_source_record') ?: join(',', [EQ_Sample_Model::STATUS_APPROVED, EQ_Sample_Model::STATUS_TESTED]);

		if ($s) {
			$s = Q::quote($s);
			$selector = "user[name*={$s}|name_abbr*={$s}]<sender eq_sample[status={$sample_status}][equipment={$equipment}][record_id=0]sort(dtsubmit D):limit({$start},{$n})";
		}
		else {
			$selector = "eq_sample[status={$sample_status}][equipment={$equipment}]:sort(dtsubmit D):limit({$start},{$n})";
		}
		$samples = Q($selector);
		$samples_count = $samples->total_count();

		if (!$samples_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($samples as $sample) {
				$user = $sample->sender;
				Output::$AJAX[] = [
					'html' => (string) V('eq_sample:autocomplete/sample', ['sample' => $sample]),
					'alt' => $sample->id,
					'text'  => I18N::T('eq_sample', '%user(%num) %time', ['%user' => $user->name, '%time' => Date::format($sample->dtsubmit, 'Y/m/d H:i'), '%num'=>$sample->count]),
				];
			}
//			$rest = $samples->total_count() - $samples_count;			
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
	*/

    /**
     * 西交大使用记录关联送样记录定制需求，要展示1000条
     * RQ195004
     */
    public function sample($id = 0)
    {
        $s     = Q::quote(Input::form('s'));
        $s     = trim(Input::form('s'));
        $st    = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($start >= 1000) {
            return;
        }

        $n = 50;
        if ($start == 0) {
            $n = 100;
        }

        $equipment = O('equipment', $id);

        $sample_status = Event::trigger('eq_sample.get_sample_status_for_source_record') ?: join(',', [EQ_Sample_Model::STATUS_APPROVED, EQ_Sample_Model::STATUS_TESTED]);

        if ($s) {
            $s        = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}]<sender eq_sample[status={$sample_status}][equipment={$equipment}][record_id=0]sort(dtsubmit D):limit({$start},{$n})";
        } else {
            $selector = "eq_sample[status={$sample_status}][equipment={$equipment}][record_id=0]:sort(dtsubmit D):limit({$start},{$n})";
        }

        $samples       = Q($selector);
        $samples_count = $samples->total_count();

        if (!$samples_count) {
            Output::$AJAX[] = [
                'html'    => (string) V('autocomplete/special/empty'),
                'special' => true,
            ];
        } else {
            foreach ($samples as $sample) {
                $user           = $sample->sender;
                Output::$AJAX[] = [
                    'html' => (string) V('eq_sample:autocomplete/sample', ['sample' => $sample]),
                    'alt'  => $sample->id,
                    'text' => I18N::T('eq_sample', '%user(%num) %time', ['%user' => $user->name, '%time' => Date::format($sample->dtsubmit, 'Y/m/d H:i'), '%num' => $sample->count]),
                ];
            }
            if ($start == 950) {
                Output::$AJAX[] = [
                    'html'    => (string) V('eq_sample:autocomplete/special/rest'),
                    'special' => true,
                ];
            }
        }
    }

    public function approved_sample($id = 0)
    {
        $s     = Q::quote(Input::form('s'));
        $s     = trim(Input::form('s'));
        $st    = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($start >= 1000) {
            return;
        }

        $n = 50;
        if ($start == 0) {
            $n = 100;
        }

        $equipment = O('equipment', $id);

        $sample_status = EQ_Sample_Model::STATUS_APPROVED;

        if ($s) {
            $s        = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}]<sender eq_sample[status={$sample_status}][equipment={$equipment}][record_id=0]sort(dtsubmit D):limit({$start},{$n})";
        } else {
            $selector = "eq_sample[status={$sample_status}][equipment={$equipment}][record_id=0]:sort(dtsubmit D):limit({$start},{$n})";
        }

        $samples       = Q($selector);
        $samples_count = $samples->total_count();

        if (!$samples_count) {
            Output::$AJAX[] = [
                'html'    => (string) V('autocomplete/special/empty'),
                'special' => true,
            ];
        } else {
            foreach ($samples as $sample) {
                $user           = $sample->sender;
                Output::$AJAX[] = [
                    'html' => (string) V('eq_sample:autocomplete/sample', ['sample' => $sample]),
                    'alt'  => $sample->id,
                    'text' => I18N::T('eq_sample', '%user(%num) %time', ['%user' => $user->name, '%time' => Date::format($sample->dtsubmit, 'Y/m/d H:i'), '%num' => $sample->count]),
                ];
            }
            if ($start == 950) {
                Output::$AJAX[] = [
                    'html'    => (string) V('eq_sample:autocomplete/special/rest'),
                    'special' => true,
                ];
            }
        }
    }

    //当前机主+申请者
    public function records($id = 0)
    {
        $me = L('ME');
        $s = Q::quote(Input::form('s'));
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        $n = 5;
        if ($st) {
            $start = ($st - 1) * $n;
        }
        if ($start >= 1000) {
            return;
        }

        $equipment = O('equipment', $id);
		foreach (Q("$equipment user.incharge") as $incharge) {
			$userids[$incharge->id] = $incharge->id;
		}

        $users = implode(',', $userids);

        if ($s) {
            $selector = "user[id={$users}] eq_record[dtend][id*=$s][equipment={$equipment}]sort(dtstart D):limit({$start},{$n})";
        } else {
            $selector = "user[id={$users}] eq_record[dtend][equipment={$equipment}]:sort(dtstart D):limit({$start},{$n})";
        }

        $records = Q($selector);
        $records_count = $records->total_count();

        if (!$records_count) {
            Output::$AJAX[] = [
                'html' => (string)V('autocomplete/special/empty', ['msg' => '无可用使用记录']),
                'special' => true,
                'total_count' => $records_count
            ];
        } else {
            foreach ($records as $record) {
                Output::$AJAX[] = [
                    'html' => (string)V('equipments:autocomplete/record', ['record' => $record]),
                    'alt' => $record->id,
                    'text' => I18N::T('equipment', '%id %time', [
                        '%id' => Number::fill($record->id, 6),
                        '%time' => date('Y/m/d H:i:s', $record->dtstart) . "-" . date('Y/m/d H:i:s', $record->dtend)
                    ]),
                    'id' => $record->id,
                    'total_count' => $records_count
                ];
            }
            if ($start == 950) {
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/special/rest'),
                    'special' => true,
                    'total_count' => $records_count
                ];
            }
        }
    }

}

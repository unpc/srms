<?php

class Autocomplete_Controller extends AJAX_Controller
{

    function equipment($project_id = 0)
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;

        $n = 5;
        if ($st) {
            $start = ($st - 1) * $n;
        }
        $status = EQ_Status_Model::IN_SERVICE;
        
        if ($start == 0) $n = 10;

        if ($start >= 100) return;

        if ($project_id) {
            if ($s) {
                $s = Q::quote($s);
                $equipments = Q("equipment[status={$status}][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
            } else {
                $equipments = Q("equipment[status={$status}]:limit({$start},{$n})");
            }
        } else {
            if ($s) {
                $s = Q::quote($s);
                $equipments = Q("equipment[status={$status}][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
            } else {
                $equipments = Q("equipment[status={$status}]:limit({$start},{$n})");
            }
        }

        $equipments_count = $equipments->total_count();

        if ($start == 0 && !$equipments_count) {
            Output::$AJAX[] = [
                'html' => (string)V('autocomplete/special/equipment/empty'),
                'special' => TRUE,
                'total_count' => $equipments_count
            ];
        } else {
            foreach ($equipments as $equipment) {
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/equipment', ['equipment' => $equipment]),
                    'alt' => $equipment->id,
                    'text' => $equipment->name,
                    'data' => $equipment->id,
                    'id' => $equipment->id,
                    'total_count' => $equipments_count
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/special/rest'),
                    'special' => TRUE,
                    'total_count' => $equipments_count
                ];
            }
        }
    }

    function projects($eqid = 0)
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        $n = 5;
        if ($start == 0) $n = 10;

        if ($start >= 100) return;

        if ($s) {
            $s = Q::quote($s);
            $projects = Q("service_project[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
        } else {
            $projects = Q("service_project:limit({$start},{$n})");
        }
        $projects_count = $projects->total_count();

        if ($start == 0 && !$projects_count) {
            Output::$AJAX[] = [
                'html' => (string)V('technical_service:autocomplete/special/empty'),
                'special' => TRUE
            ];
        } else {
            foreach ($projects as $project) {
                Output::$AJAX[] = [
                    'html' => (string)V('technical_service:autocomplete/equipment_projects', ['project' => $project]),
                    'alt' => $project->id,
                    'text' => $project->name,
                    'data' => $project->id
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string)V('technical_service:autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

    //申请者
    public function samples($id = 0)
    {
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

        $apply_record = O('service_apply_record', $id);

        $sample_status = EQ_Sample_Model::STATUS_TESTED;

        if ($s) {
            $selector = "eq_sample[dtend][sender={$apply_record->user}][id*=$s][status={$sample_status}][equipment={$apply_record->equipment}]sort(dtsubmit D):limit({$start},{$n})";
        } else {
            $selector = "eq_sample[dtend][sender={$apply_record->user}][status={$sample_status}][equipment={$apply_record->equipment}]:sort(dtsubmit D):limit({$start},{$n})";
        }

        $samples = Q($selector);
        $samples_count = $samples->total_count();

        if (!$samples_count) {
            Output::$AJAX[] = [
                'html' => (string)V('autocomplete/special/empty', ['msg' => '如未检测到所需送样记录，请联系中心管理员']),
                'special' => true,
                'total_count' => $samples_count
            ];
        } else {
            foreach ($samples as $sample) {
                $user = $sample->sender;
                Output::$AJAX[] = [
                    'html' => (string)V('eq_sample:autocomplete/sample', ['sample' => $sample]),
                    'alt' => $sample->id,
                    'text' => I18N::T('eq_sample', '%id %user(%num) %time', ['%id' => Number::fill($sample->id, 6), '%user' => $user->name, '%time' => Date::format($sample->dtsubmit, 'Y/m/d H:i'), '%num' => $sample->count]),
                    'id' => $sample->id,
                    'total_count' => $samples_count
                ];
            }
            if ($start == 950) {
                Output::$AJAX[] = [
                    'html' => (string)V('eq_sample:autocomplete/special/rest'),
                    'special' => true,
                    'total_count' => $samples_count
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

        $apply_record = O('service_apply_record', $id);
        $equipment = $apply_record->equipment;
        if ($s) {
            $selector = "$me eq_record[dtend][id*=$s][equipment={$equipment}]:not(service_apply_record eq_record):sort(dtstart D):limit({$start},{$n})";
        } else {
            $selector = "$me eq_record[dtend][equipment={$equipment}]:not(service_apply_record eq_record):sort(dtstart D):limit({$start},{$n})";
        }
        $records = Q($selector);
        $records_count = $records->total_count();

        if (!$records_count) {
            Output::$AJAX[] = [
                'html' => (string)V('autocomplete/special/empty', ['msg' => '如未检测到所需使用记录，请联系中心管理员']),
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

    function apply_record($uid = 0)
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $eqid = Input::form('equipment');
        $start = 0;
        if ($st) {
            $start = $st;
        }

        $n = 5;
        if ($start == 0) $n = 10;

        if ($start >= 100) return;

        $status = implode(',', [Service_Apply_Record_Model::STATUS_APPLY, Service_Apply_Record_Model::STATUS_TEST]);
        $sstatus = implode(',', [Service_Apply_Model::STATUS_PASS,Service_Apply_Model::STATUS_SERVING]);
        $service = " service_apply[status={$sstatus}]<apply ";
        if ($eqid) {
            $equipment = O('equipment', $eqid);
            if ($s) {
                $s = Q::quote($s);
                $projects = Q("{$service} service_apply_record[equipment={$equipment}][status={$status}][user_id={$uid}][ref_no*={$s}]:limit({$start},{$n})");
            } else {
                $projects = Q("{$service} service_apply_record[equipment={$equipment}][status={$status}][user_id={$uid}]:limit({$start},{$n})");
            }
        } else {
            if ($s) {
                $s = Q::quote($s);
                $projects = Q("{$service} service_apply_record[status={$status}][user_id={$uid}][ref_no*={$s}]:limit({$start},{$n})");
            } else {
                $projects = Q("{$service} service_apply_record[status={$status}][user_id={$uid}]:limit({$start},{$n})");
            }
        }

        $projects_count = $projects->total_count();

        if ($start == 0 && !$projects_count) {
            Output::$AJAX[] = [
                'html' => (string)V('technical_service:autocomplete/special/empty', ['msg' => '未找到符合条件的申请']),
                'special' => TRUE
            ];
        } else {
            foreach ($projects as $record) {
                Output::$AJAX[] = [
                    'html' => (string)V('technical_service:autocomplete/apply_record', ['record' => $record]),
                    'alt' => $record->id,
                    'text' => $record->ref_no,
                    'data' => $record->id
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string)V('technical_service:autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

}

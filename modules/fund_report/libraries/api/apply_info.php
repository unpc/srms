<?php

class API_Apply_Info extends API_Common {
    function set_Apply($data = []) {
        $result = [
            'status' => 0,
            'data' => []
        ];
        if (count($data)) {
            foreach ($data as $value) {
                $fund_report_annual = O('fund_report_apply', ['source_id' => $value['id']]);
                $fund_report_annual->source_id = $value['id'];
                $fund_report_annual->fund_report_annual = O('fund_report_annual', ['source_id' => $value['annual']]);
                $fund_report_annual->num = $value['num'];
                $fund_report_annual->equipment = O('equipment', $value['equipment']);
                $fund_report_annual->user = O('user', $value['user']);
                $fund_report_annual->ctime = $value['ctime'];
                $fund_report_annual->type = $value['type'];
                $fund_report_annual->status = $value['status'];
                $fund_report_annual->save();
                if ($fund_report_annual->id) {
                    $result['data'][] = $fund_report_annual->source_id;
                }
            }
            return $result;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }

    function set_annual($data = []){
        $result = [
            'status' => 0,
            'data' => []
        ];
        if (count($data)) {
            foreach ($data as $value) {
                $fund_report_annual = O('fund_report_annual', ['source_id' => $value['id']]);
                $fund_report_annual->title = $value['title'];
                $fund_report_annual->dtstart = $value['dtstart'];
                $fund_report_annual->dtend = $value['dtend'];
                $fund_report_annual->source_id = $value['id'];
                $fund_report_annual->save();
                if ($fund_report_annual->id) {
                    $result['data'][] = $fund_report_annual->source_id;
                }
            }
            return $result;
        } else {
            return [
               'status' => 1//数据为空
            ];
        }
    }

    function delete_annual($data = []){
        $result = [
            'status' => 0,
            'data' => []
        ];
        if (isset($data['id'])) {
            $fund_report_annual = O('fund_report_annual', ['source_id' => $data['id']]);
            $fund_report_annual->delete();
            $result['data'][] = $fund_report_annual->source_id;
            return $result;
        } else {
            return [
                'status' => 1//数据为空
            ];
        }
    }
}

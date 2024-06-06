<?php
class Exam
{
    public static function show_sidebar($e)
    {
        $me = L('ME');
        if (!$me->id) {
            $e->return_value = false;
            return false;
        }
    }
    public static function get_remote_user($e, $token)
    {
        list($token, $backend) = Auth::parse_token($token);
        $gateway = Config::get('gapper.gateway');
        $rest = new REST($gateway['get_user_detail']);
        $data = $rest->get('default', ['gapper-oauth-token'=>$_SESSION['gapper_oauth_token']]);
        $user = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'ref_no' => $data['ref_no'],
        ];
        if ($user) {
            $e->return_value = $user;
            return false;
        }
    }

    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $user = L('ME');
        $equipment = $view->calendar->parent;
        if (!$equipment->require_exam) {
            $check_list[] = [
                'title' => I18N::T('equipments', '设备考试'),
                'result' => true,
                'description' => I18N::T('exam', '无需考试'),
            ];
        } else {
            if (($user->access('为所有仪器添加预约'))
                || ($user->group->id && $user->access('为下属机构仪器添加预约') && $user->group->is_itself_or_ancestor_of($equipment->group))
                || ($user->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($user, $equipment))
            ) {
                $check_list[] = [
                    'title' => I18N::T('equipments', '设备考试'),
                    'result' => true,
                    'description' => I18N::T('exam', '无需考试'),
                ];
            } else {
                if ($user->id && !$user->gapper_id) {
                    $lousers = (new LoGapper())->get('users', ['email'=> $user->email]);
                    $louser = @current($lousers['items']);
                    if ($louser['id']) {
                        $user->gapper_id = $louser['id'];
                        $user->save();
                    }
                }
                $exam = Q("$equipment exam")->current();

                $history_exams =  (array)$equipment->history_exams;
                $exams_id_str = implode(',', $history_exams);
                $remote_exam_app = Config::get('exam.remote_exam_app');
                $remote_ids = Q("exam[id={$exams_id_str}][remote_app={$remote_exam_app}]")->to_assoc('remote_id', 'remote_id');
                if ($user->gapper_id) $result = (new HiExam())->get("user/{$user->gapper_id}/exams/result", [
                    'exams' => $remote_ids
                ]);

                foreach ((array)$result as $res) {
                    if ($res['status'] == '通过') {
                        $passed = true;
                        break;
                    }
                }

                if (!$passed) {
                    $url = $exam->getRemoteUrl();
                    $description = I18N::T('equipments', '未通过仪器考试, ');
                    $description .= '<a class="blue prevent_default" href="'.$url.'" target="_blank">'.I18N::T('equipments', '点击参加').'</a>';
                    $check_list[] = [
                        'title' => I18N::T('equipments', '设备考试'),
                        'result' => false,
                        'description' => $description
                    ];
                } else {
                    $check_list[] = [
                        'title' => I18N::T('equipments', '设备考试'),
                        'result' => true,
                        'description' => I18N::T('exam', '考试通过'),
                    ];
                }
            }
        }
        $view->check_list = $check_list;
    }
}

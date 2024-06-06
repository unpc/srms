<?php

class Service_Controller extends Base_Controller
{
    public function index()
    {
        URI::redirect(URI::url('!nrii/nrii.service'));
    }

    public function edit()
    {
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $form = Form::filter(Input::form())
                ->validate('serviceUrl', 'not_empty', I18N::T('nrii', '请输入在线服务平台网址!'))
                ->validate('serviceUrlyear', 'not_empty', I18N::T('nrii', '请输入正确在线平台建设年份!'))
                ->validate('serviceUrlyear', 'is_numeric', I18N::T('nrii', '请输入正确在线平台建设年份!'))
                ->validate('billNum', 'not_empty', I18N::T('nrii', '请输入正确开放服务发票凭证数量（张）!'))
                ->validate('billNum', 'is_numeric', I18N::T('nrii', '请输入正确开放服务发票凭证数量（张）!'))
                ->validate('billNum', 'compare(>0)', I18N::T('nrii', '请输入正确开放服务发票凭证数量（张）!'))
                ->validate('billWorth', 'not_empty', I18N::T('nrii', '请输入正确服务收入总金额（万元）!'))
                ->validate('billWorth', 'is_numeric', I18N::T('nrii', '请输入正确服务收入总金额（万元）!'))
                ->validate('billWorth', 'compare(>0)', I18N::T('nrii', '请输入正确服务收入总金额（万元）!'))
                ->validate('billWorthInstr', 'not_empty', I18N::T('nrii', '请输入正确对外服务收入总金额（万元）!'))
                ->validate('billWorthInstr', 'is_numeric', I18N::T('nrii', '请输入正确对外服务收入总金额（万元）!'))
                ->validate('billWorthInstr', 'compare(>0)', I18N::T('nrii', '请输入正确对外服务收入总金额（万元）!'))
                ->validate('instrNum', 'not_empty', I18N::T('nrii', '请输入正确50万元以上仪器总数量（台套）!'))
                ->validate('instrNum', 'is_numeric', I18N::T('nrii', '请输入正确50万元以上仪器总数量（台套）!'))
                ->validate('instrNum', 'compare(>0)', I18N::T('nrii', '请输入正确50万元以上仪器总数量（台套）!'))
                ->validate('instrWorth', 'not_empty', I18N::T('nrii', '请输入正确50万元以上仪器总原值（万元）!'))
                ->validate('instrWorth', 'is_numeric', I18N::T('nrii', '请输入正确50万元以上仪器总原值（万元）!'))
                ->validate('instrWorth', 'compare(>0)', I18N::T('nrii', '请输入正确50万元以上仪器总原值（万元）!'))
                ->validate('remark', 'not_empty', I18N::T('nrii', '请输入支撑本单位科技创新成效!'))
                ->validate('remarks', 'not_empty', I18N::T('nrii', '请输入支撑外单位科技创新成效!'))
                ->validate('remarkOne', 'not_empty', I18N::T('nrii', '请输入重大科研基础设施开放共享情况!'))
                ->validate('remarkTwo', 'not_empty', I18N::T('nrii', '请输入管理制度及实验队伍建设情况!'));

            if (mb_strlen($form['serviceUrl']) > 100) {
                $form->set_error('serviceUrl', I18N::T('nrii', '管理单位建设的在线服务平台所在网站的URL，字数不应超过100字!'));
            }
            if (mb_strlen($form['remark']) > 2000) {
                $form->set_error('remark', I18N::T('nrii', '支撑本单位科技创新成效，字数不应超过2000字!'));
            }
            if (mb_strlen($form['remarks']) > 2000) {
                $form->set_error('remarks', I18N::T('nrii', '支撑外单位科技创新成效，字数不应超过2000字!'));
            }
            if (mb_strlen($form['remarkOne']) > 2000) {
                $form->set_error('remarkOne', I18N::T('nrii', '重大科研基础设施开放共享情况，字数不应超过2000字!'));
            }
            if (mb_strlen($form['remarkTwo']) > 2000) {
                $form->set_error('remarkTwo', I18N::T('nrii', '管理制度及实验队伍建设情况，字数不应超过2000字!'));
            }

            $intensityFile = Input::file('intensityFile');
            if (!Lab::get('nrii.service.intensityFileIdName') && $intensityFile['error']) {
                $form->set_error('intensityFile', I18N::T('nrii', '请上传对外服务收入汇总表	!'));
            }
            $specializationFile = Input::file('specializationFile');
            if (!Lab::get('nrii.service.intensityFileIdName') && $specializationFile['error']) {
                $form->set_error('specializationFile', I18N::T('nrii', '请上传50万元以上仪器资产明细表	!'));
            }
            
            if ($form->no_error) {
                foreach ($form as $k => $v) {
                    if ($k == 'submit') {
                        continue;
                    } elseif ($k == 'serviceUrl') {
                        $v = URI::url('/');
                    }
                    Lab::set("nrii.service.{$k}", $v);
                }

                $_handleFile = function($file, $type) {
                    $file_name = NFS::fix_name($file['name'], true);
                    if ($file_name) {
                        $prefix = '/home/disk/'.SITE_ID.'/'.LAB_ID."/attachments/nrii.service.{$type}/";
                        $full_path = $prefix . $file_name;
                        // 仅能上传1个附件
                        File::rmdir($prefix);
                        File::check_path($full_path);
                        move_uploaded_file($file['tmp_name'], $prefix . $file_name);
                        Lab::set("nrii.service.{$type}IdName", $file_name);
                    }
                };

                $_handleFile($intensityFile, 'intensityFile');
                $_handleFile($specializationFile, 'specializationFile');

                Log::add(strtr('[nrii_service] %user_name[%user_id]编辑服务成效', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '编辑服务成效成功!'));
                URI::redirect(URI::url('!nrii/service/edit'));
            }
        }

        $service = [
            'serviceUrl' => Lab::get('nrii.service.serviceUrl'),
            'serviceUrlyear' => Lab::get('nrii.service.serviceUrlyear'),
            'billNum' => Lab::get('nrii.service.billNum'),
            'billWorth' => Lab::get('nrii.service.billWorth'),
            'billWorthInstr' => Lab::get('nrii.service.billWorthInstr'),
            'instrNum' => Lab::get('nrii.service.instrNum'),
            'instrWorth' => Lab::get('nrii.service.instrWorth'),
            'remark' => Lab::get('nrii.service.remark'),
            'remarks' => Lab::get('nrii.service.remarks'),
            'remarkOne' => Lab::get('nrii.service.remarkOne'),
            'remarkTwo' => Lab::get('nrii.service.remarkTwo'),
            'intensityFileIdName' => Lab::get('nrii.service.intensityFileIdName'),
            'specializationFileIdName' => Lab::get('nrii.service.specializationFileIdName'),
        ];
        $breadcrumb = [
            [
                'url' => URI::url('!nrii/nrii.service'),
                'title' => I18N::T('nrii', '服务成效')
            ],
            [
                'url' => URI::url('!nrii/service/edit'),
                'title' => I18N::T('nrii', '编辑')
            ]
        ];
        $panel_buttons = [
            'add' => [
                'text' => I18N::T('nrii', '刷新数据'),
                'extra' => 'class="button button_refresh" q-object="service_refresh" q-event="click" q-src="' . H(URI::url('!nrii/service')) . '"',
            ],
        ];

        $this->layout->body->primary_tabs
            ->add_tab('service', ['*' => $breadcrumb])
            ->select('service')
            ->set('content', V('nrii:service/edit', [
                    'form' => $form,
                    'panel_buttons' => $panel_buttons,
                    'service' => $service
                ]));
    }

    public function sync()
    {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_service';
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上报服务成效至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.service'));
    }
}

class Service_AJAX_Controller extends AJAX_Controller
{
    public function index_service_refresh_click()
    {
        JS::dialog((string)V(
            'nrii:service/refresh',
            [
            'trigger_url' => URI::url('!nrii/service')
            ]
        ));
    }

    public function index_refresh_click()
    {
        $year = date('Y', time());
        $year = date('m', time()) > 8 ? : $year - 1; //统计时间按学年（今年的9月1日到下一年的8月31日）
        $dtstart = strtotime($year.'-09-01');
        $dtend = time();

        $db = Database::factory();
        $SQL = "SELECT COUNT(DISTINCT project_id) AS cnt FROM eq_record WHERE project_id != 0 AND dtstart >= $dtstart AND dtstart <= $dtend";
        $projectCnt = $db->query($SQL);
        $projectCnt = $projectCnt->row();

        $eqs = Q("equipment[price>500000]");

        $ret = [
            'serviceUrl' => URI::url('/'),
            'billWorth' => (float)Q("billing_transaction[ctime>$dtstart][ctime<$dtend]")->SUM('outcome') / 10000,
            'instrNum' => (int)$eqs->total_count(),
            'instrWorth' => (int)$eqs->sum('price') / 10000,
        ];

        Output::$AJAX['info'] = $ret;
    }
}

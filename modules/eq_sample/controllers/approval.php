<?php

class Approval_Controller extends Layout_Controller {

	function export($id) {
		$me = L('ME');
        $sample = O('eq_sample', $id);
        $sample_results = Q("sample_result[sample=$sample]");
		if (!$sample->id) URI::redirect('error/404');
        $ystart = mktime(0, 0, 0, 1, 1, date('Y'));
        $yend = mktime(0, 0, 0, 1, 1, date('Y') + 1);
        $selector = "eq_sample[code][id<$id][dtsubmit=$ystart~$yend]";
        $code = (Q($selector)->total_count() ? : 0) + 1;
        
    	$autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);
        
        $pdf = new TCPDF();
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();

		$pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->SetFont('cid0cs', '', 12);
		$pdf->Text(Config::get('eq_sample.approval.title.horizontal', 67), 3.5, H(Config::get('eq_sample.approval.title')));
        $pdf->SetFont('cid0cs', '', 10);
		$pdf->Line(20, 9.5, 190, 9.5, ['width'=>0.1]);
        
        $pdf->SetFont('cid0cs', '', 16);
		$pdf->Text(85, 15.5, H('样品检测任务单'));
        $pdf->SetFont('cid0cs', '', 10);
        $pdf->Text(35, 25, date('Y') . H('年 第 ') . $code . H(' 号'));
        $pdf->Text(130, 25, H('接样日期:'));
        $pdf->Text(160, 25, H('年'));
        $pdf->Text(170, 25, H('月'));
        $pdf->Text(180, 25, H('日'));

        $pdf->writeHTMLCell(20, 20, 20, 32, (string)V('sample_approval:export', [
            'code' => $code,
            'sample' => $sample,
            'sample_results' => $sample_results,
        ]), 0, 1, 0, true, '', true);

        $pdfName = str_pad($sample->id, 9, 0, STR_PAD_LEFT).'.pdf';
        $pdf->Output($pdfName, 'I');
	}

    function report($id) {
        $me = L('ME');
        $sample = O('eq_sample', $id);
        $equipment = $sample->equipment;
        $sample_results = Q("sample_result[sample=$sample]");
        if (!$sample->id) URI::redirect('error/404');
        $ystart = mktime(0, 0, 0, 1, 1, date('Y'));
        $yend = mktime(0, 0, 0, 1, 1, date('Y') + 1);
        $selector = "eq_sample[code][id<$id][dtsubmit=$ystart~$yend]";
        $code = (Q($selector)->total_count() ? : 0) + 1;
        
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);
        
        $pdf = new TCPDF();
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();

        $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->SetFont('cid0cs', '', 12);
        $pdf->Text(Config::get('eq_sample.approval.title.horizontal', 67), 3.5, H(Config::get('eq_sample.approval.title')));    
        $pdf->SetFont('cid0cs', '', 18);
        $pdf->Text(75, 8, H('样品检测结果报告单'));
        $pdf->SetFont('cid0cs', '', 10);
        
        $pdf->SetFont('cid0cs', '', 13);
        $pdf->Text(25, 25, H('平台(') . date('Y') . H(')第') . $code . H('号'));
        //$pdf->Text(160, 25, H('共1页 第1页'));
        
        $pdf->Line(20, 35, 190, 35, ['width'=>0.1]);
        
        $pdf->Text(25, 40, H('1.委托单位:') . $sample->sender->organization);
        $pdf->Text(25, 47, H('2.样品类别:') . $sample->type);
        $pdf->Text(25, 54, H('3.检测仪器:') . $equipment->name . '(' . $equipment->ref_no . ')');
        $pdf->Text(25, 61, H('4.检测依据:'));
        $pdf->Text(25, 68, H('5:测试日期:') . date('Y年m月d日', $sample->stime));
        $pdf->Text(25, 75, H('6.检测结果:'));

        $pdf->writeHTMLCell(20, 20, 20, 82, (string)V('sample_approval:report', [
            'sample' => $sample,
            'sample_results' => $sample_results,
        ]), 0, 1, 0, true, '', true);

        $pdfName = str_pad($sample->id, 9, 0, STR_PAD_LEFT).'.pdf';
        $pdf->Output($pdfName, 'I');
    }
}

class Approval_AJAX_Controller extends AJAX_Controller {
    function index_batch_edit_sample_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $equipment_id = false;
        $same_equipment = true;
        $sample_ids = [];

        if ($form['submit'] == 'submit') {
            try {
                // 仪器负责人在个人主页-送样审批，审批同一台仪器的送样，送样状态选择已测试时，测样成功数填写大于所选送样记录的样品数的最小值时，应提交失败
                if ($form['equipment_id']) {
                    $min_samples = false;

                    foreach($form['select'] as $key => $value) {
                        if ($value == 'on') {
                            $sample = O('eq_sample', $key);
                            if (!$sample->id || !($me->is_allowed_to('修改', $sample) || (Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人'))) continue;

                            if ($min_samples == false) {
                                $min_samples = $sample->count;
                            }
        
                            $min_samples = min($min_samples, $sample->count);
                        }
                    }

                    if ((int) $form['success_samples'] > (int) $min_samples) {
                        $form->set_error('success_samples', I18N::T('eq_sample', '测样成功数不能大于所选送样记录的样品数的最小值!'));
                    }
                }

                if ($form->no_error) {
                    foreach($form['select'] as $key => $value) {
                        if ($value == 'on') {
                            $sample = O('eq_sample', $key);
                            if (!$sample->id || !$me->is_allowed_to('修改', $sample)) continue;
        
                            $sample->status = $form['status'];
                            $sample->operator = $me;
        
                            if ($sample->status == EQ_Sample_Model::STATUS_TESTED && $form['equipment_id']) {
                                $sample->success_samples = (int)max($form['success_samples'], 0);
                            }
    
                            if ($sample->save()) {
                                // Event::trigger('extra.form.post_submit', $sample, $form);
    
                                if ($form['equipment_id']) {
                                    foreach($form['connect_records'] as $record_id) {
                                        $record = O('eq_record', $record_id);
                                        if ($record->id && $sample->equipment_id == $record->equipment_id) {
                                            $sample->connect($record);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {

            }

            if ($form->no_error) {
                JS::close_dialog();
                JS::refresh();
                return true;
            }
        }

        if(is_array($form['select']) && !empty($form['select'])) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $sample = O('eq_sample', $key);
                    if (!$sample->id || !($me->is_allowed_to('修改', $sample) || (Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人'))) continue;

                    if ($equipment_id === false) {
                        $equipment_id = $sample->equipment->id;
                    }

                    if ($equipment_id != $sample->equipment->id) {
                        $same_equipment = false;
                        $equipment_id = 0;
                    }

                    $sample_ids[] = $sample->id;
                }
            }
        }

        if (!count($sample_ids)) {
            JS::alert('请先选择送样记录!');
            return;
        }

        $connect_records = [];
        if ($form['connect_records']) {
            foreach($form['connect_records'] as $record_id) {
                $record = O('eq_record', $record_id);
                if ($record->id) {
                    $connect_records[$record->id] = (string)V('equipments:autocomplete/record', ['record' => $record]);
                }
            }
        }

        //弹出dialog编辑送样
		JS::dialog(V('eq_sample:edit/batch_edit', [
                'form' => $form,
                'same_equipment' => $same_equipment,
                'equipment_id' => $equipment_id,
                'sample_ids' => $sample_ids,
                'connect_records' => $connect_records
            ]
        ), ['title'=>I18N::T('eq_sample', '批量审批')]);
    }
}

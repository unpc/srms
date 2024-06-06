<?php

class CLI_Export_Apply
{

    static function export() {
       
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
		$applys = Q($selector);
        $excel = new Excel($params[1]);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($applys as $apply) {
            $data = [];
            if (array_key_exists('ref_no', $valid_columns)) {
                $data[] = $apply->ref_no ?: '';
            }
            if (array_key_exists('service_name', $valid_columns)) {
                $data[] = $apply->service->name ?: '';
            }
            if (array_key_exists('service_incharge', $valid_columns)) {
                $incharges = Q("{$apply->service}<incharge user")->to_assoc('id','name');
                $data[] = implode(',',$incharges) ?: '';
            }
            if (array_key_exists('status', $valid_columns)) {
                $data[] = Service_Apply_Model::$status_labels[$apply->status] ?? '';
            }
            if (array_key_exists('service_time_length', $valid_columns)) {
                $time_lenth = 0;
                foreach (Q("service_apply_record[apply={$apply}]") as $item)
                    $time_lenth += $item->dtlength;
                $data[] = round($time_lenth / 3600, 2);
            }
            if (array_key_exists('amount', $valid_columns)) {
                $data[] = $apply->status != Service_Apply_Model::STATUS_APPLY ? $apply->totalAmount() : '待定';
            }
            if (array_key_exists('user', $valid_columns)) {
                $data[] = $apply->user->name ?: '';
            }
            if (array_key_exists('phone', $valid_columns)) {
                $data[] = $apply->user->phone ?: '';
            }
            if (array_key_exists('lab', $valid_columns)) {
                $data[] = Q("{$apply->user} lab")->current()->name ?: '';
            }
            if (array_key_exists('ctime', $valid_columns)) {
                $data[] = $apply->ctime ? date('Y-m-d H:i:s', $apply->ctime) : '-';
            }
            if (array_key_exists('dtrequest', $valid_columns)) {
                $data[] = $apply->dtrequest ? date('Y-m-d H:i:s', $apply->dtrequest) : '-';
            }

            //定制的输出项
            $data_extra = Event::trigger('service_apply.export_list_csv', $apply, $data, $valid_columns);
            if(is_array($data_extra)) $data = $data_extra;

            $excel->write($data);
        }

        $excel->save();
    }

    public static function export_results()
    {
        $params = func_get_args();
        $filename = $params[1];
        $form = json_decode($params[0], true);
        $ext = 'zip';

        $apply = O('service_apply', $form['id']);
        if (!$apply->id || $apply->status != Service_Apply_Model::STATUS_DONE) return;

        $fileDir = self::create_result($apply);

        $zip = new \ZipArchive();
        $zipName = Config::get('system.excel_path') . $filename . '.' . $ext;
        $zip->open($zipName, \ZipArchive::CREATE);

        foreach ($fileDir as $fd) {
            //更改路径
            $zip->addFile($fd, explode('/', $fd)[count(explode('/', $fd)) - 1]);
        }
        $zip->close();
    }

    public static function create_result($apply)
    {

        $basepath = Config::get('system.excel_path') . $apply->ref_no;
        $filename = $basepath . $apply->ref_no . rand(1, 1000) . '.docx';

        $zip_files = [$filename];

        try {

            $rowtdWidth = 1450;
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $tableStyle = array(
                'cellMargin' => 50,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            );
            /* 报告首页 */
            $phpWord->setDefaultFontName('宋体');//设置全局字体

            $title_paragraph_style = 'title_stype';
            $phpWord->addParagraphStyle($title_paragraph_style, array('bold' => true, 'align' => 'center'));
            $title_font_style = 'title_font_style';
            $phpWord->addFontStyle($title_font_style, array('bold' => true, 'size' => 18));

            $sub_title_paragraph_style = 'sub_title_stype';
            $phpWord->addParagraphStyle($sub_title_paragraph_style, array('bold' => true));
            $sub_title_font_style = 'sub_title_font_style';
            $phpWord->addFontStyle($sub_title_font_style, array('bold' => true, 'size' => 18));

            $text_paragraph_style = 'text_stype';
            $phpWord->addParagraphStyle($text_paragraph_style, ['spaceBefore' => 200]);
            $text_font_style = 'text_font_style';
            $phpWord->addFontStyle($text_font_style, array('size' => 14, 'name' => '宋体'));

            $center_text_paragraph_style = 'center_text_stype';
            $phpWord->addParagraphStyle($center_text_paragraph_style, array('bold' => true, 'align' => 'center'));
            $center_text_font_style = 'center_text_font_style';
            $phpWord->addFontStyle($center_text_font_style, array('bold' => true, 'size' => 12));

            $section = $phpWord->addSection();
            $section->addTextBreak(1, ['size' => 20]);
            $tname = "{$apply->service->name}({$apply->service->ref_no})服务报告No:{$apply->ref_no}";
            $section->addTextRun($title_paragraph_style)->addText(strtr('%task_title', ['%task_title' => $tname]), ['size' => 13, 'bold' => true]);
            $section->addTextBreak(1);
            $section->addTextRun(['align' => 'right'])->addText('打印时间: ' . date('Y-m-d H:i:s', time()), ['size' => 8]);
            $section->addTextRun(['align' => 'right'])->addText('预约时间: ' . date('Y-m-d H:i:s', $apply->ctime), ['size' => 8]);

            $phpWord->addTitleStyle(1, array('bold' => true, 'size' => 20), array('bold' => true));
            $cellAllLeft = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START, 'valign' => 'center');
            $attachmentStyle = array('color' => '#3333e7');
            $nomaltxt = ['size' => 9];
            $titletxt2 = ['size' => 10, 'bold' => true];
            $titletxt = ['size' => 11, 'bold' => true];
            $cellSixColSpan = array('gridSpan' => 6, 'valign' => 'center');
            $cellSixColSpanCount = 6;
            $cellTwoColSpan = array('gridSpan' => 2, 'valign' => 'center');
            $cellTwoColSpanCount = 2;
            $cellFourColSpan = array('gridSpan' => 4, 'valign' => 'center');
            $cellFourColSpanCount = 4;
            $cellFiveColSpan = array('gridSpan' => 5, 'valign' => 'center');
            $cellFiveColSpanCount = 3;
            $phpWord->addTableStyle('recordtable', $tableStyle);
            $table = $section->addTable('recordtable');

            //必须设置最小单元格才行。。。。。不然样式错乱
            $table->addRow();
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);
            $table->addCell($rowtdWidth, ['borderSize' => 1, 'borderColor' => 'FFFFFF']);

            $table->addRow();
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText('预约信息', $titletxt);

            $table->addRow();
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('预约者', $nomaltxt);
            $lab = Q("{$apply->user} lab")->current();
            $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($apply->user->name . "({$lab->name})", $nomaltxt);
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('所属单位', $nomaltxt);
            $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($apply->user->group->name, $nomaltxt);

            $table->addRow();
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('收费金额', $nomaltxt);
            $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($apply->totalAmount() . "元", $nomaltxt);
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('期望完成时间', $nomaltxt);
            $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($apply->dtrequest ? date('Y-m-d H:i:s', $apply->dtrequest) : '', $nomaltxt);

            $table->addRow();
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText('样品信息', $titletxt);
            $table->addRow();
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('样品数', $nomaltxt);
            $table->addCell($rowtdWidth * $cellFiveColSpanCount, $cellFiveColSpan)->addTextRun($cellAllLeft)->addText($apply->samples ?? '', $nomaltxt);
            $table->addRow();
            $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText('描述', $nomaltxt);
            $table->addCell($rowtdWidth * $cellFiveColSpanCount, $cellFiveColSpan)->addTextRun($cellAllLeft)->addText($apply->samples_description ?? '', $nomaltxt);

            $table->addRow();
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText('服务进程', $titletxt);

            $table->addRow();
            $t1 = date('Y-m-d', $apply->ctime) . "    申请人提交申请";
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText($t1, $nomaltxt);
            $table->addRow();
            $t1 = ($apply->approval_time ? date('Y-m-d', $apply->approval_time) : '') . "    负责人审核通过";
            $cell = $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan);
            $cell->addTextRun($cellAllLeft)->addText($t1, $nomaltxt);
            $full_path = NFS::get_path($apply, '', 'attachments', TRUE);
            if (is_dir($full_path)) {
                $flist = NFS::file_list($full_path, '');
                if (count($flist)) {
                    foreach ($flist as $f1_data) {
                        $f1_path = $full_path . $f1_data['name'];
                        $zip_files[] = $f1_path;
                        $cell->addTextRun($nomaltxt + $attachmentStyle)
                            ->addLink("./{$f1_data['name']}", $f1_data['name'], $attachmentStyle);
                    }
                }
            }
            $table->addRow();
            $t1 = date('Y-m-d', $apply->dtstart) . "    承检人开始服务";
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText($t1, $nomaltxt);
            $table->addRow();
            $t1 = date('Y-m-d', $apply->dtend) . "    全部服务完成";
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText($t1, $nomaltxt);

            $table->addRow();
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText('服务项目', $titletxt);

            $records = Q("service_apply_record[apply={$apply}]");
            $index = 1;
            foreach ($records as $record) {
                $table->addRow();
                $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($index++ . '、' . $record->project->name, $nomaltxt);
                $table->addCell($rowtdWidth * $cellTwoColSpanCount, $cellTwoColSpan)->addTextRun($cellAllLeft)->addText($record->equipment->name . '/' . $record->operator->name, $nomaltxt);
                $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText(Service_Apply_Record_Model::$status_labels[$record->status], $nomaltxt);
                $table->addCell($rowtdWidth)->addTextRun($cellAllLeft)->addText($record->dtend ? date('Y-m-d', $record->dtend) : '', $nomaltxt);
            }

            $table->addRow();
            $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText('检测结果', $titletxt);

            $index = 1;
            foreach ($records as $record) {
                $table->addRow();
                $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan)->addTextRun($cellAllLeft)->addText($index++ . '、' . $record->project->name, $titletxt2);

                $table->addRow();
                $cell = $table->addCell($rowtdWidth * $cellSixColSpanCount, $cellSixColSpan);
                $cell->addTextRun($cellAllLeft)->addText($record->result ?: '', $nomaltxt);

                $full_path = NFS::get_path($record, '', 'attachments', TRUE);
                if (is_dir($full_path)) {
                    $flist = NFS::file_list($full_path, '');
                    if (count($flist)) {
                        foreach ($flist as $f1_data) {
                            $f1_path = $full_path . $f1_data['name'];
                            $zip_files[] = $f1_path;
                            $cell->addTextRun($cellAllLeft + $attachmentStyle)
                                ->addLink("./{$f1_data['name']}", $f1_data['name'], $attachmentStyle);
                        }
                    }
                }
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            ob_clean();
            $objWriter->save($filename); // 文件通过浏览器下载

            return $zip_files;

        } catch (\Exception $e) {
            error_log('exception export' . $e->getMessage());
        }
    }

}
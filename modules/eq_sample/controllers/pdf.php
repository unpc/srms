<?php


class Pdf_Controller extends Controller {
    function view($id){

        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

        $sample = O('eq_sample', $id);
        if(!L('ME')->is_allowed_to('查看', $sample)) return;
        $pdf = new TCPDF;
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        
        $pdf->AddPage();
        $pdf->SetFont('cid0cs', '', 20);
        
        $html = V('eq_sample:pdf', ['sample'=>$sample]);

        $sample_id = Number::fill($sample->id,6);
        $pdf->writeHTMLCell(0, 0, 10, 20, $html, 0, 1, 0, true, '', true);

        $pdf->Output('ID-'.$sample_id.'.pdf', 'I');
    }
}

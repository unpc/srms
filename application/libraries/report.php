<?php

class Report {
	
	private $template_name;
	private $data = [];
	private $template = [];
	
	function __construct($template, $data = []) {
		$this->load_template($template);
		$this->load($data);
	}
	
	private function get_path($name, $ext = EXT) {
		return LAB_PATH . PRIVATE_BASE.'sheets/'.$name.$ext;
	}
	
	function load_template($template_name) {
		include($this->get_path($template_name));
		$arrs = get_defined_vars();
		$item = $arrs['item'];
		$this->template_name = $template_name;
		$this->template = $item;
	}
	
	function load($data) {
		$this->data = (array) $data;
	}
	
	function output($type = 'PDF') {
		$method = '_output_'.$type;
		if (method_exists($this, $method)) {
			return $this->$method();
		}
	}

	function item_config($key) {
		$template = & $this->template;
		$default = $template['*'];
		$item = $template[$key];

		return (array)$item + (array)$default;
	}
	
	private function _output_PDF() {
		//1.载入pdf的核心函数文件
		Core::load(THIRD_BASE, 'pdf/fpdi/fpdi', '*');
		Core::load(THIRD_BASE, 'pdf/fpdf/fpdf', '*');
		Core::load(THIRD_BASE, 'pdf/fpdf/chinese', '*');
		Core::load(THIRD_BASE, 'pdf/fpdf/unicode', '*');

		if (!class_exists('PDF_Chinese')) exit;

		//2.设置基本参数
		$pdf = new PDF_Chinese('L', 'mm', 'A4');
		$pdf->AddGBFont();
		$pdf->AddPage();
		$filename = $this->get_path($this->template_name, '.pdf');
		$pdf->setSourceFile($filename);
		$tplIdx = $pdf->importPage(1);
		$pdf->useTemplate($tplIdx);
		
		//3.写入数据
		foreach ($this->data as $key=>$val) {
			$item = $this->item_config($key);
			$pdf->SetFont($item['font-family'],'',$item['font-size']);
			$pdf->SetXY(($item['left'] + $item['offset-left']),($item['top'] + $item['offset-top']));
			if(preg_match("/[^\d-., ]/",$val)) {
				if($item[$key]['direction']>0) {
					$len = mb_strlen($val);
					$x = $item['left'] + $item['offset-left'];
					$y = $item['top'] + $item['offset-top'];
					for($i = 0 ; $i < $len ; $i++){
						$str = mb_substr($val,$i,1);
						$pdf->SetXY($x,$y);
						$pdf->write(0,iconv('UTF8','GBK',$str));
						$y += 4;
					}
				}
				else {
					$pdf->write(0,iconv('UTF8','GBK',$val));
				}
				
			}
			else{
				$pdf->MultiCell($item['width'],$item['height'],$val,0,1,'C');
			}
			
			
			
		}
		//4.打印
		header('Content-Transfer-Encoding', 'binary');
		header('Cache-Control: maxage=3600'); 
		header('Pragma: public');
		$pdf->Output($this->template_name.'.pdf', 'D');
		exit;	
	}
	
	static function enumerate_links($object, $type = NULL) {
	
		$links = new ArrayIterator;
		$model_name = $object->name();
		Event::trigger("report.enumerate.links[$model_name]", $object, $type, $links);
		// report.enumerate.links[equipment]
		
		return (array) $links;
	}
	
}
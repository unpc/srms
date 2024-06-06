<?php

class QRCode_Controller extends Controller {

	function index($id=0) {
		$me = L('ME');
		$order = O('order', $id);

		if (!class_exists('QRcode', false)) {
			Core::load(THIRD_BASE, 'qrcode', '*');
		}

        $data = $order->qrcode_data();

		header('Expires: Thu, 15 Apr 2100 20:00:00 GMT');
		header('Pragma: public');
		header('Content-type: image/png');
		QRcode::png($data, NULL, QR_ECLEVEL_M, 3,0);
		exit;
	}
}

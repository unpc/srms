<?php

class QRCode_Controller extends Controller {

    function equipment($id = 0) {

        Core::load(THIRD_BASE, 'qrcode', '*');

        if ($id) {
            $data = Config::get('equipment.teach_information_url').$id;
        }
        else {
            $data = 'ACCESS DENIED';
        }
        header('Expires: Thu, 15 Apr 2100 20:00:00 GMT');
        header('Pragma: public');
        header('Cache-Control: max-age=604800');
        header('Content-type: image/png');
        QRcode::png($data, NULL, QR_ECLEVEL_M, 3.3,0);
        exit;
    }
}
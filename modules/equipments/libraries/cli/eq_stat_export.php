<?php

class CLI_EQ_Stat_Export {

    public static function export_sj_tri() {
        $params = func_get_args();
        $id = $params[0];
        $file_name = $params[1];
        $sj_tri = Config::get('eq_stat.sj_tri');

        $equipment = O('equipment', $id);

        $excel_header[] = I18N::T('equipments', '仪器编号');
        $excel_header[] = I18N::T('equipments', '仪器名称');
        $excel_header[] = I18N::T('equipments', '仪器负责人');
        $excel_header[] = I18N::T('equipments', '仪器联系人');

        foreach($sj_tri as $key => $value) {
            $excel_header[] = I18N::T('equipments', $value['name']);
        }

        $excel = new Excel($file_name);

        $excel->write($excel_header);

        $data = [];
        $data[] = H($equipment->ref_no);
        $data[] = H($equipment->name);
        $data[] = join(', ', Q("{$equipment} user.incharge")->to_assoc('id', 'name'));
        $data[] = join(', ', Q("{$equipment} user.contact")->to_assoc('id', 'name'));

        $dtstart = strtotime(Config::get('eq_stat.export.start', '20150901'));
        $dtend = strtotime(Config::get('eq_stat.export.end', '20160831'));

        foreach($sj_tri as $key => $value) {
            $data[] = Event::trigger('eq_stat.sj_tri.'.$key, $equipment, $dtstart, $dtend);
            
        }

        $excel->write($data);

        $excel->save();
     
    }
}
<?php

class Index_AJAX_Controller extends AJAX_Controller {

    function index_output_click() {
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return FALSE;
        }
        $type = $form['type'];
        $columns = Config::get('eq_evaluate.export_columns.eq_evaluate');
        switch ($type) {
            case 'csv':
                $title = I18N::T('equipments', '请选择要导出CSV的列');
                $query = $_SESSION[$form_token]['selector'];
                $total_count = Q($query)->total_count();
                if($total_count > 8000){
                    $description = I18N::T('equipments', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
                }
                break;
            case 'print':
                $title = I18N::T('equipments', '请选择要打印的列');
                break;
        }
        $view = V('eq_evaluate:report/form', [
            'description' => $description,
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
        ]);
        JS::dialog($view, ['title' => $title]);
    }

}
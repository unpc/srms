<?php

class Extra_Controller extends Controller
{
    public function index($id = null)
    {
        $extra = O('extra', $id);
        if (!$extra->id) return;
        $readonly = !L('ME')->is_allowed_to('修改', $extra);

        echo V('extra:edit/categories', [
            'extra'=> $extra,
            'categories'=> $extra->get_categories(),
            'readonly' => $readonly
        ]);
    }
}

class Extra_AJAX_Controller extends AJAX_Controller
{
    // 创建category
    public function index_create_category_click()
    {
        $form = Input::form();
        $extra = O('extra', $form['extra_id']);
        if (!$extra->id) return;

        JS::dialog(V('extra:edit/create_category', ['extra'=> $extra]), ['title'=> I18N::T('extra', '添加类别')]);
    }

    // 保存category
    public function index_create_category_submit()
    {
        $form = Form::filter(Input::form());
        $extra = O('extra', $form['extra_id']);

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra)) {
            $form->set_error('name', I18N::T('extra', '保存异常，请刷新重试!'));
        }

        $form->validate('name', 'not_empty', I18N::T('extra', '类别名称不能为空!'));

        if (in_array($form['name'], $extra->get_categories())) {
            $form->set_error('name', I18N::T('extra', '该类别已存在, 请输入其他名称!'));
        }

        if ($form->no_error) {
            $extra->add_category($form['name']);
            Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('extra', '添加类别成功!'));
            JS::refresh();
        } else {
            JS::dialog(V('extra:edit/create_category', ['form'=> $form, 'extra'=> $extra]), ['title'=> I18N::T('extra', '添加类别')]);
        }
    }

    //删除category
    public function index_delete_category_click()
    {
        $form = Input::form();
        $extra = O('extra', $form['extra_id']);

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra)) {
            $form->set_error('name', I18N::T('extra', '保存异常，请刷新重试!'));
        }

        $params = $extra->params;

        //如果有下述属性, 说明该类别下游字段
        if (count($params[$form['category']])) {
            $confirm = I18N::T('extra', '删除该类别, 将导致该类别中的字段丢失, 您确定要删除吗?');
        } else {
            $confirm = I18N::T('extra', '是否确定要删除该类别?');
        }

        if (JS::confirm($confirm)) {
            if ($extra->delete_category($form['category'])) {
                if (Module::is_installed('yiqikong') && $extra->object_name == 'equipment') {
                    CLI_YiQiKong::update_equipment_setting($extra->object->id);
                }
                Lab::MESSAGE(Lab::MESSAGE_NORMAL, I18N::T('extra', '删除类别成功!'));
            } else {
                Lab::MESSAGE(Lab::MESSAGE_ERROR, I18N::T('extra', '删除类别失败!'));
            }
            JS::refresh();
        }
    }

    // 添加field
    public function index_add_field_click()
    {
        $form = Input::form();
        $extra = O('extra', $form['extra_id']);
        if (!$extra->id) return;

        $view = V('extra:edit/field_with_handle', [
            'prefix' => $form['prefix'],
            'extra' => $extra,
            'form' => $form,
        ]);

        JS::dialog($view, ['title' => I18N::T('extra', '添加字段')]);
    }

    // 添加field
    public function index_add_field_submit()
    {
        $_form = Form::filter(Input::form());
        parse_str($_form['field_form'], $form);
        $form = Form::filter($form);
        $extra = O('extra', $form['extra_id']);
        $category_title = $form['category'];

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title) {
            JS::alert(I18N::T('extra', '保存失败，请刷新重试!'));
            return;
        }

        $extra_fields = $extra->get_fields($category_title);
        $exist_title = [];
        $repeat_category = [];
        $repeat_ids = [];
        $error_ids = [];
        $change_ids = [];

        //空的select radio checkbox等
        $empty_ids = [];

        //获取field
        foreach((array) $form['field'] as $prefix => $field) {
            if (!$field['title']) {
                JS::alert(I18N::T('extra', '字段标题不能为空!'));
                return;
            }

            $extra_field = $extra->get_field($category_title, $field['title']);
            if (count($extra_field)) {
                JS::alert(I18N::T('extra', '字段标题[' . $field['title'] . ']重复, 请重新填写!'));
                return;
            }

            //初始化
            $f = [];
            $uniqid = $extra->get_uniqid(); //设定uniqid
            $f['type'] = $field['type']; //类型
            $f['title'] = $field['title']; //标题
            $f['required'] = (int) ($field['required'] == 'on'); //是否必填
            $f['adopted'] = $field['adopted'];
            $f['remarks'] = $field['remarks'];

            //根据type获取params
            switch($field['type']) {
                case Extra_Model::TYPE_RADIO :
                    $f['params'] = array_filter($field['radio']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_CHECKBOX :
                    $f['params'] = array_filter($field['checkbox']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_SELECT :
                    $f['params'] = array_filter($field['select']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_RANGE :
                    //因为范围值可能含有0 所以不使用array_filter
                    foreach ($field['range'] as $key => $value) {
                        if ($value && !is_numeric($value) && is_numeric($key)) {
                            $error_ids[] = "field[$prefix][range][$key]";
                        } elseif ($value && is_numeric($key)) {
                            $field['range'][$key] = (double)$value;
                        }
                    }

                    if (isset($field['range'][0]) && isset($field['range'][1]) && ($field['range'][0] > $field['range'][1])) {
                        $t = $field['range'][0];
                        $field['range'][0] = $field['range'][1];
                        $field['range'][1] = $t;
                    }

                    if (isset($field['range'][2]) && isset($field['range'][3]) && ($field['range'][2] > $field['range'][3])) {
                        $t = $field['range'][2];
                        $field['range'][2] = $field['range'][3];
                        $field['range'][3] = $t;
                    }

                    $f['params'] = $field['range'];
                    if (!$f['params']) $empty_ids[] = $prefix;
                    if ($f['params'][0] == '' && $f['params'][1] == '' && $f['params'][2] == '' && $f['params'][3] == '') {
                        $empty_ids[] = $prefix;
                    } elseif ($f['params'][0] == '' || $f['params'][1] == '' || $f['params'][2] == '' || $f['params'][3] == '') {
                        $error_ids[] = $prefix;
                    }
                    break;
                case Extra_Model::TYPE_NUMBER :
                    //因为范围值可能含有0 所以不使用array_filter
                    if ($field['number']) foreach ($field['number'] as $key => $value) {
                        if ($value && !is_numeric($value)) {
                            $error_ids[] = "field[$prefix][number][$key]";
                        } elseif ($value && is_numeric($key)) {
                            $field['number'][$key] = (double)$value;
                        }
                    }

                    if ($field['number'][0] != '' && $field['number'][1] != '' && ($field['number'][0] > $field['number'][1])) {
                        $t = $field['number'][0];
                        $field['number'][0] = $field['number'][1];
                        $field['number'][1] = $t;
                    }
                    $f['params'] = $field['number'];
                    if (!$f['params'] && !$field['adopted']) $empty_ids[] = $prefix;
                    if ($f['params'][0] == '' && $f['params'][1] == '') {
                        $empty_ids[] = $prefix;
                    } elseif ($f['params'][0] == '' || $f['params'][1] == '') {
                        $error_ids[] = $prefix;
                    }
                    break;
                default :
                    $f['params'] = NULL;
                    break;
            }
            unset($f['params']['default_value']);

            $f['default'] = 0;
            if ($field['default'] == 'on') {
                $f['default'] = 1;
                switch($field['type']) {
                    case Extra_Model::TYPE_NUMBER:
                        if (isset($field['number']['default_value']) && !is_numeric($field['number']['default_value'])) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        } elseif (isset($field['number']['default_value'])) {
                            $field['number']['default_value'] = (double)$field['number']['default_value'];
                        }

                        if (isset($field['number']['default_value']) && $f['params'][0] != null && $field['number']['default_value'] < $f['params'][0]) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        }

                        if (isset($field['number']['default_value']) && $f['params'][1] != null && $field['number']['default_value'] > $f['params'][1]) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        }

                        $f['default_value'] = $field['number']['default_value'];
                        break;
                    case Extra_Model::TYPE_TEXT:
                        $f['default_value'] = $field['text']['default_value'];
                        break;
                    case Extra_Model::TYPE_TEXTAREA:
                        $f['default_value'] = $field['textarea']['default_value'];
                        break;
                    case Extra_Model::TYPE_RADIO :
                        $f['default_value'] = $field['radio']['default_value'];
                        break;
                    case Extra_Model::TYPE_CHECKBOX :
                        $f['default_value'] = $field['checkbox']['default_value'];
                        break;
                    case Extra_Model::TYPE_SELECT :
                        $f['default_value'] = $field['select']['default_value'];
                        break;
                    case Extra_Model::TYPE_RANGE :
                        foreach ($field['range']['default_value'] as $key => $value) {
                            if (isset($value) && !is_numeric($value)) {
                                $error_ids[] = "field[$prefix][range][default_value][$key]";
                            } elseif (isset($value)) {
                                $field['range']['default_value'][$key] = (double)$value;
                            }
                        }

                        if (isset($field['range']['default_value'][0]) && $f['params'][0] != null && $field['range']['default_value'][0] < $f['params'][0]) {
                            $error_ids[] = "field[$prefix][range][default_value][0]";
                        }

                        if (isset($field['range']['default_value'][0]) && $f['params'][1] != null && $field['range']['default_value'][0] > $f['params'][1]) {
                            $error_ids[] = "field[$prefix][range][default_value][0]";
                        }

                        if (isset($field['range']['default_value'][1]) && $f['params'][2] != null && $field['range']['default_value'][1] < $f['params'][2]) {
                            $error_ids[] = "field[$prefix][range][default_value][1]";
                        }

                        if (isset($field['range']['default_value'][1]) && $f['params'][3] != null && $field['range']['default_value'][1] > $f['params'][3]) {
                            $error_ids[] = "field[$prefix][range][default_value][1]";
                        }

                        $f['default_value'] = $field['range']['default_value'];
                        break;
                    case Extra_Model::TYPE_DATETIME:
                        $f['default_value'] = $field['datetime']['default_value'];
                        break;
                    default :
                        $f['default_value'] = $field;
                        break;
                }
            }

            $f = Event::trigger('extra_setting.requirement.extra.field.post_submit', $field, $f)?:$f;

            $extra_fields[$uniqid] = $f;
        }

        if (count($empty_ids)) {
            JS::alert(I18N::T('extra', '请添加选项!'));
            return;
        }

        if (count($error_ids)) {
            JS::alert(I18N::T('extra', '字段值填写错误!'));
            return;
        }

        // 保存category下的fields
        if ($extra->set_category_fields($category_title, $extra_fields)) {
            if (Module::is_installed('app') && $extra->object_name == 'equipment') {
                CLI_YiQiKong::update_equipment_setting($extra->object->id);
            }

            Output::$AJAX['message'] = (string) V('extra:edit/message', ['message'=> I18N::T('extra', '自定义字段更新成功!')]);
            Output::$AJAX['new_relate'] = (string) V('extra:edit/relate',['category'=> $category_title, 'extra'=>$extra, 'relate_uniqid'=> $form['relate_uniqid']]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        } else {
            JS::alert(I18N::T('extra', '保存失败，请刷新重试!'));
            return;
        }
    }

    // 编辑field
    public function index_edit_field_click()
    {
        $form = Input::form();
        $category_title = $form['category'];
        $filed_title = $form['filed_title'];
        $extra = O('extra', $form['extra_id']);
        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title || !$filed_title) {
            JS::alert(I18N::T('extra', '编辑失败，请刷新重试!'));
            return;
        }

        $extra_field = $extra->get_field($category_title, $filed_title);
        if (!$extra_field) {
            JS::alert(I18N::T('extra', '编辑失败，请刷新重试!'));
            return;
        }

        $view = V('extra:edit/field_with_handle', [
            'prefix' => $form['prefix'],
            'extra' => $extra,
            'field' => $extra_field,
            'form' => $form,
            'edit' => true,
        ]);

        JS::dialog($view, ['title' => I18N::T('extra', '编辑字段')]);
    }

    // 编辑field
    public function index_edit_field_submit()
    {
        $_form = Form::filter(Input::form());
        parse_str($_form['field_form'], $form);
        $form = Form::filter($form);
        $extra = O('extra', $form['extra_id']);
        $category_title = $form['category'];

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title) {
            JS::alert(I18N::T('extra', '保存异常，请刷新重试!'));
            return;
        }

        $extra_fields = $extra->get_fields($category_title);
        $exist_title = [];
        $repeat_category = [];
        $repeat_ids = [];
        $error_ids = [];
        $change_ids = [];

        //空的select radio checkbox等
        $empty_ids = [];

        //获取field
        foreach((array) $form['field'] as $prefix => $field) {
            if (!$field['title']) {
                JS::alert(I18N::T('extra', '字段标题不能为空!'));
                return;
            }

            $extra_field = $extra->get_field($category_title, $field['title'], $field['original_title']);
            if (count($extra_field)) {
                JS::alert(I18N::T('extra', '字段标题[' . $field['title'] . ']重复, 请重新填写!'));
                return;
            }

            $uniqid = $extra->get_field_uniqid($category_title, $field['original_title']);
            if (strlen($uniqid) < 1) {
                JS::alert(I18N::T('extra', '保存异常，请刷新重试!'));
                return;
            }

            //初始化
            $f = [];
            $f['type'] = $field['type']; //类型
            $f['title'] = $field['title']; //标题
            $f['required'] = (int) ($field['required'] == 'on'); //是否必填
            $f['adopted'] = $field['adopted'];
            $f['remarks'] = $field['remarks'];
            
            //根据type获取params
            switch($field['type']) {
                case Extra_Model::TYPE_RADIO :
                    $f['params'] = array_filter($field['radio']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_CHECKBOX :
                    $f['params'] = array_filter($field['checkbox']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_SELECT :
                    $f['params'] = array_filter($field['select']);
                    if (!$f['params']) $empty_ids[] = $prefix;
                    break;
                case Extra_Model::TYPE_RANGE :
                    //因为范围值可能含有0 所以不使用array_filter
                    foreach ($field['range'] as $key => $value) {
                        if ($value && !is_numeric($value) && is_numeric($key)) {
                            $error_ids[] = "field[$prefix][range][$key]";
                        } elseif ($value && is_numeric($key)) {
                            $field['range'][$key] = (double)$value;
                        }
                    }

                    if (isset($field['range'][0]) && isset($field['range'][1]) && ($field['range'][0] > $field['range'][1])) {
                        $t = $field['range'][0];
                        $field['range'][0] = $field['range'][1];
                        $field['range'][1] = $t;
                    }

                    if (isset($field['range'][2]) && isset($field['range'][3]) && ($field['range'][2] > $field['range'][3])) {
                        $t = $field['range'][2];
                        $field['range'][2] = $field['range'][3];
                        $field['range'][3] = $t;
                    }

                    $f['params'] = $field['range'];
                    if (!$f['params']) $empty_ids[] = $prefix;
                    if ($f['params'][0] == '' && $f['params'][1] == '' && $f['params'][2] == '' && $f['params'][3] == '') {
                        $empty_ids[] = $prefix;
                    } elseif ($f['params'][0] == '' || $f['params'][1] == '' || $f['params'][2] == '' || $f['params'][3] == '') {
                        $error_ids[] = $prefix;
                    }
                    break;
                case Extra_Model::TYPE_NUMBER :
                    //因为范围值可能含有0 所以不使用array_filter
                    if ($field['number']) foreach ($field['number'] as $key => $value) {
                        if ($value && !is_numeric($value)) {
                            $error_ids[] = "field[$prefix][number][$key]";
                        } elseif ($value && is_numeric($key)) {
                            $field['number'][$key] = (double)$value;
                        }
                    }

                    if ($field['number'][0] != '' && $field['number'][1] != '' && ($field['number'][0] > $field['number'][1])) {
                        $t = $field['number'][0];
                        $field['number'][0] = $field['number'][1];
                        $field['number'][1] = $t;
                    }
                    $f['params'] = $field['number'];
                    if (!$f['params'] && !$field['adopted']) $empty_ids[] = $prefix;
                    if ($f['params'][0] == '' && $f['params'][1] == '') {
                        $empty_ids[] = $prefix;
                    } elseif ($f['params'][0] == '' || $f['params'][1] == '') {
                        $error_ids[] = $prefix;
                    }
                    break;
                default :
                    $f['params'] = NULL;
                    break;
            }
            unset($f['params']['default_value']);

            $f['default'] = 0;
            if ($field['default'] == 'on') {
                $f['default'] = 1;
                switch($field['type']) {
                    case Extra_Model::TYPE_NUMBER:
                        if (isset($field['number']['default_value']) && !is_numeric($field['number']['default_value'])) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        } elseif (isset($field['number']['default_value'])) {
                            $field['number']['default_value'] = (double)$field['number']['default_value'];
                        }

                        if (isset($field['number']['default_value']) && $f['params'][0] != null && $field['number']['default_value'] < $f['params'][0]) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        }

                        if (isset($field['number']['default_value']) && $f['params'][1] != null && $field['number']['default_value'] > $f['params'][1]) {
                            $error_ids[] = "field[$prefix][number][default_value]";
                        }

                        $f['default_value'] = $field['number']['default_value'];
                        break;
                    case Extra_Model::TYPE_TEXT:
                        $f['default_value'] = $field['text']['default_value'];
                        break;
                    case Extra_Model::TYPE_TEXTAREA:
                        $f['default_value'] = $field['textarea']['default_value'];
                        break;
                    case Extra_Model::TYPE_RADIO :
                        $f['default_value'] = $field['radio']['default_value'];
                        break;
                    case Extra_Model::TYPE_CHECKBOX :
                        $f['default_value'] = $field['checkbox']['default_value'];
                        break;
                    case Extra_Model::TYPE_SELECT :
                        $f['default_value'] = $field['select']['default_value'];
                        break;
                    case Extra_Model::TYPE_RANGE :
                        foreach ($field['range']['default_value'] as $key => $value) {
                            if (isset($value) && !is_numeric($value)) {
                                $error_ids[] = "field[$prefix][range][default_value][$key]";
                            } elseif (isset($value)) {
                                $field['range']['default_value'][$key] = (double)$value;
                            }
                        }

                        if (isset($field['range']['default_value'][0]) && $f['params'][0] != null && $field['range']['default_value'][0] < $f['params'][0]) {
                            $error_ids[] = "field[$prefix][range][default_value][0]";
                        }

                        if (isset($field['range']['default_value'][0]) && $f['params'][1] != null && $field['range']['default_value'][0] > $f['params'][1]) {
                            $error_ids[] = "field[$prefix][range][default_value][0]";
                        }

                        if (isset($field['range']['default_value'][1]) && $f['params'][2] != null && $field['range']['default_value'][1] < $f['params'][2]) {
                            $error_ids[] = "field[$prefix][range][default_value][1]";
                        }

                        if (isset($field['range']['default_value'][1]) && $f['params'][3] != null && $field['range']['default_value'][1] > $f['params'][3]) {
                            $error_ids[] = "field[$prefix][range][default_value][1]";
                        }

                        $f['default_value'] = $field['range']['default_value'];
                        break;
                    case Extra_Model::TYPE_DATETIME:
                        $f['default_value'] = $field['datetime']['default_value'];
                        break;
                    default :
                        $f['default_value'] = $field;
                        break;
                }
            }

            $f = Event::trigger('extra_setting.requirement.extra.field.post_submit', $field, $f)?:$f;

            $extra_fields[$uniqid] = $f;
        }

        if (count($empty_ids)) {
            JS::alert(I18N::T('extra', '请添加选项!'));
            return;
        }

        if (count($error_ids)) {
            JS::alert(I18N::T('extra', '字段值填写错误!'));
            return;
        }

        // 保存category下的fields
        if ($extra->set_category_fields($category_title, $extra_fields)) {
            if (Module::is_installed('app') && $extra->object_name == 'equipment') {
                CLI_YiQiKong::update_equipment_setting($extra->object->id);
            }

            Output::$AJAX['message'] = (string) V('extra:edit/message', ['message'=> I18N::T('extra', '自定义字段更新成功!')]);
            Output::$AJAX['new_relate'] = (string) V('extra:edit/relate',['category'=> $category_title, 'extra'=>$extra, 'relate_uniqid'=> $form['relate_uniqid']]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        } else {
            JS::alert(I18N::T('extra', '保存失败，请刷新重试!'));
            return;
        }
    }

    // 删除字段
    public function index_delete_field_click()
    {
        $form = Form::filter(Input::form());
        $extra = O('extra', $form['extra_id']);
        $category_title = $form['category'];
        $filed_title = $form['filed_title'];

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title || !$filed_title) {
            JS::alert(I18N::T('extra', '删除失败，请刷新重试!'));
            return;
        }

        if (JS::confirm(I18N::T('extra', '是否确定要删除该字段?'))) {
            $extra_fields = $extra->get_fields($category_title);
            foreach($extra_fields as $uniqid => $field) {
                if ($field['title'] == $filed_title) {
                    unset($extra_fields[$uniqid]);
                    break;
                }
            }

            if ($extra->set_category_fields($category_title, $extra_fields)) {
                if (Module::is_installed('app') && $extra->object_name == 'equipment') {
                    CLI_YiQiKong::update_equipment_setting($extra->object->id);
                }

                Output::$AJAX['result'] = true;
            } else {
                JS::alert(I18N::T('extra', '保存异常，请刷新重试!'));
                return;
            }
        }
    }

    // 点击字段上移、下移、最前、最后按钮
    public function index_sort_field_click()
    {
        $form = Form::filter(Input::form());
        $category_title = $form['category']; // 分类名称
        $filed_title = $form['filed_title']; // 字段名称
        $field_sort_type = $form['field_sort_type']; // 排序类型
        $relate_uniqid = $form['relate_uniqid']; // 分类字段列表显示ID
        $extra = O('extra', $form['extra_id']);

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title || !$filed_title) {
            Output::$AJAX['message'] = (string) V('extra:edit/error_message', ['message'=> I18N::T('extra', '调整失败，请刷新重试!')]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
            return;
        }

        $index = [];
        $new_extra_fields = [];
        $extra_fields = $extra->get_fields($category_title);
        $uniqid = $extra->get_field_uniqid($category_title, $filed_title);
        $extra_fields_keys = array_keys($extra_fields);

        foreach(array_keys($extra_fields) as $key => $item) {
            if ($item == $uniqid) {
                switch ($field_sort_type) {
                    case 'up':
                        if (isset($extra_fields_keys[$key - 1])) {
                            $tmp = $extra_fields_keys[$key - 1];
                            $extra_fields_keys[$key - 1] = $uniqid;
                            $extra_fields_keys[$key] = $tmp;
                        }
                        break;
                    case 'down':
                        if (isset($extra_fields_keys[$key + 1])) {
                            $tmp = $extra_fields_keys[$key + 1];
                            $extra_fields_keys[$key + 1] = $uniqid;
                            $extra_fields_keys[$key] = $tmp;
                        }
                        break;
                    case 'top':
                        unset($extra_fields_keys[$key]);
                        array_unshift($extra_fields_keys, $uniqid);
                        break;
                    case 'bottom':
                        unset($extra_fields_keys[$key]);
                        $extra_fields_keys[] = $uniqid;
                        break;
                }
            }
        }

        foreach($extra_fields_keys as $uniqid) {
            $new_extra_fields[$uniqid] = $extra_fields[$uniqid];
        }

        // 保存category下的fields
        if ($extra->set_category_fields($category_title, $new_extra_fields)) {
            if (Module::is_installed('app') && $extra->object_name == 'equipment') {
                CLI_YiQiKong::update_equipment_setting($extra->object->id);
            }

            Output::$AJAX['message'] = (string) V('extra:edit/message', ['message'=> I18N::T('extra', '自定义字段更新成功!')]);
            Output::$AJAX['new_relate'] = (string) V('extra:edit/relate',['category'=> $category_title, 'extra'=>$extra, 'relate_uniqid'=> $form['relate_uniqid']]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        } else {
            Output::$AJAX['message'] = (string) V('extra:edit/error_message', ['message'=> I18N::T('extra', '调整失败，请刷新重试!')]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        }
    }

    // 鼠标拖动字段排序
    public function index_sort_fields_move()
    {
        $form = Form::filter(Input::form());
        $category_title = $form['category']; // 分类名称
        $relate_uniqid = $form['relate_uniqid']; // 分类字段列表显示ID
        $fields = $form['fields'];
        $extra = O('extra', $form['extra_id']);

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra) || !$category_title) {
            Output::$AJAX['message'] = (string) V('extra:edit/error_message', ['message'=> I18N::T('extra', '调整失败，请刷新重试!')]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
            return;
        }

        $new_extra_fields = [];
        foreach($fields as $filed_title) {
            $uniqid = $extra->get_field_uniqid($category_title, $filed_title);
            $field = $extra->get_field($category_title, $filed_title);
            if ($field) {
                $new_extra_fields[$uniqid] = $field;
            }
        }

        // 保存category下的fields
        if ($extra->set_category_fields($category_title, $new_extra_fields)) {
            if (Module::is_installed('app') && $extra->object_name == 'equipment') {
                CLI_YiQiKong::update_equipment_setting($extra->object->id);
            }

            Output::$AJAX['message'] = (string) V('extra:edit/message', ['message'=> I18N::T('extra', '自定义字段更新成功!')]);
            Output::$AJAX['new_relate'] = (string) V('extra:edit/relate',['category'=> $category_title, 'extra'=>$extra, 'relate_uniqid'=> $form['relate_uniqid']]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        } else {
            Output::$AJAX['message'] = (string) V('extra:edit/error_message', ['message'=> I18N::T('extra', '调整失败，请刷新重试!')]);
            Output::$AJAX['relate_uniqid'] = (string) $form['relate_uniqid'];
        }
    }

    //勾选默认值
    public function index_add_default_click()
    {
        $form = Input::form();
        $value = json_decode($form['value'], true);
        $readonly = $form['readonly'];

        switch ($form['type']) {
            case Extra_Model::TYPE_TEXT:
                Output::$AJAX = (string) V('extra:default/text', [
                    'prefix' => $form['prefix'],
                    'value' => $value,
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_TEXTAREA:
                Output::$AJAX = (string) V('extra:default/textarea', [
                    'prefix' => $form['prefix'],
                    'default_value' => $form['value'],
                    'value' => $value,
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_NUMBER:
                Output::$AJAX = (string) V('extra:default/number', [
                    'prefix' => $form['prefix'],
                    'value' => $value,
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_RANGE:
                Output::$AJAX = (string) V('extra:default/range', [
                    'prefix' => $form['prefix'],
                    'min' => $value[0],
                    'max' => $value[1],
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_CHECKBOX:
                Output::$AJAX = (string) V('extra:default/checkbox', [
                    'prefix' => $form['prefix'],
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_RADIO:
                Output::$AJAX = (string) V('extra:default/radio', [
                    'prefix' => $form['prefix'],
                    'readonly' => $readonly
                ]);
                break;
            case Extra_Model::TYPE_DATETIME:
                Output::$AJAX = (string) V('extra:default/datetime', [
                    'prefix' => $form['prefix'],
                    'value' => $value,
                    'readonly' => $readonly
                ]);
                break;
            default:
                Output::$AJAX = (string) V('extra:default/default', [
                    'prefix' => $form['prefix'],
                    'readonly' => $readonly
                ]);
                break;
        }
    }

    //添加radio
    public function index_add_radio_click()
    {
        $form = Input::form();

        Output::$AJAX = (string) V('extra:edit/item/radio_item', [
            'prefix' => $form['prefix'],
            'subprefix'=> $form['subprefix'],
        ]);
    }

    //添加checkbox
    public function index_add_checkbox_click()
    {
        $form = Input::form();

        Output::$AJAX = (string) V('extra:edit/item/checkbox_item', [
            'prefix'=> $form['prefix'],
            'subprefix'=> $form['subprefix'],
        ]);
    }

    //添加select
    public function index_add_select_click()
    {
        $form = Input::form();

        Output::$AJAX = (string) V('extra:edit/item/select_item', [
            'prefix'=> $form['prefix'],
            'subprefix'=> $form['subprefix'],
        ]);
    }

    //修改category名称
    public function index_edit_category_blur()
    {
        $form = Input::form();

        $extra = O('extra', $form['extra_id']);

        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra)) {
            return;
        }

        //返回是否修改成功
        Output::$AJAX['result'] = (bool) $extra->rename_category($form['category'], $form['new_category']);
    }

    //点击category进行category切换
    public function index_select_category_click()
    {
        $form = Input::form();

        $extra = O('extra', $form['extra_id']);

        if (!$extra->id) return;

        if ($form['readonly']) {
            $data = (string) V('extra:edit/readonly', [
                'category'=> $form['category'],
                'extra'=> $extra,
                'relate_uniqid'=> $form['uniqid'],
            ]);
        } else {
            $data = (string) V('extra:edit/relate_content', [
                'category'=> $form['category'],
                'extra'=> $extra,
                'relate_uniqid'=> $form['uniqid'],
            ]);
        }

        Output::$AJAX['#'. $form['uniqid']. ' .relate_view'] = [
            'data'=> $data,
            'mode'=> 'replace'
        ];
    }

    // 分类排序
    public function index_category_change_weight()
    {
        $form = Input::form();

        $extra = O('extra', $form['extra_id']);
        if (!$extra->id || !L('ME')->is_allowed_to('修改', $extra)) {
            return FALSE;
        }

        $categories = $extra->get_categories();

        if (!in_array($form['category'], $categories)) return FALSE;

        $params = [];

        foreach($categories as $category) {

            if (!$form['prev_category']) $params[$form['category']] = $extra->get_fields($form['category']);

            //如果为需要移动的category 跳过
            if ($form['category'] == $category) continue;

            //普通设定
            $params[$category] = $extra->get_fields($category);

            if ($category == $form['prev_category']) {
                $params[$form['category']] = $extra->get_fields($form['category']);
            }
        }

        $extra->params = $params;
        $extra->save();
    }
}

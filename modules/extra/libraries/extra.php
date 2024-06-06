<?php

class Extra {

    static function setup() {

        Extra_Model::$type_name = [     
            self::TYPE_RADIO => I18N::T('extra','单选'),
            self::TYPE_CHECKBOX => I18N::T('extra','多选'),
            self::TYPE_NUMBER => I18N::T('extra','数值'),
            self::TYPE_TEXT => I18N::T('extra','单行文本'),
            self::TYPE_TEXTAREA => I18N::T('extra','多行文本'),
            self::TYPE_SELECT => I18N::T('extra','下拉菜单'),
            self::TYPE_RANGE => I18N::T('extra','数值范围'),
            self::TYPE_DATETIME => I18N::T('extra','日期时间')
        ];
    }

    static function save_extra_value($e, $object, $form) {
        $extra_value = O('extra_value', ['object'=>$object]);
        if(!$extra_value->id) $extra_value->object = $object;
        $extra_value->values = $form['extra_fields'];
        $extra_value->save();
    }

    static function validate_extra_value($e, $object, $type, $form, $category = NULL) {

    	$extra = O('extra', ['object'=>$object, 'type'=>$type]);
    	if(!$extra->id) return;

    	$extra_fields_value = $form['extra_fields'];

        if ($category) {
            $extra_fields = (array) $extra->get_fields($category);
        }
        else {
            $extra_fields = (array)$extra->get_fields();
        }

		foreach ($extra_fields as $uniqid => $field) {
            self::validate_extra_value_field($extra_fields_value, $field, $uniqid, $uniqid, $form);
		}
    }

    static function validate_extra_value_field($extra_fields_value, $field, $prefix, $uniqid, $form) {
        //如果是数字字段，应该验证是否为数字
        switch ($field['type']) {
            case Extra_Model::TYPE_NUMBER:
                $name = 'extra_fields['.$prefix.']';
                if($extra_fields_value[$uniqid] && !is_numeric($extra_fields_value[$uniqid])){
                    $form->set_error($name,  I18N::T('extra', '%field 填写有误, 请填写数值!', ['%field'=>$field['title']]));
                }
                elseif (isset($extra_fields_value[$uniqid]) && is_numeric($extra_fields_value[$uniqid])) {
                    $value = $extra_fields_value[$uniqid];
                    $value0 = $field['params'][0];
                    $value1 = $field['params'][1];
                    $message = I18N::T('extra', '%field 填写有误, 范围为 %min1 - %max1', [
                        '%field' => $field['title'],
                        '%min1' => is_numeric($value0) ? $value0 : '-∞',
                        '%max1' => is_numeric($value1) ? $value1 : '+∞',
                    ]);
                    if ($value0 != null && $value < $value0) {
                        $form->set_error($name, $message);
                    }
                    if ($value1 != null && $value > $value1) {
                        $form->set_error($name, $message);
                    }
                }
                break;
            case Extra_Model::TYPE_RANGE:
                foreach ($extra_fields_value[$uniqid] as $key => $value) {
                    $name = "extra_fields[$prefix][$key]";
                    if($value && !is_numeric($value)){
                        $form->set_error($name,  I18N::T('extra', '%field 填写有误, 请填写数值!', ['%field'=>$field['title']]));
                    }
                    elseif (isset($value) && is_numeric($value)) {
                        $value0 = $field['params'][0];
                        $value1 = $field['params'][1];
                        $value2 = $field['params'][2];
                        $value3 = $field['params'][3];
                        $message = I18N::T('extra', '%field 填写有误, 第一值范围为 %min1 - %max1, 第二值范围为 %min2 - %max2 !', [
                            '%field' => $field['title'], 
                            '%min1' => is_numeric($value0) ? $value0 : '-∞', 
                            '%max1' => is_numeric($value1) ? $value1 : '+∞',
                            '%min2' => is_numeric($value2) ? $value2 : '-∞', 
                            '%max2' => is_numeric($value3) ? $value3 : '+∞',
                        ]);
                        switch ($key) {
                            case 0:
                                if ($value0 != null && $value < $value0) {
                                    $form->set_error($name, $message);
                                }
                                if ($value1 != null && $value > $value1) {
                                    $form->set_error($name, $message);
                                }
                                break;
                            case 1:
                                if ($value2 != null && $value < $value2) {
                                    $form->set_error($name, $message);
                                }
                                if ($value3 != null && $value > $value3) {
                                    $form->set_error($name, $message);
                                }
                                break;
                            default:
                                $form->set_error($name,  I18N::T('extra', '%field 填写有误, 请填写数值!', ['%field'=>$field['title']]));
                                break;
                        }
                    }
                }
            default:
                break;
        }

        if(!$field['required']) return false;
        //如果是默认字段则跳过
        if($field['adopted'] && !$field['adopted_edit']) return false;
        switch ($field['type']) {
            case Extra_Model::TYPE_CHECKBOX:
                $checkbox_value = $extra_fields_value[$uniqid];
                if(!count(array_filter($checkbox_value, function($value){return ($value == 'on');}))){
                    // 此乃何意
                    /* foreach((array)array_keys($checkbox_value) as $id => $value) {
                        $name = 'extra_fields['.$prefix.']['.$value.']';
                        $form->set_error($name, NULL);
                    } */
                    $name = "extra_fields[{$uniqid}]";
                    $form->set_error($name,  I18N::T('extra', '%field 不能为空!', ['%field'=>$field['title']]));
                }
                break;
            case Extra_Model::TYPE_SELECT:
                if (!$extra_fields_value[$uniqid] || $extra_fields_value[$uniqid] == -1) {
                    $name = "extra_fields[{$uniqid}]";
                    $form->set_error($name,  I18N::T('extra', '%field 不能为空!', ['%field'=>$field['title']]));
                }
                break;
            case Extra_Model::TYPE_NUMBER:
                if(!strlen($extra_fields_value[$uniqid])){
                    $name = "extra_fields[{$uniqid}]";
                    $form->set_error($name,  I18N::T('extra', '%field 不能为空!', ['%field'=>$field['title']]));
                }
                break;
            case Extra_Model::TYPE_RANGE:
                foreach ($extra_fields_value[$uniqid] as $key => $value) {
                    if ($value == null) {
                        $name = "extra_fields[{$uniqid}]";
                        $form->set_error($name,  I18N::T('extra', '%field 不能为空!', ['%field'=>$field['title']]));
                    }
                }
                break;
            default:
                if($extra_fields_value[$uniqid] == '' || $extra_fields_value[$uniqid] == null){
                    $name = "extra_fields[{$uniqid}]";
                    $form->set_error($name,  I18N::T('extra', '%field 不能为空!', ['%field'=>$field['title']]));
                }
                break;
        }
    }
}

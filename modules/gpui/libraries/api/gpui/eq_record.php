<?php
class API_GPUI_Eq_Record extends API_Common
{

    /**
     * 获取反馈记录可以选择的项目
     * @param int $record_id
     * @return array
     *
     */
    public function projectByRecord($record_id = 0)
    {
        $this->_ready('gpui');
        if (!$record_id) {
            return [];
        }

        $record = O('eq_record', $record_id);
        if (!$record->id) {
            return [];
        }

        $types = [];

        $labs = Q("{$record->user} lab");

        foreach ($labs as $lab) {
            $items = $lab->get_project_items($record->user);

            if ($record->project->id && $record->project->lab->id == $lab->id) {
                $items[I18N::T('labs', Lab_Project_Model::$types[$record->project->type])][$record->project->id] = $record->project->name;
            }
            foreach ($items as $item) {
                foreach ($item as $key => $value) {
                    $types["$key"] = $value;
                }
            }
        }

        return $types;
    }

    /**
    +     * 获取反馈记录时定制项
    +     * @param int $record_id
    +     * @return array
    +     *
    +     */
    public function defaultSettings()
    {
        // 22614（3）17Kong/Sprint-252：1.4.0全面测试：平板离线，下机填写反馈，选择故障，样品数0，联网后，cf对应的使用记录样品数显示1
        $this->_ready('gpui');
        $settings = [];
        $settings['samples'] = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples', 1);
        return $settings;
    }

    public function feedbackSettings($eqid,$object_id= 0,$object_name = 'eq_record')
    {
        
        $this->_ready('gpui');
        if(is_array($eqid)){
            $query = [];
            $query['equipment_id'] = $params['equipment_id'];
            $query['object_name'] = $params['object_name'] ?? '';
            $query['object_id'] = $params['object_id'] ?? 0;
        }else{
            $query = [];
            $query['equipment_id'] = $eqid;
            $query['object_id'] = $object_id;
            $query['object_name'] = $object_name;
        }
        
        $settings = Event::trigger('equipment.api.v1.feedback-schema.GET',[], [], $query) ?? [];

        return $settings;
    }

    /**
     * 增加反馈记录
     * @param int $record_id
     * @param array $params
     * @return array
     */
    public function feedbackSubmit($record_id = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$record_id) {
            return [];
        }

        $record = O('eq_record', $record_id);

        if (!$record->id) {
            return [];
        }


        $equipment = $record->equipment;
        if (!$equipment->id || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return [];
        }


        if (empty($params['status'])) {
            return [
            "error_code" => 0,
            "error_mesage" => I18N::T('equipments', '请选择当前状态!')
        ];
        }

        if (!$record->cannot_lock_samples() && !$record->samples_lock && Config::get('feedback.require_samples')) {
            if (Config::get('equipment.feedback_samples_allow_zero', false) || Event::trigger('eq_record.check_samples_is_allow_zero', $record)) {
                if (!is_numeric($params['samples']) || intval($params['samples'])<0 || intval($params['samples'])!=$params['samples']) {
                    return [
                        "error_code" => 0,
                        "error_mesage" => I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!')
                    ];
                }
            } else {
                if (!is_numeric($params['samples']) || intval($params['samples'])<=0 || intval($params['samples'])!=$params['samples']) {
                    return [
                        "error_code" => 0,
                        "error_mesage" => I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!')
                    ];
                }
            }
        }

        if (class_exists('Lab_Project_Model')) {
            if (Config::get('eq_record.must_connect_lab_project')) {
                if ($params['project']==0) {
                    return [
                        "error_code" => 0,
                        "error_mesage" => I18N::T('equipments', '"关联项目" 不能为空!')
                    ];
                }
            }
            $record->project = O('lab_project', $params['project']);
        }

        //反馈信息包括：仪器状态（正常、故障）、样品测试数、关联项目、确认提交操作
        Event::trigger('feedback.form.submit', $record, $params);

        $record->status = $params['status'];
        //设定samples
        if (!$record->samples_lock && ($params['samples'] >= 0)) {
            $record->samples = (int)$params['samples'];
        }

        $record->save();
        Log::add(strtr(
            '[equipments] 通过"仪器平板"填写了%equipment_name[%equipment_id]仪器的使用记录[%record_id]反馈',
            ['%equipment_name'=> $equipment->name,
                '%equipment_id'=> $equipment->id,
                '%record_id'=> $record->id]
        ), 'journal');
        return ["error_code" => 1];
    }
}

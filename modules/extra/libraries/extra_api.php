<?php
class Extra_API
{
    public static function equipment_reserv_schema($e, $params, $data, $query)
    {
        $equipment = O("equipment", $params[0]);
        if (!$equipment->id) {
            throw new Exception("equipment not found", 404);
        }

        $extra = O("extra", ["object" => $equipment, "type" => "eq_reserv"]);
        if (!$extra->id) {
            throw new Exception("equipment-reserv-schema not found", 404);
        }
        $e->return_value = self::extra_format($extra);
    }

    public static function equipment_sample_schema($e, $params, $data, $query)
    {
        $equipment = O("equipment", $params[0]);
        if (!$equipment->id) {
            throw new Exception("equipment not found", 404);
        }

        $extra = O("extra", ["object" => $equipment, "type" => "eq_sample"]);
        if (!$extra->id) {
            throw new Exception("equipment-sample-schema not found", 404);
        }
        $ret = self::extra_format($extra);

        $ret["properties"]["category_0"]["properties"]["submitTime"] = [
            "title" => "送样时间",
            "default" => Date::time(),
            "type" => "string",
            "format" => "dateTime",
        ];
        $ret["properties"]["category_0"]["required"][] = "submitTime";

        // TODO: 有权限才开启下面这一堆表单
        $ret["properties"]["category_0"]["properties"]["status"] = [
            "title" => "送样状态",
            "enum" => array_values(Eq_Sample_API::$status_label),
            "enumNames" => array_values(EQ_Sample_Model::$status),
            "type" => "string",
            "widget" => "select",
            "privileges" => ["incharge", "admin"]
        ];
        $ret["properties"]["category_0"]["required"][] = "status";

        $ret["properties"]["category_0"]["properties"]["startTimeCheck"] = [
            "title" => "测样开始时间",
            "type" => "boolean",
            "privileges" => ["incharge", "admin"]
        ];
        $ret["properties"]["category_0"]["properties"]["startTime"] = [
            "title" => "测样开始时间",
            "type" => "string",
            "format" => "dateTime",
            "privileges" => ["incharge", "admin"]
        ];
        $ret["properties"]["category_0"]["properties"]["endTime"] = [
            "title" => "测样结束时间",
            "type" => "string",
            "format" => "dateTime",
            "privileges" => ["incharge", "admin"]
        ];
        $ret["properties"]["category_0"]["properties"]["pickupTime"] = [
            "title" => "取样时间",
            "type" => "string",
            "format" => "dateTime",
            "privileges" => ["incharge", "admin"]
        ];
        $ret["properties"]["category_0"]["properties"]["successSamples"] = [
            "title" => "成功测样数",
            "type" => "number",
            "format" => "integer",
            "min" => 0,
            "privileges" => ["incharge", "admin"]
        ];
        $e->return_value = $ret;
    }

    public static function equipment_feedback_schema($e, $params, $data, $query)
    {
        $equipment = O('equipment', $params[0]);
        $ret = [
            "type" => "object",
            "properties" => [
                "feedback" => [
                    "title" => "{$equipment->name}的使用反馈",
                    "type" => "object",
                    "properties" => [
                        'status' => [
                            "title" => "仪器状态",
                            "type" => "string",
                            "enum" => [
                                "normal",
                                "problem",
                            ],
                            "enumNames" => [
                                "运行正常",
                                "运行故障"
                            ],
                            "widget" => "radio"
                        ],
                        'feedback' => [
                            "title" => "使用反馈",
                            "type" => "string",
                            "format" => "textarea"
                        ]
                    ]
                ]
            ]
        ];

        $show_sampls = (int)Config::get('equipment.feedback_show_samples', 0);

        if ($show_sampls){
            $require_samples = (bool) Config::get('feedback.require_samples');
            $defaut_samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
            if (isset($query['object_name']) && $query['object_name'] == 'eq_record'){
                $record = O($query['object_name'],$query['object_id']);
                //预约记录生成使用记录后，使用记录自动取关联预约的样品数，若预约时未填写样品数，对应使用记录的样品数默认为0。
                $defaut_samples = $record->reserv->id ? (int)$record->reserv->count : $record->samples;
                if (!$record->reserv->id && $record->dtend) {
                    $reserv = Q("eq_reserv[equipment={$record->equipment}][user={$record->user}][dtstart=$record->dtstart~$record->dtend|dtstart~dtend=$record->dtstart][dtend!=$record->dtstart]:sort(dtstart A):limit(1)")->current();
                    if ($reserv->id) {
                        if (!$record->samples || $record->samples == 1 || !Config::get('equipment.feedback_show_samples', 0)) 
                        $defaut_samples = (int) $reserv->count;
                    }
                }  
            }
            //如果显示样品数，则增加样品数
            $ret['properties']['feedback']['properties']['samples'] = [
                "title" => "样品数",
                "type" => "number",
                "require" => $require_samples,
                "default" => $defaut_samples
            ];
        }

        if (Module::is_installed('eq_comment')) {
            $ret["properties"]["eqComment1"] = [
                "title" => "仪器管理员服务评价",
                "type" => "object",
                "properties" => [
                    'serviceAttitude' => [
                        "title" => "服务态度",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'serviceQuality' => [
                        "title" => "服务质量",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'technicalAbility' => [
                        "title" => "技术能力",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'emergencyCapability' => [
                        "title" => "应急处理能力",
                        "type" => "number",
                        "widget" => "star"
                    ]
                ]
            ];
            $ret["properties"]["eqComment2"] = [
                "title" => "仪器性能评价",
                "type" => "object",
                "properties" => [
                    'detectionPerformance' => [
                        "title" => "检测性能",
                        "type" => "number",
                        "widget" => "star"
                    ]
                ]
            ];
            $ret["properties"]["eqComment3"] = [
                "title" => "检测结果评价",
                "type" => "object",
                "properties" => [
                    'accuracy' => [
                        "title" => "准确性",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'compliance' => [
                        "title" => "预期目标吻合度",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'timeliness' => [
                        "title" => "测试及时性",
                        "type" => "number",
                        "widget" => "star"
                    ],
                    'sampleProcessing' => [
                        "title" => "样品的保管与处理",
                        "type" => "number",
                        "widget" => "star"
                    ]
                ]
            ];
            $ret["properties"]["commentSuggestion"] = [
                "title" => "服务评价及建议",
                "type" => "string",
                "format" => "textarea"
            ];
        }
        $e->return_value = $ret;
    }

    public static function equipment_log_schema($e, $params, $data, $query)
    {
        $equipment = O("equipment", $params[0]);
        $object = O("eq_record", $query['logId']);
        $me = L('gapperUser');
        if (!$equipment->id) {
            throw new Exception("equipment not found", 404);
        }
        if ($object->id) {
            $time = Lab::get('transaction_locked_deadline');

            /* Deadline 时间限制, 在transaction_locked_deadline范围内的记录将不予修改 */
            $dtstart = $object->dtstart;
            $dtend   = $object->dtend;

            $edit_record_before_deadline = $dtend && $dtend <= $time;

            //虽然被锁定时段内, 但是该使用记录为使用中
            //则可被修改
            if ($edit_record_before_deadline && $object->get('dtend', true)) {
                $e->return_value = [];
                return false;
            }

            if ($object->is_locked()) {
                $e->return_value = [];
                return false;
            }
        }

        if (!$me->is_allowed_to('修改仪器使用记录', $equipment)) {
            $e->return_value = [];
            return false;
        }

        $extra = O("extra", ["object" => $equipment, "type" => "use"]);
        if (!$extra->id) {
            throw new Exception("equipment-log-schema not found", 404);
        }
        $ret = self::extra_format($extra);
        $ret['properties']['userId'] = [
            "title" => "使用者",
            "type" => "number",
        ];
        if ($me->is_allowed_to('管理仪器临时用户', $equipment)) {
            $ret['properties']['userId']['widget'] = 'newUser';
        }
        if ($me->is_allowed_to('修改代开者', $equipment)) {
            $ret['properties']['agentId'] = [
                "title" => "代开者",
                "type" => "number",
            ];
        }

        $eq_preheat_cooling = Equipment_Preheat_Cooling::get_preheat_cooling($equipment);
        if ($eq_preheat_cooling->preheat_time) {
            $ret['properties']['preheat'] = [
                "title" => "预热",
                "type" => "boolean",
            ];
        }
        if ($eq_preheat_cooling->cooling_time) {
            $ret['properties']['cooling'] = [
                "title" => "冷却",
                "type" => "boolean",
            ];
        }
        $ret['properties']['amount'] = [
            "title" => "计费",
            "type" => "number",
        ];
        if (Config::get('eq_record.charge_desc')) {
            $ret['properties']['chargeDesc'] = [
                "title" => "修改金额理由",
                "type" => "string",
                "format" => "textarea",
            ];
        }
        $ret['properties']['dtstart'] = [
            "title" => "开始时间",
            "type" => "dateTime",
        ];
        $ret['properties']['dtend'] = [
            "title" => "结束时间",
            "type" => "dateTime",
        ];
        if (Config::get('equipment.enable_use_type')) {
            $ret['properties']['type'] = [
                "title" => "使用类型",
                "type" => "string",
                "enum" => [
                    "use",
                    "training",
                    "teaching",
                    "maintain",
                    "sample",
                    "analysis",
                ],
                "enumNames" => [
                    "使用",
                    "培训",
                    "教学",
                    "保养维修",
                    "委托测试",
                    "数据分析",
                ],
                "widget" => "radio"
            ];
        }
        $ret['properties']['file'] = [
            "title" => "上传文件",
            "type" => "file",
        ];
        $e->return_value = $ret;
    }

    public static function extra_format($extra)
    {
        $categories = $extra->get_categories();
        $ret = [
            "type" => "object",
            "properties" => []
        ];

        foreach ($categories as $ck => $category) {
            $ret["properties"]["category_{$ck}"] = [
                "title" => $category,
                "type" => "object",
                "properties" => []
            ];

            $required = [];
            $fields = $extra->get_fields($category);

            foreach ($fields as $fk => $field) {
                if ($field["adopted"]) {
                    $field_key = $fk;
                } else {
                    $field_key = "field_{$fk}";
                }
                if ($field["required"]) {
                    $required[] = $field_key;
                }
                $json_schema = [
                    "title" => $field["title"],
                    "description" => $field["remarks"]
                ];
                if ($field["default"]) {
                    $json_schema["default"] = $field["default_value"];
                }
                switch ($field["type"]) {
                    case Extra_Model::TYPE_RADIO: // 单选
                        $json_schema["enum"] = array_map(function ($item) {
                            return strval($item);
                        }, array_keys($field["params"]));
                        $json_schema["enumNames"] = array_values($field["params"]);
                        $json_schema["type"] = "string";
                        $json_schema["widget"] = "radio";
                        break;
                    case Extra_Model::TYPE_CHECKBOX: // 多选
                        $json_schema["enum"] = array_map(function ($item) {
                            return strval($item);
                        }, array_keys($field["params"]));
                        $json_schema["enumNames"] = array_values($field["params"]);
                        $json_schema["type"] = "array";
                        $json_schema["items"] = ["type" => "string"];
                        $json_schema["widget"] = "checkboxes";
                        if ($field["default"]) {
                            $json_schema["default"] =
                                array_map(function ($item) {
                                    return strval($item);
                                }, array_keys($field["default_value"]));
                        }
                        break;
                    case Extra_Model::TYPE_TEXT: // 单行文本
                        $json_schema["type"] = "string";
                        break;
                    case Extra_Model::TYPE_NUMBER: // 数值
                        $json_schema["type"] = "number";
                        if ($field["params"] && count($field["params"]) == 2) {
                            if ($field["params"][0] !== "") {
                                $json_schema["min"] = floatval($field["params"][0]);
                            }
                            if ($field["params"][1] !== "") {
                                $json_schema["max"] = floatval($field["params"][1]);
                            }
                        }
                        break;
                    case Extra_Model::TYPE_TEXTAREA: // 多行文本
                        $json_schema["type"] = "string";
                        $json_schema["format"] = "textarea";
                        break;
                    case Extra_Model::TYPE_SELECT: // 下拉菜单
                        $json_schema["enum"] = array_map(function ($item) {
                            return strval($item);
                        }, array_keys($field["params"]));
                        $json_schema["enumNames"] = array_values($field["params"]);
                        $json_schema["type"] = "string";
                        $json_schema["widget"] = "select";
                        break;
                    case Extra_Model::TYPE_RANGE: // 数值范围
                        $json_schema_pre = [
                            "title" => $field["title"] . "(起始)",
                            "description" => $field["remarks"],
                            "type" => "number"
                        ];
                        $json_schema["title"] = $field["title"] . "(结束)";
                        $json_schema["type"] = "number";
                        if ($field["default"]) {
                            $json_schema_pre["default"] = $field["default_value"][0];
                            $json_schema["default"] = $field["default_value"][1];
                        }
                        if ($field["required"]) {
                            $required[] = "{$field_key}_pre";
                        }
                        if ($field["params"] && count($field["params"]) == 4) {
                            if ($field["params"][0] !== "") {
                                $json_schema_pre["min"] = floatval($field["params"][0]);
                            }
                            if ($field["params"][1] !== "") {
                                $json_schema_pre["max"] = floatval($field["params"][1]);
                            }
                            if ($field["params"][2] !== "") {
                                $json_schema["min"] = floatval($field["params"][2]);
                            }
                            if ($field["params"][3] !== "") {
                                $json_schema["max"] = floatval($field["params"][3]);
                            }
                        }
                        $ret["properties"]["category_{$ck}"]["properties"]["{$field_key}_pre"] = $json_schema_pre;
                        break;
                    case Extra_Model::TYPE_STAR: // 评星
                        $json_schema["type"] = "number";
                        $json_schema["widget"] = "rate";
                        break;
                    case Extra_Model::TYPE_DATETIME: // 日期时间
                        $json_schema["type"] = "string";
                        $json_schema["format"] = "dateTime";
                        if ($field["default"]) {
                            $json_schema["default"] = $field["default_value"];
                        }
                        break;
                    default:
                        $json_schema["type"] = "string";
                }
                $ret["properties"]["category_{$ck}"]["properties"][$field_key] = $json_schema;
            }
            if (count($required)) {
                $ret["properties"]["category_{$ck}"]["required"] = $required;
            }
        }
        return $ret;
    }

    public static function extra_value_format($extra_value)
    {
        $ret = [];
        if (!$extra_value->id) {
            return $ret;
        }
        $type = $extra_value->object->name() == 'eq_record' ? 'use' : $extra_value->object->name() ;
        $extra = O('extra', ['object' => $extra_value->object->equipment, 'type' => $type]);
        $values = json_decode($extra_value->values_json, true);
        $categories = $extra->get_categories();
        foreach ($categories as $ck => $category) {
            $ret["category_{$ck}"] = [];
            $fields = $extra->get_fields($category);
            foreach ($fields as $fk => $field) {
                if ($field["adopted"]) {
                    $field_key = $fk;
                } else {
                    $field_key = "field_{$fk}";
                }
                if ($field['type'] == Extra_Model::TYPE_DATETIME) {
                    $ret["category_{$ck}"][$field_key] = $values[$fk];
                } else if ($field['type'] == Extra_Model::TYPE_SELECT) {
                    $ret["category_{$ck}"][$field_key] = in_array($values[$fk], $field['params']) ? $values[$fk] : '';
                } else {
                    $ret["category_{$ck}"][$field_key] = $values[$fk];
                }
            }
        }
        return $ret;
    }
}

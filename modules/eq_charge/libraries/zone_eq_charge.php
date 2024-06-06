<?php

class Zone_EQ_Charge
{

    /**
     * 计费设置中增加“折扣计费”收费方式
     * @param $e
     * @param $charge_type 收费类型，当前只针对时间折扣收费处理
     * @param $equipment
     * @param $form
     * @return array
     *
     */
    static function record_time_discount($e, $charge_type, $equipment, $form)
    {
        if ('record_time_discount' == $charge_type) {
            //构建存入equipment中的数据
            $record_setting['*'] = [
                'unit_price' => max(round($form['record_unit_price'], 2), 0),
                'minimum_fee' => max(round($form['record_minimum_fee'], 2), 0)
            ];
            $root = $equipment->get_root();
            $tags = $form['special_tags'];
            if ($tags) {
                //验证是否为负数
                self::checkValue($form);
                $record_setting['tag_discount'] = $form['tag_discount'];
                $formData = self::formatForm($form);
                foreach ($tags as $i => $tag) {
                    if ($tag) {
                        $special_tags = @json_decode($tag, TRUE);

                        if ($special_tags) foreach ($special_tags as $tag) {
                            //限制该仪器设定的收费标签必须是仪器root下真实存在的标签
                            $t = O('tag', ['root' => $root, 'name' => $tag]);
                            $tt = O('tag_equipment_user_tags', ['root' => Tag_Model::root('equipment_user_tags'), 'name' => $tag]);
                            if ($t->id || $tt->id) {
                                $record_setting[$tag] = $formData[$tag];
                            }
                        }
                    }
                }
            }
            $e->return_value = $record_setting;
        }
        return true;
    }


    public static function checkFormatData($perData, $index)
    {
        $msg = I18N::T('eq_charge', "第{$index}组时长累计折扣设置有误,时长范围不允许重叠");
        $msg_z = I18N::T('eq_charge', "第{$index}组时间段折扣设置有误,时间范围允许跨天但不允许重叠");
        $countData = $perData['count'];
        if ($countData['end']['key'] < $countData['start']['key'] || $countData['end']['key'] <= 0 || $countData['start']['key'] <= 0) {
            throw new Exception($msg, 12306);
        }
        $currentIndex = 0;
        $countBetween = $perData['count']['between'];
        foreach ($countBetween as $bt) {
            if ($bt['min'] <= 0 || $bt['max'] <= 0) {
                throw new Exception($msg, 12306);
            }
            $compareIndex = 0;
            if ($bt['min'] >= $bt['max']) {
                throw new Exception($msg, 12306);
            }
            if ($bt['min'] < $countData['start']['key']) {
                throw new Exception($msg, 12306);
            }
            foreach ($countBetween as $cp) {
                if ($compareIndex <= $currentIndex) {
                    $compareIndex++;
                    continue;
                }
                if ($bt['max'] > $cp['min']) {
                    throw new Exception($msg, 12306);
                }
            }
            if (($currentIndex + 1) == count($countBetween) && $countData['end']['key'] < $bt['max']) {
                throw new Exception($msg, 12306);
            }
            $currentIndex++;
        }
        //判断时间段是否重叠
        $currentIndex = 0;
        $zoneBetween = $perData['zone']['between'];
        $hasTomorrow = 0;

        $hasTomorrowZoneIndex = false;
        foreach ($zoneBetween as $key => $bt) {
            $compareIndex = 0;
            if ($bt['min'] == $bt['max']) {
                throw new Exception($msg_z, 12306);
            }
            if ($bt['min'] > $bt['max']) {
                //跨天了
                $hasTomorrowZoneIndex = $key;
            }
            foreach ($zoneBetween as $cp) {
                if ($compareIndex <= $currentIndex) {
                    $compareIndex++;
                    continue;
                }
                if (($cp['min'] > $bt['min'] && $cp['min'] < $bt['max'])
                    || ($cp['max'] > $bt['min'] && $cp['max'] < $bt['max'])) {
                    throw new Exception($msg_z, 12306);
                }
            }
            //隔天跨页面只有一个
            if ($bt['max'] < $bt['min']) {
                $hasTomorrow += 1;
            }

            $currentIndex++;
        }

        //如果有跨天的
        if ($hasTomorrowZoneIndex !== false) {
            foreach ($zoneBetween as $index => $zt) {
                if ($index == $hasTomorrowZoneIndex) {
                    continue;
                }
                if ($zt['min'] < $zoneBetween[$hasTomorrowZoneIndex]['max']) {
                    throw new Exception($msg_z, 12306);
                }
            }
        }

        if ($hasTomorrow > 1) {
            throw new Exception($msg_z, 12306);
        }
        return true;
    }

    /**
     * @param $form
     * @return array
     * @throws Exception
     * 返回值如下（这里显示json）：
     * {"5\u6298\u7528\u6237":{"count":{"start":{"key":"2","discount":"100"},"end":{"key":"10","discount":"30"},"between":[{"min":"2.5","max":"5","discount":"80"},{"min":"5.5","max":"10","discount":"50"}]},"zone":{"time_zone":{"start":"1545930053","end":"1546007453"},"between":[{"min":"02:00:53","max":"06:00:53","discount":"20"},{"min":"08:00:53","max":"20:00:53","discount":"80"},{"min":"20:30:53","max":"21:00:53","discount":"70"},{"min":"21:30:53","max":"22:00:53","discount":"60"}]}},"5.5\u6298\u7528\u6237":{"count":{"start":{"key":"2","discount":"100"},"end":{"key":"10","discount":"30"},"between":[{"min":"2.5","max":"5","discount":"80"},{"min":"5.5","max":"10","discount":"50"}]},"zone":{"time_zone":{"start":"1545930053","end":"1546007453"},"between":[{"min":"02:00:53","max":"06:00:53","discount":"20"},{"min":"08:00:53","max":"20:00:53","discount":"80"},{"min":"20:30:53","max":"21:00:53","discount":"70"},{"min":"21:30:53","max":"22:00:53","discount":"60"}]}},"7\u6298\u7528\u6237":{"count":{"start":{"key":"1","discount":"100"},"end":{"key":"5","discount":"20"}},"zone":{"time_zone":{"start":"1545937253","end":"1546009253"},"between":[{"min":"04:00:53","max":"05:00:53","discount":"20"},{"min":"06:00:53","max":"07:00:53","discount":"30"},{"min":"08:00:53","max":"09:00:53","discount":"40"}]}}}
     */
    private static function formatForm($form)
    {
        //返回值举例
        $finalFormat = [
            '五折用户' => [
                'count' => [
                    'start' => ['key' => 11, 'discount' => 11],
                    'end' => ['key' => 11, 'discount' => 11],
                    'between' => [
                        [
                            'min' => 11,
                            'max' => 11,
                            'discount' => 11
                        ],
                        [
                            'min' => 11,
                            'max' => 11,
                            'discount' => 11
                        ]
                    ],

                ],
                'zone' => [
                    'time_zone' => [
                        'start' => 1,
                        'end' => 2,
                    ],
                    'between' => [
                        [
                            'min' => 11,
                            'max' => 22,
                            'discount' => 10
                        ],
                        [
                            'min' => 11,
                            'max' => 22,
                            'discount' => 10
                        ]
                    ]
                ]
            ]
        ];
        //数据结构比较复杂，只能多套几层了
        $format = [];
        if ($form['special_tags']) {
            $specialTags = $form['special_tags'];
            if (count($specialTags) != count($form['item_start_time_count'])) {
                throw new Exception(T('请填写全部规则'), 12306);
            }
            $flexFormCounts = 0;//当前有几个大分组
            if ($form['item_start_time_count'] && $flexFormCounts = count($form['item_start_time_count'])) {
                $zoneStartStartArray = $form['item_zone_start_time_init_count']['start'];
                $zoneStartEndArray = $form['item_zone_start_time_init_count']['end'];
                $zoneEndStartArray = $form['item_zone_end_time_init_count']['start'];
                $zoneEndEndArray = $form['item_zone_end_time_init_count']['end'];
                $zoneDiscountArray = $form['item_zone_init_discount'];

                for ($i = 0; $i < $flexFormCounts; $i++) {
                    $perData = [];

                    //累计折扣起始数据
                    $perData['count']['start'] = [
                        'key' => $form['item_start_time_count'][$i],
                        'discount' => $form['item_start_time_count_discount'][$i],
                    ];
                    $perData['count']['end'] = [
                        'key' => $form['item_end_time_count'][$i],
                        'discount' => $form['item_end_time_count_discount'][$i],
                    ];

                    //累计时间区间折扣数据
                    $countZoneStartArray = $form['item_zone_start_time_count'][$i + 1];
                    $countZoneEndArray = $form['item_zone_end_time_count'][$i + 1];
                    $countZoneDiscountArray = $form['item_zone_discount'][$i + 1];
                    $countZoneCounts = count($countZoneDiscountArray);//当前flexform中有几个时间累计区间
                    for ($j = 0; $j < $countZoneCounts; $j++) {
                        $perData['count']['between'][$countZoneStartArray[$j]] = [
                            'min' => $countZoneStartArray[$j],
                            'max' => $countZoneEndArray[$j],
                            'discount' => $countZoneDiscountArray[$j]
                        ];
                    }

                    //时间区间起始数据
                    $perData['zone']['time_zone'] = [
                        'start' => self::getHiTimestamp($form['time_zone_limit_start'][$i]),
                        'end' => self::getHiTimestamp($form['time_zone_limit_end'][$i]),
                    ];
                    //时间区间区间数据
                    $zoneBetweenArray = $form['time_zone'][$i + 1];
                    $zoneBetweenDiscountArray = $form['time_zone_discount'][$i + 1];
                    //设置开始和结束
                    $s_min = array_shift($zoneStartStartArray);

                    //先验证是否有起始时间重叠的现象，没有就排序，有就报错
                    $t = $i + 1;
                    $msg_z = I18N::T('eq_charge', "第{$t}组时间段折扣设置有误,时间范围允许跨天但不允许重叠");
                    if (isset($perData['zone']['between'][$s_min])) {
                        throw new Exception($msg_z, 12306);
                    }
                    $perData['zone']['between'][$s_min] = [
                        'min' => self::getHiTimestamp($s_min),
                        'max' => self::getHiTimestamp(array_shift($zoneStartEndArray)),
                        'discount' => array_shift($zoneDiscountArray),
                    ];

                    $zoneCounts = count($zoneBetweenArray['start']);//当前flexform中有几个时间区间段
                    for ($j = 0; $j < $zoneCounts; $j++) {
                        if (isset($perData['zone']['between'][$zoneBetweenArray['start'][$j]])) {
                            throw new Exception($msg_z, 12306);
                        }
                        $perData['zone']['between'][$zoneBetweenArray['start'][$j]] = [
                            'min' => self::getHiTimestamp($zoneBetweenArray['start'][$j]),
                            'max' => self::getHiTimestamp($zoneBetweenArray['end'][$j]),
                            'discount' => $zoneBetweenDiscountArray[$j]
                        ];
                    }
                    $e_min = array_shift($zoneEndStartArray);
                    if (isset($perData['zone']['between'][$e_min])) {
                        throw new Exception($msg_z, 12306);
                    }
                    $perData['zone']['between'][$e_min] = [
                        'min' => self::getHiTimestamp($e_min),
                        'max' => self::getHiTimestamp(array_shift($zoneEndEndArray)),
                        'discount' => array_shift($zoneDiscountArray),
                    ];

                    //接受方式有点问题，在这里把收费区间排序，按min大小排,为了防止老师瞎B填写，给他排个序
                    $sort = $perData['zone']['between'];
                    ksort($sort);
                    $perData['zone']['between'] = $sort;
                    $countSort = $perData['count']['between'];
                    ksort($countSort);
                    $perData['count']['between'] = $countSort;

                    //验证当前所写数据是否正确
                    try {
                        self::checkFormatData($perData, $i + 1);
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage(), 12306);
                    }
                    $currentTags = json_decode($specialTags[$i], true);
                    foreach ($currentTags as $tag) {
                        $format[$tag] = $perData;
                    }
                }
            }
        }
        return $format;
    }

    /**
     * 1.拆分当前时间，按着0-24小时拆分成若干个时间段
     * 2.通过起始时间来切割，获取当前使用记录对应的小块信息
     */
    public function zoneDiscount($zone, $record)
    {
        $betweenZone = $zone['between'];
        ksort($betweenZone);
        $startZone = array_shift($betweenZone);
        $endZone = array_pop($betweenZone);
        $dayZone = [];//一天24小时中所有区间
        $dayStart = mktime(0, 0, 0);
        $startZoneStartTime = $startZone['min'] - strtotime(date('Y-m-d', $startZone['min'])) + $dayStart;//起始区间的起始时间，取小时，算在当天
        $startZoneEndTime = $startZone['max'] - strtotime(date('Y-m-d', $startZone['max'])) + $dayStart;//起始区间的起始时间，取小时，算在当天
        //判断当前是否跨天
        $endZoneEndTime = mktime(date('H', $endZone['max']), date('i', $endZone['max']), 0);//起始区间的起始时间，取小时，算在当天
        $endZoneStartTime = mktime(date('H', $endZone['min']), date('i', $endZone['min']), 0);//起始区间的起始时间，取小时，算在当天

        if ($endZoneEndTime <= $startZoneStartTime) {
            //说明跨天了
            $dayZone[$dayStart] = [
                'min' => $this->getHi($dayStart),
                'max' => $this->getHi($endZoneEndTime),
                'discount' => $endZone['discount']
            ];
            $dayZone[$endZoneStartTime] = [
                'min' => $this->getHi($endZoneStartTime),
                'max' => "24:00",
                'discount' => $endZone['discount']
            ];
            $dayZone[$endZoneEndTime] = [
                'min' => $this->getHi($endZoneEndTime),
                'max' => $this->getHi($startZoneStartTime),
                'discount' => 100
            ];
        } else if ($startZoneStartTime > $dayStart) {//如果正好起始区间是从0点开始
            //没有跨天，但是和0点有间距
            $dayZone[$dayStart] = [
                'min' => $this->getHi($dayStart),
                'max' => $this->getHi($startZoneStartTime),
                'discount' => 100
            ];
        }

        $dayZone[$startZone['min']] = [
            'min' => $this->getHi($startZone['min']),
            'max' => $this->getHi($startZone['max']),
            'discount' => $startZone['discount']
        ];

        if ($endZoneEndTime > $startZoneStartTime) {
            //没有跨天
            $dayZone[$endZone['min']] = [
                'min' => $this->getHi($endZone['min']),
                'max' => $this->getHi($endZone['max']),
                'discount' => $endZone['discount']
            ];
            $dayZone[$endZoneEndTime] = [
                'min' => $this->getHi($endZoneEndTime),
                'max' => "24:00",
                'discount' => 100
            ];
        }

        if (count($betweenZone)) {
            //获取中间区间
            for ($i = 0; $i < count($betweenZone); $i++) {
                $startZone = $i == 0 ? $startZone : $betweenZone[$i - 1];
                $currentZone = $betweenZone[$i];
                if ($currentZone['min'] > $startZone['max']) {
                    $dayZone[$startZone['max']] = [
                        'min' => $this->getHi($startZone['max']),
                        'max' => $this->getHi($currentZone['min']),
                        'discount' => 100
                    ];
                }
                $dayZone[$currentZone['min']] = [
                    'min' => $this->getHi($currentZone['min']),
                    'max' => $this->getHi($currentZone['max']),
                    'discount' => $currentZone['discount']
                ];

                if ($i == count($betweenZone) - 1 && $currentZone['max'] < $endZoneStartTime) {
                    $dayZone[$currentZone['max']] = [
                        'min' => $this->getHi($currentZone['max']),
                        'max' => $this->getHi($endZoneStartTime),
                        'discount' => 100
                    ];
                }
            }
        } else {
            if ($endZoneStartTime > $startZoneEndTime) {
                $dayZone[$startZoneEndTime] = [
                    'min' => $this->getHi($startZoneEndTime),
                    'max' => $this->getHi($endZoneStartTime),
                    'discount' => 100
                ];
            }
        }

        if (empty($dayZone)) {
            return true;
        }
        ksort($dayZone);
        //计算当前折扣对应的时间段
        $dtstart = $record['start_time'] + $record['lead_time'];
        $dtend = $record['end_time'] - $record['post_time'];
        $finalDiscountZone = $this->getDiscountZone($dtstart, $dtend, $dayZone);
        return $finalDiscountZone;
    }

    //寻找当前符合条件的计费区间，应该用递归的
    private function getDiscountZone($dtstart, $dtend, $dayZone)
    {
        $finalDiscountZone = [];
        while ($dtstart < $dtend) {
            foreach ($dayZone as $zone) {
                $currentUseLenth = 0;
                $currentDayStartTime = strtotime(date('Y-m-d', $dtstart));
                $startOffsetSeconds = explode(':', $zone['min'])[0] * 3600 + explode(':', $zone['min'])[1] * 60;
                $endOffsetSeconds = explode(':', $zone['max'])[0] * 3600 + explode(':', $zone['max'])[1] * 60;
                $zoneStartTime = $currentDayStartTime + $startOffsetSeconds;
                $zoneEndTime = $currentDayStartTime + $endOffsetSeconds;
                $zoneDiscount = $zone['discount'];
                if ($zoneStartTime > $dtstart || $dtstart > $zoneEndTime) {
                    continue;
                }
                $currentUseLenth = ($zoneEndTime >= $dtend) ? $dtend - $dtstart : $zoneEndTime - $dtstart;
//                array_push($finalDiscountZone, [
//                    'length' => $currentUseLenth,
//                    'discount' => $zoneDiscount,
//                    'discribtion' => date('Y-m-d H:i',$dtstart).'------'.date('Y-m-d H:i',$currentUseLenth + $dtstart),
//                ]);
                //赋值和下面的ksort排序是为了以后案例调试方便
                $finalDiscountZone[$dtstart] = [
                    'length' => $currentUseLenth,
                    'discount' => $zoneDiscount,
                    'discribtion' => date('Y-m-d H:i', $dtstart) . '------' . date('Y-m-d H:i', ($currentUseLenth + $dtstart)),
                ];

                if ($zoneEndTime >= $dtend) {
                    //说明已经完事了，完全不用
                    $dtstart = $dtend;//这个是为了退出while
                    break;
                }
                $dtstart = $zoneEndTime;
            }
        }
        ksort($finalDiscountZone);
        return $finalDiscountZone;
    }

    private function getHi($time, $format = 'H:i')
    {
        return date($format, $time);
    }

    private static function getHiTimestamp($time)
    {
        return strtotime(date('Y-m-d H:i', $time));
    }

    private static function checkValue($form)
    {
        $msg = I18N::T('eq_charge', "设置的值需为正数");
        foreach ($form['tag_discount'] as $t) {
            if ($t <= 0) {
                throw new Exception($msg, 12306);
            }
        }
        foreach ($form['item_start_time_count_discount'] as $t) {
            if ($t <= 0) {
                throw new Exception($msg, 12306);
            }
        }
        foreach ($form['item_end_time_count_discount'] as $t) {
            if ($t <= 0) {
                throw new Exception($msg, 12306);
            }
        }
        foreach ($form['item_zone_init_discount'] as $t) {
            if ($t <= 0) {
                throw new Exception($msg, 12306);
            }
        }
        foreach ($form['time_zone_discount'] as $t) {
            foreach($t as $tt){
                if($tt <= 0){
                    throw new Exception($msg, 12306);
                }
            }
        }
        foreach ($form['item_zone_discount'] as $t) {
            foreach($t as $tt){
                if($tt <= 0){
                    throw new Exception($msg, 12306);
                }
            }
        }

    }

}


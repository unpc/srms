<?php

class CLI_Export_Equipment {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $equipments = Q($selector);
        $excel = new Excel($params[1]);

        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($equipments as $equipment) {
            $data = new ArrayIterator;
            if (array_key_exists('name', $valid_columns)) {
                $data['name'] = T($equipment->name) ? T($equipment->name.Event::trigger('extra.equipment.name', $equipment)) : '';
            }
            if (array_key_exists('ref_no', $valid_columns)) {
                $data['ref_no'] = H($equipment->ref_no.' ')?:'';
            }
            if (array_key_exists('price', $valid_columns)) {
                $data['price'] = H($equipment->price) ?: 0;
            }
            if (array_key_exists('eq_cf_id', $valid_columns)) {
                $data['eq_cf_id'] = H($equipment->id)?:'';
            }
            if (array_key_exists('cat', $valid_columns)) {
                $root = Tag_Model::root('equipment');
                $tags = Q("$equipment tag_equipment[root=$root]");
                $cats = [];
                foreach ($tags as $cat) {	
                    $cats[] = $cat->name;
                }
                $data['cat'] = T(implode(', ',$cats))?:'';
            }

            if (array_key_exists('control_mode', $valid_columns)) {
                $control_modes = Config::get('equipment.control_modes');
                $data['control_mode'] = $control_modes[T($equipment->control_mode)]?:'';
            }
            if (array_key_exists('location', $valid_columns)) {
                if (Config::get('equipment.location_type_select')){
                    $tag = $equipment->location;
                    if (!$tag || !$tag->id || $tag->id == Tag_Model::root('location')->id) {
                        // null
                    } else {
                        $anchors = [];
                        $tag_root = $equipment->location->root;
                        if (!isset($tag_root)) $tag_root = $tag->root;
                        $found_root = ($tag_root->id == $tag->root->id);
                        foreach ((array) $tag->path as $unit) {
                            list($tag_id, $tag_name) = $unit;
                            if (!$found_root) {
                                if ($tag_id != $tag_root->id) continue;
                                $found_root = TRUE;
                            }
                            $anchors[] = T($tag_name);
                        }
                        $data['location'] = implode(', ', $anchors);
                    }	
                    $data['location'] = $data['location'] ?: '无';
                }else{
                    $location = [
                        H($equipment->location)
                    ];
                    $data['location'] = ($location[0]=='' && $location[1]=='')?'-':H(implode(', ',$location));
                }
            }
            if (array_key_exists('contacts', $valid_columns)) {
                $users = Q("$equipment<contact user");
                $contacts = [];
                foreach ($users as $contact) {	
                    $contacts[] = $contact->name;
                }
                $data['contacts'] = T(implode(', ',$contacts))?:'';         	
            }
            if (array_key_exists('phone', $valid_columns)) {
                $data['phone'] = T($equipment->phone)?:'-';       	
            }

            if (array_key_exists('group', $valid_columns)) {
                $tag = $equipment->group;
                if (!$tag || !$tag->id || $tag->id == Tag_Model::root('group')->id) {
                    // cheng.liu@geneegroup 2018.8.9 这么写是为了让后来人能看清楚逻辑，否则我就直接反转到 else 操作了
                    // 解决问题为: 20183208  西安交通大学仪器目录里组织机构选择全部时导出一直显示“请稍后”
                }
                else {
                    $anchors = [];
                    $tag_root = $equipment->group->root;
                    if (!isset($tag_root)) $tag_root = $tag->root;
                    $found_root =  ($tag_root->id == $tag->root->id);
                    foreach ((array) $tag->path as $unit) {
                        list($tag_id, $tag_name) = $unit;
                        if (!$found_root) {
                            if ($tag_id != $tag_root->id) continue;
                            $found_root = TRUE;
                        }
                        $anchors[] = T($tag_name);
                    }
                    $data['group'] = implode(', ', $anchors);
                }	
                $data['group'] = $data['group'] ?: '无';
            }

            if (array_key_exists('atime', $valid_columns)) {
                $data['atime'] = $equipment->atime ? Date::format($equipment->atime, 'Y/m/d') : '-';
            }
            
            if (array_key_exists('specification', $valid_columns)) {
                $data['specification'] = H($equipment->specification)?:'';
            }
            if (array_key_exists('brand', $valid_columns)) {
                $data['brand'] = H($equipment->brand)?:'';
            }
            if (array_key_exists('model_no', $valid_columns)) {
                $data['model_no'] = H($equipment->model_no)?:'';
            }
            if (array_key_exists('manufacturer', $valid_columns)) {
                $data['manufacturer'] = H($equipment->manufacturer)?:'';
            }
            if (array_key_exists('manu_at', $valid_columns)) {
                $data['manu_at'] = H($equipment->manu_at)?:'';
            }
            if (array_key_exists('purchased_date', $valid_columns)) {
                $data['purchased_date'] = $equipment->purchased_date?(T(date('Y/m/d',$equipment->purchased_date))?:""):'';
            }
            if (array_key_exists('manu_date', $valid_columns)) {
                $data['manu_date'] = $equipment->manu_date?(T(date('Y/m/d',$equipment->manu_date))?:""):'';
            }
            if (array_key_exists('cat_no', $valid_columns)) {
                $data['cat_no'] = H($equipment->cat_no)?:'';
            }
            if (array_key_exists('tech_specs', $valid_columns)) {
                $data['tech_specs'] = H($equipment->tech_specs)?:'';
            }
            if (array_key_exists('features', $valid_columns)) {
                $data['features'] = H($equipment->features)?:'';
            }
            if (array_key_exists('configs', $valid_columns)) {
                $data['configs'] = H($equipment->configs)?:'';
            }
            
            /*
			* Author: cheng.liu@geneegroup.com
			* Description: Add hook for Adjust export equipment data
			* Design by: 【中南大学】RQ171001 仪器目录增加信息及激活管理
			* Date: 2017-03-28   
            */

            // 这个 trigger 太不通用，打印还得再写一遍
            Event::trigger('equipments.before.export_equipment', $equipment, $valid_columns, $data);

            // 如果打印导出都需要增加列，请用我这个 trigger，秒杀上面那个
            $data_custom = Event::trigger('equipments.export_list_csv', $equipment, $data, $valid_columns);
            if(is_array($data_custom)) $data = $data_custom;

            $data = array_replace($valid_columns, iterator_to_array($data));
            $data = array_values($data);
            
            $excel->write($data);
        }
        $excel->save();
    }
}

<?php

class CLI_Export_Labs {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $valid_columns_key_name = array_search('实验室名称', $valid_columns);
        if ($valid_columns_key_name) {
            $valid_columns[$valid_columns_key_name] = '课题组名称';
        }

        $valid_columns_key_contact = array_search('实验室联系方式', $valid_columns);
        if ($valid_columns_key_contact) {
            $valid_columns[$valid_columns_key_contact] = '课题组联系方式';
        }

        $labs = Q($selector);

        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        if ($labs->total_count()) {
            foreach ($labs as $lab) {
                $data = [];
                if (array_key_exists('lab_name', $valid_columns)) {
                    $data[] = H($lab->name)?:'-';
                }
                if (array_key_exists('owner', $valid_columns)) {
                    $owner = O('user',$lab->owner_id);
                    $data[] = H($owner->name)!='--' ? H($owner->name) : '-';
                }
                if (array_key_exists('lab_contact', $valid_columns)) {
                    $data[] = H($lab->contact)?:'-';
                }
                if (array_key_exists('group', $valid_columns)) {
                    $anchors = [];
                    if ( Config::get('tag.group_limit')>=0 && $lab->group->id ) {
                        $tag = $lab->group;
                        $tag_root = $lab->group->root;

                        if (!$tag || !$tag->id || ($tag->id == Tag_Model::root('group')->id)) {
                            $data[] = '-';
                        } else {
                            if (!isset($tag_root)) $tag_root = $tag->root;
                            $found_root =  ($tag_root->id == $tag->root->id);
                            foreach ((array) $tag->path as $unit) {
                                list($tag_id, $tag_name) = $unit;
                                if (!$found_root) {
                                    if ($tag_id != $tag_root->id) continue;
                                        $found_root = TRUE;
                                }
                                $anchors[] =  HT($tag_name);
                            }
    
                            if ( $anchors ) {
                                $data[] = implode(', ', $anchors);
                            } else {
                                $data[] = '-';
                            }
                        }
                    } else {
                        $data[] = '-';
                    }
                }
                if (array_key_exists('description', $valid_columns)) {
                    $data[] = H($lab->description)?:'-';
                }
                if (array_key_exists('creator', $valid_columns)) {
                    $data[] = H($lab->creator->name)!='--'?H($lab->creator->name):'-';
                }
                if (array_key_exists('auditor', $valid_columns)) {
                    $data[] = H($lab->auditor->name)!='--' ? H($lab->auditor->name) : '-';
                }
                $excel->write($data);
            }
        }
        $excel->save();
    }
}

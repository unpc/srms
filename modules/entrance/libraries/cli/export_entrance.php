<?php

class CLI_Export_Entrance {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $visible_columns = json_decode($params[2], true);
		$records = Q($selector);

        $excel = new Excel($params[1]);
        $visible_columns_key = array_search('实验室', $visible_columns);
        if ($visible_columns_key) {
            $visible_columns[$visible_columns_key] = '课题组';
        }
        $excel->write(array_values($visible_columns));

        $start = 0;
        $per_page = 100;

        while(1) {
            $pp_records = $records->limit($start,$per_page);
            if($pp_records->length() == 0 ) break;

            foreach($pp_records as $record) {
                $data = [];
                 foreach ($visible_columns as $key => $value) {
                    switch ($key) {
                    case 'name':
                        $data[] = H($record->door->name);
                        break;
                    case 'location':
                        $tags = [];
                        $tag_root = Tag_Model::root('location');
                        foreach (Q("{$record->door} tag_location") as $tag) {
                            $repeated = false;
                            if ($tag->id == $tag_root->id) {
                                continue;
                            }
                            $tags[$tag->id] = $tag;
                            $tag = $tag->parent;
                            while ($tag->id && $tag->id != $tag_root->id) {
                                if (array_key_exists($tag->id, $tags)) {
                                    unset($tags[$tag->id]);
                                }
                                $tag = $tag->parent;
                            }
                        }

                        $locations = [];
                        foreach ($tags as $id => $tag) {
                            $locations[] = H(strip_tags(V('application:tag/path', ['tag' => $tag, 'tag_root' => Tag_Model::root('location'), 'url_template' => URI::url('', 'location_id=%tag_id')])));
                        }
                        $data[] = join("\n", $locations);
                        break;
                    case 'user':
                        $data[] = H($record->user->name);
                        break;
                    case 'lab':
                        $labs = Q("{$record->user} lab")->to_assoc('id', 'name');
                        $data[] = H(join(',', $labs));
                        break;
                    case 'date':
                        $data[] = H(date('Y/m/d H:i:s',$record->time));
                        break;
                    case 'direction':
                        $data[] = H(DC_Record_Model::$direction[$record->direction]);
                        break;
                    case 'site':
                        $data[] = H(Config::get('site.map')[$record->door->site]);
                        break;
                    }
                }
                $excel->write($data);
            }
            $start += $per_page;
        }

        $excel->save();
    }
}

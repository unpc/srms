<?php

class CLI_Export_Charge {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[1], true);
        $extra_columns = json_decode($params[3], true);
		$charges = Q($selector);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[2]);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }

        $valid_columns_key = array_search('实验室组织机构', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组组织机构';
        }

        $new_valid_columns = Event::trigger('eq_charge_export.cloumns', $valid_columns, 'equipment', 0) ?: $valid_columns;
        if ($new_valid_columns) $valid_columns = $new_valid_columns;

        $excel->write(array_values($valid_columns));

        while (1) {
            $pp_cs = $charges->limit($start, $per_page);
            if ($pp_cs->length() == 0) break;
            /*
            NO.BUG#099
            2010.11.05
            张国平
            导出成CSV，数字不能包含￥/$标志
            */
            foreach ($pp_cs as $c) {
                $user = $c->user;
                $equipment = $c->equipment;
                $data = [];

                if (array_key_exists('equipment', $valid_columns)) {
                    $data[] = T($equipment->name) ? T($equipment->name.Event::trigger('extra.equipment.name', $equipment)) : '';
                }
                if (array_key_exists('eq_ref_no', $valid_columns)) {
                    $data[] = $equipment->ref_no;
                }
                if (array_key_exists('eq_cf_id', $valid_columns)) {
                    $data[] = $equipment->id;
                }
                if (array_key_exists('eq_group', $valid_columns)) {
                    $data[] = H($equipment->group->name);
                }
                if (array_key_exists('incharge', $valid_columns)) {
                        $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
                        $users = join(', ', $users);
                        $data[] = H($users);
                }
                if (array_key_exists('user', $valid_columns)) {
                    $data[] = H($user->name);
                }
                if (array_key_exists('user_ref_no', $valid_columns)) {
                    $data[] = H($user->ref_no);
                }
                if (array_key_exists('login_token', $valid_columns)) {
                    list($t, $b) = Auth::parse_token($user->token);
                    $data[] = H($t);
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $data[] = H($c->lab->name);
                }
                if (array_key_exists('user_group', $valid_columns)) {
                    $data[] = H($user->group->name);
                }
                if (array_key_exists('lab_group', $valid_columns)) {
                    $data[] = H($c->lab->group->name);
                }
                if (array_key_exists('charge_ref_no', $valid_columns)) {
                    $data[] = Number::fill($c->transaction->id, 6);
                }
                if (array_key_exists('date', $valid_columns)) {
                    $data[] = Date::format($c->ctime, 'Y/m/d H:i');
                }
                if (array_key_exists('samples', $valid_columns)) {
                    $source = $c->source;
                    if ($source->id) {
                        switch($source->name()){
                            case 'eq_sample':
                                $data[] = (int)max(1, $source->count);
                                break;
                            case 'eq_record':
                                $data[] = $source->samples;
                                break;
                            default:
                                $data[] = '--';
                                break;
                        }
                    }
                    else {
                        $data[] = '--';
                    }
                }
                if (array_key_exists('amount', $valid_columns)) {
                    $data[] = $c->amount;
                }
                if (array_key_exists('material_amount', $valid_columns)) {
                    $data[] = $c->material_amount;
                }
                if (array_key_exists('type', $valid_columns)) {
                    if ($c->source->id) {
                        switch($c->source->name()) {
                            case 'eq_sample':
                                $data[] = I18N::HT('eq_charge', '送样收费');
                                break;
                            case 'eq_reserv':
                                $data[] = I18N::HT('eq_charge', '预约收费');
                                break;
                            case 'service_apply_record':
                                $data[] = I18N::HT('eq_charge', '服务收费');
                                break;
                            default:
                                $data[] = I18N::HT('eq_charge', '使用收费');
                        }
                    }
                    else {
                        $data[] = NULL;
                    }
                }
                if (array_key_exists('charge_time', $valid_columns)) {
                    if (Config::get('eq_charge.foul_charge') && $c->source_id < 0) {
                        $data[] = $c->dtstart&&$c->dtend ? date('Y/m/d H:i:s', $c->dtstart) . ' - ' . date('Y/m/d H:i:s', $c->dtend) : '--';
                    } elseif ($c->charge_duration_blocks) {
                        $data[] = $c->charge_duration_blocks;
                    } else {
                        $data[] = $c->source->dtstart&&$c->source->dtend ? date('Y/m/d H:i:s', $c->source->dtstart) . ' - ' . date('Y/m/d H:i:s', $c->source->dtend) : '--';
                    }
                }
                if (array_key_exists('description', $valid_columns)) {
                    $data[] = strip_tags(str_replace('</p>', "\n", $c->description));
                }

                if (array_key_exists('site', $valid_columns)) {
                    $data[] = Config::get('site.map')[$c->equipment->site];
                }

                //新增西安交大报销号等信息
                try{
                    $extData = Event::trigger("index_charges_billing.export.row",$c,$valid_columns,$data);
                    if(!empty($extData)){
                        $data = $extData;
                    }
                }catch (Exception $e){

                }
                //end

                $new_data = Event::trigger('eq_charge.export_columns', $c, $valid_columns, $data);
                if ($new_data) $data = $new_data;

                foreach($extra_columns as $extra_column) {
                    $data[] = EQ_Charge_Search::get_export_value($extra_column, $c);
                }

                $excel->write($data);
            }

            $start += $per_page;
        }

        $excel->save();
    }
}

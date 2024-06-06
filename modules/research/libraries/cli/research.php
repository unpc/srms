<?php

class CLI_Research {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $researches = Q($selector);
        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        $root = Tag_Model::root('group');
        foreach ($researches as $research) {
            $data = new ArrayIterator;
            if (array_key_exists('ref_no', $valid_columns)) {
                $data['ref_no'] = H($research->ref_no);
            }
            if (array_key_exists('name', $valid_columns)) {
                $data['name'] = H($research->name);
            }
            if (array_key_exists('group', $valid_columns)) {
                $g = $research->group;
                $groups = [];
                while ($g->id != $root->id) {
                    array_unshift($groups, H($g->name));
                    $g = $g->parent;
                }
                $data['group'] = join(' » ', $groups);
            }
            if (array_key_exists('charge', $valid_columns)) {
                $data['charge'] = H($research->charge);
            }
            if (array_key_exists('location', $valid_columns)) {
                $data['location'] = H($research->location);
            }
            if (array_key_exists('contacts', $valid_columns)) {
                $contacts = [];
                foreach (Q("{$research} user.contact") as $u) {
                    $contacts[] = $u->name;
                }
                $data['contacts'] = join(', ', $contacts);
            }

            $data = array_replace($valid_columns, iterator_to_array($data));
            $data = array_values($data);

            $excel->write($data);
        }
        $excel->save();
    }

    static function export_record() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $records = Q($selector);
        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        $root = Tag_Model::root('group');
        foreach ($records as $record) {
            $data = new ArrayIterator;

            $research = $record->research;
            $lab = Q("{$record->user} lab")->current();

            if (array_key_exists('id', $valid_columns)) {
                $data['id'] = Number::fill(H($record->id), 6);
            }
            if (array_key_exists('research_no', $valid_columns)) {
                $data['research_no'] = H($record->research_no);
            }
            if (array_key_exists('research_name', $valid_columns)) {
                $data['research_name'] = H($record->research->name);
            }
            if (array_key_exists('research_group', $valid_columns)) {
                $g = $research->group;
                $groups = [];
                while ($g->id != $root->id) {
                    array_unshift($groups, H($g->name));
                    $g = $g->parent;
                }
                $data['research_group'] = join(' » ', $groups);
            }
            if (array_key_exists('research_contacts', $valid_columns)) {
                $contacts = [];
                foreach (Q("{$research} user.contact") as $u) {
                    $contacts[] = $u->name;
                }
                $data['research_contacts'] = join(', ', $contacts);
            }
            if (array_key_exists('discount', $valid_columns)) {
                $data['discount'] = round($record->discount, 2) . "%";
            }
            if (array_key_exists('price', $valid_columns)) {
                $data['price'] = Number::currency($record->price);
            }
            if (array_key_exists('lab_ref_no', $valid_columns)) {
                $data['lab_ref_no'] = H($lab->ref_no);
            }
            if (array_key_exists('company_name', $valid_columns)) {
                $data['company_name'] = H($lab->company_name);
            }
            if (array_key_exists('user_name', $valid_columns)) {
                $data['user_name'] = H($record->user->name);
            }
            if (array_key_exists('lab', $valid_columns)) {
                $data['lab'] = H($lab->name);
            }
            if (array_key_exists('quantity', $valid_columns)) {
                $data['quantity'] = H($record->quantity);
            }
            if (array_key_exists('amount', $valid_columns)) {
                $data['amount'] = Number::currency($record->amount);
            }
            if (array_key_exists('auto_amount', $valid_columns)) {
                $data['auto_amount'] = Number::currency($record->auto_amount);
            }
            if (array_key_exists('dtstart', $valid_columns)) {
                $data['dtstart'] = Date::format($record->dtstart, "Y-m-d");
            }
            if (array_key_exists('dtend', $valid_columns)) {
                $data['dtend'] = Date::format($record->dtend, "Y-m-d");
            }
            if (array_key_exists('description', $valid_columns)) {
                $data['description'] = H($record->description);
            }
            if (array_key_exists('charge_status', $valid_columns)) {
                $data['charge_status'] = I18N::T('research', Research_Record_Model::$charge_status[$record->charge_status]);
            }

            $data = array_replace($valid_columns, iterator_to_array($data));
            $data = array_values($data);

            $excel->write($data);
        }
        $excel->save();
    }
}

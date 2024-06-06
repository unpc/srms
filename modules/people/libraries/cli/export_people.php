<?php

class CLI_Export_People
{

    public static function export()
    {
        $params        = func_get_args();
        $selector      = $params[0];
        $valid_columns = json_decode($params[2], true);
        $roles         = json_decode($params[3], true);
        $title         = json_decode($params[4], true);
        $users         = Q($selector);

        $excel = new Excel($params[1]);
        $excel->write($title);
        $group_root = Tag_Model::root('group');
        foreach ($users as $user) {
            $role_names = array_intersect_key($roles, $user->roles());
            $data       = [];
            $data[]     = trim($user->name);
            foreach ($valid_columns as $key => $value) {
                switch ($key) {
                    case 'token':
                        $data[] = trim(People::print_token($user->token));
                        break;
                    case 'gender':
                        $data[] = I18N::T('people', User_Model::$genders[$user->gender]) ?: '--';
                        break;
                    case 'member_type':
                        $data[] = User_Model::get_member_label($user->member_type) ?: '--';
                        break;
                    case 'mentor_name':
                        $data[] = trim($user->mentor_name);
                        break;
                    case 'major':
                        $data[] = trim($user->major);
                        break;
                    case 'organization':
                        $data[] = trim($user->organization);
                        break;
                    case 'group':
                        $anchors    = [];
                        $found_root = ($group_root->id == $user->group->root->id);
                        foreach ((array) $user->group->path as $unit) {
                            list($tag_id, $tag_name) = $unit;
                            if (!$found_root) {
                                if ($tag_id != $group_root->id) {
                                    continue;
                                }

                                $found_root = true;
                            }
                            $anchors[] = HT($tag_name);
                        }
                        $data[] = implode(' >> ', $anchors);
                        break;
                    case 'email':
                        $data[] = trim($user->email);
                        break;
                    case 'phone':
                        $data[] = trim($user->phone);
                        break;
                    case 'personal_phone':
                        $data[] = trim($user->personal_phone);
                        break;
                    case 'address':
                        $data[] = trim($user->address);
                        break;
                    case 'lab':
                        $labs   = Q("$user lab")->to_assoc('id', 'name');
                        $data[] = trim(join(',', $labs));
                        break;
                    case 'lab_contact':
                        $labs_contact = Q("$user lab")->to_assoc('id', 'contact');
                        $data[]       = trim(join(',', $labs_contact));
                        break;
                    case 'roles':
                        $data[] = join(', ', $role_names);
                        break;
                    case 'ctime':
                        $data[] = Date::format($user->ctime, 'Y/m/d');
                        break;
                    case 'creator':
                        $data[] = $user->creator->name;
                        break;
                    case 'auditor':
                        $data[] = $user->auditor->name;
                        break;
                    default:
                        $data[] = H($user->$key);
                        break;
                }

            }
            $excel->write($data);
        }
        $excel->save();
    }
}

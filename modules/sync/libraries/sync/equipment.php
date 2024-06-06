<?php

class Sync_Equipment extends Sync_Handler
{
    public static $publish_keys = [
        'name',
        'en_name',
        'organization',
        'ref_no',
        'cat_no',
        'name_abbr',
        'model_no',
        'specification',
        'price',
        'manu_at',
        'manufacturer',
        'manu_date',
        'purchased_date',
        'location',
        'location2',
        'tech_specs',
        'features',
        'configs',
        'control_mode',
        'control_address',
        'server',
        'is_using',
        'user_using',
        'require_training',
        'status',
        'connect',
        'is_monitoring',
        'is_monitoring_mtime',
        'ctime',
        'mtime',
        'atime',
        'access_code',
        'group',
        'tag_root',
        'phone',
        'email',
        'share',
        'using_abbr',
        'contacts_abbr',
        'location_abbr',
        'accept_reserv',
        'accept_limit_time',
        'billing_dept_id',
        'accept_sample',
        'yiqikong_id',
        'yiqikong_share',
        'domain',
        'lock_incharge_control',
        'single_equipemnt_reserv',
        'bluetooth_serial_address',
        'tech_specs',
        'features',
        'configs',
        'open_reserv',
        'charge_info',
        'accept_reserv',
        'accept_sample',
        'is_sync',
    ];

    public function uuid()
    {
        return uniqid(LAB_ID, true);
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data)
    {
        foreach (self::$publish_keys as $key) {
            if (isset($new_data[$key])) {
                if (is_object($new_data[$key]) && $new_data[$key]->id != $old_data[$key]->id) {
                    return true;
                } elseif (is_scalar($new_data[$key]) && $new_data[$key] != $old_data[$key]) {
                    return true;
                }
            }
        }
        return false;
    }

    public function format()
    {
        $equipment = $this->object;
        $params = [
            'name' => $equipment->name,
            'en_name' => $equipment->en_name,
            'organization' => $equipment->organization,
            'ref_no' => $equipment->ref_no,
            'cat_no' => $equipment->cat_no,
            'name_abbr' => $equipment->name_abbr,
            'model_no' => $equipment->model_no,
            'specification' => $equipment->specification,
            'price' => $equipment->price,
            'manu_at' => $equipment->manu_at,
            'manufacturer' => $equipment->manufacturer,
            'manu_date' => $equipment->manu_date,
            'purchased_date' => $equipment->purchased_date,
            'location' => $equipment->location,
            'location2' => $equipment->location2,
            'tech_specs' => $equipment->tech_specs,
            'features' => $equipment->features,
            'configs' => $equipment->configs,
            'control_mode' => $equipment->control_mode,
            'control_address' => $equipment->control_address,
            'server' => $equipment->server,
            'is_using' => $equipment->is_using,
            'user_using_id' => $equipment->user_using_id,
            'require_training' => $equipment->require_training,
            'status' => $equipment->status,
            'connect' => $equipment->connect,
            'is_monitoring' => $equipment->is_monitoring,
            'is_monitoring_mtime' => $equipment->is_monitoring_mtime,
            'ctime' => $equipment->ctime,
            'mtime' => $equipment->mtime,
            'atime' => $equipment->atime,
            'access_code' => $equipment->access_code,
            'group_id' => $equipment->group->uuid,
            'tag_root_id' => $equipment->tag_root->uuid,
            'phone' => $equipment->phone,
            'email' => $equipment->email,
            'share' => $equipment->share,
            'using_abbr' => $equipment->using_abbr,
            'contacts_abbr' => $equipment->contacts_abbr,
            'location_abbr' => $equipment->location_abbr,
            'accept_reserv' => $equipment->accept_reserv,
            'accept_limit_time' => $equipment->accept_limit_time,
            'billing_dept_id' => $equipment->billing_dept_id,
            'accept_sample' => $equipment->accept_sample,
            'yiqikong_id' => $equipment->yiqikong_id,
            'yiqikong_share' => $equipment->yiqikong_share,
            'domain' => $equipment->domain,
            'lock_incharge_control' => $equipment->lock_incharge_control,
            'single_equipemnt_reserv' => $equipment->single_equipemnt_reserv,
            'bluetooth_serial_address' => $equipment->bluetooth_serial_address,
            'tech_specs'               => $equipment->tech_specs,
            'features'                 => $equipment->features,
            'configs'                  => $equipment->configs,
            'open_reserv'              => $equipment->open_reserv,
            'charge_info'              => $equipment->charge_info,
            'accept_reserv'            => $equipment->accept_reserv,
            'accept_sample'            => $equipment->accept_sample,
            'is_sync'                  => $equipment->is_sync,
            'source_id'                => $equipment->id,
        ];
        foreach ($params as $k => $v) {
            if (is_null($v)) {
                $params[$k] = "";
            }
        }
        return $params;
    }


    public function handle($params) {
        $equipment = $this->object;
        $equipment->name = $params['name'];
        $equipment->en_name = $params['en_name'];
        $equipment->organization = $params['organization'];
        $equipment->ref_no = $params['ref_no'] ? : null;
        $equipment->cat_no = $params['cat_no'];
        $equipment->name_abbr = $params['name_abbr'];
        $equipment->model_no = $params['model_no'];
        $equipment->specification = $params['specification'];
        $equipment->price = $params['price'];
        $equipment->manu_at = $params['manu_at'];
        $equipment->manufacturer = $params['manufacturer'];
        $equipment->manu_date = $params['manu_date'];
        $equipment->purchased_date = $params['purchased_date'];
        $equipment->location = $params['location'];
        $equipment->location2 = $params['location2'];
        $equipment->tech_specs = $params['tech_specs'];
        $equipment->features = $params['features'];
        $equipment->configs = $params['configs'];
        $equipment->control_mode = $params['control_mode'];
        $equipment->control_address = $params['control_address'];
        $equipment->server = $params['server'];
        $equipment->is_using = $params['is_using'];
        $equipment->user_using_id = $params['user_using_id'];
        $equipment->require_training = $params['require_training'];
        $equipment->status = $params['status'];
        $equipment->connect = $params['connect'];
        $equipment->is_monitoring = $params['is_monitoring'];
        $equipment->is_monitoring_mtime = $params['is_monitoring_mtime'];
        $equipment->ctime = $params['ctime'];
        $equipment->mtime = $params['mtime'];
        $equipment->atime = $params['atime'];
        $equipment->access_code = $params['access_code'] ? : null;
        $group = O('tag_group', ['uuid' => $params['group_id']]);
        $equipment->group = $group;
        $root = O('tag_group', ['uuid' => $params['tag_root_id']]);
        $equipment->root = $root;
        $equipment->phone = $params['phone'];
        $equipment->email = $params['email'];
        $equipment->share = $params['share'];
        $equipment->using_abbr = $params['using_abbr'];
        $equipment->contacts_abbr = $params['contacts_abbr'];
        $equipment->location_abbr = $params['location_abbr'];
        $equipment->accept_reserv = $params['accept_reserv'];
        $equipment->accept_limit_time = $params['accept_limit_time'];
        $equipment->billing_dept_id = $params['billing_dept_id'];
        $equipment->accept_sample = $params['accept_sample'];
        $equipment->yiqikong_id = $params['yiqikong_id'];
        $equipment->yiqikong_share = $params['yiqikong_share'];
        $equipment->domain = $params['domain'];
        $equipment->lock_incharge_control = $params['lock_incharge_control'];
        $equipment->single_equipemnt_reserv = $params['single_equipemnt_reserv'];
        $equipment->bluetooth_serial_address = $params['bluetooth_serial_address'];
        $equipment->tech_specs               = $params['tech_specs'];
        $equipment->features                 = $params['features'];
        $equipment->configs                  = $params['configs'];
        $equipment->open_reserv              = $params['open_reserv'];
        $equipment->charge_info              = $params['charge_info'];
        $equipment->accept_reserv            = $params['accept_reserv'];
        $equipment->accept_sample            = $params['accept_sample'];
        $equipment->is_sync                  = $params['is_sync'];
        $equipment->source_id                = $params['source_id'];
        $equipment->save();
    }
}

<?php

/**
 * Sheet模块作为数据接收API，该脚本作为客户端推送脚本
 * SITE_ID=cf-lite LAB_ID=fudan_chem php cli/cli.php sheet push
 */
class CLI_Sheet
{
    private static function get_token() 
    {
        // token/expires
        $info = LAB::get('sheet_token');
        if (isset($info['token']) && $info['expires'] > Date::time()) {
            return $info['token'];
        }

        $sheet_info    = Config::get('sheet.sheet_info');
        $client      = new GuzzleHttp\Client(['base_uri' => $sheet_info['api_uri'], 'timeout' => $sheet_info['timeout']]);
        $json = [
            'id' => $sheet_info['app_id'],
            'secret' => $sheet_info['app_secret'],
        ];

        try {
            $response = $client->post('agent-token', [
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'json' => $json,
            ]);
            $body                 = $response->getBody();
            $content              = $body->getContents();
            $info                 = json_decode($content, true);
            LAB::set('sheet_token', $info);
            return $info['token'];
        } catch (\Exception $e) {
            return;
        }
    }

    public static function push()
    {
        $token = self::get_token();
        if (!$token) {
            return;
        }
        $push = Config::get('sheet.push');
        foreach ($push as $object) {
            Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "推送 {$object}");
            $func = 'push_' . $object;
            self::$func($token);
        }
    }

    public static function push_equipment($token)
    {
        try {
            $sheet_info = Config::get('sheet.sheet_info');
            $client = new GuzzleHttp\Client(['base_uri' => $sheet_info['api_uri'], 'timeout' => $sheet_info['timeout']]);
            $status = EQ_Status_Model::NO_LONGER_IN_SERVICE;
            $start = 0;
            $step = 15;
            while (true) {
                $equipments = Q("equipment[status!={$status}]:limit({$start}, {$step})");
                if (!$equipments->count()) break;
                $equipment_sheet = [];
                foreach ($equipments as $equipment) {
                    if (!$equipment->ref_no) continue;
                    $incharge = Q("{$equipment} user.incharge")->current();
                    $contact = Q("{$equipment} user.contact")->current();
                    if (!$incharge->id || !$contact->id) continue;
                    $equipment_sheet['equipments'][] = [
                        'id' => $equipment->id,
                        'site' => LAB_ID,
                        'name' => $equipment->name,
                        'identity' => $equipment->ref_no,
                        'refNo' => $equipment->ref_no,
                        'catNo' => $equipment->cat_no,
                        'location' => $equipment->location->id ? $equipment->location->path : '',
                        'location2' => $equipment->location2,
                        'acceptSample' => $equipment->accept_sample,
                        'acceptReserv' => $equipment->accept_reserv,
                        'specification' => $equipment->specification,
                        'model' => $equipment->model_no,
                        'price' => $equipment->price,
                        'manuAt' => $equipment->manu_at,
                        'manuDate' => $equipment->manu_date,
                        'manufacturer' => $equipment->manufacturer,
                        'specs' => $equipment->tech_specs,
                        'features' => $equipment->features,
                        'accessories' => $equipment->configs,
                        'reservSettings' => $equipment->open_reserv,
                        'chargeSettings' => $equipment->charge_info,
                        'purchasedDate' => $equipment->purchased_date,
                        'atime' => $equipment->atime,
                        'controlMode' => $equipment->control_mode,
                        'group' => $equipment->group->id ? $equipment->group->path : '',
                        'email' => $equipment->email,
                        'phone' => $equipment->phone,
                        'action' => $equipment->sheet_id ? 'update' : 'create',
                        'owner' => [
                            'name' => $incharge->name,
                            'identity' => $incharge->ref_no,
                        ],
                        'contact' => [
                            'name' => $contact->name,
                            'identity' => $contact->ref_no,
                            'phone' => $contact->phone,
                            'email' => $contact->email,
                        ],
                        'icon' => $equipment->icon_file('real') ? $equipment->icon_url('real') :
                            ($equipment->icon_file('128') ? $equipment->icon_url('128') : ''),
                    ];
                }

                $json = $equipment_sheet;

                $response = $client->post('equipment-sheet', [
                    'headers'     => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'AUTHORIZATION' => $token,
                    ],
                    'json' => $json,
                ]);
                $body = $response->getBody();
                $content = $body->getContents();
                $info = json_decode($content, true);
                if (isset($info['ids'])) {
                    foreach ($info['ids'] as $id => $sheet_id) {
                        $eq = O('equipment', $id);
                        $eq->sheet_id = $sheet_id;
                        $eq->save();
                    }
                }

                $start += $step;
            }
        }catch (\Exception $e) {
            return;
        }
    }

    public static function push_equipment_booking($token)
    {
        $dtend = Date::time() - 60 * 60 * 24; // 24H
        $equipment_bookings = Q("eq_reserv[dtend>{$dtend}]");
        $equipment_booking_sheet = [];
        foreach ($equipment_bookings as $equipment_booking) {
            $lab = Q("{$equipment_booking->user} lab")->current();
            $equipment_booking_sheet['bookings'][] = [
                'user' => [
                    'name' => $equipment_booking->user->name,
                    'identity' => $equipment_booking->user->ref_no,
                ],
                'lab' => [
                    'name' => $lab->name,
                    'identity' => $lab->ref_no ?: $lab->owner->ref_no,
                ],
                'equipment' => [
                    'name' => $equipment_booking->equipment->name,
                    'identity' => $equipment_booking->equipment->ref_no,
                ],
                'title' => $equipment_booking->component->name,
                'startTime' => $equipment_booking->dtstart,
                'endTime' => $equipment_booking->dtend,
                'identity' => $equipment_booking->id,
                'action' => 'create',
            ];
        }

        $sheet_info  = Config::get('sheet.sheet_info');
        $client      = new GuzzleHttp\Client(['base_uri' => $sheet_info['api_uri'], 'timeout' => $sheet_info['timeout']]);
        $form_params = $equipment_booking_sheet;

        try {
            $response = $client->post('equipment-booking-sheet', [
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'AUTHORIZATION' => $token,
                ],
                'form_params' => $form_params,
            ]);
            $body = $response->getBody();
            $content = $body->getContents();
            $info = json_decode($content, true);
        } catch (\Exception $e) {
            return;
        }
    }

    public static function push_equipment_log($token)
    {
        $dtend = Date::time() - 60 * 60 * 24; // 24H
        $equipment_logs = Q("eq_record[dtend>{$dtend}]");
        $equipment_log_sheet = [];
        foreach ($equipment_logs as $equipment_log) {
            $lab = Q("{$equipment_log->user} lab")->current();
            $equipment_log_sheet['logs'][] = [
                'user' => [
                    'name' => $equipment_log->user->name,
                    'identity' => $equipment_log->user->ref_no,
                ],
                'lab' => [
                    'name' => $lab->name,
                    'identity' => $lab->ref_no ?: $lab->owner->ref_no,
                ],
                'equipment' => [
                    'name' => $equipment_log->equipment->name,
                    'identity' => $equipment_log->equipment->ref_no,
                ],
                'startTime' => $equipment_log->dtstart,
                'endTime' => $equipment_log->dtend,
                'identity' => $equipment_log->id,
                'action' => 'create',
            ];
        }

        $sheet_info  = Config::get('sheet.sheet_info');
        $client      = new GuzzleHttp\Client(['base_uri' => $sheet_info['api_uri'], 'timeout' => $sheet_info['timeout']]);
        $form_params = $equipment_log_sheet;

        try {
            $response = $client->post('equipment-log-sheet', [
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'AUTHORIZATION' => $token,
                ],
                'form_params' => $form_params,
            ]);
            $body = $response->getBody();
            $content = $body->getContents();
            $info = json_decode($content, true);
            error_log(print_r($info, 1));
        } catch (\Exception $e) {
            return;
        }
    }

    public static function push_equipment_sample($token)
    {
        $dtsubmit = Date::time() - 60 * 60 * 24; // 24H
        $equipment_samples = Q("eq_sample[dtsubmit>{$dtsubmit}]");
        $equipment_sample_sheet = [];
        foreach ($equipment_samples as $equipment_sample) {
            $lab = Q("{$equipment_sample->sender} lab")->current();
            $equipment_sample_sheet['samples'][] = [
                'user' => [
                    'name' => $equipment_sample->sender->name,
                    'identity' => $equipment_sample->sender->ref_no,
                ],
                'lab' => [
                    'name' => $lab->name,
                    'identity' => $lab->ref_no ?: $lab->owner->ref_no,
                ],
                'equipment' => [
                    'name' => $equipment_sample->equipment->name,
                    'identity' => $equipment_sample->equipment->ref_no,
                ],
                'submitTime' => $equipment_sample->dtsubmit,
                'count' => $equipment_sample->count,
                'identity' => $equipment_sample->id,
                'action' => 'create',
            ];
        }

        $sheet_info  = Config::get('sheet.sheet_info');
        $client      = new GuzzleHttp\Client(['base_uri' => $sheet_info['api_uri'], 'timeout' => $sheet_info['timeout']]);
        $form_params = $equipment_sample_sheet;

        try {
            $response = $client->post('equipment-sample-sheet', [
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'AUTHORIZATION' => $token,
                ],
                'form_params' => $form_params,
            ]);
            $body = $response->getBody();
            $content = $body->getContents();
            $info = json_decode($content, true);
            error_log(print_r($info, 1));
        } catch (\Exception $e) {
            return;
        }
    }
}

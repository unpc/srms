<?php

use \Pheanstalk\Pheanstalk;

class Yiqikong_Reserv
{

    static function reserv_tab_content_validate($e, $equipment)
    {
        $me = L('ME');
        if ($me->gapper_id && $me->outside && $equipment->yiqikong_id
            && !$me->is_allowed_to('修改预约', $equipment) && !$me->is_allowed_to('修改预约', $equipment)
            && Config::get('system.yiqikong_spread')) {
            error_log('下沉可能需要变动位置:yiqikong_reserve::sample_tab_content_validate');
            $rpc_conf = Config::get('rpc.servers')['yiqikong'];
            $url = $rpc_conf['url'];
            $rpc = new RPC($url);
            if (!$rpc->YiQiKong->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
                return TRUE;
            }

            $data = [];
            $data['site'] = SITE_ID;
            $data['lab'] = LAB_ID;
            $data['title'] = Config::get('page.title_default');
            $data['color'] = Config::get('page.title_color');
            $data['gapper'] = $me->gapper_id;
            $data['equipment'] = $equipment->yiqikong_id;
            $data['action'] = 'reserve';
            $data['redirect'] = Config::get('yiqikong_user.redirect', $_SERVER['HTTP_HOST'] . '/lims');
            $data['redirect'] .= "/!people/profile/index.{$me->id}.eq_reserv";

            $uuid = $rpc->YiQiKong->User->access($data);
            $url = Config::get('system.yiqikong_link') . "/equipment/authorize/{$uuid}";
            URI::redirect($url);
            return FALSE;
        }
    }

    static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        if(isset(L('add_component_form')['material_number']) && $reserv->id){
            Event::trigger('equipment.reserv.material.fields.values', L('add_component_form'), $reserv);
        }
        /**
         * 确定为新架构下用户
         * 配合17kong-server 当Cache::L('YiQiKongReservAction') 为FALSE时候才进行远程更新
         */
        if (!L('YiQiKongReservAction') && Config::get('lab.modules')['app']) {

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            if ($new_data['id']) {
                $path = "reserve";
                $method = 'post';
                $state = Common_Base::STATE_SUCCESS;
            } else {
                $state = Common_Base::STATE_UPDATE;
                $path = "reserve/0";
                $method = 'put';
            }

            $lab = Q("$reserv->user lab")->current();

            /**
             * 临时这样处理
             * */
            $untro = O('yiqikong_approval_uncontrol',['equipment'=>$reserv->equipment,'approval_type' => 'eq_reserv']);
            if(Module::is_installed('yiqikong_approval') &&
                Approval_Access::check_user($untro, $reserv->user)){
                $reserv->approval = Approval_Model::RESERV_APPROVAL_PASS;
            }

            /**
             * 预约表单
             */
            $extra_fields = [];
            if (Module::is_installed('extra')) {
                // 预约保存后开始像app传递的时候，并没有保存自定义表单，不能从O('extra_value')里面取
                if (isset(L('add_component_form')['extra_fields'])) {
                    $extra_fields = L('add_component_form')['extra_fields'];
                }
                else {
                    $extra = Extra_Model::fetch($equipment, 'eq_reserv');
                    $extra_value = O('extra_value', ['object'=> $reserv]);
                    $extra_fields = $extra_value->values;
                }
            }
            $extra_fields = new ArrayIterator($extra_fields ? : []);
            Event::trigger('equipment.reserv.extra.fields.values', $extra_fields, $reserv);
            $extra_fields = (array)$extra_fields;
            /**
             * 关联项目
             */
            if (isset(L('add_component_form')['project'])) {
                $reserv->project = O('lab_project', L('add_component_form')['project']);
            }
            //材料
            $materials = [];//format
            if(isset(L('add_component_form')['material_number'])){
                foreach (L('add_component_form')['material'] as $id => $val) {
                    if($val == 'on'){
                        $materials[$id] = L('add_component_form')['material_number'][$id];
                    }
                }
               
            }else{
                $materials = $reserv->materials;
            }
            if($materials){
                foreach ($materials as $id => $val) {
                    $materials_extra['m'.$id] = $val;
                }
                // $extra_fields = array_merge($extra_fields, $materials_extra);
                $extra_fields = $extra_fields + $materials_extra;
            }
            $payload = [
                'method' => $method,
                'path' => $path,
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'title' => $reserv->component->name,
                    'user' => $reserv->user->yiqikong_id,
                    'user_local' => $reserv->user->id,
                    'user_name' => $reserv->user->name,
                    'lab_name' => $lab->name ?? '',
                    'lab_id' => $lab->id ?? 0,
                    'project_name' => $reserv->project->name,
                    'phone' => $reserv->user->phone,
                    'address' => $reserv->user->address,
                    'equipment' => $reserv->equipment->yiqikong_id,
                    'equipment_local' => $reserv->equipment->id,
                    'start_time' => $reserv->dtstart,
                    'end_time' => $reserv->dtend,
                    'ctime' => $reserv->ctime,
                    'mtime' => $reserv->mtime,
                    'project' => $reserv->project_id,
                    'description' => $reserv->component->description,
                    'status' => $reserv->status,
                    'source_name' => LAB_ID,
                    'source_id' => $reserv->id,
                    'component_id' => $reserv->component_id,
                    'token' => $reserv->component->token,
                    'approval' => $reserv->approval,
                    'state' => $state,
                    'count' => $reserv->count,
                    'extra_fields' => $extra_fields,
                    'not_send_message' => L('YiQiKongReservNotSendMessage'),
                    'materials'=>$materials,
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            Cache::L('YiQiKongReservNotSendMessage', NULL);
            Cache::L('YiQiKongReservAction', NULL);

            return true;

        }
    }

    static function on_eq_reserv_deleted($e, $reserv)
    {
        /**
         * 确定为新架构下用户
         * 配合17kong-server 当Cache::L('YiQiKongReservAction') 为FALSE时候才进行远程更新
         */
        if (Config::get('lab.modules')['app'] && !L('YiQiKongReservAction')) {
            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            $payload = [
                'method' => 'DELETE',
                'header' => ['x-yiqikong-notify' => TRUE],
                'path' => 'reserve/0',
                'body' => [
                    'source_name' => LAB_ID,
                    'source_id' => $reserv->id,
                ],
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            return TRUE;
        }

        Cache::L('YiQiKongReservAction', null);
    }

    static function links($e, $user, $reserv, $params, $links)
    {
        $object = O('cal_component', $reserv->component->id);

        if ($user->is_allowed_to('修改', $object)) {
            $links[] = [
                'title' => I18N::T('equipments', '编辑'),
                'icon' => '',
                'color' => 'colorBlue',
                'action' => 'edit',
                'params' => ''
            ];
        }
        if ($user->is_allowed_to('删除', $object)) {
            $links[] = [
                'title' => I18N::T('equipments', '删除'),
                'icon' => '',
                'color' => 'colorBlue',
                'action' => 'delete',
                'params' => ''
            ];
        }
    }
}


<?php
use \Pheanstalk\Pheanstalk;

class Control_Equipment_Approval
{
    public static function orm_model_saved($e, $object, $old_data, $new_data)
    {
        
    }
    static function on_approval_saved($e, $approval, $old_data, $new_data) {
        if (!Config::get('lab.modules')['app']) return TRUE;
        if (!Config::get('lab.modules')['approval_flow']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        
        if (!$new_data['id']) {  
            // 更新操作
            $path = "approval/0";
            $method = 'PATCH';
        } else { 
            // 新增操作
            $path = "approval";
            $method = 'POST';
        }

        $data = [
            'source_id' => $approval->source_id,
            'source_name' => $approval->source_name,
            'type' => $approval->flag,
            'source' => LAB_ID,
        ];

        $data = Event::trigger('approval_flow.append_form_data', $data, $approval) ?: $data;

        switch ($approval->source_name) {
            case "eq_reserv": 
                $data['source_name'] = "reserve";
                break;
            case "eq_sample": 
                $data['source_name'] = "sample";
                break;
        }
        switch ($approval->flag) {
            case "approve_pi": 
                $data['type'] = "pi";
                break;
            case "approve_incharge": 
                $data['type'] = "incharge";
                break;
            default: 
                $data['type'] = $approval->flag;
                break;
        }


        $data = new ArrayIterator($data);

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => (array)$data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode((array)$payload, TRUE));

        return TRUE;
    }
}

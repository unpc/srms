<?php

class NSoap
{
    private static $res_code = [
        '100' => '上传成功',
        '200' => '单位编码错误',
        '201' => 'JSON字符串格式错误',
        '202' => '数据验证错误',
        '203' => '其他异常',
        '301' => '数据库操作异常'
    ];

    public static function push($type, $source_name, $instru_type, $list, $method)
    {
        $types = Config::get('nrii')['type'] ? : [];
        if (in_array($source_name, $types)) {
            $type .= "_{$source_name}";
        }

        $soap = QSOAP::of($type);
        $insCode = $list['insCode']?:Config::get("nrii")[$source_name];
        // error_log(print_r($list,true));die;
        $list = preg_replace('/^\[(.*)\]$/', '$1', json_encode([$list]));

        try {
            if ($instru_type) {
                $data = [
                    'insCode' => $insCode,
                    'instruType' => $instru_type,
                    'instruList' => $list
                ];
            } else {
                $data = [
                    'insCode' => $insCode,
                    'effectList' => $list
                ];
            }
            $ret = $soap->$method($data);

            // error_log(print_r($ret,true));
            return $ret->return;

        } catch (Exception $e){
            return "SOAP ERROR:".$e->getMessage()."\n";
        }
    }
}

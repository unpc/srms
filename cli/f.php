<?php

require 'base.php';

$equipment_keys = [
    //'eq_id',
    'eq_name' => 'cname',
    'ename' => 'ename',
    //'org',
    'inner_id' => 'innerId',
    'affiliate' => 'instrBelongsType',
    'affiliate_name' => 'instrBelongsName',
    'class' => 'instrCategory',
    'eq_source' => 'instrSource',
    'customs' => 'instrSupervise',
    'address' => 'address',
    'street' => 'street',
    'worth' => 'worth',
    'nation' => 'nation',
    'manufacturer' => 'manufacturer',
    'begin_date' => 'beginDate',
    'type_status' => 'type',
    'model_no' => 'instrVersion',
    'technical' => 'technical',
    'function' => 'function',
    //'status',
    //'share_status',
    'realm' => 'subject',
    'service_content' => 'serviceContent',
    'requirement' => 'requirement',
    'fee' => 'fee',
    'service_url' => 'serviceUrl',
    

    
    'contact' => 'contact',
    'phone' => 'phone',
    'email' => 'email',
    'resource_name' => 'resourceName',
    'contact_address' => 'address',
    'zip_code' => 'postalCode',
    
    //'achievement',
    'run_machine' => 'runMachine', // 年总运行机时
    'service_machine' => 'serviceMachine', // 年服务机时
    'funds' => 'funds', // 主要购置经费来源
    'inside_depart' => 'insideDepart', // 所属单位内部门
];

class SClient extends SoapClient {

    private $_username;
    private $_password;
    private $_digest;
    private $_event_name;

    function __construct($wsdl, $opts) {
        $this->_request_event = $opts['request_event'];
        $this->_response_event = $opts['response_event'];
        parent::__construct($wsdl, $opts);
    }

    public function __doRequest($request, $location, $saction, $version, $one_way = 0) {
        try {
            if ($this->_request_event) {
                $request = Event::trigger("soap.{$this->_request_event}", $request);
            }

            var_dump(strtr("SOAP Request Data => \n[%data]\n", ['%data' => $request]));

            $response = parent::__doRequest($request, $location, $saction, $version, $one_way);

            if ($this->_response_event) {
                $response = Event::trigger("soap.{$this->_response_event}", $response);
            }

            var_dump(strtr("SOAP Response Data => \n[%data]\n", ['%data' => $response]));

            return $response;
        } catch (SoapFault $sf) {
            var_dump(strtr("SOAP Request Error => \n[%data]\n", ['%data' => $sf->faultstring]));
        } catch (Exception $e) {
            var_dump(strtr("Request Error => \n[%data]\n", ['%data' => $e->getMessage()]));
        }


    }
}

class QQSOAP
{
    private $_db;
    private $_wsdl;

    protected $_options = [];

    private static $_SOAPs = [];

    public static function factory($opt)
    {
        return new QQSOAP($opt);
    }

    public static function to_xml($root, $namespace, $data) {

        $xml = "<$root xmlns=\"$namespace\">";
        foreach($data as $k => $v) {
            $xml .= "<$k>$v</$k>";
        }
        $xml .= "</$root>";

        return $xml;
    }

    public function __construct($opt)
    {
        $this->_options = $opt;
    }

    public function getOption($name)
    {
        return $this->_options[$name];
    }

    public function getClient($force_refresh = FALSE,$params = []) {
        if (!$this->_db || $force_refresh) {
            //设定wsdl
            $this->_wsdl = $this->getOption('wsdl');

            $initParams = [
                'cache_wsdl' => 0,
                'trace' => 1,
                'request_event' => $this->getOption('request_event'),
                'response_event' => $this->getOption('response_event'),
                'soap_version' => $this->getOption('soap_version') ? : SOAP_1_2
            ];

            //取消https验证
            $context = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'allow_self_signed' => true
                ),
            ));
            $params['stream_context'] = $context;

            //@update at 2018-12-10 by changchun.qi 增加扩展初始化字段，如是否不验证https
            if(!empty($params)){
                $initParams = @array_merge($initParams,$params);
            }
            //设定_client cache_wsdl 禁止缓存  trace 开启调试
            $this->_db = new SClient($this->_wsdl, $initParams);

            if ($location = $this->getOption('realLocation')) {
                $this->_db->__setLocation($location);
            }


            if ($this->getOption('header')) {
                $xml = self::to_xml(
                    $this->getOption('header'),
                    $this->getOption('namespace'), [
                    'UserName' => $this->getOption('user'),
                    'PassWord' => $this->getOption('password')
                ]);
                //soapVar 构造为访问协议可识别的参数 (后面3个可以不填写)
                $headerVar = new SoapVar($xml, XSD_ANYXML, null, null, null);

                //soapHeader 构造头文件
                $headers = new SoapHeader(
                        $this->getOption('namespace'),
                        $this->getOption('header'),
                        $headerVar,
                        FALSE
                        );

                $this->_db->__setSoapHeaders($headers);
            }
        }
        return $this->_db;
    }

    public function __call($method, $params) {
        var_dump(strtr("SOAP Call => {%method}, [{%params}]", ['%method' => $method, '%params' => json_encode($params)]));

        if ($method == __FUNCTION__) return FALSE;

        try {
            if (method_exists($this, $method)) {
                $return = call_user_func_array([$this, $method], $params);
            }
            else {
                $return = $this->getClient()->__soapCall($method, $params);
            }

            return $return;
        } catch(\Exception $e) {
            var_dump(strtr("SOAP Call Error => \n{%error}\n", ['%error' => $e->getMessage()]));
        }
    }

    public function getFuncs() {
        return $this->getClient()->__getFunctions();
    }
    
    public function getTypes() {
        return $this->getClient()->__getTypes();
    }

    public function addInstrument($args) {
        try {
            return $this->getClient()->addInstrument([json_encode($args)]);
        } catch(\Exception $e) {
            var_dump(strtr("SOAP Call Error => \n{%error}\n", ['%error' => $e->getMessage()]));
        }
    }

    public function queryInstrument() {
        try {
            $str = json_encode([
                'userId' => '01aed7ee-2349-4357-ba0c-81ea45963f3a',
                'pageNum' => 1,
                'pageSize' => 10,
                'startTime' => '2024-01-01',
                'endTime' => '2024-03-30'
            ]);
            var_dump($str);
            return $this->getClient()->queryInstrument(1231231, [$str]);
        } catch(\Exception $e) {
            var_dump(strtr("SOAP Call Error => \n{%error}\n", ['%error' => $e->getMessage()]));
        }
    }
}


$soap = QQSOAP::factory([
    'wsdl' => 'https://tjlab.tten.cn:8091/webservice/api?wsdl',
    'namespace' => 'http://service.dkd.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
]);

var_dump($soap->queryInstrument());

die();

// $nrii = Q('nrii_equipment[eq_id]')->limit(1);

// $params = [];
// // $params['source_name'] = LAB_ID;
// $params['userId'] = '01aed7ee-2349-4357-ba0c-81ea45963f3a';
// $equipment = O('equipment', $nrii->eq_id);
// if ($equipment->id && $equipment->icon_file(128)) {
//     $icon_url = $equipment->icon_url('128');
// }
// else {
//     $icon_url = Config::get('system.base_url').'images/equipment.jpg';
// }
// $params['image'] = $icon_url ? : '无';
// $mode = 'equipment';

// Event::trigger('nrii.equipment.push_columns');
// foreach ($equipment_keys as $key => $value) {
//     // 如果是这三类字段，通用均需要做调整
//     if ($key == 'begin_date') {
//         $params[$value] = date('Y-m-d', $nrii->$key);
//         continue;
//     }
//     // 2019.2.14 UNPC 数据校准容错，避免上传到Nrii会出现字段不存在、字段类型错误等问题
//     switch ($mode) {
//         case 'equipment':
//             if ($key == 'realm' || $key == 'funds' ) {
//                 $subjects = @json_decode($nrii->$key, true);
//                 $params[$value] = implode(', ', $subjects);
//                 break;
//             }
//             if ($key == 'service_machine') {
//                 $params[$value] = (float)$nrii->service_machine;
//                 break;
//             }
//             if ($key == 'run_machine') {
//                 $params[$value] = (float)$nrii->run_machine;
//                 break;
//             }
//             if ($key == 'eq_source') {
//                 $params[$value] = Nrii_Equipment_Model::$eq_source[(int)$nrii->eq_source];
//                 break;
//             }
//             if ($key == 'type_status') {
//                 $params[$value] = Nrii_Equipment_Model::$type_status[(int)$nrii->type_status];
//                 break;
//             }
//             if ($key == 'affiliate') {
//                 if ($nrii->affiliate == Nrii_Equipment_Model::AFFILIATE_NONE || !$nrii->affiliate) {
//                     $params[$value] = '无';
//                 }
//                 else {
//                     $params[$value] = (int) $nrii->$key;
//                 }
//                 break;
//             }
//             if ($key == 'affiliate_name') {
//                 if ($nrii->affiliate == Nrii_Equipment_Model::AFFILIATE_NONE) {
//                     $params[$value] = '无';
//                 }
//                 else if (in_array($nrii->affiliate, Nrii_Equipment_Model::$affiliate_resource_type)) {
//                     $params[$value] = '无';
//                 }
//                 else {
//                     $params[$value] = $nrii->$key;
//                 }
//                 break;
//             }
//             if ($key == 'resource_name') {
//                 if (in_array($nrii->affiliate, Nrii_Equipment_Model::$affiliate_resource_type)) {
//                     $params[$value] = $nrii->affiliate_name;
//                 }
//                 else {
//                     $params[$value] = '无';
//                 }
//                 break;
//             }
//             // 如果是单台套、仪器分类为其他，则转换成990000）
//             if ($key =='class' && $nrii->$key == '999999') {
//                 $params[$value] = '990000';
//                 break;
//             }
//             if ($key == 'inside_depart') {
//                 $params[$value] = ( $nrii->$key ?: $nrii->org ) ?: '无';
//                 break;
//             }
//             if ($key == 'fee') {
//                 $params[$value] = $nrii->$key ?: '无';
//                 break;
//             }
//             if ($key == 'customs') break;
//             if($key == 'share_status'){
//                 $params[$value] = Nrii_Equipment_Model::$share_status[$nrii->$key];
//                 break;
//             }
//             if($key == 'status'){
//                 $params[$value] = Nrii_Equipment_Model::$status[$nrii->$key];
//                 break;
//             }
//             if ($key == 'worth') {
//                 $params[$value] = (double)round($nrii->$key, 2);
//                 break;
//             }
//         default:
//             $params[$value] = $nrii->$key;
//             break;
//     }
// }
// //对字段进行重新渲染
// $params = Event::trigger('nrii.equipment.push_columns_update',$params,$nrii) ?: $params;

// $params['status'] = -1;

// //如果是单台套补充海关信息和服务记录
// $pushCustoms = false;//标识是否需要推送海关信息
// $customsParams = [];
// $params['instrSupervise'] = '否';
// $customs = O('nrii_customs', $nrii->customs->id);
// if ($customs->id){
//     $params['instrSupervise'] = '是';
//     Event::trigger('nrii.equipment.customs.push_columns');
//     foreach (self::$customs_keys as $key => $value) {
//         if ($key == 'import_date') {
//             $customsParams[$value] = date('Y-m-d', $customs->$key);
//             continue;
//         }
//         if ($key == 'share') {
//             $customsParams[$value] = $customs->$key ? '是' : '否';
//             continue;
//         }
//         if ($key == 'fees_approved') {
//             $customsParams[$value] = $customs->$key ? '是' : '否';
//             continue;
//         }
//         if ($key == 'auditStatus') {
//             $customsParams[$value] = -1;
//             continue;
//         }
//         $customsParams[$value] = $customs->$key;

//     }
//     $pushCustoms = true;
// }
// if($nrii->address){
//     $names = Nrii_Address::get_name($nrii->address);
//     $params['province'] = $names['province'];
//     $params['city'] = $names['city'];
//     $params['county'] = $names['county'];
// }else{
//     $names = $nrii->address_info;
//     $params['province'] = $names['province'];
//     $params['city'] = $names['city'];
//     $params['county'] = $names['county'];
// }

// var_dump($soap->addInstrument($params));


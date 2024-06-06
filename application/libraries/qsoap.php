<?php

class Soap_Client extends SoapClient {

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

            Log::add(strtr("SOAP Request Data => \n[%data]\n", ['%data' => $request]), 'soap');

            $response = parent::__doRequest($request, $location, $saction, $version, $one_way);

            if ($this->_response_event) {
                $response = Event::trigger("soap.{$this->_response_event}", $response);
            }

            Log::add(strtr("SOAP Response Data => \n[%data]\n", ['%data' => $response]), 'soap');

            return $response;
        } catch (SoapFault $sf) {
            Log::add(strtr("SOAP Request Error => \n[%data]\n", ['%data' => $sf->faultstring]), 'soap');
        } catch (Exception $e) {
            Log::add(strtr("Request Error => \n[%data]\n", ['%data' => $e->getMessage()]), 'soap');
        }


    }
}

class QSOAP
{
    private $_db;
    private $_wsdl;

    protected $_options = [];

    private static $_SOAPs = [];

    public static function factory($opt)
    {
        return new QSOAP($opt);
    }

    public static function of($name)
    {
        if (!isset(self::$_SOAPs[$name])) {
            $options = Config::get("soap")[$name];
            $ldap = new QSoap($options);
            self::$_SOAPs[$name] = $ldap;
        }

        return self::$_SOAPs[$name];
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

            //@update at 2018-12-10 by changchun.qi 增加扩展初始化字段，如是否不验证https
            if(!empty($params)){
                $initParams = @array_merge($initParams,$params);
            }
            //设定_client cache_wsdl 禁止缓存  trace 开启调试
            $this->_db = new Soap_Client($this->_wsdl, $initParams);

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
        Log::add(strtr("SOAP Call => {%method}, [{%params}]", ['%method' => $method, '%params' => json_encode($params)]), 'soap');

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
            Log::add(strtr("SOAP Call Error => \n{%error}\n", ['%error' => $e->getMessage()]), 'soap');
        }
    }

    public function getFuncs() {
        return $this->getClient()->__getFunctions();
    }
}


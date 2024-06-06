<?php

$config['instru'] = [
    'wsdl' => 'http://124.207.169.20:8686/WS_Server/cxf/instru?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['effect'] = [
    'wsdl' => 'http://124.207.169.20:8686/WS_Reg/cxf/institution?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['instru_nuc'] = [
    'wsdl' => 'http://218.26.227.179/WS_shanxi/cxf/instru?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['effect_nuc'] = [
    'wsdl' => 'http://218.26.227.179/WS_shanxi/cxf/effect?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['instru_gdut'] = [
    'wsdl' => 'http://210.21.13.235:8082/WS_guangdong/cxf/instru?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['effect_gdut'] = [
    'wsdl' => 'http://210.21.13.235:8082/WS_guangdong/cxf/effect?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

$config['instru_ncu'] = [
//    'wsdl' => 'http://124.205.141.247:8099/cxf/instruService?wsdl',//对方开发提供的接口地址
//    'wsdl' => 'http://124.205.220.108:8099/SubPlat_Province/cxf/instruService?wsdl',//对方开发提供的接口地址
    'wsdl' => 'http://www.jxky.cn/cxf/instruService?wsdl',
    'namespace' => 'http://instru.server.ws.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response'
];

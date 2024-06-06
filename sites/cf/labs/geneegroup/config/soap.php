<?php

$config['door'] = [
    'wsdl' => 'http://yqgx.xatrm.com/ws_server/cxf/instru?wsdl',
    'namespace' => 'http://yqgx.xatrm.com/',
    'header' => 'MySoapHeader',
    'soap_version' => SOAP_1_1,
    'response_event' => 'jsx_to_xml.response',
    'realLocation' => "http://yqgx.xatrm.com/ws_server/cxf/instru"
];
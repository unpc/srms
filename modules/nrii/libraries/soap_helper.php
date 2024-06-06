<?php

class Soap_Helper
{
    public static function replace_jsx_to_xml_data($e, $response)
    {
        
        preg_match('|<soap:.*Envelope>|U', $response, $match);
        
        if ($soap = $match[0]) {
            $e->return_value = "<?xml version=\"1.0\" encoding=\"utf-8\"?>{$soap}";
            return FALSE;
        }

        $e->return_value = $response;

        return FALSE;
    }

}

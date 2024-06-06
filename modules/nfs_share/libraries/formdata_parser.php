<?php
	
class FormData_Parser
{
	public $retData;
	public $info;

    private static $partSize = 4096;    //每次最大获取字节

    /**
     * 负责解析FormData
     */
    public static function parser($options = ['saveFile' => 1])
    {
        //$options['saveFile'] = true; 测试能否正常保存临时文件
        $formData = fopen("php://input", "r");

        $retData = [];

        $boundary = rtrim(fgets($formData), "\r\n");     //第一行是boundary

        $info = []; //info段的信息
        $data = ''; //拼接的数据
        $infoPart = true; //是否是info段
        
		while ($line = fgets($formData, self::$partSize)) {
            if ($boundary . "\r\n" == $line || $boundary . "--\r\n" == $line) {
                //如果是分割
                $infoPart = true;

                if ($info['type'] == 'json') {
                    // $data = json_decode($data, true);
                    $retData[$info['name']] = trim($data, "\n");
                } else if($info['type'] == 'file') {

                    if(isset($info['tmp_file'])) {
                        fclose($info['file_handle']);
                        $retData[$info['name']] = [
                            'org_name' => $info['org_name'],
                            'tmp_file' => $info['tmp_file']
                        ];
                    } else {
                        $retData[$info['name']] = $data;
                    }

                }

                $data = '';
            } else if ("\r\n" == $line) {
                if ($infoPart) {
                    //解析info
                    $info = self::parserInfo($data, $options);
                    if (isset($info['tmp_file'])) {
                        $info['file_handle'] = fopen($info['tmp_file'], 'w');
                    }
                    $data = '';
                    $infoPart = false;
                }
            } else {
                if($infoPart == false && isset($info['tmp_file'])) {
                    fwrite($info['file_handle'], $line);
                } 
				else {
                    $data .= $line;
                }
            }
        }
        fclose($formData);
		#error_log(print_r($retData, true));
		return [
			'form' => $retData,
			'file' => $info
		];
    }

    private static function parserInfo($data, $options)
    {
        //获取参数名称, type
        $infoPattern = '/name="(.+?)"(; )?(filename="(.+?)")?/'; //todo: 待优化
        preg_match($infoPattern, $data, $matches);

        $info['name'] = $matches[1];
        $info['type'] = 'json';

        //如果是文件
        if (count($matches) > 4) {
            $info['type'] = 'file';
            $info['org_name'] = $matches[4];
            //如果设置保存文件, 保存到临时文件
            if (isset($options['saveFile']) && $options['saveFile']) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'FD');
                $info['tmp_file'] = $tmpFile;
            }
        }

        return $info;
    }

}


<?php
/*{
    'name' => '系统名称, (必填)',
    'client_id'  => '系统标志， (必填)',
    'shortName' => '短名称',
    'url'         => '访问地址',
    'description' => '描述',
    'platform'   =>  '所属平台'
}*/
require "base.php";
echo "========begin to register app to gapper\n";
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;
echo 'begin to register app to gapper';
$params=[
    'name'=>'lims_nankai',
    'client_id'=>'lims_nankai',
    'shortName'=>'lims',
    'url'=>'lims3.17kong.com',
    'description'=>'lims3.0大数据体系对接',
    'platform'=>'17kong'
];
$params['_expiretime']=time();
ksort($params);
$base_url='http://lims3.17kong.com';
$client = new GuzzleHttp\Client(['base_uri' => $base_url]);
$header=['X-GINI-SIGN'=>MD5('geneegroup' . json_encode($params, JSON_UNESCAPED_UNICODE))];

$response=$client->request('POST', '/app-center/api/app', ['headers' => $header,'json'=>$params]);
echo "response:\n";
print_r(json_decode($response->getBody()->getContents()));

echo "\n end";


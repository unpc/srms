<?php

require "base.php";
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

/**
 * 浙江省大仪平台对接
 */
class CLI_Zhejiang_Dayi
{
    private static function get_guzzle_client()
    {
        return new GuzzleHttp\Client(['base_uri' => 'http://42.192.36.152/', 'timeout' => 5]);
    }

    private static function get_token()
    {
        $client = self::get_guzzle_client();

        $config = Config::get('zhejiangdayi.zhejiang_dayi');

        $headers = [
            'User-Agent' => 'Apifox/1.0.0 (https://www.apifox.cn)',
            'Content-Type' => 'application/json',
        ];

        $json = [
            "username" => $config['username'],
            "password" => $config['password'],
        ];

        try {
            $response = $client->post('external-serve/authorization/getToken', [
                'headers' => $headers,
                'json' => $json,
            ]);

            $body = $response->getBody();
            $content = $body->getContents();
            $info = json_decode($content, true);

            return $info['token'] ?: false;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            return;
        }
    }

    public static function push_records()
    {
        $token = self::get_token();
        if (!$token) {
            return;
        }

        $client = self::get_guzzle_client();

        $headers = [
            'Authorization' => $token,
            'User-Agent' => 'Apifox/1.0.0 (https://www.apifox.cn)',
            'Content-Type' => 'application/json',
        ];

        $equipments = Q("equipment[ref_no]");
        foreach ($equipments as $equipment) {
            $records = Q("eq_record[equipment={$equipment}][dtend>0]:sort('dtend D')");
            foreach ($records as $record) {
                $record = Q("eq_record[equipment_id=100][dtend>0]:sort('dtend D')")->current();
                if ($record->reserv->id) {
                    $charge = O('eq_charge', ['source' => $record->reserv]);
                } else {
                    $charge = O('eq_charge', ['source' => $record]);
                }
                $incharge = Q("{$equipment} user.incharge")->current();

                $json = [
                    "orderCode" => 'gkdhz_record_' . str_pad($record->id, 6, 0, STR_PAD_LEFT),
                    "assetCode" => $record->equipment->ref_no,
                    "gs1Code" => '',
                    "serviceDate" => round(($record->dtend - $reccord->dtstart) / 3600, 2),
                    "serviceCost" => $charge->amount,
                    "serviceContent" => "{$reocrd->user}使用仪器收费",
                    "servicingTime" => date('Y-m-d', $reocrd->dtstart), // 服务时间
                    "serviceUser" => $record->user->name, // 服务时间
                    "servicingRole" => '2', // 服务用户角色 1：企业；2：个人
                    "serviceEnterprise" => $record->user->organization, // 企业名称 - 如果用户角色是的企业，企业名称必填，如果用户角色是的个人，企业名称填个人所在的单位
                    "servicingSource" => '1', // 服务来源 1：单位内服务；2：单位外部服务；
                    "servicingEnterpriseProperty" => '1', // 企业属性 提供枚举【单位属性枚举表 1】，根据大仪的单位属性分类进行分
                    "servicingEnterpriseType" => '3', // 企业类型 提供枚【单位属性枚举表 1】，根据大仪的单位属性分类进行分
                    "servicePerson" => $incharge->name, // 服务人员名称 - 本单位进行这个服务的人名
                ];

                $body = json_encode($json, JSON_UNESCAPED_UNICODE);
                $body = '[' . $body . ']';

                try {
                    $request = new GuzzleHttp\Psr7\Request('POST', '/external-serve/service/batch-push-instrument-service', $headers, $body);
                    $res = $client->sendAsync($request)->wait();
                    $body = $res->getBody();
                    $info = json_decode($body, JSON_UNESCAPED_UNICODE);
                    if ($info['code'] == 200) {
                        echo $record->equipment->name . '(服务记录编号' . $record->id . ')' . ' 上传成功' . PHP_EOL;
                    }

                } catch (\Exception $e) {
                    return;
                }
            }

        }

        self::logout($token);

    }

    public static function push_equipments()
    {
        $token = self::get_token();
        if (!$token) {
            return;
        }

        $client = self::get_guzzle_client();
        $config = Config::get('zhejiangdayi.zhejiang_dayi');

        $headers = [
            'Authorization' => $token,
            'User-Agent' => 'Apifox/1.0.0 (https://www.apifox.cn)',
            'Content-Type' => 'application/json',
        ];

        $equipments = Q("equipment[ref_no]");
        foreach ($equipments as $equipment) {

            $contact = Q("{$equipment} user.contact")->current();
            $json = [
                "id" => $equipment->id,
                "name" => $equipment->name, // 中文名称
                "ename" => $equipment->en_name, // 英文名称
                "companyId" => $config['companyId'], // 统一社会信用代码 - 该单位的统一信用代码
                "code" => '无', // 所属资产载体名称 - 当【是否集约化管理】项填是，所属资源平台必填。在大仪注册的载体id，可登入大仪查看获取
                "instruId" => $equipment->ref_no, // 仪器编码 - 单位内唯一编码，请谨慎填写，填写后就不能进行改动
                "typeCode" => $equipment->cat_no ?: '无', // 仪器分类编码
                "source" => '新购', // 仪器设备来源 - 新购、研制、调拨、接收捐赠、置换、盘盈、其他
                "customs" => '否', // 是否免税 - 是否免税，免税填是，不免税填否
                "price" => round($equipment->price / 10000, 2), // 原值
                "country" => $equipment->manu_at, // 产地国别
                "buildCompany" => $equipment->manufacturer, // 生产制造商
                "startDate" => $equipment->purchased_date ? date('Y-m-d', $equipment->purchased_date) : '0000-00-00', // 启用日期
                "pormat" => $equipment->model_no, // 规格型号
                "technology" => $equipment->tech_specs ?: '无', // 主要技术指标
                "functionField" => $equipment->features ?: '无', // 规格型号
                "purpose" => '无', // 主要用途
                "applicationArea" => '无', // （暂时无用）主要应用领域 - 按GB/T 13745-2009规定选择大型科研仪器支持科技活动的主要学科名称及代码，涉及多个学科领域的可多选（最多4个）
                "subjectAreas" => '无', // 主要学科领域
                "share" => $equipment->open_reserv ?: '无', // 对外开放共享服务须知
                "province" => '浙江省', // 仪器安放所在省份
                "city" => '杭州市', // 仪器安放所在市
                "county" => '无', // 仪器安防所在县
                "street" => $equipment->location . '' . $equipment->location2, // 仪器安防详细地址
                "sContact" => $contact->id ? $contact->name : '', // 仪器联系人
                "sPhone" => $contact->id ? $contact->phone : '', // 电话
                "sEmail" => $contact->id ? $contact->get_binding_email() : '', // 电子邮箱
                "imgurl" => $equipment->icon_url('64'), // 仪器图片
                "funds" => '单位自有资金', // 主要购置经费来源 - 枚举：单位自有资金、中央财政资金、地方财政资金、单位自有资金、其他（可多选）
                "running" => EQ_Status_Model::$status[$equipment->status], // 运行状态
                "gs1Code" => '无', // 唯一编码，GS1Code编码服务生成，非必填项；若无，省大型科研仪器开放共享平台自行生成
            ];

            $body = json_encode($json, JSON_UNESCAPED_UNICODE);
            $body = '[' . $body . ']';

            try {
                $request = new GuzzleHttp\Psr7\Request('POST', '/external-serve/manage/enterprise/school-instrument-push', $headers, $body);
                $res = $client->sendAsync($request)->wait();
                $body = $res->getBody();
                $info = json_decode($body, JSON_UNESCAPED_UNICODE);
                if ($info['code'] == 200) {
                    echo $equipment->name . '(' . $equipment->ref_no . ')' . ' 上传成功' . PHP_EOL;
                }
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                return;
            }
        }

        self::logout($token);

    }

    private static function logout($token)
    {
        $client = self::get_guzzle_client();

        $headers = [
            'Authorization' => $token,
            'User-Agent' => 'Apifox/1.0.0 (https://www.apifox.cn)',
        ];

        try {
            $request = new GuzzleHttp\Psr7\Request('POST', '/external-serve/logout', $headers);
            $res = $client->sendAsync($request)->wait();
            $body = $res->getBody();
            $info = json_decode($body, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return;
        }
    }
}

CLI_Zhejiang_Dayi::push_equipments();

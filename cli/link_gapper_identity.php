#!/usr/bin/env php
<?php
/*
 * file link_gapper_identity
 * author Rui Ma <rui.ma@geneegroup.com>
 * date 2015/05/08
 *
 * useage 用于对用户遍历, 尝试对用户的 Gapper 信息进行 identity 更新
 * brief SITE_ID=cf LAB_ID=xx php link_gapper_identity.php
 */

require 'base.php';

$gapper_url = 'http://gapper.in/api';

$rpc = new RPC($gapper_url);

$client_id = Config::get('gapper.client_id');
$client_secret = Config::get('gapper.client_secret');

$rpc->Gapper->Authorize($client_id, $client_secret);

//对所有的 ids.nankai 的激活用户尝试进行 Gapper 更新
foreach(Q('user[token*=ids.nankai][atime]') as $user) {

    //用户无 Gapper_id, 则尝试升级
    if (!$user->gapper_id) {
        $cname = trim($user->name);
        $cemail = trim($user->email);

        $data = $rpc->Gapper->User->GetInfo($cemail);

        //获取到了用户信息
        if ($data) {
            if (strpos($data['name'], $user->name) !== FALSE || strpos($user->name, $data['name']) !== FALSE) {
                //可绑定

                //更新 gapper_id
                $user->set('gapper_id', $data['id'])->save();
            }
        }
        else {
            //创建新的用户

            $d = [
                'username'=> $user->email,
                'password'=> uniqid(),
                'name'=> $user->name,
                'email'=> $user->email,
                ];

            $id = $rpc->Gapper->User->RegisterUser($d);

            if ($id) {
                //更新 gapper_id
                $user->set('gapper_id', $id)->save();
            }
        }
    }


    //如果用户有 gapper_id, 则进行比对、link
    if ($user->gapper_id) {

        $identity = $rpc->Gapper->User->GetIdentity((int)$user->gapper_id, 'nankai');

        //进行 ids_token 的处理
        $token = $user->token;
        $lp = strpos($token, 'less.nankai');
        if ($lp) {
            $token = substr($token, 0, $lp);
        }

        list($ids_token, $foo) = explode('|', $token);

        //如果已关联了, 比对 identity 和 token 中 ids.nankai 前的数据
        if ($identity) {

            if ($identity != $ids_token) {
                $delete[] = $user->gapper_id;

                $u = $rpc->Gapper->User->GetUserByIdentity('nankai', $identity);

                if ($u['id']) {
                    //更新 gapper_id
                    $user->set('gapper_id', $u['id'])->save();
                }
            }
        }
        else {
            //进行 Link
            $rpc->Gapper->User->LinkIdentity($user->gapper_id, 'nankai', $ids_token);
        }
    }
}

if (count($delete)) {
    echo "如下 Gapper 信息需要删除\n";
    echo "======\n";
    foreach($delete as $d) {
        echo "$d\n";
    }
    echo "======\n";
}

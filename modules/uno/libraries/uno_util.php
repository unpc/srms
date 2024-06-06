<?php

class Uno_Util
{

    public static function get_remote_user($token = '', $condition = [])
    {
        try {
            $server = Config::get('gateway.server');
            $rest = new REST($server['url']);
            $token = $token ?: $_SESSION['gapper_oauth_token'];
            $data = $rest->get('current-user', ['gapper-oauth-token' => $token, 'includes' => 'groups']);
            if (empty($data) || $data['status'] != 4) return [];
            $user = [
                'status' => $data['status'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'ref_no' => $data['ref_no'],
                'avatar' => $data['avatar'],
                'gapper_id' => $data['id'],
            ];
            $user_token = $rest->get('/auth/owner', ['gapper-oauth-token' => $token]);
            $user['token'] = $user_token['id'] ?? $data['id'];

            //获取group
            $remoteGroup = Gateway::getRemoteUserGroups([
                'user_id' => $user['gapper_id'],
                'group_type' => 'organization' // lab
            ]);
            $remoteLab = Gateway::getRemoteUserGroups([
                'user_id' => $user['gapper_id'],
                'group_type' => 'lab' // lab
            ]);

            $groups = isset($remoteGroup['items']) ? $remoteGroup['items'] : [];
            $labs = isset($remoteLab['items']) ? $remoteLab['items'] : [];

            $user['lab'] = $labs;
            $user['group'] = $groups;
            return $user;
        } catch (\Exception $e) {
            return [];
        }
    }

}
<?php

class API_Uno
{

    public function login($accessToken)
    {
        if ($accessToken) {

            try {
                $gateway = Config::get('gapper.gateway');
                $rest = new REST($gateway['get_user_url']);
                $_SESSION['gapper_oauth_token'] = $accessToken;
                $data = $rest->get('owner', ['gapper-oauth-token' => $accessToken]);
                $user_token = $data['id'];

                if ($user_token) {
                    $localUser = O('user', ['token' => $user_token]);
                    if ($localUser->id && !$localUser->gapper_id) {
                        $gateway = Config::get('gapper.gateway');
                        $rest = new REST($gateway['get_user_detail']);
                        $u = $rest->get('default', ['gapper-oauth-token' => $_SESSION['gapper_oauth_token']]);
                        if ($u['id']) {
                            $localUser->gapper_id = $u['id'];
                            $localUser->save();
                        }
                    }elseif(!$localUser->id){
                        //注册用户
                        $remoteUser = Uno_Util::get_remote_user();
                        if (!empty($remoteUser)) {
                            $user = O('user', ['gapper_id' => $remoteUser['gapper_id']]);
                            if (!$user->id) {
                                $user->name = $remoteUser['name'];
                                $user->gapper_id = $remoteUser['gapper_id'];
                                $remoteUser['email'] ? $user->email = $remoteUser['email'] : '';
                                $remoteUser['phone'] ? $user->phone = $remoteUser['phone'] : '';
                                $remoteUser['ref_no'] ? $user->ref_no = $remoteUser['ref_no'] : '';
                                $user->atime = time();
                                $user->token = $remoteUser['token'];
                                $user->gapper_name = $remoteUser['name'];
                                $user->gapper_email = $remoteUser['email'];
                                $user->gapper_phone = $remoteUser['phone'];
                                $user->gapper_ref_no = $remoteUser['ref_no'];
                                $user->gapper_avatar = $remoteUser['avatar'];
                                $user->save();
                                if (!empty($remoteUser['lab'])) {
                                    foreach ($remoteUser['lab'] as $remoteLab) {
                                        $lab = O('lab', ['gapper_id' => $remoteLab['id']]);
                                        if (!$lab->id) {
                                            $lab->name = $remoteLab['name'];
                                            $lab->gapper_id = $remoteLab['id'];
                                            $lab->atime = time();
                                            $lab->description = I18N::T('uno', '自动创建!');
                                            $lab->save();
                                            $user->connect($lab);
                                        }
                                    }

                                } else {
                                    $lab = Lab_Model::default_lab();
                                    $user->connect($lab);
                                }
                            }
                            $localUser = $user;
                            $user_token = $user->token;
                        }
                    }
                    Auth::login($user_token);
                    Cache::L('ME', $localUser);
                    return $user_token;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }
    }
}
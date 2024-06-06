<?php

class Gapper_Login_Controller extends Controller
{
    public function Auth()
    {
        $form = Input::form();
        $source = $form['source'];
        $code = $form['code'];
        $conf = Config::get('gapper.oauth');
        $gateway = Config::get('gapper.gateway');
        if ($code && $source) {
            $client = new OAuth_Client_OAuth2($conf);
            $client->callback = $conf['callback'];
            $client->authorization_grant($form);
            $token = $client->get_token();

            $rest = new REST($gateway['get_user_url']);
            $_SESSION['gapper_oauth_token'] = $token;
            $data = $rest->get('owner', ['gapper-oauth-token' => $token]);
            $user_token = Event::trigger('gapper_login.user_token', $data) ?: $data['id'];

            if ($user_token) {
                $access_token = $client->get_accessToken();
                LoGapper::cacheUserOAuthToken($user_token, $access_token['access_token'], $access_token['expires_in'], $access_token['refresh_token']);
                $_SESSION['#LOGOUT_REFERER'] = Config::get('gapper.logout_url') ?: URI::url('/');

                $localUser = O('user', ['token' => $user_token]);
                if ($localUser->id && !$localUser->gapper_id) {
                    $gateway = Config::get('gapper.gateway');
                    $rest = new REST($gateway['get_user_detail']);
                    $u = $rest->get('default', ['gapper-oauth-token' => $_SESSION['gapper_oauth_token']]);
                    if ($u['id']) {
                        // error_log("login_gapper" . $u['id'] . "====" . $localUser->gapper_id);
                        $localUser->gapper_id = $u['id'];
                        $localUser->save();
                    }
                }

                Auth::login($user_token);
            }
        }
        URI::redirect('/');
    }
}

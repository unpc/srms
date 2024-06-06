<?php

class Index_Controller extends Layout_Controller
{

    function index($tab = "") {
        $entries = Config::get('billing_manage.entries', []);
        $me = L('ME');
        $token = Remote_Billing_Manage::getAuthToken();
        $link = $entries[$tab]['redirect'] . "&accesstoken=$token";
        $this->layout->body = V('billing_manage:view', ["src" => $link]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{

    function index_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;
        $form = Input::form();
        if ($form['user_id']) {
            $component = O('cal_component', $form['component_id']);
            $equipment = O('equipment', $form['equipment_id']);
            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_manage:view/billing_authorized', [
                    'tr_authorized_id' => $form['tr_authorized_id'],
                    'component' => $component,
                    'equipment' => $equipment,
                    'user' => O('user', $form['user_id']),
                    'change' => 1,
                ]),
                'mode' => 'replace',
            ];
        }
    }

    function index_add_eq_sample_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;

        $form = Input::form();

        if ($form['user_id']) {
            $user = O('user', $form['user_id']);

            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_manage:view/eq_sample/add_grant', [
                    'tr_authorized_id' => $form['tr_authorized_id'],
                    'user' => $user,
                ]),
                'mode' => 'replace',
            ];
        }
    }

    function index_edit_eq_sample_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;

        $form = Input::form();

        if ($form['user_id']) {
            $sample = O('eq_sample', $form['sample_id']);
            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_manage:view/eq_sample/edit_grant', [
                    'sample' => $sample,
                    'user' => O('user',$form['user_id']),
                ]),
                'mode' => 'replace',
            ];
        }
    }
}
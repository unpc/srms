<?php

class Index_Controller extends Base_Controller {

    function index() {
        URI::redirect('!exhibition/index/statistics');
    }

	function statistics(){
        $me = L('ME');

        $this->layout->body->primary_tabs->select('statistics');

        if (in_array($me->token, Config::get('lab.admin'))) {
            $form = Form::filter(Input::form());

            $total = Lab::get('exhibition.statistics.total', 0);
            $share = Lab::get('exhibition.statistics.share', 0);
            $use = Lab::get('exhibition.statistics.use', 0);
            $maintain = Lab::get('exhibition.statistics.maintain', 0);

            if (Input::form('submit')) {
                $form
                    ->validate('total', 'number(>=0)', I18N::T('exhibition', '设备总数必须大于等于0'))
                    ->validate('share', 'number(>=0)', I18N::T('exhibition', '共享中必须大于等于0'))
                    ->validate('use', 'number(>=0)', I18N::T('exhibition', '使用中必须大于等于0'))
                    ->validate('maintain', 'number(>=0)', I18N::T('exhibition', '维护中必须大于等于0'));

                if ($form->no_error) {
                    Lab::set('exhibition.statistics.total', $form['total']);
                    Lab::set('exhibition.statistics.share', $form['share']);
                    Lab::set('exhibition.statistics.use', $form['use']);
                    Lab::set('exhibition.statistics.maintain', $form['maintain']);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('exhibition', '保存成功'));
                    URI::redirect();
                }
            }

            $this->layout->body->primary_tabs->content = V('statistics', [
                'form' => $form,
                'total' => $total,
                'share' => $share,
                'use' => $use,
                'maintain' => $maintain,
            ]);
        }
    }

    function similarity(){
        $me = L('ME');

        $this->layout->body->primary_tabs->select('similarity');

        if (in_array($me->token, Config::get('lab.admin'))) {
            $form = Form::filter(Input::form());

            $eqs = json_decode(Lab::get('exhibition.similarity.equipments', ''), true);

            $equipments = [];

            foreach ($eqs as $eq) {
                $equipments[] = O('equipment', $eq);
            }

            if (Input::form('submit')) {
                if ($form->no_error) {
                    $eqs = [];
                    $eqs = json_encode($form['similarity']);
                    Lab::set('exhibition.similarity.equipments', $eqs);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('exhibition', '保存成功'));
                    URI::redirect();
                }
            }

            $this->layout->body->primary_tabs->content = V('similarity', [
                'form' => $form,
                'equipments' => $equipments,
            ]);
        }
    }

    function forecast(){
        $me = L('ME');

        $this->layout->body->primary_tabs->select('forecast');

        if (in_array($me->token, Config::get('lab.admin'))) {
            $form = Form::filter(Input::form());

            $eqs = json_decode(Lab::get('exhibition.forecast.equipments', ''), true);

            $equipments = [];

            foreach ($eqs as $eq) {
                $equipments[] = O('equipment', $eq);
            }

            if (Input::form('submit')) {
                if ($form->no_error) {
                    $eqs = [];
                    $eqs = json_encode($form['forecast']);
                    Lab::set('exhibition.forecast.equipments', $eqs);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('exhibition', '保存成功'));
                    URI::redirect();
                }
            }

            $this->layout->body->primary_tabs->content = V('forecast', [
                'form' => $form,
                'equipments' => $equipments,
            ]);
        }
    }
}
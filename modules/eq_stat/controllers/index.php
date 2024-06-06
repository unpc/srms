<?php

class Index_Controller extends Controller {

    function index() {

        //如果配置了eq_stat的url, 则直接跳转到对应url(独立模块)
        $url = Config::get('eq_stat.url');
        if ($url) URI::redirect($url, ['oauth-sso'=> Config::get('rpc.hostname')]);

        if (L('ME')->access('查看统计图表')) {
            URI::redirect('!eq_stat/chart');
        }
        else {
            URI::redirect('!eq_stat/perfs');
        }
    }
}

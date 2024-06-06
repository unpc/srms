<?php

class Node_Controller extends Base_Controller
{

    public function index($node_id = null, $tab = 'realtime')
    {

        $node = O('env_node', $node_id);
        if (!$node->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('查看', $node)) {
            URI::redirect('error/401');
        }

        $content = V('node/view', ['node' => $node]);

        $this->layout->body->primary_tabs
            ->add_tab('node', [
                'url' => $node->url(),
                'title' => I18N::T('envmon', '%node_name', ['%node_name' => H($node->name)]),
            ])
            ->select('node')
            ->set('content', $content);

        $content->secondary_tabs = Widget::factory('tabs');
        $content->secondary_tabs
            ->add_tab('realtime', [
                'url' => $node->url('realtime'),
                'title' => I18N::T('envmon', '历史曲线'),
                'weight' => 99,
            ]);
        /*
        ->add_tab('history', array(
        'url'=>$node->url('history'),
        'title'=>I18N::T('envmon', '历史记录'),
        'weight'=>100,
        ))*/

        Event::bind('node.view.content', [$this, '_index_realtime_content'], 0, 'realtime');
        /*Event::bind('node.view.content', array($this, '_index_history_content'),0, 'history');*/

        $content->secondary_tabs
            ->set('class', 'secondary_tabs')
            ->set('node', $node)
            ->tab_event('node.view.tab')
            ->content_event('node.view.content')
            ->select($tab);
    }

    public function _index_realtime_content($e, $tabs)
    {
        $node = $tabs->node;
        $me = L('ME');

        if (Browser::name() == 'ie') {
            if (Browser::version() <= 6) {
                $tabs->content = V('node/realtime_not_support', [
                    'node' => $node,
                    'options' => $options,
                ]);
                return;
            }

            if (Browser::version() < 9) {
                $this->add_js('envmon:excanvas');
            }

        }

        $this->add_css('envmon:jqplot envmon:common');
        $this->add_js('envmon:jqplot envmon:jqplot.plugins/logaxisrenderer envmon:jqplot.plugins/textrenderer envmon:jqplot.plugins/highlighter envmon:jqplot.plugins/cursor ');

        $tabs->content = V('node/realtime', [
            'node' => $node,
            'options' => $options,
        ]);

    }

    public function _index_history_content($e, $tabs)
    {
        $form = Lab::form();

        $node = $tabs->node;
        $me = L('ME');
        $now = time();

        $datapoints = Q("{$node}<node env_sensor<sensor env_datapoint[ctime < {$now}]:sort(ctime D)");

        $pagination = Lab::pagination($datapoints, $form['st'], 15);

        $tabs->content = V('node/history', [
            'node' => $node,
            'datapoints' => $datapoints,
            'pagination' => $pagination,
        ]);

    }

}

class Node_AJAX_Controller extends AJAX_Controller
{

    public function index_plot_fetch_data()
    {

        $plot_weight = Config::get('envmon.plot_weight', 10);

        $form = Input::form();
        $db = Database::factory();

        $node = O('env_node', $form['node_id']);
        $sensors = Q("env_sensor[node={$node}]");

        if (!$form['xmin'] || !$form['xmax']) {
            $xmax = time();
            $xmin = $xmax - 2 * 24 * 60 * 60;
        } else {
            $xmax = $form['xmax'];
            $xmin = $form['xmin'];
        }

        $pwidth = $form['width'];

        $spp = (int) max(1, ($xmax - $xmin) * $plot_weight / $pwidth);

        $env_data = [];
        foreach ($sensors as $sensor) {

            $sql = "SELECT ctime, value, ROUND(ctime/%d, 0) atime FROM env_datapoint WHERE sensor_id=%d AND ctime>=%d AND ctime<=%d GROUP BY atime";

            $points = $db->query($sql, $spp, $sensor->id, $xmin, $xmax);

            if (!count($points)) {
                Envmon_sum::get_suv_data($sensor);
                $points = $db->query($sql, $sensor->id, $xmin, $xmax);
            }

            $data = [];

            while ($row = $points->row()) {
                $data[] = [(int) $row->ctime, (float) $row->value];
            }

            $id = (int) $sensor->id;
            $env_data[$sensor->id] = [
                'id' => $sensor->id,
                'name' => $sensor->name,
                'data' => $data,
                'min' => $sensor->vfrom - ($sensor->vto - $sensor->vfrom),
                'max' => $sensor->vto + ($sensor->vto - $sensor->vfrom),
            ];
        }

        Output::$AJAX['curves'] = $env_data;
    }

    public function index_add_node_click()
    {
        JS::dialog(V('envmon:node/add'), ['title' => I18N::T('envmon', '添加监控对象')]);
    }

    public function index_add_node_submit()
    {
        $form = Form::filter(Input::form());

        if ($form['submit']) {

            $form->validate('name', 'not_empty', I18N::T('envmon', '请填写监控对象名称!'));
            $incharges = (array)@json_decode($form['incharge'], TRUE);
            if (count($incharges) == 0) {
                $form->set_error('incharge', I18N::T('envmon', '监控对象负责人不能为空!'));
            }
            Event::trigger('envmon[edit].post_submit_validate', $form);
            if ($form->no_error) {
                $node = O('env_node');
                $node->name = $form['name'];
                $node->location = $form['location'];
                $node->location2 = $form['location2'];

                Event::trigger('envmon[edit].post_submit', $form, $node);

                if ($node->save()) {

                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $node->connect($user, 'incharge');
                    }
                    //log
                    $me = L('ME');
                    Log::add(strtr('[envmon] %user_name[%user_id] 添加了新的监控对象 %node_name[%node_id]', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%node_name' => $node->name,
                        '%node_id' => $node->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象添加成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '监控对象添加失败!'));
                }
                JS::refresh();

            } else {
                JS::dialog(V('envmon:node/add', ['form' => $form]));
            }

        }
    }

    public function index_edit_node_click()
    {
        $me = L('ME');
        $form = Input::form();

        $node = O('env_node', $form['node_id']);
        if (!$node->id) {
            return false;
        }

        JS::dialog(V('envmon:node/edit', ['node' => $node]), ['title' => I18N::T('envmon', '修改监控对象')]);
    }

    public function index_edit_node_submit()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $node = O('env_node', $form['node_id']);
        if (!$node->id) {
            return;
        }

        if (!$me->is_allowed_to('修改', $node)) {
            return false;
        }

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('envmon', '请填写监控对象名称!'));
            $incharges = (array) @json_decode($form['incharge'], true);
            if (count($incharges) == 0) {
                $form->set_error('incharge', I18N::T('envmon', '监控对象负责人不能为空!'));
            }

            Event::trigger('envmon[edit].post_submit_validate', $form);

            if ($form->no_error) {
                $node->name = $form['name'];


                $node->location = $form['location'];
                $node->location2 = $form['location2'];
                // Event::trigger('node.form.submit', $node, $form);
                Event::trigger('envmon[edit].post_submit', $form, $node);
                if ($node->save()) {
                    foreach (Q("$node user.incharge") as $user) {
                        $node->disconnect($user, 'incharge');
                    }

                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $node->connect($user, 'incharge');
                    }

                    //log
                    Log::add(strtr('[envmon] %user_name[%user_id] 修改了监控对象 %node_name[%node_id] 的基本信息', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%node_name' => $node->name,
                        '%node_id' => $node->id
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象修改成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '监控对象修改失败!'));
                }
                JS::refresh();

            } else {
                JS::dialog(V('envmon:node/edit', ['node' => $node, 'form' => $form]));
            }
        }
    }

    public function index_delete_node_click()
    {
        $me = L('ME');
        $form = Input::form();

        $node = O('env_node', $form['node_id']);
        if (!$node->id) {
            return false;
        }

        if (!$me->is_allowed_to('删除', $node)) {
            return false;
        }

        if (JS::confirm(I18N::T('envmon', '你确定要删除吗？删除后不可恢复!'))) {
            $node_name = $node->name;
            $node_id = $node->id;

            if ($node->delete()) {
                //log
                Log::add(strtr('[envmon] %user_name[%user_id] 删除了监控对象 %node_name[%node_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%node_name' => $node_name,
                    '%node_id' => $node_id,
                ]), 'journal');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象删除成功!'));
            } else {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象删除失败!'));
            }
            JS::refresh();
        }

        return false;
    }

    function index_plot_save_options()
    {
        $form = Input::form();

        $options = (array)$_SESSION['biolection.bucket.vis_options'];

        $options = array_intersect_key($form, [
            'legend' => 0,
            'xaxis' => 0, 'yaxis' => 0, 'y2axis' => 0,
            'axes' => 0,
        ]);

        $convert_bool = function (&$opt) {
            if ($opt == 'true') {
                $opt = true;
            } elseif ($opt == 'false') {
                $opt = false;
            } else {
                $opt = $opt ? true : false;
            }

        };

        $convert_bool($options['legend']);
        $convert_bool($options['yaxis']['log']);
        $convert_bool($options['y2axis']['log']);

        $_SESSION['biolection.bucket.vis_options'] = $options;
    }

    public function index_edit_node_icon_click()
    {
        $me = L('ME');
        $form = Input::form();
        $node = O('env_node', $form['node_id']);

        if (!$node->id || !$me->is_allowed_to('修改', $node)) {
            return false;
        }

        JS::dialog(V('envmon:node/edit_icon', ['node' => $node]));
    }

    public function index_edit_node_icon_submit()
    {

        $form = Form::filter(Input::form());
        $node = O('env_node', $form['node_id']);
        if (!$node->id || !L('ME')->is_allowed_to('修改', $node)) {
            return false;
        }

        $file = Input::file('file');
        if ($file['tmp_name']) {
            try {
                $ext = File::extension($file['name']);
                $node->save_icon(Image::load($file['tmp_name'], $ext));

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象图标已更新'));
            } catch (Error_Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '监控对象图标更新失败!'));
            }
            JS::refresh();
        } else {
            $form->set_error('file', I18N::T('envmon', '请选择您要上传的监控对象图标文件!'));
            JS::dialog(V('envmon:node/edit_icon', ['node' => $node, 'form' => $form]));
        }
    }

    public function index_delete_node_icon_click()
    {
        $me = L('ME');
        $form = Input::form();
        $node = O('env_node', $form['node_id']);

        if (!$node->id || !$me->is_allowed_to('修改', $node) || !JS::confirm(I18N::T('envmon', '确定要删除图标吗?'))) {
            return false;
        }

        $node->delete_icon();
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '监控对象图像删除成功!'));
        JS::refresh();
    }
}
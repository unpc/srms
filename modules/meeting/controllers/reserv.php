<?php

class Reserv_Controller extends Base_Controller
{
    //查看所有负责会议室的预约情况
    public function incharge_reserv()
    {
        $me = L('ME');

        $this->layout->body->primary_tabs
            ->add_tab('incharge_reserv', [
                'url'   => URI::url('!meeting/reserv/incharge_reserv'),
                'title' => I18N::HT('meeting', '%user负责的所有会议室的预约情况', ['%user' => $me->name]),
            ])
            ->select('incharge_reserv');

        $calendar = O('calendar', ['parent' => $me, 'type' => 'me_incharge']);
        if (!$calendar->id) {
            $calendar->parent = $me;
            $calendar->type   = 'me_incharge';
            $calendar->name   = I18N::T('meeting', '%name负责会议室的预约', ['%name' => $me->name]);
            $calendar->save();
        }

        $this->layout->body->primary_tabs->content = V('meeting:incharge/calendar', ['calendar' => $calendar]);

    }

    public function index()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('查看所有会议室预约', 'meeting')) {
            URI::redirect('error/401');
        }

        Event::bind('me_reserv.fourth_tabs.content', 'me_reserv::_reserv_tab_content_list', 10, 'list');
        Event::bind('me_reserv.fourth_tabs.content', 'me_reserv::_reserv_tab_content_calendar', 0, 'calendar');
        Event::bind('meeting.all.index.tab.tool_box', 'me_reserv::_reserv_all_calendar_tool', 0, 'list');
        Event::bind('meeting.all.index.tab.tool_box', 'me_reserv::_reserv_all_calendar_tool', 0, 'calendar');

        $primary_tabs = $this->layout->body->primary_tabs
            ->select('reserv');

        $meeting = O('meeting');

        $form = Lab::form();

        $calendar = O('calendar', ['type' => 'all_meetings']);
        if (!$calendar->id) {
            //calendar的parent设置为 parent_id=0 parent_name=calendar 跟其他calendar区分开
            $calendar->parent = $calendar;

            $calendar->type = 'all_meetings';
            $calendar->name = I18N::T('meeting', '所有会议室的预约');
            $calendar->save();
        }

        $params = Config::get('system.controller_params');

        $params = $params[1] ? $params[1] : 'calendar';

        $url = URI::url();
        $url = strstr($url, 'index', true);

        $content = V('meeting:calendar/view');

        $primary_tabs->set('content', $content);

        $content->fourth_tabs = Widget::factory('tabs')
            ->set('class', 'fourth_tabs')
            ->set('form', $form)
            ->add_tab('calendar', [
                'url'    => $url . 'index.0.calendar',
                'title'  => I18N::T('eq_reserv', '日历'),
                'weight' => 20,
            ])
            ->add_tab('list', [
                'url'    => $url . 'index.0.list',
                'title'  => I18N::T('eq_reserv', '列表'),
                'weight' => 30,
            ])
            ->tab_event('calendar.secondary_tabs.tab')
            ->set('calendar', $calendar)
            ->content_event('me_reserv.fourth_tabs.content')
            ->tool_event('meeting.all.index.tab.tool_box')
            ->select($params);
    }

    public function export()
    {
        $form = Input::form();

        if ($form['form_token']) {
            $form_token = $form['form_token'];
            $old_form   = (array) $_SESSION[$form_token];
            $new_form   = (array) $form;
            if (isset($new_form['columns'])) {
                unset($old_form['columns']);
            }
            $form = $_SESSION[$form_token] = $new_form + $old_form;
        }

        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id || !in_array($form['type'], ['print', 'csv'])) {
            URI::redirect('error/401');
        }

        $dtstart = $form['dtstart'];
        $dtend   = $form['dtend'];

        $components = Q("cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]");

        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend);

        if ($new_components) {
            $components = $new_components;
        }

        $valid_columns   = Config::get('calendar.export_columns.meeting');
        $visible_columns = $form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        return call_user_func_array([$this, '_export_' . $form['type']], [$components, $valid_columns, $form]);
    }

    private function _export_print($components, $columns, $form)
    {
        $this->layout = V('meeting:calendar/print', ['columns' => $columns, 'form' => $form, 'components' => $components]);
    }

    private function _export_csv($components, $columns)
    {

        $csv = new CSV('php://output', 'w');

        $csv->write(
            array_map(function ($v) {
                return I18N::T('meeting', $v);
            }, $columns)
        );

        foreach ($components as $c) {
            $data = [];

            foreach ($columns as $column => $title) {
                switch ($column) {
                    case 'name':
                        $data[] = $c->name;
                        break;
                    case 'organizer':
                        $data[] = $c->organizer->name;
                        break;
                    case 'meeting':
                        $data[] = $c->calendar->parent->name;
                        break;
                    case 'time':
                        $data[] = Date::format($c->dtstart, 'Y/m/d H:i:s') . ' - ' . Date::format($c->dtend, 'Y/m/d H:i:s');
                        break;
                    case 'duration':
                        $data[] = I18N::T('meeting', '%duration小时', [
                            '%duration' => round(($c->dtend - $c->dtstart) / 3600, 2),
                        ]);
                        break;
                    case 'description':
                        $data[] = $c->description;
                        break;
                    default:
                        $data[] = Event::trigger('meeting.export_columns.csv', $c, $column);
                        break;
                }
            }

            $csv->write($data);
        }

        $csv->close();
    }
}

class Reserv_AJAX_Controller extends AJAX_Controller
{

    public function index_export_components_click()
    {

        $form     = Input::form();
        $calendar = O('calendar', $form['calendar_id']);

        // 错误传值
        if (!$calendar->id) {
            return false;
        }

        if (!in_array($form['type'], ['print', 'csv'])) {
            return false;
        }

        $_SESSION[$form['form_token']] = $form;

        $view_params = [
            'type'        => $form['type'],
            'dtstart'     => $form['dtstart'],
            'dtend'       => $form['dtend'],
            'calendar_id' => $form['calendar_id'],
            'form_token'  => $form['form_token'],
        ];

        $dialog_params = ['title' => I18N::T('meeting', $form['type'] == 'print' ? '请选择要打印的列' : '请选择要导出的列')];

        JS::dialog((string) V('meeting:calendar/dialog', $view_params), $dialog_params);

    }

    public function index_preview_click()
    {

        $form         = Input::form();
        $component_id = $form['component_id'];
        $component    = O('cal_component', $component_id);
        $calendar     = $component->calendar;
        if ($calendar->id && $calendar->parent_name == 'meeting') {
            if (!L('ME')->is_allowed_to('查看', $component)) {
                return;
            }

            Output::$AJAX['preview'] = (string) V('meeting:calendar/reserv/preview', ['component' => $component]);
        }
    }

}

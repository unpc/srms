<?php

class Technical_Service_Admin
{

    public static function setup()
    {
        if (L('ME')->access('管理所有内容')) {
            Event::bind('admin.index.tab', 'Technical_Service_Admin::_primary_tab');
        }
    }

    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Technical_Service_Admin::_primary_content', 0, 'service');

        $tabs->add_tab('service', [
            'url' => URI::url('admin/service'),
            'title' => I18N::T('technical_service', '服务管理'),
            'weight' => 30,
        ]);

    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        $secondary_tabs = Widget::factory('tabs');

        $tabs->content->secondary_tabs = $secondary_tabs
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.service.tab')
            ->content_event('admin.service.content');

        if (L('ME')->is_Allowed_To('管理服务分类', 'technical_service')) {
            $secondary_tabs->add_tab('type', [
                'url' => URI::url('admin/service.type'),
                'title' => I18N::T('technical_service', '服务分类'),
                'weight' => 120
            ]);

            Event::bind('admin.service.content', 'Technical_Service_Admin::_secondary_service_type_content', 0, 'type');
        }

        if (L('ME')->is_Allowed_To('管理服务项目', 'technical_service')) {
            $secondary_tabs->add_tab('project', [
                'url' => URI::url('admin/service.project'),
                'title' => I18N::T('technical_service', '项目库'),
                'weight' => 120
            ]);

            Event::bind('admin.service.content', 'Technical_Service_Admin::_secondary_service_project_content', 0, 'project');
        }

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs->select($params[1]);

    }

    static function _secondary_service_type_content($e, $tabs)
    {
        $root = Tag_Model::root('service_type');
        $tags = Q("tag_service_type[parent={$root}]:sort(weight A)");
        Controller::$CURRENT->add_js('tag_sortable');

        $uniqid = "tag_" . uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
        $tabs->content = V('application:admin/tags/tag_root', ['tags' => $tags, 'root' => $root, 'uniqid' => $uniqid, 'title' => '服务分类', 'button_title' => '服务分类']);

    }

    static function _secondary_service_project_content($e, $tabs)
    {
        $me = L('ME');

        if ($me->is_Allowed_To('管理服务项目', 'technical_service')) {
            $panel_buttons[] = [
                'text' => I18N::HT('service_project', '添加'),
                'tip' => I18N::HT('service_project', '添加'),
                'extra' => 'class="button button_add"  q-object="add_project" q-event="click" q-src="' . URI::url('!technical_service/project') . '"',
            ];
        }

        $projects = Q("service_project");

        $tabs->content = V('technical_service:admin/project', ['projects' => $projects, 'panel_buttons' => $panel_buttons]);

    }

    public static function import_tab($e, $tabs)
    {

        if (L('ME')->is_Allowed_To('管理服务项目', 'technical_service')) {
            Event::bind('admin.import.content', 'Import::index', 0, 'project');
            $tabs->add_tab('project', [
                'title' => T('导入服务项目'),
                'url' => URI::url('admin/import.project')
            ]);
        }
    }

    public static function import_projects()
    {
        if (Input::form('submit') == "导入") {
            Event::bind('import.add_project', 'Technical_Service_Admin::add_projects', 0, 'import_data');
        }
    }

    public static function add_projects($e, $row, $fields, $user_submit)
    {
        $error = [];
        $warning = [];
        $form = [];
        foreach ($row as $key => $value) {
            if (!in_array($key, $fields)) continue;
            switch ($key) {
                case 'name':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        $error[$key] = T('需填写项目名称');
                        goto output;
                    }
                    $form['name'] = $trim_value;
                    break;
                case 'eq_ref_no':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        $equipment = O('equipment', ['ref_no' => $trim_value]);
                        if (!$equipment->id) {
                            $error[$key] = T("编号{$trim_value}的仪器不存在");
                            goto output;
                        }
                    }
                    $form['eq_ref_no'] = $trim_value;
                    break;
                default:
                    $form[$key] = trim($value) ?: '';
                    break;
            }
        }

        $project = O('service_project', ['name' => $form['name']]);
        if (!$project->id) {
            $project->name = $form['name'];
            $project->save();
        }
        if ($form['eq_ref_no'] && $equipment->id) {
            $project->connect($equipment);
        }

        output:
        $e->return_value = [
            'error' => $error,
            'warning' => $warning
        ];
        return;
    }

    public static function import_tab_params($e, $params)
    {
        if (L('ME')->is_Allowed_To('管理服务项目', 'technical_service')) {
            $params[] = 'project';
        }
    }

}
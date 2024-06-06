<?php

class Departments_Controller extends Base_Controller
{

    public function index()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('列表', 'billing_department')) {
            URI::redirect('error/401');
        }

        $form = Lab::form();

        $group_root = Tag_Model::root('group');

        $selector = "billing_department";

        if ($form['name']) {
            $name     = Q::quote($form['name']);
            $selector = $selector . "[name*=$name]";
        }

        $group         = O('tag_group', $form['group_id']);
        $pre_selectors = [];

        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selectors[] = $group;
        } else {
            $pre_selectors[] = 'tag_group';
            $group = null;
        }

        $new_pre_selectors = Event::trigger('billing.departments.search', $selector, $pre_selectors, $form);
        if ($new_pre_selectors) {
            $pre_selectors = $new_pre_selectors;
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', $pre_selectors) . ') ' . $selector;
        }

        //分页效果
        $start    = (int) $form['st'];
        $per_page = 20;
        $start    = $start - ($start % $per_page);

        if (!$me->access('管理财务中心') && !$me->access('查看财务中心')) {
            $selector = "{$me} {$selector}";
        }

        $departments = Q($selector);

        if ($start > 0) {
            $last = floor($departments->total_count() / $per_page) * $per_page;
            if ($last == $departments->total_count()) {
                $last = max(0, $last - $per_page);
            }

            if ($start > $last) {
                $start = $last;
            }
            $departments = $departments->limit($start, $per_page);
        } else {
            $departments = $departments->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'    => $start,
            'per_page' => $per_page,
            'total'    => $departments->total_count(),
        ]);

        if (L('ME')->is_allowed_to('添加', 'billing_department')) {
            if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage()) {
                $url = "{$master['host']}!billing/department/add?oauth-sso=db_sync." . LAB_ID;
            } else {
                $url = URI::url('!billing/department/add');
            }
            $panel_buttons[] = [
                'url'   => $url,
                'tip'   => I18N::T('billing', '添加财务部门'),
                'extra' => 'class="button button_add "',
                'text' => I18N::T('billing', '添加财务部门'),
            ];
        }

        $columns   = self::get_departments_field($form, $group, $group_root);

        $search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'advanced_search' => false, 'columns' => $columns]);
        $content    = V('departments', [
            'departments'   => $departments,
            'pagination'    => $pagination,
            'group_root'    => $group_root,
            'columns'       => $columns,
            'search_box'    => $search_box,
            'panel_buttons' => $panel_buttons,
            'form'          => $form,
        ]);

        $this->layout->body->primary_tabs
            ->select('department.list')
            ->set('content', $content);

    }
    public static function get_departments_field($form, $group, $group_root)
    {
        $field = [
            'name'        => [
                'title'  => I18N::T('billing', '名称'),
                'filter' => [
                    'form'  => V('billing:departments_table/filters/name', ['name' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
                'nowrap' => true,
            ],
            'group'       => [
                'title'  => I18N::T('billing', '组织机构'),
                'filter' => [
                    'form'  => V('billing:departments_table/filters/group', [
                        'tag'  => $group,
                        'root' => $group_root,
                        'name' => 'group_id',
                    ]),
                    'value' => ($group->id && $group->id != $group_root->id) ? V('application:tag/path', [
                        'tag'          => $group,
                        'tag_root'     => $group_root,
                        'url_template' => URI::url('', 'group_id=%tag_id'),
                    ]) : null,
                    'field' => 'group_id',
                ],
                'nowrap' => true,
            ],
            'users'       => [
                'title'  => I18N::T('billing', '负责人'),
                'nowrap' => true,
            ],
            'description' => [
                'title'  => I18N::T('billing', '备注'),
                'nowrap' => false,
            ],
            'rest'        => [
                'title'  => I18N::T('billing', '操作'),
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];

        return $field;
    }

}

<?php

class Buildings_Controller extends Base_Controller
{

    public function index()
    {
        if (!L('ME')->is_allowed_to('列表', 'gis_building')) {
            URI::redirect('error/401');
        }

        //多栏搜索
        $form = Lab::form();

        //多栏搜索
        $pre_selectors = [];

        //GROUP搜索
        $group      = O('tag_group', $form['group_id']);
        $group_root = Tag_Model::root('group');

        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selectors['group'] = "$group";
        } else {
            $group = null;
        }

        $selector = 'gis_building';
        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $selector .= "[name*=$name|name_abbr*=$name]";
        }

        //排序
        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        if ($sort_by) {
            $selector .= ":sort({$sort_by} {$sort_flag})";
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', $pre_selectors) . ') ' . $selector;
        }

        $selector = Event::trigger('gismon.buildings.extra_selector', $selector, $form) ? : $selector;

        $buildings = Q($selector);

        $start    = (int) $form['st'];
        $per_page = 15;
        $start    = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($buildings->total_count() / $per_page) * $per_page;
            if ($last == $buildings->total_count()) {
                $last = max(0, $last - $per_page);
            }

            if ($start > $last) {
                $start = $last;
            }
            $buildings = $buildings->limit($start, $per_page);
        } else {
            $buildings = $buildings->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'    => $start,
            'per_page' => $per_page,
            'total'    => $buildings->total_count(),
        ]);

        $content = V('gismon:buildings',
            [
                'buildings'  => $buildings,
                'pagination' => $pagination,
                'form'       => $form,
                'group'      => $group,
                'group_root' => $group_root,
                'sort_by'    => $sort_by,
                'sort_asc'   => $sort_asc,
            ]);

        $this->layout->body->primary_tabs
            ->select('buildings')
            ->set('content', $content);

    }
    
    //修改为ajax方式
    public function add()
    {
        if (!L('ME')->is_allowed_to('添加', 'gis_building')) {
            URI::redirect('error/401');
        }

        $this->layout->body->primary_tabs
            ->add_tab('add', [
                'url'   => URI::url('!gismon/buildings/add'),
                'title' => I18N::T('gismon', '添加楼宇'),
            ]);

        $building = O('gis_building');
        $group_root = Tag_Model::root('group');

        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('gismon', '请填写楼宇名称!'));

            Event::trigger('gismon_building[edit].post_submit_validate', $form);
            if ($form->no_error) {

                $building->name      = $form['name'];
                $building->longitude = $form['longitude'];
                $building->latitude  = $form['latitude'];
                /*
                NO.BUG#299(guoping.zhangg@2010.12.26
                添加楼宇中填充组织机构信息
                 */
                $group = O('tag', $form['group_id']);

                if ($group->root->id == $group_root->id) {
                    $building->group = $group;
                }

                /*
                新增楼宇时设置默认长宽
                以防BUG #865::GIS地理监控，给楼宇中添加仪器时，不能移动仪器位置
                (xiaopei.li@2011.07.18)
                 */
                $building->width  = 161.8;
                $building->height = 100;

                $building->save();

                if ($building->id) {

                    /*添加记录*/
                    Log::add(strtr('[gismon] %user_name[%user_id] 添加了楼宇 %building_name[%building_id] ', [
                        '%user_name'     => L('ME')->name,
                        '%user_id'       => L('ME')->id,
                        '%building_name' => $building->name,
                        '%building_id'   => $building->id,
                    ]), 'journal');

                    if ($group->root->id == $group_root->id) {
                        $group_root->disconnect($building);
                        $group->connect($building);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '楼宇信息添加成功!'));
                    URI::redirect($building->url(null, null, null, 'edit'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '楼宇信息添加失败! 请与系统管理员联系。'));
                }
            }
        }

        $this->layout->form = $form;
        $this->layout->body->primary_tabs
            ->select('add')
            ->set('content', V(
                'building/add', ['building' => $building, 'form' => $form, 'group_root' => $group_root, 'group' => $group]
            )
            );

    }

    
}

class Buildings_AJAX_Controller extends AJAX_Controller
{
    

    public function index_add_build_click()
    {
        if (!L('ME')->is_allowed_to('添加', 'gis_building')) {
            URI::redirect('error/401');
        }
        $building = O('gis_building');
        $group_root = Tag_Model::root('group');
        $view = V('building/add', [
            'building'   => $building,
            'group_root' => $group_root,
        ]);
        JS::dialog($view,['title' => I18N::T('lab', '添加楼宇')]);
    }
    public function index_add_build_submit()
    {
        if (!L('ME')->is_allowed_to('添加', 'gis_building')) {
            URI::redirect('error/401');
        }
        $building = O('gis_building');

        $group_root = Tag_Model::root('group');

        $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('gismon', '请填写楼宇名称!'));

        if ($form->no_error) {
            $building->name      = $form['name'];
            $building->longitude = $form['longitude'];
            $building->latitude  = $form['latitude'];
            /*
            NO.BUG#299(guoping.zhangg@2010.12.26
            添加楼宇中填充组织机构信息
             */
            $group = O('tag', $form['group_id']);

            if ($group->root->id == $group_root->id) {
                $building->group = $group;
            }

            /*
            新增楼宇时设置默认长宽
            以防BUG #865::GIS地理监控，给楼宇中添加仪器时，不能移动仪器位置
            (xiaopei.li@2011.07.18)
             */
            $building->width  = 161.8;
            $building->height = 100;

            $building->save();

            if ($building->id) {
                /*添加记录*/
                Log::add(strtr('[gismon] %user_name[%user_id] 添加了楼宇 %building_name[%building_id] ', [
                        '%user_name'     => L('ME')->name,
                        '%user_id'       => L('ME')->id,
                        '%building_name' => $building->name,
                        '%building_id'   => $building->id,
                    ]), 'journal');

                if ($group->root->id == $group_root->id) {
                    $group_root->disconnect($building);
                    $group->connect($building);
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '楼宇信息添加成功!'));
                JS::refresh();
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '楼宇信息添加失败! 请与系统管理员联系。'));
            }
        }
        $view = V('building/add', [
                'building'   => $building,
                'group_root' => $group_root,
                'form'=>$form
            ]);
        JS::dialog($view, ['title' => I18N::T('lab', '添加楼宇')]);
    }
    public function index_building_click()
    {
        $building = O('gis_building', Input::form('id'));
        $title    = $building->id ? '修改楼宇' : '添加楼宇';
 

        JS::dialog($view, [
            'title' => I18N::T('lab', $title),
        ]);
    }
    public function index_building_submit()
    {
        $form     = Input::form();
        $building = O('gis_building', $form['id']);

        $group_root = Tag_Model::root('group');
        if ($form['submit'] == 'delete') {
            $name = $building->name;
            try {
                foreach (Q("equipment[location=$name]") as $equipment) {
                    $equipment->location = '';
                    $equipment->save();
                }
                $building->delete();
                Log::add(strtr('[gismon] %user_name[%user_id] 删除了楼宇 %building_name[%building_id] ', [
                    '%user_name'     => L('ME')->name,
                    '%user_id'       => L('ME')->id,
                    '%building_name' => $building->name,
                    '%building_id'   => $building->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '删除成功!'));
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '删除失败!'));
            }
            JS::refresh();
        } else if ($form['submit']) {
            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('gismon', '请填写楼宇名称!'));

            $other = O('gis_building', ['name' => $form['name']]);
            if ($form['id'] && $other->id && $other->id != $form['id']) {
                $form->set_error('name', I18N::T('gismon', '该楼宇已经存在!'));
            }

            if (!$form['id'] && $other->id) {
                $form->set_error('name', I18N::T('gismon', '该楼宇已经存在!'));
            }

            if ($form->no_error) {
                $name                = $building->name;
                $building->name      = $form['name'];
                $building->longitude = $form['longitude'];
                $building->latitude  = $form['latitude'];

                if ($form['id'] && $building->id) {
                    $group_root->disconnect($building);
                    $building->group = null;
                }

                $group = O('tag_group', $form['group_id']);

                if ($group->root->id == $group_root->id) {
                    $building->group = $group;
                }

                $building->width  = 161.8;
                $building->height = 100;

                if ($building->save() && $form['id']) {
                    foreach (Q("equipment[location=$name]") as $equipment) {
                        $equipment->location = $form['name'];
                        $equipment->save();
                    }
                }

                if ($building->id) {
                    Log::add(strtr('[gismon] %user_name[%user_id] 编辑了楼宇 %building_name[%building_id] ', [
                        '%user_name'     => L('ME')->name,
                        '%user_id'       => L('ME')->id,
                        '%building_name' => $building->name,
                        '%building_id'   => $building->id,
                    ]), 'journal');

                    if ($group->root->id == $group_root->id) {
                        $group_root->disconnect($building);
                        $group->connect($building);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('gismon', '保存成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('gismon', '保存失败!'));
                }
                JS::refresh();
            } else {
                $view = V('build/add', [
                    'building'   => $building,
                    'form'       => $form,
                    'group_root' => $group_root,
                    'group'      => $group,
                ]);

                $title = $building->id ? '修改楼宇' : '添加楼宇';

                JS::dialog($view, [
                    'title' => I18N::T('lab', $title),
                ]);
            }
        }
    }

}

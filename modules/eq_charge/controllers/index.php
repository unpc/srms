<?php

class Index_Controller extends Layout_Controller {

    public function entries($tab = '') {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }
        $this->layout->body = V('body');
        $this->layout->title = '收费记录';
        $tabs = Widget::factory("tabs");
        $allow_tabs = [];

        if ($me->is_allowed_to('查看收费情况', Q("$me lab")->current())) {
            $tabs
                ->add_tab('lab', [
                    'url'   => URI::url('!eq_charge/index/entries.lab'),
                    'title' => I18N::T('eq_charge', '组内收费记录'),
                ]);
            !$tab && $tab = 'lab';
            $allow_tabs[] = 'lab';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Charge::lab_view_content', 0, 'lab');
            Event::bind('equipment.samples.entries.view.tool_box', 'EQ_Charge::lab_view_tool', 0, 'lab');
        }
        if (Q("$me<incharge equipment")->total_count() > 0) {
            $tabs
                ->add_tab('incharge', [
                    'url'=>URI::url('!eq_charge/index/entries.incharge'),
                    'title'=>I18N::T('eq_charge', '您负责的所有仪器的收费记录'),
                ]);
            !$tab && $tab = 'incharge';
            $allow_tabs[] = 'incharge';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Charge::charge_primary_tab_content', 100, 'incharge');
        }
        if ($me->access('管理所有内容') || $me->access('查看下属机构仪器的使用收费情况')) {
            $tabs
                ->add_tab('group', [
                    'url'=>URI::url('!eq_charge/index/entries.group'),
                    'title'=>I18N::T('eq_charge', '下属机构所有仪器的收费记录'),
                ]);
            !$tab && $tab = 'group';
            $allow_tabs[] = 'group';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Charge::charges_tab_content', 100, 'group');
        }

        if ($me->access('管理所有内容') || $me->access('查看所有仪器的使用收费情况')) {
            $tabs
                ->add_tab('all', [
                    'url'=>URI::url('!eq_charge/index/entries.all'),
                    'title'=>I18N::T('eq_charge', '所有仪器的收费记录'),
                ]);
            !$tab && $tab = 'all';
            $allow_tabs[] = 'all';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Charge::charges_tab_content', 100, 'all');
        }

        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

        switch ($tab) {
            case "lab":
                $tabs->lab = Q("$me lab")->current();
                break;
            case "incharge":;break;
            case "group":        
                $tabs->group = $me->group;
                break;
            case "all":;break;
        }

        $tabs
            ->tab_event('equipment.samples.entries.view.tab')
            ->content_event('equipment.samples.entries.view.content')
            ->tool_event('equipment.samples.entries.view.tool_box')
            ->select($tab);

        $this->layout->body->primary_tabs = $tabs;

    }

	public function me()
    {
        $me        = L('ME');
        if(!$me->id){
            URI::redirect('error/401');
        }
        $user =  O("user",$me->id);
    
        $this->layout->body = V('body');
		$this->layout->body->primary_tabs= Widget::factory('tabs');

        EQ_Charge::charge_view_content($this->layout->body->primary_tabs, $user);
        EQ_Charge::_tool_box_charge(null, $this->layout->body->primary_tabs);
    }
}


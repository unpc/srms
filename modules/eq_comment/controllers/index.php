<?php

class Index_Controller extends Layout_Controller {

    public function entries($tab) {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }
        $this->layout->body = V('body');
        $this->layout->title = '使用评价';
        $tabs = Widget::factory("tabs");
        $allow_tabs = [];

        if ($me->is_allowed_to('列表全部仪器使用评价', 'equipment')) {
            $tabs
                ->add_tab('all', [
                    'url'=>URI::url('!eq_comment/index/entries.all'),
                    'title'=>I18N::T('eq_comment', '所有仪器的评价记录'),
                ]);
            !$tab && $tab = 'all';
            $allow_tabs[] = 'all';
            Event::bind('equipment.comments.entries.view.content', "EQ_Comment_User::extra_all_primary_tab_content", 0, 'all');
        }

        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

        switch ($tab) {
            case "all":
                $tabs->base_url = URI::url('!eq_comment/index/entries.all');
                break;
        }

        $tabs
            ->tab_event('equipment.comments.entries.view.tab')
            ->content_event('equipment.comments.entries.view.content')
            ->tool_event('equipment.comments.entries.view.tool_box')
            ->select($tab);

        $this->layout->body->primary_tabs = $tabs;

    }
}


<?php
class Index_Controller extends Base_Controller {
    
    function index () {
        $this->pf();
    }

    function pf ($tab = 'pf') {
        $me = L('ME');
        $form = Input::form();
        if (!$me->is_allowed_to('管理', 'servant')) URI::redirect('error/401');

        $pre = [];
        $selector = 'platform';

        switch ($tab) {
            case 'my':
                $pre['owner'] = "{$me}<owner";
            break;
        }

        if ($form['name']) {
            $selector .= "[name*={$form['name']}]";
        }
        if ($form['code']) {
            $selector .= "[code*={$form['code']}]";
        }
        if ($form['address']) {
            $selector .= "[address*={$form['address']}]";
        }
        if ($form['owner']) {
            $pre['owner'] = "user{[name*={$form['owner']}]}<owner";
        }
        if ($form['creator']) {
            $pre['creator'] = "user{[name*={$form['creator']}]}<creator";
        }

        count($pre) && $selector = '(' . implode(',', $pre) . ') ' . $selector;
        $pfs = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($pfs, $start, $per_page);

        $buttons = new ArrayIterator;
        if ($me->is_allowed_to('添加', 'platform')) {
            $buttons[] = [
                // 'url' => URI::url('!servant/platform/add'),
                'url' => "#",
                'text' => I18N::T('servant', '添加下属机构'),
                'extra' => 'class="button button_add" q-object="pf_create" q-event="click" q-src="' . URI::url('!servant/platform') .'"'
            ];
        }

        $tab = $this->layout->body->primary_tabs->select($tab);
        $tab->content = V('pf', [
            'pfs' => $pfs,
            'form' => $form,
            'buttons' => $buttons,
            'pagination' => $pagination,
        ]);
    }

}

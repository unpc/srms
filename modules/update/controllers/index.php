<?php

class Index_Controller extends Base_Controller
{

    public function index($tabs = 'all')
    {

        $me      = L('ME');
        $form    = Lab::form();
        $start   = $form['start'] ? $form['start'] : 0;
        $updates = Update::fetch($start, 10, $next_start, $tabs);

        $this->layout->body->primary_tabs
            ->select($tabs)
            ->content = V('update:desktop/update_desktop', [
                'next_start' => $next_start,
                'updates'    => $updates,
                'model_name' => $tabs,
            ]);
    }

}

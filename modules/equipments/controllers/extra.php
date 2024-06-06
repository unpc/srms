<?php

class Extra_Controller extends Base_Controller
{

    public function index($tab = null)
    {
        Event::trigger('trigger_extra_primary_tabs', $this->layout->body->primary_tabs,  'equipment', $tab);
        $this->layout->body->primary_tabs->select($tab);
    }

}

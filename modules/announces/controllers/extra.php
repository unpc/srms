<?php

class Extra_Controller extends Base_Controller
{

    public function index($tab = null)
    {
        $this->layout->body->primary_tabs->select($tab);
    }

}

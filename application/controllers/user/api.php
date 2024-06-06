<?php
Core::load(CONTROLLER_BASE, 'api/v1');
class User_API_Controller extends _API_V1_Controller
{
    public function index()
    {
        $args = func_get_args();
        $this->dispatch($args);
    }
}

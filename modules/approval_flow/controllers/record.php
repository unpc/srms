<?php

class Record_Controller extends Layout_Controller {
	public function me($type = 'reserv_approval')
    {
        $me        = L('ME');
        if(!$me->id){
            URI::redirect('error/401');
        }
        $this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->body->primary_tabs->user = O("user",$me->id);
        $this->layout->body->primary_tabs->type = $type ;
        Approval_Flow_Mine::my_approval_content(null,$this->layout->body->primary_tabs,$type);
    }
}


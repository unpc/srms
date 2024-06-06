<?php 
class Item_Widget extends Widget
{
    public $title;
    public $content=[];
    public $image;
    public $menu=[];
	function __construct($vars){
        parent::__construct('item', $vars);
	}
    public function set_content($content)
    {
        $this->content=$content;
    }
    public function set_title($title)
    {
        $this->title=$title;
    }
    public function set_image($image)
    {
        $this->image=$image;
    }
    public function add_menu_item($menu_item)
    {
        $this->menu[]=$menu_item;
    }
   

}
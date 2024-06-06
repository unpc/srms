<?php

class Index_Controller extends Base_Controller {

	function index(){
		
		$form = Lab::form();
		
		$selector = "note";
		
		if ($form['title']) {
			$title = Q::quote($form['title']);
			$selector = $selector."[title*=$title]";
		}
		if ($form['content']) {
			$content = Q::quote($form['content']);
			$selector = $selector."[content*=$content]";
		}
		
		$notes = Q($selector);
		//分页效果
		$pagination = Lab::pagination($notes, (int)$form['st'], 15);
		
		$content = V('note/note_list',[
							'notes'=>$notes,
							'pagination'=>$pagination,
							'form'=>$form,
					]);
		
		$this->layout->body->primary_tabs
			->select('my_note')
			->set('content', $content);
		
	}

}

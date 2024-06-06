<?php

class Newsletter {

	static function get_category_view($content, $category) {
		$view_name = "application:newsletter/".$category;
		$categories = Config::get('newsletter.categories');
		$view = (string)V($view_name, [
			'content'=> $content,
			'title'=>$categories[$category]['title'],
			]);
		return $view;
	}

	static function get_note_view($categories) {
		$view = (string)V("application:newsletter/note", [
				'categories' => $categories,
			]);
		return $view;
	}

	static function get_html($user, $body) {
		$view = (string)V("application:newsletter/view", [
				'body'=>$body,
				'user'=>$user
			]);
		return $view;
	}
}
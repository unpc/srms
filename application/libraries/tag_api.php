<?php

class Tag_API {
	
	static function is_has_children($t_id, $r_id) {
		$tag = O('tag', $t_id);
		$root = O('tag', $r_id);
		$root = $root->id ? $root : O('tag', $tag->root->id);
		
		$real_root = $root->root->id ? $root->root : $root;
		$children = Q("tag[root=$real_root][parent=$tag]");
		
		return count($children);
	}
	
	static function show_next_view($t_id, $r_id, $uniqid) {
		$tag = O('tag', $t_id);
		$root = O('tag', $r_id);
		$root = $root->id ? $root : O('tag', $tag->root->id);
		
		$real_root = $root->root->id ? $root->root : $root;
		$children = Q("tag[root=$real_root][parent=$tag]");
		
		return (string)V('widgets/tag_selector/show_tag', [
							'root'=>$root,
							'tags'=>$children,
							'uniqid'=>$uniqid,
						]);
	}
	
	static function change_tag_selector($t_id, $r_id, $root_name, $name, $uniqid) {
		$tag = O('tag', $t_id);
		$root = O('tag', $r_id);
		
		return (string) V('widgets/tag_selector/container',[
										'tag' => $tag->id ? $tag : $root,
										'root' => $root,
										'root_name' => $root_name,
										'name' => $name,
										'uniqid' => $uniqid,
									]);
	}
	
	static function get_root_id($name) {
		return Tag_Model::root($name)->id;
	}
}

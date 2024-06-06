<?php

class API_Tag extends API_Common {

	function is_has_children($t_id, $r_id) {
		$tag = O('tag', $t_id);
		$root = O('tag', $r_id);
		$root = $root->id ? $root : O('tag', $tag->root->id);
		
		$real_root = $root->root->id ? $root->root : $root;
		$children = Q("tag[root=$real_root][parent=$tag]");
		
		return count($children);
	}
	
	function show_next_view($t_id, $r_id, $uniqid) {
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
	
	function change_tag_selector($t_id, $r_id, $root_name, $name, $uniqid) {
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
	
	function get_root_id($name) {
		return Tag_Model::root($name)->id;
	}

    function get_tag_id_by_root_id($tag_name, $root_id) {
        $root = O('tag', $root_id);
        return O('tag', ['name'=>$tag_name, 'root'=>$root])->id;
	}
	
	function get_tags($type='group') {
		$this->_ready();

		$root = Tag_Model::root($type);
		$tags = Q("{$root->name()}[root=$root]");
		$group = [];
		$group[] = [
			'source' => LAB_ID,
			'id' => $root->id,
			'name' => $root->name,
			'parent_id' => $root->parent_id,
			'root' => $root->root_id,
			'weight' => $root->weight,
			'ctime' => $root->ctime,
			'path' => $root->path,
            'icon16_url' => $root->icon_url(16),
            'icon32_url' => $root->icon_url(32),
            'icon48_url' => $root->icon_url(48),
            'icon64_url' => $root->icon_url(64),
            'icon128_url' => $root->icon_url(128),
            'uuid' => $root->uuid ? $root->uuid : '',
		];
		foreach ($tags as $tag) {
			$group[] = [
				'source' => LAB_ID,
				'id' => $tag->id,
				'name' => $tag->name,
				'parent_id' => $tag->parent_id,
				'root_id' => $tag->root_id,
				'weight' => $tag->weight,
                'ctime' => $tag->ctime,
				'path' => $tag->path,
                'icon16_url' => $tag->icon_url(16),
                'icon32_url' => $tag->icon_url(32),
                'icon48_url' => $tag->icon_url(48),
                'icon64_url' => $tag->icon_url(64),
                'icon128_url' => $tag->icon_url(128),
                'uuid' => $tag->uuid ? $tag->uuid : '',
			];
		}
		return $group;
	}
}

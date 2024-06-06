<?php
class Wordpress {

	private $client, $blogid, $username, $password;

	static function base64_encode($url) {

		$fp = fopen($url, 'rb') or error_log("you can't open the file!");
		$content = new IXR_Base64(stream_get_contents($fp));
		fclose($fp);

		return $content;
	}

	function __construct($blogid, $username, $password) {
		Core::load(THIRD_BASE, 'ixr', 'wordpress');

		$wp_base = Config::get('wordpress.wp_base');
		$server_url = $wp_base . '/xmlrpc.php';

		$this->client = new IXR_Client($server_url);

		$this->blogid = $blogid;
		$this->username = $username;
		$this->password = $password;
	}

	function last_error() {
		return 'An error occurred - '.$this->last_error_code.":".$this->last_error_message;
	}

	function sync_post($content, $publish = TRUE) {
		if (!$this->client->query('lims2.sync_post',
								  $this->blogid,
								  $this->username,
								  $this->password,
								  $content,
								  $publish)) {

			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;

		}

		return $this->client->getResponse();
	}

	// 上传 presentable object 的 icon(xiaopei.li@2012-02-21)
	function upload_icon($object) {
		$image = $object->icon_url('128');
		$bits = Wordpress::base64_encode($image);
		$data = [];
		$data['name'] = $object->name() . '-' . $object->id . '.jpg'; // e.g. user-2.jpg
		$data['type'] = 'image/jpeg';
		$data['bits'] = $bits;
		$data['overwrite'] = TRUE;

		$image_info = $this->upload_file($data);
		// id, file, url, type

		return (object) $image_info;
	}

	// 上传 object 的附件(xiaopei.li@2012-02-25)
    function upload_attachments($object, $attach_to) {
        $attachments_path = NFS::get_path($object, NULL, 'attachments', TRUE);
        if (File::exists($attachments_path)) {
            foreach (scandir($attachments_path) as $file) {
                $file_path = $attachments_path . $file;
                if (is_file($file_path)) {
                    // set parent_post and upload

                    $bits = Wordpress::base64_encode($file_path);
                    $data = [];
                    $data['name'] = $file;
                    $data['type'] = mime_content_type($file_path); // wp only acept some types of file (xiaopei.li@2012-02-22)
                    $data['bits'] = $bits;
                    $data['overwrite'] = TRUE;

                    $this->upload_file($data, $attach_to);
                }
            }
        }
    }

	function insert_or_update_user($user) {
		// render post content

		$member_title = I18N::T('people', User_Model::get_member_label($user->member_type)); // 可读的用户类型

		if ($user->member_type == 10) {
			$member_type = '10 - pi'; // PI, 不需考虑 PI 的 dto
		}
		elseif (!$user->dto || $user->dto > time()) {
			$member_type = '20 - cm'; // current member， 目前成员
		}
		else {
			$member_type = '30 - fm'; // formal member， 过期成员
		}

		$content = [];

		// $content['sub_post_type'] = 'people';
		// 随 wp plugin 的 post type 修改为单数形式(xiaopei.li@2012-02-25)
		$content['sub_post_type'] = 'person';

		$content['title'] = $user->name;

		$content['wp_slug'] = $user->name; // TODO 重名怎么办?

		// 上传头像, 头像地址用来在页面内容中添加 (xiaopei.li@2012-02-21)
		$icon = $this->upload_icon($user);

		$content['thumbnail_id'] = $icon->id;

		$content['description'] = (string)V('wordpress:page/person', ['user' => $user, 'icon' => $icon]);

		$content['wp_page_order'] = $user->extra['order'];

		if($user->extra['title']) {
			$member_title = $user->extar['title'];
		}
		$user_custom_fields = [
			['key' => '@object_id',
				  'value' => $user->id],
			['key' => 'name',
				  'value' => $user->name],
			['key' => 'email',
				  'value' => $user->email],
			['key' => 'phone',
				  'value' => $user->phone],
			['key' => 'address',
				  'value' => $user->address],
			['key' => 'member_title',
				  'value' => $member_title],
			['key' => 'dfrom',
				  'value' => $user->dfrom],
			['key' => 'dto',
				  'value' => $user->dto],
			['key' => 'member_type',
				  'value' => $member_type],
			// extra (xiaopei.li@2012-03-03)
//			array('key' => 'member_title',
//				  'value' => $user->extra['title']),
			['key' => 'suffix',
				  'value' => $user->extra['degree']],
			['key' => 'fax',
				  'value' => $user->extra['fax']],
			['key' => 'introduction',
				  'value' => $user->extra['note']],
			];

		// this user's publications
		$publications = Q("ac_author[user={$user}]<achievement publication")->to_assoc('id', 'id');
		if ($publications) {
			$user_custom_fields[] = ['key' => 'publications',
										  'value' => json_encode($publications)];
		}

		$content['custom_fields'] = $user_custom_fields;

		$ret = $this->sync_post($content);

		return $ret;
	}

	function insert_or_update_equipment($equipment) {

		// 关键:
		// 组织机构
		// 仪器类型
		// 联系人
		// 联系电话
		// 地点
		// 主要规格及技术指标
		// 主要功能及特色
		// 主要附件及配置
		// (以上三项需要格式, 至少要有下级标题/列表等, + markdown?)

		// render post content
		$content = [];

		$content['sub_post_type'] = 'equipment';

		$content['title'] = $equipment->name;

		$content['wp_slug'] = $equipment->id;

		$icon = $this->upload_icon($equipment);

		$content['thumbnail_id'] = $icon->id;

		// $content['description'] = (string)V('wordpress:page/equipment', array('equipment' => $equipment, 'icon' => $icon));
		$content['description'] = I18N::T('wordpress', '请使用自定义栏目修改');

		$contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');

		// custom_fields aka 自定义栏目
		$equipment_custom_fields = [
			['key' => '@object_id',
				  'value' => $equipment->id],
			['key' => 'name',
				  'value' => $equipment->name],
			['key' => 'location',
				  'value' => $equipment->location],
			['key' => 'phone',
				  'value' => $equipment->phone],
			['key' => 'email',
				  'value' => $equipment->email],
			['key' => 'contact',
				  'value' => join(', ', $contacts)],

			['key' => 'manufacturer', // 生产厂家
				  'value' => $equipment->manufacturer],
			['key' => 'model_no', // 型号
				  'value' => $equipment->model_no],
			['key' => 'specification', // 规格
				  'value' => $equipment->specification],
			['key' => 'tech_specs', // 主要规格及技术指标
				  'value' => $equipment->tech_specs],
			['key' => 'features', // 主要功能及特色
				  'value' => $equipment->features],
			['key' => 'configs', // 主要附件及配置
				  'value' => $equipment->configs],

			['key' => '@accept_reserv',
				  'value' => $equipment->accept_reserv],
			['key' => '@reserv_url',
				  'value' => $equipment->url('reserv')],
			['key' => '@accept_sample',
				  'value' => $equipment->accept_sample],
			['key' => '@sample_url',
				  'value' => $equipment->url('sample')],
			];

		$term_slugs = [];
		$group_tag_root = Tag_Model::root('group');
		$term_slugs[] = 'group_' . $group_tag_root->id;
		$group_tag_ids = Q("$equipment tag[root=$group_tag_root]")->to_assoc('id', 'id');
		foreach ($group_tag_ids as $group_tag_id) {
			$term_slugs[] = 'group_' . $group_tag_id;
		}
		$equipment_tag_root = Tag_Model::root('equipment');
		$term_slugs[] = 'eq_tag_' . $equipment_tag_root->id;
		$equipment_tag_ids = Q("$equipment tag[root=$equipment_tag_root]")->to_assoc('id', 'id');
		foreach ($equipment_tag_ids as $equipment_tag_id) {
			$term_slugs[] = 'eq_tag_' . $equipment_tag_id;
		}
		// error_log('@lims term_slugs: ' . json_encode($term_slugs));

		$content['term_slugs'] = $term_slugs;

		$content['custom_fields'] = $equipment_custom_fields;

		$ret = $this->sync_post($content);

		return $ret;
	}

	function sync_groups() {
		$root = Tag_Model::root('group');
		$groups = Q("tag[root=$root]:sort(parent_id):sort(id)");

		$terms = [];
		$terms[] = [
			'name' => $root->name,
			'slug' => 'group_' . $root->id,
			];

		foreach ($groups as $group) {
			$terms[] = [
				'name' => $group->name,
				'slug' => 'group_' . $group->id,
				'parent_slug' => 'group_' . $group->parent_id,
			];
		}

		if ( !$this->client->query('lims2.sync_equipment_terms',
								   $this->blogid,
								   $this->username,
								   $this->password,
								   $terms) ) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return $this->last_error_code . ': ' . $this->last_error_message;

		}

		return $this->client->getResponse();

	}

	function sync_eq_tags() {
		$root = Tag_Model::root('equipment');
		$tags = Q("tag[root=$root]:sort(parent_id):sort(id)");

		$terms = [];
		$terms[] = [
			'name' => $root->name,
			'slug' => 'eq_tag_' . $root->id,
			];

		foreach ($tags as $tag) {
			$terms[] = [
				'name' => $tag->name,
				'slug' => 'eq_tag_' . $tag->id,
				'parent_slug' => 'eq_tag_' . $tag->parent_id,
			];
		}

		if ( !$this->client->query('lims2.sync_equipment_terms',
								   $this->blogid,
								   $this->username,
								   $this->password,
								   $terms) ) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return $this->last_error_code . ': ' . $this->last_error_message;

		}

		return $this->client->getResponse();

	}


	function insert_or_update_publication($publication) {

		// render post content
		$content = [];

		// $content['sub_post_type'] = 'publications';
		// 随 wp plugin 的 post type 修改为单数形式(xiaopei.li@2012-02-25)
		$content['sub_post_type'] = 'publication';

		$content['title'] = $publication->title;

		$content['wp_slug'] = $publication->title; // TODO 重名怎么办?

		$content['description'] = (string)V('wordpress:page/publication', ['publication' => $publication]);

		$ac_authors = Q("ac_author[achievement={$publication}]:sort(position A)");

		$a_ac_authors = [];

		foreach ($ac_authors as $ac_author) {
			$a_ac_authors[] = [
				'achievement_name' => $ac_author->achievement_name,
				'achievement_id' => $ac_author->achievement_id,
				'user_id' => $ac_author->user_id,
				'position' => $ac_author->position,
				'name' => $ac_author->name,
				];
		}

		/*
		$authors = array();
		foreach ($ac_authors as $ac_author) {
			$authors[] = H($ac_author->name);
		}
		$s_authors = empty($authors) ? '' : implode(', ',$authors);
		*/

		$publication_custom_fields = [
			['key' => '@object_id',
				  'value' => $publication->id],
			['key' => 'title',
				  'value' => $publication->title],
			['key' => 'journal',
				  'value' => $publication->journal],
			['key' => 'date',
				  'value' => $publication->date],
			['key' => 'volume',
				  'value' => $publication->volume],
			['key' => 'issue',
				  'value' => $publication->issue],
			['key' => 'page',
				  'value' => $publication->page],
			['key' => 'authors',
				  'value' => json_encode($a_ac_authors)],
			/*
			array('key' => 'authors',
				  'value' => $s_authors),
			*/
			];

		// TODO 现在普通 lims2 系统中未记录 pmid, 但自 lims1 升级来的
		// 系统会继承 lims1 中的 pubmed_id 存在 pubmed 扩展属性中, 应
		// 确认系统中 pubmed id 到底用哪个属性保存
		// (xiaopei.li@2012-02-29)

		if ($publication->pubmed) {
			$pubmed_id = $publication->pubmed;
		}
		elseif ($publication->pmid) {
			$pubmed_id = $publication->pmid;
		}

		if ($pubmed_id) {
			$publication_custom_fields[] = [
				'key' => 'pubmed_id',
				'value'=> $pubmed_id];
		}

		$content['custom_fields'] = $publication_custom_fields;

		$post_id = $this->sync_post($content);

		if ($post_id) {
			$this->upload_attachments($publication, $post_id);
		}

		return $post_id;
	}

	function upload_file($data, $post_id = NULL) {

		// TODO 文件不应重复上传
		if (!$this->client->query('lims2.upload_file',
								  $this->blogid,
								  $this->username,
								  $this->password,
								  $data,
								  $post_id)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function get_page_list() {

		if (!$this->client->query('wp.getPageList',
								  $this->blogid,
								  $this->username,
								  $this->password
				)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function get_page($page_id) {
		if (!$this->client->query('wp.getPage',
								  $this->blogid,
								  $page_id,
								  $this->username,
								  $this->password
				)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function get_users_blogs() {
		if (!$this->client->query('wp.getUsersBlogs',
								  $this->username,
								  $this->password
				)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function get_categories() {
		if (!$this->client->query('wp.getCategories',
								  $this->blogid,
								  $this->username,
								  $this->password
				)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function get_media_library($content) {
		if (!$this->client->query('wp.getMediaLibrary',
								  $this->blogid,
								  $this->username,
								  $this->password,
								  $content
				)) {
			$this->last_error_code = $this->client->getErrorCode();
			$this->last_error_message = $this->client->getErrorMessage();
			return FALSE;
		}

		return $this->client->getResponse();
	}

	function hello()
	{
		$this->client->query('lims2.say_hello');
		var_dump($this->client->getResponse());
	}

}

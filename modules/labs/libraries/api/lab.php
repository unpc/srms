<?php

class API_Lab {

    //验证失败返回错误信息
    const AUTH_FAILED = 0;
    
    private function _checkAuth()
    {
        $labs = Config::get('rpc.servers')['labs'];
        if ((!isset($_SESSION['labs.client_id']) || 
            $labs['client_id'] != $_SESSION['labs.client_id'])
			&& !Scope_Api::is_authenticated('lab')) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    function authorize($clientId, $clientSecret)
    {
        $labs = Config::get('rpc.servers')['labs'];
        if ($labs['client_id'] == $clientId && 
            $labs['client_secret'] == $clientSecret) {
            $_SESSION['labs.client_id'] = $clientId;
            return session_id();
        }
        return false;
    }

    function get_lab($user, $keys=null){
		$this->_checkAuth();

        $info = [];

        if($user){
            $u = O('user', ['token'=>$user]);
            $lab = O('lab', ['owner'=>$u]);
        }

        if(!$lab->id) return FALSE;

        $tag = $lab->group;
        $group = $tag->id ? [$tag->id => $tag->name] : null ;
        while ($tag->parent->id && $tag->parent->root->id) {
			$group[$tag->parent->id] = $tag->parent->name;
            $tag = $tag->parent;
        }
		
        $projects_array = [];
        $status = Lab_Project_Model::STATUS_ACTIVED;
        $projects = Q("lab_project[status={$status}][lab=$lab]");
        foreach ($projects as $project) {
            $type = $project->type;
            switch($type){
                case Lab_Project_Model::TYPE_EDUCATION:
                    $projects_array[$type][] = [
                        'name' => $project->name,
                        'book_type' => $project->book_type,
                        'textbook' => $project->textbook,
                        'student_count' => $project->student_count,
                        'dtstart' => $project->dtstart,
                        'dtend' => $project->dtend,
                        'type' => $type,
                    ];
                    break;
				default:
                    $projects_array[$type][] = [
                        'name' => $project->name,
                        'description' => $project->description,
                        'dtstart' => $project->dtstart,
                        'dtend' => $project->dtend,
                        'type' => $type,
                    ];
                    break;
            }

        }

		$info = [
			'name' => $lab->name,
			'description' => $lab->description,
			'ctime' => $lab->ctime,
			'contact' => $lab->contact,
			'group' => $group,
			'owner' => $lab->owner->token,
			'projects' => $projects_array,
        ];

		if (!$keys) return $info;
		
        if (is_array($keys)) {
            $data = [];
            foreach ($keys as $key) {
                if(array_key_exists($key, $info)){
                    $data[$key] = $info[$key];
                }
            }
            return $data;
        }
        elseif (array_key_exists($keys, $info)) {
            return $info[$keys];
        }
	}

	function searchLabs($opts) {
		$this->_checkAuth();

		$selector = "lab";

		if ($opts['name']) {
			$name = Q::quote($opts['name']);
			$selector .= "[name*={$name}]";
		}

		if ($opts['atime']) {
			$selector .= "[atime>0]";
		}

		$token = md5('Lab'.time().uniqid());
		$_SESSION[$token] = $selector;

		$total = Q($selector)->total_count();

		return ['token' => $token, 'total' => $total];

	}

	function getLabs($token=null, $start=0, $num=5) {
		$this->_checkAuth();

		if (!$token) {
			throw new API_Exception(T('Token未定义!'), self::AUTH_FAILED);
		}

		$selector = $_SESSION[$token];

		$labs = Q($selector)->limit($start, $num);

		$glabs = [];

		foreach ($labs as $lab) {
			$tag = $lab->group;
			$group = $tag->id ? [$tag->name] : null ;
			while($tag->parent->id && $tag->parent->root->id){
				array_unshift($group, $tag->parent->name);
				$tag = $tag->parent;
			}

			$glabs[$lab->id] = [
				'name' => $lab->name,
				'description' => $lab->description,
				'ctime' => $lab->ctime,
				'contact' => $lab->contact,
				'group' => $group,
				'owner'=> $lab->owner->token
			];

		}

		return $glabs;
	}

	function getLab($params) {
		$this->_checkAuth();

		$lab = O('lab', $params);
		if (!$lab->id) $lab = O('lab', ['name' => $params['name']]);
		if (!$lab->id) {
			$u = O('user', ['token' => $params['username']]);
			$lab = O('lab', ['owner' => $u]);
		}
		
		if (!$lab->id) return false;

		$tag = $lab->group;
		$group = $tag->id ? [$tag->id => $tag->name] : null ;
		while($tag->parent->id && $tag->parent->root->id){
			$group[$tag->parent->id] = $tag->parent->name;
			$tag = $tag->parent;
		}


		$projects_array = [];
		$status = Lab_Project_Model::STATUS_ACTIVED;
		$projects = Q("lab_project[status={$status}][lab=$lab]");
		foreach ($projects as $project) {
			$type = $project->type;
			switch($type){
				case Lab_Project_Model::TYPE_EDUCATION:
					$projects_array[$type][] = [
						'name' => $project->name,
						'book_type' => $project->book_type,
						'textbook' => $project->textbook,
						'student_count' => $project->student_count,
						'dtstart' => $project->dtstart,
						'dtend' => $project->dtend,
						'type' => $type,
					];
					break;
				default:
					$projects_array[$type][] = [
						'name' => $project->name,
						'description' => $project->description,
						'dtstart' => $project->dtstart,
						'dtend' => $project->dtend,
						'type' => $type,
					];
					break;
			}
		}

		return [
			'id' => $lab->id,
			'name' => $lab->name,
			'description' => $lab->description,
			'ctime' => $lab->ctime,
			'contact' => $lab->contact,
			'group' => $group,
			'owner'=> $lab->owner->token,
			'projects' => $projects_array,
			'source' => $lab->source_name ? : LAB_ID, 
		];
	}

	// 对外接口
    public function labStatus ($dtstart = 0, $dtend = 0 , $criteria = []) {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
		$dimension = $criteria['dimension'];
		$pre = $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user";
		
        if (!$dtstart) $dtstart = Date::get_year_start();
        if (!$dtend) $dtend = Date::get_year_end();
		$data = [];
		
		$lab_reserv = Module::is_installed('eq_reserv')
		? Q("{$pre} eq_reserv[project][dtend={$dtstart}~{$dtend}]")->to_assoc('id', 'project_id') : [];
		$lab_record = Module::is_installed('equipments') 
		? Q("{$pre} eq_record[project][dtend={$dtstart}~{$dtend}]")->to_assoc('id', 'project_id') : [];
		$lab_sample = Module::is_installed('eq_sample') 
		? Q("{$pre} eq_sample[project][dtend={$dtstart}~{$dtend}]")->to_assoc('id', 'project_id') : [];
		$lab_projects = count(array_merge($lab_reserv, $lab_record, $lab_sample));

		$test_reserv = Module::is_installed('eq_reserv') 
		? Q("{$pre} eq_reserv[project][dtend={$dtstart}~{$dtend}]")->total_count() : [];
		$test_record = Module::is_installed('equipments') 
		? Q("{$pre} eq_record[project][dtend={$dtstart}~{$dtend}]")->total_count() : [];
		$test_sample = Module::is_installed('eq_sample') 
		? Q("{$pre} eq_sample[project][dtend={$dtstart}~{$dtend}]")->total_count() : [];
		$test_projects = count(array_merge($test_reserv, $test_record, $test_sample));

		$data['project'] = $tag->id 
		? Q("{$tag} lab lab_project")->total_count() 
		: Q("lab lab_project")->total_count();
        $data['lab'] = $lab_projects;
        $data['test'] = $test_projects;
        
        return $data;
    }
}

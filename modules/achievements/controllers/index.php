<?php

class Index_Controller extends Base_Controller{

	function index(){
		URI::redirect('!achievements/publications/index');
	}

    function download($template = NULL)
    {
        if (in_array($template, ['publications', 'awards', 'patents'])) {
            $full_path = MODULE_PATH . 'achievements/' . PRIVATE_BASE . $template . '.xls';
            Downloader::download($full_path);
            exit();
        }
        URI::redirect('error/401');
    }

    function import($template = '')
    {
        $me = L('ME');
        if (!$me->is_allowed_to('添加成果', 'lab')) {
            URI::redirect('error/401');
        }

        $file = Input::file('file');
        if (in_array($template, ['publications', 'awards', 'patents']) && !$file['error']) {
            try{
                $ext = File::extension($file['name']);
                if ($ext !== 'xls' && $ext !== 'xlsx') {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '文件类型错误!'));
                    goto output;
                }

                //$autoload = ROOT_PATH.'vendor/autoload.php';
                //if(file_exists($autoload)) require_once($autoload);

                $PHPReader = new \PHPExcel_Reader_Excel2007;

                if(!$PHPReader->canRead($file['tmp_name'])){
                    $PHPReader = new \PHPExcel_Reader_Excel5;
                    if(!$PHPReader->canRead($file['tmp_name'])){
                        echo "file error\n";
                        return;
                    }
                }

                $PHPExcel = $PHPReader->load($file['tmp_name']);
                $currentSheet = $PHPExcel->getSheet(0);

                $import_func = '_import_' . $template;
                $this->$import_func($currentSheet);

                Log::add(strtr('[achievements] %user_name[%user_id]导入成果', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');
            }
            catch(Error_Exception $e){
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '导入失败!'));
            }
        }else{
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '请选择您要上传的文件!'));
        }

        output:
        URI::redirect("!achievements/{$template}/index");
    }

    private function _import_publications($sheet)
    {
        $allRow = $sheet->getHighestRow();
        $ColumnToKey = [
            0 => 'title',
            1 => 'authors',
            2 => 'journal',
            3 => 'content',
            4 => 'date',
            5 => 'volume',
            6 => 'issue',
            7 => 'page',
            8 => 'lab_ref_no',
            9 => 'lab',
            10 => 'tags',
            11 => 'impact',
            12 => 'notes',
            13 => 'lab_project',
            14 => 'equipments_ref_no',
            15 => 'equipments',
        ];

        //必填列
        $require_columns = [
            0 => '标题',
            1 => '作者',
            2 => '期刊',
            4 => '日期',
            8 => '课题组编号',
            9 => '课题组',
            14 => '关联仪器编号',
        ];

        $no_error = true;
        $all_data = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $data = [];
            foreach ($ColumnToKey as $k => $key) {
                $value = H(trim($sheet->getCellByColumnAndRow($k, $currentRow)->getValue()));
                if ($require_columns[$k] && !$value) {
                    Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行数据 必填项({$require_columns[$k]})未填写");
                    $no_error = false;
                    break 2;
                }
                if ($key == 'lab_ref_no') {
                    $lab = O('lab', ['ref_no' => $value]);
                    if (!$lab->id) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行课题组编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                if ($key == 'equipments_ref_no') {
                    $equipments = Q("equipment[ref_no={$value}]");
                    if ($equipments->total_count() != count(explode(',', $value))) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行有关联仪器编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                if ($key == 'impact') {
                    if (strlen($value) > 0 && ($value == '0' || ( !is_numeric($value) || (double)$value < 0 ))) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行影响因子请填写大于0的数值");
                        $no_error = false;
                        break 2;
                    }
                }
                $data[$key] = $value;
            }
            $all_data[] = $data;
        }

        if ($no_error) {
            foreach ($all_data as $item) {
                $publication = O('publication');
                $publication->owner = L('ME');
                $publication->title = $item['title'];
                $publication->journal = $item['journal'];
                $publication->date = strtotime($item['date']);
                $publication->volume = $item['volume'] ?: 0;
                $publication->issue = $item['issue'] ?: 0;
                $publication->content = $item['content'] ?: '';
                $publication->page = $item['page'] ?: 0;
                $publication->notes = $item['notes'] ?: '';
                $publication->impact = $item['impact'] ?: '';
                if ($publication->save()) {
                    foreach (Q("equipment[ref_no={$item['equipments_ref_no']}]") as $equipment) {
                        $publication->connect($equipment);
                    }

                    $lab = O('lab', ['ref_no' => $item['lab_ref_no']]);
                    $publication->connect($lab);

                    $tag_root = Tag_Model::root('achievements_publication');
                    $tags = Q("tag_achievements_publication[root={$tag_root}][name={$item['tags']}]")->to_assoc('id', 'name');
                    Tag_Model::replace_tags($publication, $tags, 'achievements_publication');
                    if (!count($tags)) {
                        $publication->connect($tag_root);
                    }

                    $authors = explode(',', $item['authors']);
                    foreach ($authors as $author) {
                        $ac_author = O("ac_author");
                        $ac_author->name = $author;
                        $ac_author->user = NULL;
                        $ac_author->achievement = $publication;
                        $ac_author->position = 1;
                        $ac_author->save();
                    }

                    if ($item['lab_project']) {
                        $project = O('lab_project', ['name' => $item['lab_project'], 'lab' => $lab]);
                        if ($project->id) {
                            $publication->connect($project);
                        }
                    }
                }
            }
            Lab::message(Lab::MESSAGE_NORMAL, "导入成功");
        }
    }

    private function _import_awards($sheet)
    {
        $allRow = $sheet->getHighestRow();
        $ColumnToKey = [
            0 => 'lab_ref_no',
            1 => 'lab',
            2 => 'name',
            3 => 'tags',
            4 => 'date',
            5 => 'people',
            6 => 'description',
            7 => 'lab_project',
            8 => 'equipments_ref_no',
            9 => 'equipments',
        ];

        //必填列
        $require_columns = [
            0 => '课题组编号',
            1 => '课题组',
            2 => '获奖名称',
            4 => '获奖日期',
            8 => '关联仪器编号',
            9 => '关联仪器',
        ];

        $no_error = true;
        $all_data = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $data = [];
            foreach ($ColumnToKey as $k => $key) {
                $value = H(trim($sheet->getCellByColumnAndRow($k, $currentRow)->getValue()));
                if ($require_columns[$k] && !$value) {
                    Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行数据 必填项({$require_columns[$k]})未填写");
                    $no_error = false;
                    break 2;
                }
                if ($key == 'lab_ref_no') {
                    $lab = O('lab', ['ref_no' => $value]);
                    if (!$lab->id) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行课题组编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                if ($key == 'equipments_ref_no') {
                    $equipments = Q("equipment[ref_no={$value}]");
                    if ($equipments->total_count() != count(explode(',', $value))) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行有关联仪器编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                $data[$key] = $value;
            }
            $all_data[] = $data;
        }

        if ($no_error) {
            foreach ($all_data as $item) {
                $award = O('award');
                $award->owner = L('ME');
                $award->name = $item['name'];
                $award->date = strtotime($item['date']);
                $award->description = $item['description'] ?: '';

                if ($award->save()) {
                    foreach (Q("equipment[ref_no={$item['equipments_ref_no']}]") as $equipment) {
                        $award->connect($equipment);
                    }

                    $lab = O('lab', ['ref_no' => $item['lab_ref_no']]);
                    $award->connect($lab);

                    $tag_root = Tag_Model::root('achievements_award');
                    $tags = Q("tag_achievements_award[root={$tag_root}][name={$item['tags']}]")->to_assoc('id', 'name');
                    Tag_Model::replace_tags($award, $tags, 'achievements_award');
                    if (!count($tags)) {
                        $award->connect($tag_root);
                    }

                    if ($item['people']) {
                        $authors = explode(',', $item['people']);
                        foreach ($authors as $author) {
                            $ac_author = O("ac_author");
                            $ac_author->name = $author;
                            $ac_author->user = NULL;
                            $ac_author->achievement = $award;
                            $ac_author->position = 1;
                            $ac_author->save();
                        }
                    }

                    if ($item['lab_project']) {
                        $project = O('lab_project', ['name' => $item['lab_project'], 'lab' => $lab]);
                        if ($project->id) {
                            $award->connect($project);
                        }
                    }
                }
            }
            Lab::message(Lab::MESSAGE_NORMAL, "导入成功");
        }
    }

    private function _import_patents($sheet)
    {
        $allRow = $sheet->getHighestRow();
        $ColumnToKey = [
            0 => 'lab_ref_no',
            1 => 'lab',
            2 => 'name',
            3 => 'ref_no',
            4 => 'date',
            5 => 'tags',
            6 => 'people',
            7 => 'lab_project',
            8 => 'equipments_ref_no',
            9 => 'equipments',
        ];

        //必填列
        $require_columns = [
            0 => '课题组编号',
            1 => '课题组',
            2 => '专利名称',
            3 => '专利号',
            4 => '日期',
            8 => '关联仪器编号',
            9 => '关联仪器'
        ];

        $no_error = true;
        $all_data = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $data = [];
            foreach ($ColumnToKey as $k => $key) {
                $value = H(trim($sheet->getCellByColumnAndRow($k, $currentRow)->getValue()));
                if ($require_columns[$k] && !$value) {
                    Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行数据 必填项({$require_columns[$k]})未填写");
                    $no_error = false;
                    break 2;
                }
                if ($key == 'lab_ref_no') {
                    $lab = O('lab', ['ref_no' => $value]);
                    if (!$lab->id) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行课题组编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                if ($key == 'equipments_ref_no') {
                    $equipments = Q("equipment[ref_no={$value}]");
                    if ($equipments->total_count() != count(explode(',', $value))) {
                        Lab::message(Lab::MESSAGE_ERROR, "第{$currentRow}行有关联仪器编号不存在");
                        $no_error = false;
                        break 2;
                    }
                }
                $data[$key] = $value;
            }
            $all_data[] = $data;
        }

        if ($no_error) {
            foreach ($all_data as $item) {
                $patent = O('patent');
                $patent->lab = $lab;
                $patent->owner = L('ME');
                $patent->name = $item['name'];
                $patent->date = strtotime($item['date']);
                $patent->ref_no = trim($item['ref_no']);

                if ($patent->save()) {
                    foreach (Q("equipment[ref_no={$item['equipments_ref_no']}]") as $equipment) {
                        $patent->connect($equipment);
                    }

                    $lab = O('lab', ['ref_no' => $item['lab_ref_no']]);
                    $patent->connect($lab);

                    if ($item['tags']) {
                        $tag_root = Tag_Model::root('achievements_patent');
                        $tags = Q("tag_achievements_patent[root={$tag_root}][name={$item['tags']}]")->to_assoc('id', 'name');
                        Tag_Model::replace_tags($patent, $tags, 'achievements_patent');
                        if (!count($tags)) {
                            $patent->connect($tag_root);
                        }
                    }

                    if ($item['people']) {
                        $authors = explode(',', $item['people']);
                        foreach ($authors as $author) {
                            $ac_author = O("ac_author");
                            $ac_author->name = $author;
                            $ac_author->user = NULL;
                            $ac_author->achievement = $patent;
                            $ac_author->position = 1;
                            $ac_author->save();
                        }
                    }

                    if ($item['lab_project']) {
                        $project = O('lab_project', ['name' => $item['lab_project'], 'lab' => $lab]);
                        if ($project->id) {
                            $patent->connect($project);
                        }
                    }
                }
            }
            Lab::message(Lab::MESSAGE_NORMAL, "导入成功");
        }
    }
}


class Index_AJAX_Controller extends AJAX_Controller {

	function index_select_author_click () {
		$form = Input::form();
		$type = $form['type'];
		switch ($type) {
			case 'publication':
				$object = O('publication',$form['object']);
				break;
			case 'award':
				$object = O('award',$form['object']);
				break;
			case 'patent':
				$object = O('patent',$form['object']);
				break;
			default :
				$object = null;
		
		}
		
		$me = L('ME');
		$labs = $me->access('添加/修改所有实验室成果') ? Q("$me lab") : Q("$me lab<pi");
		$ac_authors = Q("ac_author[achievement={$object}]");
		$authors = [];
		foreach ($ac_authors as $ac_author) {
			$authors[$ac_author->name] = $ac_author->name;
		}
		
        $author_names = join(',', $authors);

        $users = Q("$labs user[name*=$author_names]:limit(10)");
        if (!$users->total_count()) $users = Q("$labs user:limit(10)");
		
		Session::set_url_specific('user_ids', $users->to_assoc('id', 'id'));
		
		JS::dialog(V('achievements:authors',[
            'object'=>$object,
            'ac_authors'=>$ac_authors,
            'users'=>$users,
            'type'=>$type
        ]));
		
	}

	function index_add_author_click () {
		$form = Form::filter(Input::form());
		$uniqid = $form['uniqid'];
		
		try {

			$user = O('user', $form['user_id']);
			
			if (!$user->id) {
				JS::alert(I18N::T('achievements','请输入正确的姓名!'));
				throw new Error_Exception;
			}	
			
			$user_ids = Session::get_url_specific('user_ids', []);
			if (isset($user_ids[$user->id])) {
				JS::alert(I18N::T('achievements','您输入的姓名已存在!'));
				throw new Error_Exception;
			}
			
			$users = Q('user:empty');
			foreach ($user_ids as $uid) {
				$u = O('user', $uid);
				if ($u->id) {
					$users->append($u); //数据对象添加用 append
				}
			}
			
			$users->append($user);
			Session::set_url_specific('user_ids', $users->to_assoc('id', 'id'));
			Output::$AJAX['#'.$uniqid] = [
						'data'=>(string)V("achievements:users",[
											'users' => $users,
											'uniqid' => $uniqid,
										]),
						'mode'=>'replace'
				];
			
		}
		catch (Error_Exception $e) {
		}
				
	}
	
	function index_delete_lab_user_click() {
			$form = Form::filter(Input::form());
			$user_id = $form['user_id'];
			$user_ids = Session::get_url_specific('user_ids', []);
			$uniqid = $form['uniqid'];
			
			if (isset($user_ids[$user_id])) {
				$user_ids[$user_id] = '';
				
			}
			
			$users = Q('user:empty');
			foreach ($user_ids as $uid) {
				$u = O('user', $uid);
				if ($u->id) {
					$users->append($u); //数据对象添加用 append
				}
			}
			
			
			Session::set_url_specific('user_ids', $users->to_assoc('id', 'id'));
			Output::$AJAX['#'.$uniqid] = [
						'data'=>(string)V("achievements:users",[
											'users' => $users,
											'uniqid' => $uniqid,
										]),
						'mode'=>'replace'
				];
			
		
	}
	
	function index_author_form_submit()	{
		$form = Form::filter(Input::form());
		$authors = $form['authors'];
		if (is_array($authors)) foreach ($authors as $id=>$author) {
			$ac_author = O('ac_author',$id);
			
			$user = O('user',$author);
			$ac_author->user = $user;
			$ac_author->save();
			
		
		}
		JS::refresh();
		
	}
}

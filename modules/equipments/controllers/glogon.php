<?php

class Glogon_Controller extends Layout_Controller {

    //登录 view 页面
    public function login() {

        $this->css = [];
        $this->head_js = [];
        $this->body_js = [];
        $this->head_js[] = ['file' => "equipments:glogon/jquery  json livequery form", "mode" => ""];
        $this->head_js[] = ['file' => "q/core q/loader q/ajax q/browser", "mode" => ""];
        $this->head_js[] = ['file' => "lims", "mode" => ""];
        $id = Input::form('id');
        $equipment = O('equipment', $id);
        Config::set('system.locale', $equipment->device['lang']);
		I18N::shutdown();
        I18N::setup();
        $this->add_css('equipments:glogon');
        $this->layout = V('equipments:glogon/login', ['equipment' => $equipment]);

    }

    //登出 view 页面
    public function logout() {

        $form = Input::form();
        $user = O('user', ['token'=> $form['token']]);
        $project_id = 0;
        $lab_id = 0;

        $dtstart = Q("eq_record[user=$user][dtend=0]")->current()->dtstart;

        $projects = [];
        $labs = Q("$user lab");
        if ($dtstart) {
            $project = Q("eq_reserv[dtstart<$dtstart][dtend>$dtstart][user=$user]")->current();
            $project_id = $project->project_id;
            $lab_id = $project->lab->id;
        }

        if ($labs->total_count() > 0) {
            $status = Lab_Project_Model::STATUS_ACTIVED;
            foreach ($labs as $lab) {
                $lab_projects = Q("lab_project[lab={$lab}][status={$status}]")->to_assoc('id', 'name');
                $projects[] = [
                    'lab_id' => $lab->id . '_lab_id',
                    'lab_name' => $lab->name,
                    'lab_projects' => $lab_projects
                ];
            }
        }
        //获取默认仪器使用 lab
        else {
            $lab = Equipments::default_lab();
            $lab_id = $lab->id;
            $lab_projects = Q("lab_project[lab={$lab}][status={$status}]")->to_assoc('id', 'name');
            $projects[] = [
                 'lab_id' => $lab->id . '_lab_id',
                'lab_name' => $lab->name,
                'lab_projects' => $lab_projects
            ];
        }

        $equipment = O('equipment', $form['id']);

        $check_project = class_exists('Lab_Project_Model') && Config::get('eq_record.must_connect_lab_project');

        Config::set('system.locale', $equipment->device['lang']);
		I18N::shutdown();
        I18N::setup();
        $this->css = [];
        $this->head_js = [];
        $this->body_js = [];
        $this->head_js[] = ['file' => "equipments:glogon/jquery  json livequery form", "mode" => ""];
        $this->head_js[] = ['file' => "q/core q/loader q/ajax q/browser", "mode" => ""];
        $this->head_js[] = ['file' => "lims", "mode" => ""];
        $this->add_css('equipments:glogon');

        $view = Module::is_installed('eq_evaluate') ? 'eq_evaluate:glogon/logout' : 'equipments:glogon/logout';

        $this->layout = V($view, [
            'user' => $user,
            'lab_id' => $lab_id,
            'check_project' => $check_project,
            'project_id' => $project_id,
            'projects' => $projects,
            'equipment' => $equipment,
            'reserv_id' => $project->id
        ]);

    }

    public function prompt() {
        $form = Input::form();
        $id = $form['id'];
        $equipment = O('equipment', $id);
        $this->css = [];
        $this->head_js = [];
        $this->body_js = [];
        $this->head_js[] = ['file' => "equipments:glogon/jquery  json livequery form", "mode" => ""];
        $this->head_js[] = ['file' => "q/core q/loader q/ajax q/browser", "mode" => ""];
        $this->head_js[] = ['file' => "lims", "mode" => ""];
        $this->add_css('equipments:glogon');
        $this->layout = V('equipments:glogon/prompt', ['equipment' => $equipment]);

    }

    public function offline_login() {

        $locale = Input::get('locale');
        $path = Module::is_installed('eq_evaluate')
            ? 'modules/eq_evaluate/views/glogon/package/'. $locale. '/login.zip'
            : 'modules/equipments/views/glogon/package/'. $locale. '/login.zip';
        $file = file_exists(LAB_PATH.$path) ? LAB_PATH.$path : (file_exists(ROOT_PATH.$path) ? ROOT_PATH.$path : FALSE);

        if ($file) {
            Downloader::download($file, TRUE);
        }
    }

    public function offline_logout() {

        $locale = Input::get('locale');
        $path = Module::is_installed('eq_evaluate')
            ? 'modules/eq_evaluate/views/glogon/package/'. $locale. '/logout.zip'
            : 'modules/equipments/views/glogon/package/'. $locale. '/logout.zip';
        $file = file_exists(LAB_PATH.$path) ? LAB_PATH.$path : (file_exists(ROOT_PATH.$path) ? ROOT_PATH.$path : FALSE);

        if ($file) {
            Downloader::download($file, TRUE);
        }
    }

    public function offline_prompt() {

        $locale = Input::get('locale');
        $path = Module::is_installed('eq_evaluate')
            ? 'modules/eq_evaluate/views/glogon/package/'. $locale. '/prompt.zip'
            : 'modules/equipments/views/glogon/package/'. $locale. '/prompt.zip';
        $file = file_exists(LAB_PATH.$path) ? LAB_PATH.$path : (file_exists(ROOT_PATH.$path) ? ROOT_PATH.$path : FALSE);

        if ($file) {
            Downloader::download($file, TRUE);
        }
    }
    
    public function using () {
        $user_id = Input::form('user');
        $equipment_id = Input::form('equipment');
        $user = O('user', $user_id);
        $equipment = O('equipment', $equipment_id);
		$equipments = Q("equipment[user_using={$user}]")->to_assoc('id', 'name');
        
        Config::set('system.locale', $equipment->device['lang']);

		I18N::shutdown();
        I18N::setup();
        
        $this->layout = V('equipments:glogon/using', [
            'equipments' => $equipments,
        ]);
    }

    public function overtime () {
        $user_id = Input::form('user');
        $equipment_id = Input::form('equipment');
        $user = O('user', $user_id);
        $equipment = O('equipment', $equipment_id);
		$limit = Input::form('limit');
        
        Config::set('system.locale', $equipment->device['lang']);

		I18N::shutdown();
        I18N::setup();
        
        $this->layout = V('equipments:glogon/overtime', [
            'username' => $user->name,
            'equipment_name' => $equipment->name,
            'limit' => $limit
        ]);
    }
}

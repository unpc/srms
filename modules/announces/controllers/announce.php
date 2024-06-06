<?php 
class Announce_Controller extends Base_Controller{
	function index($id=0){
		$me = L('ME');
		$announce = O('announce', $id);
		if (!$announce->id) {
			URI::redirect('error/404');
		}
		
		$user_announce = O('user_announce', ['announce'=>$announce,'receiver'=>$me]);
		if ($user_announce->id && !$user_announce->is_read) {
			$user_announce->is_read = 1;
			$user_announce->save();
		}
		
		$content = V('announce')->set('announce', $announce);
		$content->set('receivers',json_decode($announce->receiver,true));
		

		$this->layout->body->primary_tabs
			->add_tab('view',[
				'url' => URI::url('!announces/announce/index.'.$announce->id),
				'title' => I18N::T('announces','查看公告'),
                'weight' => 20
			])
			->select('view')
			->set('content', $content);
	}
}
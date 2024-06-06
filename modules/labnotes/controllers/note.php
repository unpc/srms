<?php

class Note_Controller extends Base_Controller {

	function index($id=0) {
		$note = O('note', $id);

		if (!$note->id) URI::redirect('error/404');
		
		$this->layout->body->primary_tabs
			->add_tab('view', [
					'url'=>$note->url(NULL,NULL,NULL,'view'),
					'title'=>$note->title,
				])
			->select('view');
			
		$content = V('note/view');
		$content->note = $note;
		
		$this->layout->body->primary_tabs->content = $content;
	
	}

	function edit($id=0) {
	
		$note = O('note',$id);
		$me = L("ME");
		
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
			$form->validate('title', 'not_empty', I18N::T('labnotes', '实验记录标题不能为空！'));
			if ($form->no_error) {
				$note->title = $form['title'];
				$note->content = $form['content'];
				$note->ctime = $note->ctime ?: (int)time() ; 
				$note->mtime = (int)time();
				$note->lock = $note->lock ?: 0;
				$note->owner = $me;
				
				if ($note->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labnotes', '更新成功!'));
				}else{
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('labnotes', '更新失败!'));
				}
			}
			
		}
			
		$stock_view = Event::trigger('labnotes.stock.edit', $note);
		$nfs_view = Event::trigger('labnotes.nfs.edit', $note);
		$task_view = Event::trigger('labnotes.task.edit', $note);
		
		$content = V('note/edit',[
									'note'=>$note,
									'form'=>$form,
									'stock_view'=>$stock_view,
									'task_view'=>$task_view,
									'nfs_view'=>$nfs_view, 
					]);
		$this->layout->body->primary_tabs 
					->add_tab('edit',[
							'url'=>URI::url('!labnotes/note/edit.').$note->id,	
							'title'=> $note->id ? I18N::T('labnotes', '编辑实验记录') : I18N::T('labnotes', '添加实验记录'),
						])
					->select('edit')
					->set('content', $content);
		
	}
	
	function delete($id=0) {
		$note = O('note', $id);
		
		if ($note->id>0) {
			if ($note->delete()) {
				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('labnotes','记录删除成功!'));
			}
			else {
				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('labnotes','记录删除失败!'));
			}							
		}
		URI::redirect(URI::url('!labnotes'));
	}
	
	
	function add() {
		$this->edit(0);
	}
}

class Note_AJAX_Controller extends AJAX_Controller {
	function index_lock_note_click() {
		$id = Input::form('id');
		$note = O('note', $id);
		if (!$note->id) return;
		
		
		if (!$note->lock) {
			$note->lock = 1;
			$note->save();
		}	
		else {
			$note->lock = 0;
			$note->save();
		}					
		
		JS::refresh();
	}
}


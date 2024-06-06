<?php

class Update_Model extends Presentable_Model {
	
	function merge($object) {
		if ($object->name() != 'update')
			return;
		$new_data = json_decode($this->new_data, TRUE) ? json_decode($this->new_data, TRUE) : [];
		$new_data = array_merge((array)@json_decode($object->new_data, TRUE), (array)$new_data);
		$old_data = json_decode($this->old_data, TRUE) ? json_decode($this->old_data, TRUE) : [];
		$old_data = array_merge((array)@json_decode($object->old_data, TRUE), (array)$old_data);
		$reference = $this->reference ? $this->reference : [];
		$reference[] = $object->id;
		$this->new_data = json_encode($new_data, TRUE);
		$this->old_data = json_encode($old_data, TRUE);

		$this->reference = $reference;
		$this->object = $this->object->id ? $this->object : $object->object;
		$this->subject = $this->subject->id ? $this->subject : $object->subject;
		$this->ctime = $this->ctime ?: $object->ctime;
		$this->action = $this->action ?: $object->action;
	}


	private $_message = NULL;
	function message() {
		if (NULL === $this->_message) {
			$this->_message = Event::trigger($this->object->name().'_model.update.message model.update.message', $this);
		}
		return $this->_message;
	}

	private $_message_view = NULL;
	function message_view() {
		if (NULL === $this->_message_view) $this->_message_view = Event::trigger($this->object->name().'_model.update.message_view model.update.message_view', $this);
		return $this->_message_view;
	}

}

<?php

class Cal_Attendee_Model extends ORM_Model {
	
	//事件类型
	const ROLE_CHAIR = 0;
	const ROLE_REQ_PARTICIPANT = 1;
	const ROLE_OPT_PARTICIPANT = 2;
	const ROLE_NON_PARTICIPANT = 3;

	static function roles() {
		return [
			self::ROLE_CHAIR => I18N::T('calendars', '主持者'),
			self::ROLE_REQ_PARTICIPANT => I18N::T('calendars', '必须参与者'),
			self::ROLE_OPT_PARTICIPANT => I18N::T('calendars', '可选参与者'),
			self::ROLE_NON_PARTICIPANT => I18N::T('calendars', '知情者'),
		];
	}
	
}


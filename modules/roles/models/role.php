<?php

class Role_Model extends Presentable_Model {

    static $privacy = [
        self::PRIVACY_ALL =>'所有人可见',
        self::PRIVACY_GROUP =>'组织机构管理员可见',
        self::PRIVACY_ADMIN =>'系统管理员可见'
    ];

    const PRIVACY_ALL = 2;
    const PRIVACY_GROUP = 0;
    const PRIVACY_ADMIN = 1;

	protected $object_page = [
		'delete'=>'!roles/delete.%id',
	];

	function save($overwrite=FALSE) {
		if (!$this->id) {
			$last_role = Q('role:sort(weight D):limit(1)')->current();
			if ((int)$this->weight == 0) $this->weight = max($last_role->weight, 0) + 1;
		}
		return parent::save();
	}
}


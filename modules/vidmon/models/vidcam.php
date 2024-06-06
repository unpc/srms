<?php

class Vidcam_Model extends Presentable_Model {

    const TYPE_GENEE = 1;
    const TYPE_STREAM = 2;

    static $type = [
		self::TYPE_GENEE => '定时截图',
        self::TYPE_STREAM => '流媒体',
    ];
    
	protected $object_page = [
		'view'=>'!vidmon/vidcam/index.%id[.%arguments]',
        'edit' => '!vidmon/vidcam/edit.%id[.%arguments]',
		'snapshot' => '!vidmon/snapshot/index.%id[.%arguments]',
		'snapshot_upload' => '!vidmon/snapshot/upload.%id[.%arguments]',
		];

	function & links($mode='list') {
		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
		case 'list':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => URI::url(),
					'text'  => I18N::T('vidmon', '修改'),
					'tip' => I18N::T('vidmon', '修改'),
					'extra'=>'class="blue" q-object="vidcam_edit" q-event="click" q-static="'. H(['vidcam_id'=>$this->id]). '" q-src="'.H(URI::url('!vidmon/vidcam')).'"'
				];
			}
			break;
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => URI::url(),
					'text' => I18N::T('vidmon', '修改'),
					'tip' => I18N::T('vidmon', '修改'),
					'extra'=>'class="button button_edit fa-lg" q-object="vidcam_edit" q-event="click" q-static="'. H(['vidcam_id'=>$this->id]). '" q-src="'.H(URI::url('!vidmon/vidcam')).'"'
				];
			}
        }
		Event::trigger('vidcam.exitra.links', $this, $links, $mode);
		return (array) $links;
	}

    /* 以下功能暂时关闭

    //根据数据，返回警报点, 用于页面time_line显示
    public function get_last_alarm_points($count) {
        $now = Date::time();
        return Q("vidcam_alarm[ctime<={$now}]:sort(ctime DESC):limit($count)")->to_assoc('id', 'ctime');
    }

    //获取某时间点的前一个警报点
    public function get_prev_alarm_point($ctime) {
        $alarm_point = O('vidcam_alarm', array('ctime'=>$ctime));

        if ($alarm_point->id) {
            $prev_point = Q("vidcam_alarm[id<{$alarm_point->id}]:sort(ctime DESC):limit(1)")->current();

            return $prev_point->id : $prev_point : FALSE;
        }

        return FALSE;
    }

    //获取某时间点的下一个警报点
    public function get_next_alarm_point($ctime) {

        $alarm_point = O('vidcam_alarm', array('ctime'=>$ctime));

        if ($alarm_point->id) {
            $prev_point = Q("vidcam_alarm[id>{$alarm_point->id}]:sort(ctime ASC):limit(1)")->current();

            return $prev_point->id : $prev_point : FALSE;
        }

        return FALSE;
    }

    //获取对应时间最近的警报点
    public function get_nearby_alarm_point($search_time) {
        $alarm_point = O('vidcam_alarm', array('ctime'=>$search_time));
        //如果传入时间存在警报点，则直接返回
        if ($alarm_point->id) {
            return $alarm_point;
        }
        else {
            //前一警报点
            $prev_point = Q("vidcam_alarm[ctime<{$search_time}]:sort(ctime DESC):limit(1)")->current();
            //后一个警报点
            $next_point = Q("vidcam_alarm[ctime>{$search_time}]:sort(ctime ASC):limit(1)")->current();

            if ($prev_point->id && $next_point->id) {
                //判断时间间距
                return ($search_time - $prev_point->ctime) <= ($next_point->ctime - $search_time) ? $prev_point : $next_point;
            }
            elseif ($prev_point->id) {
                return $prev_point;
            }
            elseif ($next_point->id) {
                return $next_point;
            }
            else {
                return FALSE;
            }
        }
    }

    //默认加载页面，加载最后一个警报点
    public function get_last_alarm_point() {
        return Q("vidcam_alarm:sort(ctime DESC):limit(1)")->current();
    }
    */

    const RESTART_STATUS_SUCCESS = 0;
    const RESTART_STATUS_FAILED = 1;
    const RESTART_STATUS_NO_CONNECTION = 2;

    static $restart_message = [
        self::RESTART_STATUS_SUCCESS => '重启成功!',
        self::RESTART_STATUS_FAILED => '重启失败!',
        self::RESTART_STATUS_NO_CONNECTION => '网络故障! 无法连接!',
    ];

    public function restart() {
        if ($this->id && $this->control_address) {

            try {
                $client = new Vidmon_Client($this);
			    $ret = $client->restart() ? self::RESTART_STATUS_SUCCESS : self::RESTART_STATUS_FAILED;
            }
            catch(Exception $e) {
                $ret = self::RESTART_STATUS_NO_CONNECTION;
            }

            return $ret;
        }

        return self::RESTART_STATUS_NO_CONNECTION;
    }
}

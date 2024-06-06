<?php
class EQ_Lua {

    protected $lua;
    protected $script;

    function __construct($script) {
        $this->script = $script;

        $this->lua = new Lua;

        $this->lua->registerCallback('week_day', [$this, 'week_day']);
        $this->lua->registerCallback('month', [$this, 'month']);
        $this->lua->registerCallback('month_day', [$this, 'month_day']);
        $this->lua->registerCallback('today', [$this, 'today']);
        $this->lua->registerCallback('month_start', [$this, 'month_start']);
        $this->lua->registerCallback('month_end', [$this, 'month_end']);
        $this->lua->registerCallback('week_start', [$this, 'week_start']);
        $this->lua->registerCallback('week_end', [$this, 'week_end']);
        $this->lua->registerCallback('day_start', [$this, 'day_start']);
        $this->lua->registerCallback('day_end', [$this, 'day_end']);
        $this->lua->registerCallback('days', [$this, 'days']);
        $this->lua->registerCallback('second_to_hour', [$this, 'second_to_hour']);
        $this->lua->registerCallback('second_to_min', [$this, 'second_to_min']);
        $this->lua->registerCallback('sprintf', 'sprintf');
        $this->lua->registerCallback('round', 'round');
        $this->lua->registerCallback('date', 'date');
        $this->lua->registerCallback('mktime', 'mktime');
        $this->lua->registerCallback('strong', [$this, 'strong']);

        $this->lua->registerCallback('user_violation', [$this, 'user_violation']);

        $this->lua->registerCallback('visual_vars', [$this, 'visual_vars']);

        //很多用户所以用了 os.date("*t", time) 来获取具体的时间
        //由于 os 存在安全问题, 并且 os.date("*t", time) 获取时间会存在时区问题, 故此处重写了该函数
        $this->lua->registerCallback('dateT', [$this, 'dateT']);
        $this->lua->assign('now', Date::time()); //assign now
        $this->lua->assign('HTML_END', '<br />'); //assign HTML_END

        //RTFM !
        //disable io functions
        //io 函数可进行文件的增删改查
        //http://www.lua.org/manual/5.2/manual.html#6.8
        $this->lua->assign('io', NULL);

        //disable os functions
        //os 函数中的 remove 和 execute 有很大的安全隐患
        //http://www.lua.org/manual/5.2/manual.html#6.9
        $this->lua->assign('os', NULL);

        //disable debug functions
        //debug 函数无用, 不予抛出
        $this->lua->assign('debug', NULL);
        $this->lua->registerCallback('error_log', [$this, 'error_log']);
    }

    //此函数是为了模拟 os.date("*t", time)
    function dateT($time = 0) {
        if (empty($time)) $time = Date::time();
        return [
            'hour'=> (float) date('H', $time),
            'ghour'=> (float) date('G', $time),
            'day'=> (float) date('d', $time),
            'sec'=> (float) date('s', $time),
            'wday'=> (float) date('w', $time) + 1,
            'week'=> (float) date('W', $time),
            'min'=> (float) date('i', $time),
            'month'=> (float) date('m', $time),
            'nmonth'=> (float) date('n', $time),
            'year'=> (float) date('Y', $time),
            'yday'=> (float) date('z', $time) + 1,
        ];
    }

    function error_log($message) {
        error_log('@eq_lua: '. print_r($message, true));
    }

    function _check_syntax(&$err) {
        try{
            //使用ob机制，防止进行输出
            ob_start();
            $this->lua->eval($this->script);
            ob_end_clean();
        }
        catch(LuaException $e) {
            $err = $e->getMessage();
            return FALSE;
        }

        return TRUE;
    }

    //得到lua脚本的内容
    static function get_lua_content($module, $script){
        list($dir, $path) = explode(':', $script, 2);
        if($dir != 'private'){
            return $script;
        }
        else{
            //考虑使用Core::file_exists替换原有直接load, 便于lab重载lua
            $lua_file = Core::file_exists(PRIVATE_BASE. 'lua/'.$path, $module);
            if(File::exists($lua_file)){
                return file_get_contents($lua_file);
            }
        }
    }

    //自定义暴露给lua使用的方法
    function second_to_hour($time, $n=2) {
        return (float)round($time / 3600, $n);
    }

    function second_to_min($time, $n = 4)
    {
        return (float)round($time / 60, $n);
    }

    function today($format) {
        return (int)strtotime($format);
    }

    function week_day($time) {
        return (int) date('w', $time);
    }

    function month($time) {
        return (int) date('n', $time);
    }

    function month_day($time) {
        return (int) date('j', $time);
    }

    function days($number) {
        return (int)$number * 86400;
    }

	function month_start($time=NULL) {
		list($start, $end) = (array)self::get_limit_time('m', $time);
		return (int)$start;
	}
	function month_end($time=NULL) {
		list($start, $end) = (array)self::get_limit_time('m', $time);
		return (int)$end - 1;
	}
    function week_start($time=NULL) {
        list($start, $end) =  (array)self::get_limit_time('w', $time);
        return (int)$start;
    }

    function week_end($time=NULL) {
        list($start, $end) =  (array)self::get_limit_time('w', $time);
        return (int) $end - 1;
    }

    function day_start($time=NULL) {
        list($start, $end) =  (array)self::get_limit_time('d', $time);
        return (int) $start;
    }

    function day_end($time=NULL) {
        list($start, $end) =  (array)self::get_limit_time('d', $time);
        return (int) $end - 1;
    }

    static function get_limit_time($format, $time = NULL) {
        $time = $time ?: Date::time();
        //此处应考虑使用默认的Date系列函数进行运算
        $year = date('Y', $time);
        $month = date('m', $time);
        $week = date('d', $time) - date('w', $time);
        $day = date('d', $time);
        switch ($format) {
            case 'w':
                $begin_time = mktime(0, 0, 0, $month, $week, $year);
                break;
            case 'm':
                $begin_time = mktime(0, 0, 0, $month, 1, $year);
                break;
            case 'y':
                $begin_time = mktime(0, 0, 0, 1, 1, $year);
                break;
            case 'd':
            default:
                $begin_time = mktime(0, 0, 0, $month, $day, $year);
                break;
        }
        return [$begin_time, $begin_time + self::$units[$format]];
    }

    static $units = [
        'd' => 86400,
        'w' => 604800,
        'm' => 2592000,
        'y' => 31536000
    ];

    //检查用户标签
    static function user_has_tag($name, $user, $roots) {

        if (!is_array($roots)) $roots = [$roots];
        $q_name = Q::quote($name);
        foreach($roots as $root) {

            $tag_name = $root->name();
            if (!$user->id) return NULL;
            if (Q("{$user} {$tag_name}[name={$q_name}][root={$root}]")->total_count() > 0) {
                return 1;
            }

            if ($GLOBALS['preload']['people.multi_lab']) {
                $user_group = $user->group;
                $group_root = Tag_Model::root('group');
                $groups = Q("{$tag_name}[name={$q_name}][root={$root}] tag_group[root={$group_root}]");
                foreach ($groups as $group) {
                    if ($group->is_itself_or_ancestor_of($user_group)) {
                        return 1;
                    }
                }
            }
            else {
                $lab = Q("$user lab")->current();
                if ($lab->id && Q("{$lab} {$tag_name}[name={$q_name}][root={$root}]")->total_count() > 0) {
                    return 1;
                }

                $lab_group = $lab->group;
                $user_group = $user->group;
                $group_root = Tag_Model::root('group');
                $groups = Q("{$tag_name}[name={$q_name}][root={$root}] tag_group[root={$group_root}]");
                foreach ($groups as $group) {
                    if ($group->is_itself_or_ancestor_of($user_group) || $group->is_itself_or_ancestor_of($lab_group)) {
                        return 1;
                    }
                }
            }
        }

        return NULL;
    }

    static function user_violation() {
        if (!Module::is_installed('eq_ban')) return [];
        $user = $this->_user;
        $violation = O('user_violation', ['user' => $user]);
        if (!$violation->id) return [];
        return [
            'total_count' => $violation->id ? $violation->total_count : 0,
            'eq_miss_count' => $violation->id ? $violation->eq_miss_count : 0,
            'eq_leave_early_count' => $violation->id ? $violation->eq_leave_early_count : 0,
            'eq_overtime_count' => $violation->id ? $violation->eq_overtime_count : 0,
            'eq_late_count' => $violation->id ? $violation->eq_late_count : 0,
        ];
    }

    //Html相应的一系列方法
    function strong($string) {
        return (string) '<strong>' . $string . '</strong>';
    }

    function run(array $args){

        try {

            //run lua
            ob_start();

            //对lua解析后的输出内容进行ob获取，防止直接传递至前台
            $this->lua->eval($this->script);
            $lua_output = ob_get_contents();

            ob_end_clean();
        }
        catch(LuaException $e) {
            error_log($e->getMessage());
            //发现问题后, 提示错误信息
            Log::add("执行发生错误! {$this->script}", 'lua');

            //同步发送邮件通知到
            //support@geneegroup.com

            $subject = strtr('服务器Lua脚本出现问题!(地址: %url)', [
                '%url'=> URI::url(),
            ]);

            $body = "错误脚本\n {$this->script} \n lua报错内容: {$e->getMessage()}\n";

            $email_user = O('user', ['email' => 'support@geneegroup.com']);

            Notification::send("#VIEW#|equipments:equipment/exception", $email_user, [
                '#TITLE#' => $subject,
                'body' => $body
            ]);
        }

        $results = [];
        foreach($args as $arg) {
            $results[$arg] = $this->lua->$arg;
        }

        return $results;
    }

    //将php数组转换为lua数组
    static function array_p2l($options) {
        if(!is_array($options)) return;
        $script = self::get_lua_content('equipments','private:array_p2l.lua');
        $lua = new Lua();
        $lua->assign('php_array', $options);
        $lua->eval($script);
        return $lua->lua_array;
    }

    /**
     * 自定义脚本可视化获取变量值
     * @return array
     * $type => [
     *      eq_reserv:预约脚本可视化
     *      reserv_charge_script:计费脚本可视化-预约计费
     * ]
     */
    function visual_vars(){
        $source2CustomName = [
            'eq_reserv' => 'reserv_charge_script',
            'eq_sample' => 'sample_charge_script',
            'eq_record' => 'record_charge_script',
        ];
        if($this->_source){
            //收费LUA脚本
            $equipment = $this->_source->equipment;
            $type = $source2CustomName[$this->_source->name()];
        }elseif($this->_component){
            //预约LUA脚本
            $equipment = $this->_component->calendar->parent;
            $type = 'eq_reserv';
        }
        $this->error_log($equipment->id);
        if(!$equipment->id){
            return [];
        }
        //这里为当前变量赋值默认值，以通过脚本的保存。
        $vars = (array) $equipment->custom_vars[$type];
        $this->error_log(print_r($vars,true));
        foreach($vars as $k => &$v){
            if(!$v){
                $v = 0;
            }
        }
        $this->error_log(print_r($vars,true));
        return $vars;
    }
}

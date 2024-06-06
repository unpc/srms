<?php
class Search_Tn_Task extends Search_Iterator {

	static protected $model_name = 'tn_task';

    //检索
    public function __construct($opt = NULL) {
        parent::__construct($opt);

        $SQL = 'SELECT `id` FROM `' . self::get_index_name() . '`';

        $where = [];

        if (isset($opt['status'])) {
            $tn_status = (int) $this->sphinx->escape($opt['status']);
            $where[] = "task_status=$tn_status";
        }

        if ($opt['priority']) {
            $where[] = "priority={$opt['priority']}";
        }

        if ($opt['dstart']) {
            $dtstart = (int) $this->sphinx->escape($opt['dstart']);
            $where[] = "deadline>=$dtstart";
        }

        if ($opt['dend']) {
            $dtend = (int) $this->sphinx->escape($opt['dend']);
            $where[] = "deadline<=$dtend";
        }

        if ($opt['cstart']) {
            $cstart = (int) $this->sphinx->escape($opt['cstart']);
            $where[] = "ctime>=$cstart";
        }

        if ($opt['cend']) {
            $cend = (int) $this->sphinx->escape($opt['cend']);
            $where[] = "ctime<=$cend";
        }

        if ($opt['mstart']) {
            $mstart = (int) $this->sphinx->escape($opt['mstart']);
            $where[] = "mtime>=$mstart";
        }

        if ($opt['mend']) {
            $mend = (int) $this->sphinx->escape($opt['mend']);
            $where[] = "mtime<=$mend";
        }

        if (isset($opt['is_complete'])) {
            $is_complete = (int) $this->sphinx->escape($opt['is_complete']);
            $where[] = "is_complete={$is_complete}";
        }

        if ($opt['user_id']) {
            $user_id = (int) $this->sphinx->escape($opt['user_id']);
            $where[] = "user_id=$user_id";
        }

        if ($opt['reviewer_id']) {
            $reviewer_id = (int) $this->sphinx->escape($opt['reviewer_id']);
            $where[] = "reviewer_id={$reviewer_id}";
        }

        if ($opt['project_id']) {
            $project_id = (int) $this->sphinx->escape($opt['project_id']);
            $where[] = "project_id={$project_id}";
        }

        if (isset($opt['parent_task_id'])) {
            $parent_task_id = (int) $this->sphinx->escape($opt['parent_task_id']);
            $where[] = "parent_task_id={$parent_task_id}";
        }

        if ($opt['content']) {
            $content = $this->sphinx->escape($opt['content']);
            $where[] = "MATCH('@(title,description,notes,task_files,notes_files,comments,user_name) {$content}')";
        }


        foreach((array) $opt['not'] as $nkey=>$nvalue) {
            $where[] = "$nkey!=$nvalue";
        }

        if (count($where)) {
            $SQL .= ' WHERE '. implode(' AND ', $where);
        }

        if ($opt['order_by']) {
            $order_by = $opt['order_by'];
        }
        else {
            $order_by = ' ORDER BY `deadline` ASC, `priority DESC`';
        }

        $this->sphinx_SQL = $SQL;
        $this->sphinx_order_by_sql = $order_by;
    }

    //清空index
    static function empty_index() {
        return parent::empty_index_of(self::get_index_name());
    }

    //删除单一index
    static function delete_index($task) {
        if (!$task->id) return FALSE;

        $sphinx = Database::factory('@sphinx');
        $sphinx->query('DELETE FROM `' . self::get_index_name() . '` WHERE ID=%d', $task->id);
    }

    //建立index
    static function update_index($task) {
        if (!$task->id) return FALSE;

        $sphinx = Database::factory('@sphinx');

        $k = ['id', 'title', 'description'];
        foreach ($k as $value) {
            $v[] = $sphinx->quote($task->$value);
        }

        //任务的记录的描述
        $k[] = 'notes';
        $v[] = $sphinx->quote(implode(',', $task->notes()->to_assoc('id', 'content')));

        //任务的附件文件
        $k[] = 'task_files';
        $v[] = $sphinx->quote(join(',', $task->get_files()));

        //任务的记录的附件文件
        $k[] = 'notes_files';
        $v[] = $sphinx->quote(join(',', $task->get_notes_files()));

        //评论
        $k[] = 'comments';
        $task_comments_array = Q("comment[object={$task}]")->to_assoc('id', 'content');
        $notes_comments_array = Q("$task tn_note comment.object")->to_assoc('id', 'content');
        $v[] = $sphinx->quote(implode(',', $task_comments_array + $notes_comments_array));

        //负责人名称
        $k[] = 'user_name';
        $v[] = $sphinx->quote($task->user->name);

        //任务的状态
        $k[] = 'task_status';
        $v[] = (int) $task->status;

        //任务的优先级
        $k[] = 'priority';
        $v[] = (int) $task->priority;

        //截止时间
        $k[] = 'deadline';
        $v[] = $task->deadline;

        //mtime
        $k[] = 'mtime';
        $v[] = $task->mtime;

        //ctime
        $k[] = 'ctime';
        $v[] = $task->ctime;

        //是否完成
        $k[] = 'is_complete';
        $v[] = (int) $task->is_complete;

        //负责人ID
        $k[] = 'user_id';
        $v[] = $task->user->id;

        //reviewer ID
        $k[] = 'reviewer_id';
        $v[] = $task->reviewer->id;

        //project_id
        $k[] = 'project_id';
        $v[] =  $task->project->id;

        //parent_task_id
        $k[] = 'parent_task_id';
        $v[] = $task->parent_task->id;

        $SQL = 'REPLACE INTO `' . self::get_index_name() . '` ('.implode(',', $k) .') VALUES ('. implode(',', $v) .')';
        $sphinx->query($SQL);
    }
}

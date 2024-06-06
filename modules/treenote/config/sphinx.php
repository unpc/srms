<?php
$config['tn_task'] = [
	'fields' => [
		'title' => ['type' => 'rt_field'],
		'description' => ['type' => 'rt_field'],
		'notes' => ['type' => 'rt_field'],
		'task_files' => ['type' => 'rt_field'],
		'notes_files' => ['type' => 'rt_field'],
		'comments' => ['type' => 'rt_field'],
		'user_name' => ['type' => 'rt_field'],
		'task_status' => ['type' => 'rt_attr_uint'],
		'priority' => ['type' => 'rt_attr_uint'],
		'is_complete' => ['type' => 'rt_attr_uint'],
		'user_id' => ['type' => 'rt_attr_bigint'],
		'reviewer_id' => ['type' => 'rt_attr_bigint'],
		'project_id' => ['type' => 'rt_attr_bigint'],
		'parent_task_id' => ['type' => 'rt_attr_bigint'],
		'deadline' => ['type' => 'rt_attr_timestamp'],
		'mtime' => ['type' => 'rt_attr_timestamp'],
		'ctime' => ['type' => 'rt_attr_timestamp'],
		],
];

/*
index lab_demo_tn_task: rt_default
{
    path = /var/lib/sphinxsearch/data/lims2/lab_demo_tn_task
    rt_field = title
    rt_field = description
    rt_field = notes
    rt_field = task_files
    rt_field = notes_files
    rt_field = comments
    rt_field = user_name
    rt_attr_uint = task_status
    rt_attr_uint = priority
    rt_attr_uint = is_complete
    rt_attr_bigint = user_id
    rt_attr_bigint = reviewer_id
    rt_attr_bigint = project_id
    rt_attr_bigint = parent_task_id
    rt_attr_timestamp = deadline
    rt_attr_timestamp = mtime
    rt_attr_timestamp = ctime
}
*/
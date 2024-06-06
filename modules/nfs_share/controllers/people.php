<?php

class People_Controller extends Base_Controller
{

    public function index($id = 0)
    {

        $form = Lab::form();

        $selector = "user[!hidden][atime>0]";

        $pre_selectors = new ArrayIterator();
        #if($form['lab']){
        #	$lab = Q::quote($form['lab']);
        #	$pre_selectors['labs'] = "lab[name*=$lab|name_abbr*=$lab]";
        #}

        if ($form['user']) {
            $user_name = Q::quote($form['user']);
            $selector .= "[name*=$user_name|name_abbr*=$user_name]";
        }

        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'D' : 'A';

        switch ($form['sort']) {
            case 'size':
                $selector .= ":sort(nfs_size {$sort_flag}, mtime D)";
                $sort_by = 'size';
                break;
            case 'used':
                //先按照空间总大小排序，可以把没有开通空间的用户排序到后面，再按照空间使用情况排序
                $selector .= ":sort(nfs_size {$sort_flag}, nfs_used {$sort_flag}, mtime D)";
                $sort_by = 'used';
                break;
            case 'mtime':
                $selector .= ":sort(nfs_mtime {$sort_flag},mtime D)";
                $sort_by = 'mtime';
            default:
                $selector .= ":sort(nfs_mtime D,mtime D)";
                $sort_by = 'mtime';
        }

        $new_selector = Event::trigger('sort.condition.selector', $selector, $pre_selectors, 'user');
        if ($new_selector) {
            $selector = $new_selector;
		}

		if (count($pre_selectors)) $selector = '('.implode(',', (array)$pre_selectors).') '.$selector;

        $users = Q($selector);

        $start = (int) $form['st'];
        $per_page = 30;
        $pagination = Lab::pagination($users, $start, $per_page);

        $field = self::get_nfs_field($form);
        $columns = new ArrayObject($field);
        $search_box = V('application:search_box', ['is_offset' => false, 'top_input_arr' => ['user', 'lab'], 'columns' => $columns]);

        $this->layout->body->primary_tabs
            ->select('people');
        $content = V('people/index');

        $content->set('users', $users);
        $content->set('pagination', $pagination);
        $content->set('sort_by', $sort_by);
        $content->set('sort_asc', $sort_asc);
        $content->set('form', $form);
        $content->set('columns', $field);
        $content->set('search_box', $search_box);

        $this->layout->body->primary_tabs
            ->set('content', $content);

    }

    public function get_nfs_field($form)
    {
        $columns = [
            'user' => [
                'title' => I18N::T('people', '姓名'),
                'filter' => [
                    'form' => V('users_table/filters/user', ['form' => $form]),
                    'value' => $form['user'] ? H($form['user']) : null,
                ],
                'nowrap' => true,
            ],
            'used' => [
                'title' => I18N::T('nfs', '空间使用情况'),
                'nowrap' => true,
                'align' => 'left',
                'sortable' => true,
            ],
            'mtime' => [
                'title' => I18N::T('nfs', '更新日期'),
                'nowrap' => true,
                'align' => 'center',
                'sortable' => true,
            ],
            'rest' => [
                'title' => '操作',
                'nowrap' => true,
                'align' => 'left',
            ],
        ];

        if (Module::is_installed('labs')) {
            $columns += [
                'lab' => [
                    'title' => I18N::T('labs', '实验室'),
                    'filter' => [
                        'form' => V('users_table/filters/lab', ['form' => $form]),
                        'value' => $form['lab'] ? H($form['lab']) : null,
                    ],
                    'invisible' => true,
                    'nowrap' => true,
                ],
            ];
        }

        return $columns;
    }

    public function open($id)
    {
        $id = (int) $id;
        $user = O('user', $id);
        if ($user->nfs_size == 0) {
            if (NFS_Share::setup_share($user)) {
                /* 记录日志 */
                Log::add(strtr('[nfs_share] %operator_name[%operator_id]开通了用户%user_name[%user_id]的文件分区', [
                    '%operator_name' => L('ME')->name,
                    '%operator_id' => L('ME')->id,
                    '%user_name' => $user->name,
                    '%user_id' => $user->id,
                ]), 'journal');
            }
        }

        URI::redirect($_SESSION['system.current_layout_url']);
    }

    public function close($id)
    {

        $id = (int) $id;
        $user = O('user', $id);

        if ($user->nfs_size > 0) {
            if (NFS_Share::destroy_share($user)) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nfs_share', '操作成功!'));
                /* 记录日志 */
                Log::add(strtr('[nfs_share] %operator_name[%operator_id]关闭了用户%user_name[%user_id]的文件分区', [
                    '%operator_name' => L('ME')->name,
                    '%operator_id' => L('ME')->id,
                    '%user_name' => $user->name,
                    '%user_id' => $user->id,
                ]), 'journal');
            }
        }
        URI::redirect($_SESSION['system.current_layout_url']);
    }

}
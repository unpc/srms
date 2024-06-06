<?php

class Labs_Controller extends Base_Controller
{

    public function get_labs_columns($form)
    {
        return
            [
            /*'@'=>[
            'nowrap'=>TRUE,
            'align'=>'top'
            ],*/
            'lab' => [
                'title' => I18N::T('labs', '实验室'),
                'filter' => [
                    'form' => V('labs_table/filters/lab', ['query' => $form['query']]),
                    'value' => $form['query'] ? H($form['query']) : null,
                    'field' => 'query',
                ],
                'nowrap' => true,
            ],
            'group' => [
                'title' => I18N::T('labs', '组织机构'),
                'nowrap' => true,
                'invisible' => true,
            ],
            'used' => [
                'title' => I18N::T('labs', '空间使用情况'),
                'nowrap' => true,
                'align' => 'left',
                'sortable' => true,
            ],
            'mtime' => [
                'title' => I18N::T('labs', '更新日期'),
                'nowrap' => true,
                'align' => 'center',
                'sortable' => true,
            ],
            'rest' => [
                'title' => I18n::T('labs', '操作'),
                'nowrap' => true,
                'align' => 'left',
            ],
        ];
    }
    public function index()
    {

        if (!Module::is_installed('labs')) {
            URI::redirect('error/404');
        }

        $form = Lab::form();

        $selector = "lab[atime>0]";

        $query = $form['query'];

        if ($query) {
            $query = Q::quote($query);
            $selector .= "[name*=$query|name_abbr*=$query]";
        }

        $sort_asc = $form['sort_asc'];

        $sort_flag = $sort_asc ? 'D' : 'A';

        switch ($form['sort']) {
            case 'used':
                $selector .= ":sort(nfs_used {$sort_flag}, nfs_size {$sort_flag}, mtime D)";
                $sort_by = 'used';
                break;
            case 'mtime':
                $selector .= ":sort(nfs_mtime {$sort_flag},mtime D)";
                $sort_by = 'mtime';
            default:
                $selector .= ":sort(nfs_mtime D,mtime D)";
                $sort_by = 'mtime';
        }

        $pre_selectors = new ArrayIterator();
        $new_selector = Event::trigger('sort.condition.selector', $selector, $pre_selectors, 'lab');
        if ($new_selector) {
            $selector = $new_selector;
		}
        if (count($pre_selectors)) $selector = '('.implode(',', (array)$pre_selectors).') '.$selector;

        $labs = Q($selector);

        $pagination = Lab::pagination($labs, (int) $form['st'], 15);

        $columns = $this->get_labs_columns($form);

        $this->layout->body->primary_tabs
            ->select('labs')
            ->set('content', V('labs/index',
                [
                    'labs' => $labs,
                    'pagination' => $pagination,
                    'columns' => $columns,
                    'form' => $form,
                    'sort_by' => $sort_by,
                    'sort_asc' => $sort_asc,
                ]));

    }

    public function open($id)
    {
        $id = (int) $id;
        $lab = O('lab', $id);
        if ($lab->nfs_size == 0) { /* 若 lab 目录尚未开通，则开通 lab 目录 */

            if (NFS_Share::destroy_share($lab) && NFS_Share::setup_share($lab)) {
                /* 记录日志 */
                Log::add(strtr('[nfs_share] %user_name[%user_id]开通了实验室%lab_name[%lab_id]的文件分区', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%lab_name' => $lab->name,
                    '%lab_id' => $lab->id,
                ]), 'journal');
                $total_users = Q("user[lab={$lab}]")->total_count();
                $start = 0;
                $per_page = 500;
                while ($start <= $total_user) {
                    $users = Q("user[lab={$lab}]")->limit($start, $per_page);
                    foreach ($users as $user) {
                        if ($user->nfs_size > 0) {
                            NFS_Share::setup_share($user);
                        }
                    }
                    $start += 500;
                }
            }
        }
        URI::redirect('!nfs_share/labs');
    }

    public function close($id)
    {

        $id = (int) $id;
        $lab = O('lab', $id);
        if ($lab->nfs_size > 0) {
            if (NFS_Share::destroy_share($lab)) {
                /* 记录日志 */
                Log::add(strtr('[nfs_share] %user_name[%user_id]关闭了实验室%lab_name[%lab_id]的文件分区', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%lab_name' => $lab->name,
                    '%lab_id' => $lab->id,
                ]), 'journal');

                $total_users = Q("user[lab={$lab}]")->total_count();
                $start = 0;
                $per_page = 500;
                while ($start <= $total_user) {
                    $users = Q("user[lab={$lab}]")->limit($start, $per_page);
                    foreach ($users as $user) {
                        //关闭课题组分区，需要将user中的lab目录也删除
                        $link = NFS_Share::get_share_path($user, 'lab');
                        is_link($link) and @unlink($link);
                    }
                    $start += 500;
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nfs_share', '操作成功!'));
            }
        }
        URI::redirect('!nfs_share/labs');
    }

}
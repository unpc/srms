<?php

class Finder_Controller extends Base_Controller
{

    public function index()
    {
        URI::redirect('!nfs_share/finder/user');
    }

    public function user($id = 0)
    {
        $url  = URI::url();
        $me   = L('ME');
        $user = O('user', $id);

        if (!$user->id) {
            $user = $me;
        }

        if (!NFS::user_access($me, '列表文件', $user, ['type' => 'share'])) {
            URI::redirect('error/401');
        }

        $primary_tabs = $this->layout->body->primary_tabs;

        if ($user->id == $me->id) {
            $primary_tabs->select('mine');
        } else {
            $primary_tabs->add_tab('user', [
                'url'   => URI::url('!nfs_share/finder/user.' . $id),
                'title' => I18N::T('nfs_share', '%name的个人分区', ['%name' => $user->name]),
            ])
                ->select('user');
        }

        if ($user->nfs_size) {
            $content = V('nfs_share:finder', ['object' => $user, 'path_type' => 'share']);
        } else {
            $content = V('nfs_share:people/unavail', ['user' => $user]);
        }

        $primary_tabs->set('content', $content);
    }

    public function lab($id = 0)
    {

        $me  = L('ME');
        $lab = O('lab', $id);
        if (!$lab->id) {
            URI::redirect('error/404');
        }

        if (!NFS::user_access($me, '列表文件', $lab, ['type' => 'share'])) {
            URI::redirect('error/401');
        }

        if (!Module::is_installed('labs')) {
            URI::redirect('error/404');
        }

        $primary_tabs = $this->layout->body->primary_tabs;

        $primary_tabs
            ->add_tab('lab', [
                'url'   => URI::url('!nfs_share/finder/lab.' . $id),
                'title' => I18N::T('nfs_share', '%name的分区', ['%name' => $lab->name]),
            ])
            ->select('lab');

        if ($lab->nfs_size) {
            $content = V('nfs_share:finder', ['object' => $lab, 'path_type' => 'share']);
        } else {
            $content = V('nfs_share:labs/unavail', ['lab' => $lab]);
        }

        $primary_tabs->set('content', $content);
    }

}

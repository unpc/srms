<?php

class Tabs_Widget extends _Tabs_Widget {

    public function delete_tab($tid) {
        unset($this->vars['tabs'][$tid]);
        return $this;
    }

    public function get_tabs()
    {
        return $this->vars['tabs'];
    }

}

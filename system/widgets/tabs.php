<?php

class _Tabs_Widget extends Widget
{
    public function __construct($vars = array())
    {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars += array(
            'tabs'=>array(),
        );
        parent::__construct('tabs', $vars);
    }

    public function sort_tabs()
    {
        $tabs = $this->vars['tabs'];
        $order = array();
        $weight = array();
        $i = 0;
        foreach ($tabs as $k => $v) {
            $order[$k] = $i++;
            $weight[$k] = $tabs[$k]['weight'];
        }

        uksort($tabs, function ($ak, $bk) use ($weight, $order) {
            $aw = $weight[$ak];
            $bw = $weight[$bk];

            if ($aw != $bw) {
                return $aw - $bw;
            }
            return $order[$ak] - $order[$bk];
        });

        $this->vars['tabs'] = $tabs;
    }

    public function add_tab($tid, $data)
    {
        $this->vars['tabs'][$tid] = $data;

        //tabs排序
        $this->sort_tabs();

        return $this;
    }

    public function get_tab($tid)
    {
        return $this->vars['tabs'][$tid];
    }

    public function set_tab($tid, $tab)
    {
        if ($tab === null) {
            unset($this->vars['tabs'][$tid]);
        } else {
            $this->vars['tabs'][$tid] = $tab;
            $this->sort_tabs();
        }
        return $this;
    }

    public function select($tid)
    {
        if ($this->tab_event) {
            Event::trigger($this->tab_event, $this);
        }

        if ($this->vars['tabs']) {
            if (!isset($this->vars['tabs'][$tid])) {
                $tid = key($this->vars['tabs']);
            }
            $this->vars['tabs'][$tid]['active'] = true;
            $this->vars['selected'] = $tid;

            if ($this->content_event) {
                Event::trigger_one($this->content_event, $tid, $this);
            }
            if($this->tool_event){
                Event::trigger_one($this->tool_event, $tid, $this);
            }
        }

        return $this;
    }

    private $tab_event;
    private $content_event;
    private $tool_event;

    public function tool_event($name)
    {
        $this->tool_event=$name;
        return $this;
    }
    public function content_event($name)
    {
        $this->content_event = $name;
        return $this;
    }

    public function tab_event($name)
    {
        $this->tab_event = $name;
        return $this;
    }
}

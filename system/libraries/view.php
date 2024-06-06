<?php

abstract class _View
{
    protected $vars=array();
    protected $parent;
    protected $path;

    public function __construct($path, $vars=null)
    {
        $this->path = $path;
        if (is_array($vars)) {
            $this->vars = array_merge($this->vars, $vars);
        }
    }

    public static function factory($path, $vars=null)
    {
        return new View($path, $vars);
    }

    public static function setup()
    {
    }

    // 返回子View
    public function __get($key)
    {
        return $this->vars[$key];
    }

    public function __set($key, $value)
    {
        if ($value === null) {
            if ($value instanceof _View) {
                $value->parent = null;
            }
            unset($this->vars[$key]);
        } else {
            $this->vars[$key] = $value;
            if ($value instanceof _View) {
                $value->parent = $this;
            }
        }
    }

    public function __unset($key)
    {
        unset($this->vars[$key]);
    }

    public function __isset($key)
    {
        return isset($this->vars[$key]);
    }

    public function ob_clean()
    {
        unset($this->_ob_cache);
        return $this;
    }

    //返回View内容
    private $_ob_cache;

    private function _include_view($_path, $_vars)
    {
        if ($_path) {
            ob_start();
            extract($_vars);

            @include($_path);

            $output = ob_get_contents();
            ob_end_clean();
        }

        return $output;
    }

    public function __toString()
    {
        if ($this->_ob_cache !== null) {
            return $this->_ob_cache;
        }

        // 从$path里面获取category;
        list($category, $path) = explode(':', $this->path, 2);
        if (!$path) {
            $path = $category;
            $category = null;
        }

        $event = $category ? "view[{$category}:{$path}].prerender ":'';
        $event .= "view[{$path}].prerender view.prerender";

        Event::trigger($event, $this);

        $v = $this;
        $_vars = array();
        while ($v) {
            $_vars += $v->vars;
            $v = $v->parent;
        }

        $locale = Config::get('system.locale');

        if (isset($GLOBALS['view_map']) && is_array($GLOBALS['view_map'])) {
            $view_map = $GLOBALS['view_map'];
            $category = $category ?: MODULE_ID;
            if ($category && isset($view_map["$category:@$locale/$path"])) {
                $_path = $view_map["$category:@$locale/$path"];
            } elseif ($category && isset($view_map["$category:$path"])) {
                $_path = $view_map["$category:$path"];
            } elseif (isset($view_map["@$locale/$path"])) {
                $_path = $view_map["@$locale/$path"];
            } elseif (isset($view_map[$path])) {
                $_path = $view_map[$path];
            }
        } else {
            $_path = Core::file_exists(VIEW_BASE.'@'.$locale.'/'.$path.VEXT, $category);
            if (!$_path) {
                $_path=Core::file_exists(VIEW_BASE.$path.VEXT, $category);
            }
        }

        if ($_path) {
            $output = $this->_include_view($_path, $_vars);
        }

        $event = $category ? "view[{$category}:{$path}].postrender ":'';
        $event .= "view[{$path}].postrender view.postrender";

        $new_output = (string) Event::trigger($event, $this, $output);

        $output = $new_output ?: (string) $output;

        if ($this->vars['add_tab_content'] == 1) {
            $output = '<div class="tab_content">'.$output.'</div>';
        }

        return $this->_ob_cache = $output;
    }

    public function set($name, $value=null)
    {
        if (is_array($name)) {
            array_map(array($this, __FUNCTION__), array_keys($name), array_values($name));
            return $this;
        } else {
            $this->$name=$value;
        }

        return $this;
    }

    public function render()
    {
        // 2018-10-09 将primary_tabs包裹在<div class="tab_content"></div>标签内
        $this->body->primary_tabs->content->vars['add_tab_content'] = true;
        echo $this;
    }

    public function embed($view)
    {
        $view = $view instanceof View ? $view: V($view);
        $view->parent = $this;
        return $view;
    }
}

function V($path, $vars=null)
{
    return View::factory($path, $vars);
}

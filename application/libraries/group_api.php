<?php
class Group_API
{
    public static function GroupRoot_get($e, $params, $data, $query)
    {
        $root = Tag_Model::root('group');
        $e->return_value = Group_API::group_format($root);
        return;
    }


    public static function groups_get($e, $params, $data, $query)
    {
        $parent_id = $params[0];
        $groups = [];

        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;

        if ($query['type'] == "lab") {
            $selector = "lab";

            if ($query['name']) {
                $name = Q::quote($query['name']);
                $selector .= "[name*={$name}]";
            }

            $total = $pp = Q("$selector")->total_count();
            $selector .= ":limit({$start},{$per_page})";

            foreach (Q($selector) as $group) {
                $groups[$group->id] = self::lab_format($group);
            }
        }
        if ($query['type'] == "organization") {
            $root = Tag_Model::root('group');
            $parent = O($root->name(), $parent_id);
            $selector = $root->name();
            $selector .= "[root=$root][parent=$parent]";

            if ($query['name']) {
                $name = Q::quote($query['name']);
                $selector .= "[name*={$name}]";
            }

            $total = $pp = Q("$selector")->total_count();
            $selector .= ":limit({$start},{$per_page})";
            $selector .= ":sort(weight)";

            foreach (Q($selector) as $group) {
                $groups[$group->id] = self::group_format($group);
            }
        }

        $ret = [
            'items' => array_values($groups),
        ];

        $ret['total'] = $total ?: count($ret['items']);
        $e->return_value = $ret;
        return;
    }
    
    public static function Group_get($e, $params, $data, $query)
    {
        $group_id = $params[0];

        if ($query['type'] == "lab") {
            $lab = O('lab', $group_id);
            error_log($group_id);
            error_log(print_r(self::lab_format($lab), 1));
            $e->return_value = self::lab_format($lab);
        }

        if ($query['type'] == "organization") {
            $root = Tag_Model::root('group');
            $group = O($root->name(), $group_id);
            $e->return_value = self::group_format($group);
        }

        return;
    }

    public static function group_format($group)
    {
        return [
            'id' => (int) $group->id,
            'name' => (string) $group->id ? $group->name : "",
            'type' => (string) "organization",
            'parent_id' => (int) $group->parent_id
        ];
    }

    public static function lab_format($lab)
    {
        $root = Tag_Model::root('group');
        return [
            'id' => (int) $lab->id,
            'name' => (string) $lab->id ? $lab->name : "",
            'type' => (string) "lab",
            'parent_id' => (int) $root->id
        ];
    }
}

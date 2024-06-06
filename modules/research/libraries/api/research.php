<?php

class API_Research extends API_Common
{
    function get_research($id = 0) {
        //$this->_ready('research');

        $research = O('research', $id);
        if (!$research->id) return FALSE;
        $group = $research->group;
        $groups = [];
        $root = Tag_Model::root('group');
        while ($group->id != $root->id) {
            array_unshift($groups, $group->name);
            $group = $group->parent;
        }
        $data = new ArrayIterator([
            'id' => $research->id,
            'ref_no' => $research->ref_no,
            'name' => $research->name,
            'group_id' => $research->group_id,
            'group' => join(' » ', $groups),
            'content' => $research->content,
            'charge' => $research->charge,
            'location' => $research->location,
            'phone' => $research->phone,
            'email' => $research->email,
            'ctime' => $research->ctime,
        ]);
        $info = $data->getArrayCopy();

        return (array)$info;
    }

    function get_researchs($start = 0, $step = 100) {
        //$this->_ready('research');

		$researchs = Q('research')->limit($start, $step);
		$info = [];

		if (count($researchs)) {
			foreach ($researchs as $research) {
                $group = $research->group;
                $groups = [];
                $root = Tag_Model::root('group');
                while ($group->id != $root->id) {
                    array_unshift($groups, $group->name);
                    $group = $group->parent;
                }
                $data = new ArrayIterator([
					'id' => $research->id,
                    'ref_no' => $research->ref_no,
                    'name' => $research->name,
                    'group_id' => $research->group_id,
                    'group' => join(' » ', $groups),
                    'content' => $research->content,
                    'charge' => $research->charge,
                    'location' => $research->location,
                    'phone' => $research->phone,
                    'email' => $research->email,
                    'ctime' => $research->ctime,
                ]);
                $info[] = $data->getArrayCopy();
			}
        }
        return $info;
	}
}

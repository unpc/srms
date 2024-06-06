<?php

class API_Lab_Info extends API_Common {

    function get_labs($start = 0, $step = 100) {
        $this->_ready();

        $labs = Q('lab[hidden=0]')->limit($start, $step);
        $info = [];

        if (count($labs)) {
            foreach ($labs as $lab) {
                $tag = $lab->group;
                $group = $tag->id ? [$tag->name] : null ;
                while($tag->parent->id && $tag->parent->root->id){
                    array_unshift($group, $tag->parent->name);
                    $tag = $tag->parent;
                }

                $data = new ArrayIterator([
                    'creator_id' => $lab->creator_id,
                    'auditor_id' => $lab->auditor_id,
                    'name_abbr' => $lab->name_abbr,
                    'icon16_url' => $lab->icon_url(16),
                    'icon32_url' => $lab->icon_url(32),
                    'icon48_url' => $lab->icon_url(48),
                    'icon64_url' => $lab->icon_url(64),
                    'icon128_url' => $lab->icon_url(128),
                    'id' => $lab->id,
                    'source' => LAB_ID,
                    'name' => $lab->name,
                    'group' => $group,
                    'textbook' => $lab->group->id ? : 0,
                    'group_id' => $lab->group->id,
                    'contact' => $lab->contact,
                    'ref_no' => $lab->ref_no,
                    'type' => $lab->type,
                    'subject' => $lab->subject,
                    'util_area' => $lab->util_area,
                    'location' => $lab->location,
                    'location2' => $lab->location2,
                    'owner'=> $lab->owner->name,
                    'owner_id'=> $lab->owner->id,
                    'description' => $lab->description,
                    'atime' => $lab->atime,
                    'ctime' => $lab->ctime
                ]);
                Event::trigger('lab.extra.keys', $lab, $data);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }

    function get_projects($criteria = []) {
        $this->_ready();
        
        $lab_id = $criteria['lab_id'];
        if (!$lab_id) return [];

        if (is_array($lab_id)) {
            $lab = implode(',', $lab_id);
            $selector = "lab_project[lab_id={$lab}]";
        }
        else {
            $lab = O("lab", (int) $lab_id);
            if (!$lab->id) return [];
            $selector = "lab_project[lab={$lab}]";
        }

        $type = $criteria['type'];
        if (in_array($type, Lab_Project_Model::$stat_types)) {
            $ptype = array_search($type, Lab_Project_Model::$stat_types);
            $selector .= "[type={$ptype}]";
        }

        if ($criteria['order']) foreach ($criteria['order'] as $order) {
            list($field, $sort) = $order;
            $sort = strtoupper($sort);
            $selector .= ":sort({$field} {$sort})";
        }

        list($start, $end) = $criteria['limit'];
        $start = $start ? : 0;
        $end = $end ? : 100;
        $selector .= ":limit({$start}, {$end})";

        $projects = Q($selector);

        $info = [];

        if (count($projects)) foreach ($projects as $project) {
            $info[] = [
                'id' => $project->id,
                'name' => $project->name,
                'textbook' => $project->textbook,
                'book_type' => $project->book_type,
                'incharge' => $project->incharge,
                'ptype' => $project->type,
                'grant' => $project->grant,
                'student_count' => $project->student_count,
                'dtstart' => $project->dtstart,
                'dtend' => $project->dtend,
                'atime'=> $project->atime
            ];
        }
        return $info;
    }

    function searchLabs($opts) {
        $this->_ready();
        
		$selector = "lab";

		if ($opts['name']) {
			$name = Q::quote($opts['name']);
			$selector .= "[name*={$name}]";
		}

		if ($opts['atime']) {
			$selector .= "[atime>0]";
		}

		if (isset($opts['hidden'])) {
			$selector .= "[hidden=" . $opts['hidden'] . "]";
        } else {
            $selector .= "[hidden=0]";
        }

		$token = md5('Lab'.time().uniqid());
		$_SESSION[$token] = $selector;

        $total = Q($selector)->total_count();
        
		return ['token' => $token, 'total' => $total];
	}

    public function get_lab_users($id) {
        $this->_ready();
        $data = [];
        $lab = o("lab", $id);

        if ($lab->id) {
            $users = Q("$lab user");
            foreach ($users as $user) {
                if($lab->owner->id == $user->id) $type = "pi";
                else $type = "";
                $data[] = [
                    "source_id" =>  $user->id,
                    "source_name" => LAB_ID,
                    "type" => $type
                ];
            }
        }
        return $data;
    }
}

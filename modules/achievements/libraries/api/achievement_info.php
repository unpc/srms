<?php

class API_Achievement_Info extends API_Common
{
    public function get_publications($start = 0, $step = 100, $equipment_id=NULL)
    {
        $this->_ready();
        if ($equipment_id!==NULL) {
            $publications = Q("equipment[id={$equipment_id}] publication")->limit($start, $step);
        } else {
            $publications = Q('publication')->limit($start, $step);
        }
        
        $info = [];

        if (count($publications)) {
            foreach ($publications as $publication) {
                $ac_author = [];
                foreach(Q("{$publication}<achievement ac_author") as $ac) {
                    $ac_author[$ac->id] = [
                        'user_id' => $ac->user_id,
                        'name' => $ac->name,
                    ];
                }
                $tags = Q("{$publication} tag")->to_assoc('id', 'name');
                $data = new ArrayIterator([
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'ac_author' => $ac_author,
                    'journal' => $publication->journal,
                    'date' => $publication->date,
                    'volume' => $publication->volume,
                    'issue' => $publication->issue,
                    'tag' => $tags,
                    'page' => $publication->page,
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }

    public function get_awards($start = 0, $step = 100, $equipment_id = NULL)
    {
        $this->_ready();
        if ($equipment_id!==NULL) {
            $awards = Q("equipment[id={$equipment_id}] award")->limit($start, $step);
        } else {
            $awards = Q('award')->limit($start, $step);
        }
        $info = [];

        if (count($awards)) {
            foreach ($awards as $award) {
                $ac_author = [];
                foreach(Q("{$award}<achievement ac_author") as $ac) {
                    $ac_author[$ac->id] = [
                        'user_id' => $ac->user_id,
                        'name' => $ac->name,
                    ];
                }
                $tags = Q("{$award} tag_achievements_award")->to_assoc('id', 'name');
                $data = new ArrayIterator([
                    'id' => $award->id,
                    'name' => $award->name,
                    'tag' => $tags,
                    'date' => $award->date,
                    'ac_author' => $ac_author,
                    'description' => $award->description,
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }
    
    public function get_patents($start = 0, $step = 100, $equipment_id=NULL)
    {
        $this->_ready();
        if ($equipment_id!==NULL) {
            $patents = Q("equipment[id={$equipment_id}] patent")->limit($start, $step);
        } else {
            $patents = Q('patent')->limit($start, $step);
        }

        $info = [];

        if (count($patents)) {
            foreach ($patents as $patent) {
                $ac_author = [];
                foreach(Q("{$patent}<achievement ac_author") as $ac) {
                    $ac_author[$ac->id] = [
                        'user_id' => $ac->user_id,
                        'name' => $ac->name,
                    ];
                }
                $tags = Q("{$patent} tag_achievements_patent")->to_assoc('id', 'name');
                $data = new ArrayIterator([
                    'id' => $patent->id,
                    'name' => $patent->name,
                    'ref_no' => $patent->ref_no,
                    'date' => $patent->date,
                    'tag' => $tags,
                    'ac_author' => $ac_author,
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }
}

<?php

class Analysis_Mark_Model  extends Presentable_Model {
    
    function polymerize () {
        $filter = [
            'lab' => $this->lab,
            'user' => $this->user,
            'equipment' => $this->equipment,
            'project' => $this->project,
            'time' => $this->date
        ];

        $analysis = O('analysis', $filter);
        $analysis->user = $this->user;
        $analysis->lab = $this->lab;
        $analysis->equipment = $this->equipment;
        $analysis->project = $this->project;
        $analysis->time = $this->date;

        $fields = Config::get('schema.analysis')['fields'];
        $diff = array_diff_key($fields, $filter);

        $total = 0;
        $db = Database::factory();
        foreach ($diff as $key => $des) {
            $value = Event::trigger("field.{$key}.refresh", $db, $this);
            if ($value) {
                $analysis->{$key} = $value;
                $total += $value;
            }
        }

        // if ($total == 0 && !$analysis->id) return true;
        $analysis->save();
    }

}

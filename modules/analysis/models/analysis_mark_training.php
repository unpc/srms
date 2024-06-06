<?php

class Analysis_Mark_Training_Model  extends Presentable_Model {
    
    function polymerize () {
        $filter = [
            'equipment' => $this->equipment,
            'time' => $this->date
        ];

        $analysis = O('analysis_training', $filter);
        $analysis->equipment = $this->equipment;
        $analysis->time = $this->date;

        $fields = Config::get('schema.analysis_training')['fields'];
        $diff = array_diff_key($fields, $filter);

        $total = 0;
        $db = Database::factory();
        foreach ($diff as $key => $des) {
            $value = Event::trigger("analysis.training.{$key}.refresh", $db, $this);
            if ($value) {
                $analysis->{$key} = $value;
                $total += $value;
            }
        }

        if ($total == 0 && !$analysis->id) return true;
        $analysis->save();
    }

}

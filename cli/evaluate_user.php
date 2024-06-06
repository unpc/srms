#!/usr/bin/env php
<?php

require 'base.php';

$records = Q('eq_record[evaluate_user=0]');

foreach ($records as $record) {
    $evaluate = O('eq_evaluate_user');
    $evaluate->equipment = $record->equipment;
    $evaluate->user = $record->user;
    $evaluate->status = 1;
    //$evaluate->status_feedback = $form['status_feedback'];
    //$evaluate->attitude = $form['attitude'];
    //$evaluate->attitude_feedback = $form['attitude_feedback'];
    //$evaluate->proficiency = $form['proficiency'];
    //$evaluate->proficiency_feedback = $form['proficiency_feedback'];
    //$evaluate->cleanliness = $form['cleanliness'];
    //$evaluate->cleanliness_feedback = $form['cleanliness_feedback'];
    if ($evaluate->save()) {
        $record->evaluate_user = $evaluate;
        $record->save();
    }
}

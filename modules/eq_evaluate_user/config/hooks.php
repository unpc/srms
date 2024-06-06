<?php

$config['record.links_edit'][] = ['weight' => -999 , 'callback' => 'EQ_Evaluate_User::record_links_edit'];

$config['is_allowed_to[使用者确认].eq_record'][] = 'EQ_Evaluate_User::evaluate_user_ACL';

$config['eq_record.list.row'][] = 'EQ_Evaluate_User::eq_record_list_row';

$config['eq_record.list.columns'][] = 'EQ_Evaluate_User::eq_record_list_columns';

$config['eq_record_model.before_delete'][] = 'EQ_Evaluate_User::eq_record_user_before_delete';

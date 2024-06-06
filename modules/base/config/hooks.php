<?php

/*$config['auth.login'][] = "Base_point::login_point";

$config['auth.logout'][] = "Base_point::logout_point";

$config['controller[*].ready'][] = "Base_Action::action";*/
$config['eq_reserv_model.saved']['base_reserv_saved'] = 'Base_Reserv::on_eq_reserv_saved';

$config['eq_sample_model.saved']['base_sample_saved'] = 'Base_Sample::on_eq_sample_saved';

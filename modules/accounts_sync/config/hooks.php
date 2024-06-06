<?php
$config['accounts.notification.message'][] = 'Accounts_Sync::alarm_only_in_dir_labs';

//$config['controller[!accounts/account].ready'][] = 'Accounts_Sync::setup_account';

$config['lims_account_model.before_delete'][] = 'Accounts_Sync::before_lims_account_delete';

$config['lims_account.check_is_open'][] = 'Accounts_Sync::hooked_site_is_open';

$config['lims_account_model.before_save'][] = 'Accounts_Sync::lims_account_before_save';

$config['lims_account_model.saved'][] = 'Accounts_Sync::lims_account_saved';

<?php
class CLI_Support {
    static function preferences_sbmenu_mode () {
        $args = func_get_args();
        $value = in_array($args[0], ['list', 'icon']) ? $args[0] : 'icon';
        foreach (Q("user") as $user) {
            if ($user->sbmenu_mode && $user->sbmenu_mode != $value) {
                $user->sbmenu_mode = NULL;
                $user->save();
            }
        }
    }

    static function update_billing_account() {
        if (!Module::is_installed('billing')) return;
        $args = func_get_args();
        foreach ($args as $labId) {
            foreach (Q("lab#{$labId} billing_account") as $account) {
                $account->update_balance();
            }
        }
    }
}
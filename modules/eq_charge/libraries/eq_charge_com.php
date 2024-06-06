<?php

class EQ_Charge_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;
        $is_incharge = !!Q("{$me} equipment.incharge")->total_count();

        if ($is_incharge) {
            $components[] = [
                'id' => 'tollSum',
                'key' => 'tollSum',
                'name' => '使用收费总额',
            ];
        }

        $e->return_value = $components;
        return TRUE;
    }

    static function view_tollSum ($e, $query) {
        $me = L('ME');
        $title = '';
        
        if (Q("{$me} equipment.incharge")->total_count()) {
            $count = Q("({$me} equipment.incharge) eq_charge")->sum('amount');
            $title = '使用收费总额';
        }
        
        $view = V('eq_charge:components/tollSum', [
            'title' => $title,
            'count' => $count
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

}
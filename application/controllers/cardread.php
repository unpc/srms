<?php

class Cardread_Controller extends AJAX_Controller
{

    public function user()
    {
        $card_no              = Q::quote(Input::form('card_no'));
        $user                 = O('user', ['card_no' => $card_no]);
        Output::$AJAX['user'] = [
            'alt'  => $user->id,
            'text' => $GLOBALS['preload']['people.multi_lab'] ?
            T('%user (%lab)', ['%user' => $user->name, '%lab' => Q("$user lab")->current()->name]) :
            $user->name,
        ];
    }
}

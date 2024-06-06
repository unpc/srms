<?php

class LUA_Test extends Environment {

    static $_object;

    static function set_object($object) {
        self::$_object = $object;
    }

    static function fee() {
        $test = self::$_object;
        $lua = new EQ_Charge_LUA(self::$_object);
        $result =  $lua->run(['fee']);
        return (float) $result['fee'];
    }

    static function description() {
        $lua = new EQ_Charge_LUA(self::$_object);
        $result = $lua->run(['description']);
        return (string) $result['description'];
    }

    static function title() {
        return call_user_func_array(['Unit_Test', 'echo_title'], func_get_args());
    }

    static function assert() {
        return call_user_func_array(['Unit_Test', 'assert'], func_get_args());
    }

    static function make_billing_environment($equipment) {
        $dept_name = '财务部门';
        $dept = O('billing_department');
        $dept->name = $dept_name;
        Unit_Test::assert('准备财务部门', $dept->save());

        $equipment->billing_dept = $dept;        
        $equipment->save();    
    }
}

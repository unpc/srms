<?php

//faker 用于创建假数据
class Faker {

    private static $_equipment;
    //创建假的equipment
    static function equipment() {
        if (self::$_equipment) return self::$_equipment;
        $equipment = O('equipment');
        return self::$_equipment = $equipment;
    }

    private static $_calendar;
    //创建假的calendar
    static function calendar($parent, $type = NULL) {
        if (self::$_calendar) {
            $calendar = self::$_calendar;
        }
        else {
            self::$_calendar = $calendar = O('calendar');
        }

        $calendar->parent = $parent;
        if ($type) $calendar->type = $type;

        return $calendar;
    }

    private static $_cal_component;
    //创建假的component
    static function cal_component($calendar) {
        if (self::$_cal_component) {
            $cal_component = self::$_cal_component;
        }
        else {
            self::$_cal_component = $cal_component = O('cal_component');
        }

        $cal_component->calendar = $calendar;

        return $cal_component;
    }

    private static $_eq_record;
    //创建假的eq_record
    static function eq_record($equipment) {
        if (self::$_eq_record) {
            $eq_record = self::$_eq_record;
        }
        else {
            self::$_eq_record = $eq_record = O('eq_record');
        }

        $eq_record->equipment = $equipment;

        return $eq_record;
    }

    private static $_eq_sample;
    //创建假的eq_sample
    static function eq_sample($equipment) {
        if (self::$_eq_sample) {
            $eq_sample = self::$_eq_sample;
        }
        else {
            self::$_eq_sample = $eq_sample = O('eq_sample');
        }

        $eq_sample->equipment = $equipment;

        return $eq_sample;
    }

    private static $_eq_reserv;
    //创建假的eq_reserv
    static function eq_reserv($equipment) {
        if (self::$_eq_reserv) {
            $eq_reserv = self::$_eq_reserv;
        }
        else {
            self::$_eq_reserv = $eq_reserv = O('eq_reserv');
        }

        $eq_reserv->equipment = $equipment;

        return $eq_reserv;
    }

    private static $_eq_charge;
    //创建假的eq_charge
    static function eq_charge($source) {
        if (self::$_eq_charge) {
            $eq_charge = self::$_eq_charge;
        }
        else {
            self::$_eq_charge = $eq_charge = O('eq_charge');
        }

        $eq_charge->source = $source;

        return $eq_charge;
    }
}

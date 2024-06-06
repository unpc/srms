<?php

class Credit_Measures_Model extends ORM_Model
{
    const TYPE_REWARD     = 1; // 奖励
    const TYPE_PUNISHMENT = 0; // 惩罚

    public static $type = [
        self::TYPE_REWARD     => '奖励',
        self::TYPE_PUNISHMENT => '惩罚',
    ];
}

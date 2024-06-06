<?php

class WP_Sync {

    //按发表时间降序排列
    const TYPE_TIME_DESC = 1;
    //按发表时间升序排列
    const TYPE_TIME_ASC = 2;

    //同步publication的所有类型
    static $sync_publication_types = [
        self::TYPE_TIME_DESC => '按发表时间降序排列',
        self::TYPE_TIME_ASC => '按发表时间升序排列',
    ];
}

<?php
//修复课题组owner存在但是connect无关联的数据
require 'base.php';

foreach(Q('lab[atime>0]') as $lab){
    if($lab->owner->id){
        $lab->owner->connect($lab, 'pi');
    }
}
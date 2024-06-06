<?php
require "base.php";

$eq_sample = Q("eq_sample");
foreach ($eq_sample as $sample) {
    $start = Date::get_day_start($sample->ctime);
    $count = Q("eq_sample[ctime={$start}~{$sample->ctime}]")->total_count();
    $sample->serial_number = Date::format($sample->ctime, 'Ymd').'-'.Number::fill(($count),5);
    $sample->save();
}


#!/usr/bin/env php
<?php

require "base.php";

foreach (Q("eq_record") as $record) {
	if (!$record->equipment->id) {
		$record->delete();
	}
}

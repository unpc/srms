<?php

require 'base.php';

if (Q('user')->total_count() > 0) {
	echo 'SITE_ID=' . SITE_ID . ' ' . 'LAB_ID=' . LAB_ID . "\n";

}


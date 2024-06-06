<?php

error_reporting(0);

error_log(json_decode(file_get_contents('php://input')));

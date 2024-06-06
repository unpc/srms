#!/usr/bin/env php
<?php
/*
  清空 eq_chat (仪器监控聊天记录)
  (xiaopei.li@2012-08-15)
*/
require 'base.php';

Q('eq_chat')->delete_all();

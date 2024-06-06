#!/usr/bin/env php
<?php

require 'base.php';
$id = $argv[2];
$attendees = json_decode(base64_decode($argv[1]),TRUE);
$component = O('cal_component', $id);
foreach ($attendees as $key => $value) {
	$user = O('user', $key);
	Notification::send('schedule.add_event.to_attendee', $user, [
		'%user' => Markup::encode_Q($user),
		'%name' => H($component->name),
		'%description' => H($component->description),
		'%dtstart' => Date::format($component->dtstart),
		'%dtend' => Date::format($component->dtend),
		'%link' => URI::url('!schedule/index')
	]);
}


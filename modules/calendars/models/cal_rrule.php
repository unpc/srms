<?php
class Cal_RRule_Model extends ORM_Model {
/*
  NO.TASK#261 (xiaopei.li@2010.11.20)
*/
	 private $key_component;

	 function sync_components($key_component, $dtfrom, $dtto) {

		  $key_component->cal_rrule = $this;
		  $key_component->save();
		  $this->key_component = $key_component;
		  $calendar = $key_component->calendar;
		  $cal_rrule = $key_component->cal_rrule;

		  $rule = json_decode($this->rule);
		  $repeat_dt_pairs = $this->_parse_rule_to_dt_pairs($rule, $dtfrom, $dtto);

		  $messages = [];
		  $me = L('ME');

          foreach ($repeat_dt_pairs as $rdp) {
              list($repeat_dtstart, $repeat_dtend) = $rdp;

              if ($repeat_dtstart >= $key_component->dtstart && $repeat_dtend <= $key_component->dtend) continue;

              $mutex_file = Config::get('system.tmp_dir'). Misc::key('calendar'. $calendar->id);

              $fp = fopen($mutex_file, 'w+');

              if ($fp) {
                  if (flock($fp, LOCK_EX+LOCK_NB)) {

                      //符合自身预约规则，cal_rrule相同的预约，不算冲突
                      $repeat_component = Q("cal_component[calendar={$calendar}][id!={$key_component->id}][dtstart~dtend={$repeat_dtstart}|dtstart~dtend={$repeat_dtend}|dtstart={$repeat_dtstart}~{$repeat_dtend}][cal_rrule={$cal_rrule}]")->total_count();

                      if(!$repeat_component) {

                          $component = clone $key_component;

                          // new dtstart/dtend
                          $component->dtstart = $repeat_dtstart;
                          $component->dtend = $repeat_dtend;

                          //is_allowed_to('添加', $component)会判断重复预约，并提示错误信息
                          if ($me->is_allowed_to('添加', $component)) $component->save();

                          //用于处理rruel对应的component存储完成后的后期操作
                          Event::trigger('calendar.rrule_sub_component.saved', $component, $key_component);
                      }
                      flock($fp, LOCK_UN);
                  }

                  fclose($fp);
              }
          }

          return $messages;
	 }

	 function delete_components($dtfrom, $dtto) {

		  if (!$this->id) {
			   return;
		  }

		  $dtto = Date::next_time($dtto); // make sure to delete THE last day's component

		  $selector = "cal_component".
			   "[cal_rrule=$this]".
			   "[dtstart~dtend={$dtfrom}|dtstart~dtend={$dtto}|dtstart={$dtfrom}~{$dtto}]";

		  $components_to_delete = Q($selector);

		  $messages = [];
		  $me = L('ME');

		  foreach ($components_to_delete as $c2d) {
			   if ($me->is_allowed_to('删除', $c2d)) {
					$c2d->delete();
			   }
		  }
		  return $messages;
	 }

	 private function _rotate(&$array) {
		  // rotate an array
		  // e.g. {1, 2, 3} => 1, 2, 3, 1, 2, 3, ...
		  $item = current($array);
		  if (!next($array))
			   reset($array);
		  return $item;
	 }

	 private function _parse_rule_to_dt_pairs($rule, $dtfrom, $dtto) {
          // parse a rule to an array of dtstart/dtend pairs

		  $ONE_DAY = 86400;

		  $dtstart = $this->key_component->dtstart;
		  $dtend = $this->key_component->dtend;
		  $length = $dtend - $dtstart;

		  $repeat_dt_pairs = [];

		  $delta_days = ceil(($dtfrom - $dtstart) / $ONE_DAY);
		  $delta_secs = $delta_days * $ONE_DAY;

		  if ($rule->rtype == TM_RRule::RRULE_DAILY) {
			   // set daily intervals
			   $interval_day = $rule->rnum;
			   $interval_second_array = [$interval_day * $ONE_DAY];
		  }
		  else if ($rule->rtype == TM_RRule::RRULE_WEEKLY) {
			   list($week_days) = $rule->rrule;

			   // set weekly delta
			   $dtfrom_week_day_at = date('w', $dtfrom);
			   $ceil_week_day = $week_days[0] + 7;
			   $week_day_at = $week_days[0];

			   foreach ($week_days as $wd) {
					if ($wd >= $dtfrom_week_day_at) {
						 $ceil_week_day = $wd;
						 $week_day_at = $wd;
						 break;
					}
			   }

			   $delta_week_days = $ceil_week_day - $dtfrom_week_day_at;
			   $delta_secs += $delta_week_days * $ONE_DAY;

			   // set weekly intervals
			   $interval_week = $rule->rnum;
			   $interval_second_array = $this->_get_weekly_interval_second($interval_week, $week_days, $week_day_at);
		  }

		  // parse to dtstart/dtend pairs
		  $dtfrom = $dtstart + $delta_secs;
		  $interval_second = 0;

		  /*
		  do{
			   $repeat_dtstart = $dtfrom + $interval_second;
			   $repeat_dtend = $repeat_dtstart + $length;
			   $repeat_dt_pairs[] = array($repeat_dtstart, $repeat_dtend);

			   $dtfrom = $repeat_dtstart;
			   $interval_second = $this->_rotate($interval_second_array);

		  } while ($repeat_dtend < $dtto);
		  */

		  $repeat_dtstart = $dtfrom + $interval_second;
		  $repeat_dtend = $repeat_dtstart + $length;

          while ($repeat_dtend <= Date::get_day_end($dtto)) {
			  $repeat_dt_pairs[] = [$repeat_dtstart, $repeat_dtend];

			  $dtfrom = $repeat_dtstart;
			  $interval_second = $this->_rotate($interval_second_array);

			  $repeat_dtstart = $dtfrom + $interval_second;
			  $repeat_dtend = $repeat_dtstart + $length;
		  }

		  return $repeat_dt_pairs;
	 }

	 private function _get_weekly_interval_second($interval_week, $week_days, $week_day_at) {

		  $interval_days = [];

		  if (count($week_days) == 1) {
			   // one day per week
			   $interval_days[] = $interval_week *  7;
		  }
		  else {
			   // many days per week
			   $week_days_before = [];
			   $pos = array_search($week_day_at, $week_days);
			   for ($i = 0; $i <= $pos; $i++) {
					$week_days_before[] = $week_days[$i] + $interval_week * 7;
			   }
			   $week_days = array_slice($week_days, $pos);
			   $week_days = array_merge($week_days, $week_days_before);
			   for ($i = 1, $size = count($week_days); $i < $size; $i++) {
					$interval_days[] = $week_days[$i] - $week_days[$i-1];
			   }
		  }
		  $interval_second_array = [];
		  foreach ($interval_days as $interval_day) {
			   $interval_second_array[] = $interval_day * 86400;
		  }
		  return $interval_second_array;
	 }

	 /*
	 //事件重复类型
	 const FREQ_SECONDLY = 0;
	 const FREQ_MINUTELY = 1;
	 const FREQ_HOURLY = 2;
	 const FREQ_DAILY = 3;
	 const FREQ_WEEKLY = 4;
	 const FREQ_MONTHLY = 5;
	 const FREQ_YEARLY = 6;
	 */
}

/*
  byseclist  = [seconds, seconds, ...]
  seconds    = 0 to 59

  byminlist  = [minutes, minutes, ...]
  minutes    = 0 to 59

  byhrlist   = [hour, hour, ...]
  hour       = 0 to 23

  bywdaylist = [weekdaynum, weekdaynum, ...]
  weekdaynum = [weekday, ordwk]
  ordwk      = -/+ 1 to 53
  weekday    = 0 to 6

  bymodaylist = [[monthdaynum, ...], bywknolist, bysplist]
  monthdaynum   = 0 to 30
  bywknolist = [weekdaynum, ...]

  byyrdaylist = [[yeardaynum, ...], bymolist, bywknolist]
  yeardaynum = 1 to 366
  bymolist   = [monthnum, ...]
  monthnum   = 1 to 12
  bywknolist = [weekdaynum, ...]

  bysplist   = [setposday, ...]
  setposday  = yeardaynum  -/+ 1~366
*/

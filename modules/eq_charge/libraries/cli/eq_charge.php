<?php

class CLI_EQ_Charge {

	static function check($dtstart = 0) {
		$dtend = (dtstart == 0) ? strtotime(date('Y-m-d')) : $dtstart ;
		$dtstart = strtotime(date('Y-m-d')) - 86400;
		$db = Database::factory();

		$data = $db->query(
		'SELECT `e`.`id` AS `id1`, `b`.`id` AS `id2` 
		FROM `eq_charge` AS `e`
		JOIN `billing_transaction` AS `b` ON `e`.`transaction_id` = `b`.`id`
		WHERE 
		`e`.`amount` <> `b`.`outcome` 
		AND `e`.`auto_amount` <> `b`.`outcome`
		AND ctime between %d AND %d', 
		$dtstart, $dtend);
		
		$data = $data ? $data->rows('assoc') : [];

		if ($data) foreach($data as $d) {
			$charge_id = $d['id1'] ? : 0;
			$billing_id = $d['id2'] ? : 0;
			$body = I18N::T('eq_charge', '提醒: 仪器收费[%charge_id]与财务明细[%billing_id]不符!', [
					'%charge_id' => $charge_id,
					'%billing_id' => $billing_id,
				]);

			$mail = new Email();
			$mail->to(Config::get('system.email_address'));
			$mail->subject($body);
			$mail->body(NULL, $body);
			$mail->send();
		}
	}
}

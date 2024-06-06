<?php

class XMLAPI_Controller extends Controller {

	function platformDesc() {
		$form = Input::form();
		if (!Cers::verifyXMLAPI($form['UserName'], $form['Password'])) {
			echo 'User Verify Failed!';
			exit; return;
		};

		header('Content-Type: text/xml');
		if (file_exists(Cers::getLabPrivateFile('Platform.xml'))) {
			echo file_get_contents(Cers::getLabPrivateFile('Platform.xml'));
		}
		else {
			echo Cers::getSchoolInfo();
		}
		exit;
	}

	function instrusShare() {
		$form = Input::form();
		if (!Cers::verifyXMLAPI($form['UserName'], $form['Password'])) {
			echo 'User Verify Failed!';
			exit; return;
		};

		header('Content-Type: text/xml');
		if (file_exists(Cers::getLabPrivateFile('InstrusAndGroups.xml'))) {
			echo file_get_contents(Cers::getLabPrivateFile('InstrusAndGroups.xml'));
		}
		else {
			echo Cers::getSchoolRoot();
		}
		
		exit;
	}

	function instrusShareEffect() {
		$form = Input::form();
		if (!Cers::verifyXMLAPI($form['UserName'], $form['Password'])) {
			echo 'User Verify Failed!';
			exit; return;
		};

		header('Content-Type: text/xml');
		if (file_exists(Cers::getLabPrivateFile('ShareEffect.xml'))) {
			echo file_get_contents(Cers::getLabPrivateFile('ShareEffect.xml'));
		}
		else {
			echo Cers::getShareEffect();
		}

		exit;
	}
}
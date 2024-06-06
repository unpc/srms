<?php

class Notification_Batch_Model extends ORM_Model {
	const STATUS_QUEUEING = 0;
	const STATUS_READY = 1;
	const STATUS_EXTRACTED =2;
}
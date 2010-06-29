<?php
class Log extends AppModel {
	var $name = 'Log';
	var $useTable = 'log';
	var $primaryKey = 'log_id';
	
	var $order = "Log.ts_update DESC";
}
?>
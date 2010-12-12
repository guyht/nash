<?php
/*
 * Nash Backup Script
 */

// Determine year, month, day and hour
$year  = date('Y');
$month = date('m');
$day   = date('d');
$hour  = date('H');

echo $month." - ".$day." - ".$hour;

// Build hourly file file name
$fname = 'Hourly-'.$hour.'.sql';

// Create backup
$out = array();
$cmd = 'add command here';
exec($cmd, $out);

?>

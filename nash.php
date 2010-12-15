<?php
/*
 * Nash Backup Script
 */

// Determine year, month, day and hour
$year       = date('Y');
$month      = date('m');
$day        = date('d');
$hour       = date('H');
$yesterday  = date('d', time() - 48*60*60);

// Load configs
$config = parse_ini_file('nash.ini');

// Build hourly file file name
$fnameHourly = 'Hourly-'.$hour.'.sql';
$fnameDaily = 'Daily-'.$year.'-'.$month.'-'.$day.'-'.$hour.'.sql';
$basedirHourly = dirname(__FILE__).'/hourly';
$basedirDaily  = dirname(__FILE__).'/daily';

// Make sure base directories are present and if not create them
if (!is_dir($basedirHourly)) {
	mkdir($basedirHourly, 0744);
}

if (!is_dir($basedirDaily)) {
	mkdir($basedirDaily, 0744);
}

// Make todays directory if it does not exist
if (!is_dir($basedirHourly.'/'.$day)) {
	mkdir($basedirHourly.'/'.$day, 0744);
}

// Remove yesterdays directory if it exists
if (is_dir($basedirHourly.'/'.$yesterday))
{
	// Directory exists so delete everything in it
	foreach (glob($basedirHourly.'/'.$yesterday.'/*') as $filename) {
		unlink($filename);
	}

	// Now delete the directory
	rmdir($basedirHourly.'/'.$yesterday);
}

// Create backup
$cmd = 'mysqldump '.$config['database'].' -u '.$config['username'].' -p'.$config['password'];
$path = $basedirHourly.'/'.$day.'/'.$fnameHourly;
exec($cmd.' > '.$path);

// Is this the daily backup
if ($hour == $config['dailyBackupHour']) {
	$path = $basedirDaily.'/'.$fnameDaily;
	exec($cmd.' > '.$path);
}

?>

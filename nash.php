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

$ext = '.sql';
$gzip = '';

// Use gzip?
if ($config['use_gzip'] == 'true') {
	$gzip = ' | gzip';
	$ext .= '.gz';
}

// Build hourly file file name
$fnameHourly = 'Hourly-'.$hour.$ext;
$fnameDaily = 'Daily-'.$year.'-'.$month.'-'.$day.'-'.$hour.$ext;
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
exec($cmd.$gzip.' > '.$path);

// Is this the daily backup
if ($hour == $config['dailyBackupHour']) {
	$path = $basedirDaily.'/'.$fnameDaily;
	exec($cmd.$gzip.' > '.$path);

	// Now send to AmazonS3 if enabled
	if ($config['use_s3'] == 'true') {
		require_once dirname(__FILE__).'aws-sdk-for-php/sdk.class.php';
		$s3 = new AmazonS3($config['aws_key'], $config['aws_secret_key']);
		$bucket = 'nash-backup/daily';
		// Check to see if bucket already exists
		$exists = $s3->if_bucket_exists($bucket);
		if (!$exists) {
			$create_bucket_response = $s3->create_bucket($bucket, AmazonS3::REGION_US_W1);
			if ($create_bucket_response->isOK()){
				$exists = $s3->if_bucket_exists($bucket);
				while (!$exists)
				{
					// Not yet? Sleep for 1 second, then check again
					sleep(1);
					$exists = $s3->if_bucket_exists($bucket);
				}
			}
		}

		// Add the file to the bucket
		$s3->create_object($bucket, $fnameDaily, array(
			'fileUpload' => $path
		));
	}
}

?>

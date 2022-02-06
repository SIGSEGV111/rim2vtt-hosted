<?php

header("Content-Type: application/json");

try
{
	if(!array_key_exists("savegame", $_FILES) || $_FILES['savegame']['error'] != UPLOAD_ERR_OK || $_FILES['savegame']['size'] <= 0)
		throw new Exception("savegame upload failed");

	$upload_rws_file = $_FILES['savegame']['tmp_name'];
	$upload_rws_stream = fopen($upload_rws_file, "r");
	if($upload_rws_stream === false)
	{
		unlink($upload_rws_file);
		throw new Exception("unable to open uploaded file");
	}

	unlink($upload_rws_file);

	$process = proc_open('exec /srv/www/rim2vtt', array(
		0 => $upload_rws_stream, // STDIN
		1 => array('pipe', 'w'), // STDOUT
		2 => array('pipe', 'w')  // STDERR
	), $pipes);

	if(!is_resource($process))
		throw new Exception("failed to create rim2vtt process");

	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_code = proc_close($process);
	if($return_code != 0)
		throw new Exception("rim2vtt failed with exit-code $return_code\n--------------------------------------------\n$stderr\n--------------------------------------------\n$stdout");

	$stderr = json_encode($stderr);
	echo("{\"log\":$stderr,\"status\":true,\"uvtt\":$stdout}");
}
catch(Exception $e)
{
	http_response_code(500);
	$error_json = json_encode($e->getMessage(), JSON_UNESCAPED_LINE_TERMINATORS);
	echo("{\"log\":$error_json,\"status\":false,\"uvtt\":null}");
}
?>

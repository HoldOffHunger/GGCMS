<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/DBAccess.php');
	clireq('traits/DNSRecords.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	
	class MySQLConnector {
		use DBAccess;
		use DNSRecords;
		use CLIAccess;
		use GlobalsTrait;
		
		public function connect() {
			$this->setHandle();
			$this->setGlobals();
			$this->setMySQLArgs();
			
			$mysql_connect_command = 'mysql ';
			
		#	return pnctl_exec('/usr/bin/mysql');
		#	return pcntl_exec($mysql_connect_command);
			#return shell_exec($mysql_connect_command);
			
	#		print("BT:!" . $create_database_command . "|");
	
		#	$pipes = [];
			
$descriptors = [
    0 => ["pipe", "r+"],  // STDIN
    1 => ["pipe", "w"],  // STDOUT
    2 => ["pipe", "w"],  // STDERR
];
			
		#	$proc = proc_open($mysql_connect_command, $descriptors, $pipes);

#$stdin = strtolower(trim(fgets($this->handle)));
#$stdin = "select 'HEY!';";

/*


while($stdin = strtolower(trim(fgets($this->handle)))) {
			$proc = proc_open($mysql_connect_command, $descriptors, $pipes);
fwrite($pipes[0], $stdin);
fclose($pipes[0]);

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);

print_r($stdout);
print_r($stderr);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);
}

*/

ob_implicit_flush(true);
/*
posix_mkfifo("stdinpipe", 0777);
posix_mkfifo("stdoutpipe", 0777);
posix_mkfifo("stderrpipe", 0777);

fopen('stdinpipe','r+'),
fopen('stdoutpipe','r+'),
fopen('stderrpipe','r+')
*/
$pipes = [
];

			$proc = proc_open($mysql_connect_command, $descriptors, $pipes);

#fwrite($pipes[0], "select 'hey';" . PHP_EOL);
#
#fclose($pipes[0]);
#$stdout = stream_get_contents($pipes[1]);
#fclose($pipes[1]);
#print($stdout);
/*
stream_set_blocking($pipes[0], false);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

stream_set_timeout($pipes[0], 1);
stream_set_timeout($pipes[1], 1);
stream_set_timeout($pipes[2], 1);*/

$stderr = '';
$stdout = '';

while($stdin = trim(fgets($this->handle))) {
#while(stream_select($pipes[1], $pipes[0], $pipes[2], 0) !== FALSE) {
fwrite($pipes[0], $stdin . PHP_EOL);

#fdatasync($pipes[0]);
#fclose($pipes[0]);
#stream_copy_to_stream(fopen('stdinpipe','r+'), $pipes[0]);

print("BT: written!" . feof($pipes[2]) . "|");


#print_r(stream_get_meta_data($pipes[0]));

$stderr = stream_get_contents($pipes[2]);
#fclose($pipes[2]);
print("BT: ERR!");
print_r($stderr);
$stdout = stream_get_contents($pipes[1]);

#fclose($pipes[1]);
print("huh...");

print_r($stdout);
print_r($stderr);
print("BT: Contents obtained!");
#}
}
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);


			
			print('___________' . PHP_EOL);
		#	print($stderr);
			print('___________' . PHP_EOL);
			
		#	print($this->formatTable(['output'=>$output]));
			
			return TRUE;
		}
		
		public function bannerMessageText() {
			return 'MySQL Connect';
		}
	}

?>
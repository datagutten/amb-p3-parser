<?Php
//Capture data from a AMB decoder using a socket
require 'vendor/autoload.php';
use datagutten\amb\parser\socket;

if(isset($argv[1]) && file_exists($conf="config_socket_{$argv[1]}.php"))
	$config = require $conf;
else
	$config = require 'config_socket.php';

$file_path = __DIR__.'/socket_data/'.$argv[1];
if(!file_exists($file_path))
	mkdir($file_path, 0777, true);

$socket = new socket($config['address'], $config['port']);

while (true) {
    $record = $socket->read();

	$type=dechex(ord(substr($record,0x08,1)));
	if(ord(substr($record,0,1))!=0x8E || ord(substr($record,-1,1))!=0x8F || $type!=0x02) //Do not save packets with only status message
	{
		$date=date('Y-m-d H:i');
		echo "$date: Saved data: $type\n";
		file_put_contents($file_path.'/'.time(),$record);
	}
}
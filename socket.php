<?Php
//Capture data from a AMB decoder using a socket
if(isset($argv[1]) && file_exists($conf="config_socket_{$argv[1]}.php"))
	require $conf;
else
	require 'config_socket.php';
if(!file_exists($file_path))
	mkdir($file_path);

$socket=socket_create(AF_INET,SOCK_STREAM,0);
$result = socket_connect($socket,$address,$port) or die("Could not connect to server\n");

while (true) {
    $record = socket_read ($socket, 1024);
	if($record===false)
	{
		echo "Could not read server response\n";
		continue;
	}

	$type=dechex(ord(substr($record,0x08,1)));
	if(ord(substr($record,0,1))!=0x8E || ord(substr($record,-1,1))!=0x8F || $type!=0x02) //Do not save packets with only status message
	{
		$date=date('Y-m-d H:i');
		echo "$date: Saved data: $type\n";
		file_put_contents($file_path.'/'.time(),$record);
	}
}
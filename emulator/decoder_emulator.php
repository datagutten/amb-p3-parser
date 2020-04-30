<?Php
if(!file_exists($argv[1]))
    die("Data folder does not exist\n");
echo "Emulator started\n";
$socket=socket_create(AF_INET,SOCK_STREAM,0);
socket_bind($socket,'127.0.0.1',5403);
socket_listen($socket);
var_dump('check');
$socket2=socket_accept($socket);
//$result = socket_connect($socket,$address,$port) or die("Could not connect to server\n");
//$buffer=socket_read ($socket, 1024);

foreach(scandir($dir=$argv[1]) as $file)
{
	if(!is_file($dir.'/'.$file))
		continue;
	$status = @socket_write($socket2,file_get_contents($dir.'/'.$file));
	if($status===false) {
	    $code = socket_last_error($socket2);
        $error = error_get_last();

        if($code===32) {
            echo "Error, reopen socket\n";
            $socket2 = socket_accept($socket);
            continue;
        }
        else
            die($error['message']);
    }
	echo "Send $file\n";
	sleep(1);
}
echo "No more data\n";
<?php


namespace datagutten\amb\parser;


use datagutten\amb\parser\exceptions\ConnectionError;

class socket
{
    /**
     * @var string Read buffer
     */
    public $buffer = '';
    /**
     * @var resource Socket resource
     */
    protected $socket;

    /**
     * socket constructor.
     * @param string $address Decoder address (DNS or IP)
     * @param int $port Decoder port (Usually 5403)
     * @throws ConnectionError
     */
    function __construct($address, $port = 5403)
    {
        $this->socket=socket_create(AF_INET,SOCK_STREAM,0);
        $result=@socket_connect($this->socket,$address,$port);
        if($result===false)
        {
            $error = error_get_last();
            throw new ConnectionError(sprintf('Could not open connection to decoder %s on port %d: %s',$address, $port, $error['message']));
        }

        //$this->buffer=socket_read ($this->socket, 1024);
        $this->read();
        //Remove incomplete data at the beginning
        if(substr($this->buffer,0,1)!=chr(0x8E))
            $this->buffer=substr($this->buffer,strpos($this->buffer,chr(0x8E)));
    }

    /**
     * Read from socket and save in buffer
     * @return string Data from socket
     * @throws ConnectionError
     */
    function read()
    {
        $data = @socket_read($this->socket, 1024);
        if($data===false)
        {
            $error = error_get_last();
            throw new ConnectionError($error['message']);
        }

        $this->buffer .= $data;
        return $data;
    }

    /**
     * Read from socket and parse records
     * @throws ConnectionError
     */
    function read_records()
    {
        //No complete data in buffer, read more
        /*if(($endpos=strpos($this->buffer,chr(0x8F)))===false)
            $this->read();*/
        if(parser::find_end($this->buffer)===false)
            $this->read();

        if(empty($this->buffer))
            return [];

        //$data_end=strrpos($this->buffer,chr(0x8F)); //Get position of last end byte
        $data_end = parser::find_last_end($this->buffer); //Get position of last end byte
        $records = parser::get_records($this->buffer);
        $this->buffer=substr($this->buffer,$data_end+1); //Remove data from buffer
        return $records;
    }

    /**
     * Close the socket
     */
    function __destruct()
    {
        socket_close($this->socket);
    }
}
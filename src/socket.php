<?php


namespace datagutten\amb\parser;


use amb_p3_parser;
use amb_p3_parser as parser;
use RuntimeException;

class socket
{
    public $buffer = '';
    public $socket;
    /**
     * @var amb_p3_parser
     */
    public $parser;
    function __construct($address, $port)
    {
        $this->socket=socket_create(AF_INET,SOCK_STREAM,0);
        $result=@socket_connect($this->socket,$address,$port);
        if($result===false)
        {
            $error = error_get_last();
            throw new RuntimeException(sprintf('Could not open connection to decoder %s on port %d: %s',$address, $port, $error['message']));
        }

        //$this->buffer=socket_read ($this->socket, 1024);
        $this->read();
        //Remove incomplete data at the beginning
        if(substr($this->buffer,0,1)!=chr(0x8E))
            $this->buffer=substr($this->buffer,strpos($this->buffer,chr(0x8E)));
        $this->parser = new amb_p3_parser();
    }

    /**
     * Read from socket and save in buffer
     * @return string Data from socket
     */
    function read()
    {
        $data = @socket_read($this->socket, 1024);
        if($data===false)
        {
            $error = error_get_last();
            throw new RuntimeException($error['message']);
        }

        $this->buffer .= $data;
        return $data;
    }

    /**
     * Read from socket and parse records
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
        $records = $this->parser->get_records($this->buffer);
        $this->buffer=substr($this->buffer,$data_end+1); //Remove data from buffer
        return $records;
    }

    function __destruct()
    {
        socket_close($this->socket);
    }
}
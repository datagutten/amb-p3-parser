<?php

use datagutten\amb\parser\socket;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class socketTest extends TestCase
{
    /**
     * @var Process
     */
    public $emulator;
    public $port;
    function setUp(): void
    {
        $this->port = rand(5404,6000);
        $data = realpath(__DIR__.'/../emulator/emulator_data');
        $emulator_script = realpath(__DIR__.'/../emulator/decoder_emulator.php');
        $this->emulator = new Process(['php', $emulator_script, $data, $this->port]);
        $this->emulator->start();
        sleep(1);
        //printf("Started emulator on port %d\n", $this->port);
        if(PHP_OS!='WINNT') {
            file_put_contents('debug_ps.txt', shell_exec('ps aux'));
            file_put_contents('debug_netstat.txt', shell_exec('netstat -anp|grep 127.0.0.1:'.$this->port));
        }
    }

    function tearDown(): void
    {
        $this->emulator->stop();
    }

    function testConnect()
    {
        if(!$this->emulator->isRunning())
            $this->markTestSkipped('Emulator is not running');

        $socket = new socket('127.0.0.1', $this->port);
        $records = $socket->read_records();

        $this->assertIsArray($records);
        $this->assertEquals(0, amb_p3_parser::find_start($records[0]));
        $this->assertEquals(strlen($records[0])-1, amb_p3_parser::find_end($records[0]));
    }

    function testConnectInvalid()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not open connection to decoder 127.0.0.1 on port 9999');
        new socket('127.0.0.1', 9999);
    }

    function testIncompleteData()
    {
        $socket = new socket('127.0.0.1', $this->port);
        $socket->buffer = ''; //Empty buffer
        $records = $socket->read_records();

        $this->assertIsArray($records);
        $this->assertEquals(0, amb_p3_parser::find_start($records[0]));
        $this->assertEquals(strlen($records[0])-1, amb_p3_parser::find_end($records[0]));
    }

    function testLostConnection()
    {
        if(!$this->emulator->isRunning())
            $this->markTestSkipped('Emulator is not running');
        if(PHP_OS!=='WINNT')
            $this->markTestSkipped('This test is currently only working on windows');

        $socket = new socket('127.0.0.1', $this->port);
        $socket->read_records();
        $this->emulator->stop();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unable to read from socket');
        $socket->read_records();
    }
}

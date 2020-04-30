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
    function setUp(): void
    {
        $data = realpath(__DIR__.'/../emulator/emulator_data');
        $emulator_script = realpath(__DIR__.'/../emulator/decoder_emulator.php');
        $this->emulator = new Process(['php', $emulator_script, $data]);
        $this->emulator->start();
    }

    function tearDown(): void
    {
        $this->emulator->stop();
    }

    function testConnect()
    {
        if(!$this->emulator->isRunning())
            $this->markTestSkipped('Emulator is not running');

        $socket = new socket('127.0.0.1', '5403');
        $records = $socket->read_records();

        $this->assertIsArray($records);
        $this->assertEquals(0, amb_p3_parser::find_start($records[0]));
        $this->assertEquals(strlen($records[0])-1, amb_p3_parser::find_end($records[0]));
    }

    function testLostConnection()
    {
        if(!$this->emulator->isRunning())
            $this->markTestSkipped('Emulator is not running');

        $socket = new socket('127.0.0.1', '5403');
        $socket->read_records();
        $this->emulator->stop();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unable to read from socket');
        $socket->read_records();
    }
}

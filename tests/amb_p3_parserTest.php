<?php


use amb_p3_parser as parser;
use datagutten\amb\parser\exceptions\AmbParseError;
use PHPUnit\Framework\TestCase;


class amb_p3_parserTest extends TestCase
{

    public function testTrim_data()
    {
        $trimmed = parser::trim_data(sprintf('asdf%1$sdfgfg%2$sllkk%1$sff%2$skk', chr(0x8E), chr(0x8F)));
        $this->assertEquals(chr(0x8E).'dfg', substr($trimmed, 0,4));
        $this->assertEquals('ff'.chr(0x8F), substr($trimmed, -3, 3));

        $data = file_get_contents(__DIR__.'/test_data/need_trimming');
        $trimmed = parser::trim_data($data);
        $this->assertEquals(chr(0x8E).chr(0x02).chr(0x33), substr($trimmed, 0,3));
        $this->assertEquals(chr(0x02).chr(0x00).chr(0x8F), substr($trimmed, -3,3));
    }

    public function testRead_fields()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $fields=parser::$messages[1] + parser::$messages['general'];
        $record = parser::read_fields($data, $fields);
        $fields_ref = [
            'PASSING_NUMBER'=>113736,
            'TRANSPONDER'=>2906141,
            'RTC_TIME'=>1437769372993000,
            'STRENGTH'=>106,
            'HITS'=>50,
            'FLAGS'=>0,
            'DECODER_ID'=>135059];
        $this->assertEquals($fields_ref, $record);
    }

    public function testUnknownField()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $data[0xA] = chr(0x9);

        $this->expectException(AmbParseError::class);
        $this->expectExceptionMessage('Unknown field ID at position a: 9');
        $fields = parser::$messages[1] + parser::$messages['general'];
        parser::read_fields($data, $fields);
    }

    public function testParse()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $passing = parser::parse($data);
        $passing_ref = [
            'version'=>2,
            'length'=>51,
            'crc'=>'c79e',
            'flags_header'=>0000,
            'type'=>1,
            'record_hex'=>'8e0233009ec700000100010448bc010003041d582c000408e805bfc4a41b050005026a0006023200080200008104930f02008f',
            'PASSING_NUMBER'=>113736,
            'TRANSPONDER'=>2906141,
            'RTC_TIME'=>1437769372993000,
            'STRENGTH'=>106,
            'HITS'=>50,
            'FLAGS'=>0,
            'DECODER_ID'=>135059
        ];
        $this->assertEquals($passing_ref, $passing);
    }

    /**
     * @requires PHPUnit 8.0
     */
    public function testInvalidRecord()
    {
        $this->expectException(AmbParseError::class);
        $this->expectExceptionMessage('Invalid record');
        parser::parse('asdf');
    }


    public function testUnknownType()
    {
        $data = file_get_contents(__DIR__.'/test_data/bad_passing');
        $data[0x8] = chr(0x99);
        $data[0x9] = chr(0x99);
        $this->expectException(AmbParseError::class);
        $this->expectExceptionMessage('Unkown record type');
        parser::parse($data);
    }

    public function testTypeNotParsable()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $data[0x8] = chr(0x45);
        $data[0x9] = chr(0x00);
        $record = parser::parse($data);
        $this->assertFalse(isset($record['DECODER_ID']));
    }

    public function testInvalidLength()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $data[0x2] = chr(0x9);
        $data[0x3] = chr(0x9);
        $this->expectException(AmbParseError::class);
        $this->expectExceptionMessage('Length is not matching. Strlen=51, length=2313');
        parser::parse($data);
    }

    public function testFilteredType()
    {
        $data = file_get_contents(__DIR__.'/test_data/status');
        $record = parser::parse($data, 0x01);
        $this->assertTrue($record);
    }

    public function testParse_header()
    {
        $data = file_get_contents(__DIR__.'/test_data/good_passing');
        $header = parser::parse_header($data);
        $header_ref = [
            'version'=>2,
            'length'=>51,
            'crc'=>'c79e',
            'flags_header'=>0000,
            'type'=>1,
            'record_hex'=>'8e0233009ec700000100010448bc010003041d582c000408e805bfc4a41b050005026a0006023200080200008104930f02008f'
        ];
        $this->assertEquals($header_ref, $header);
    }

    public function testUnescape()
    {
        $data = file_get_contents(__DIR__.'/test_data/1437857960_0');
        $record = parser::unescape($data);
        $this->assertEquals(chr(0x8F), substr($record, 5,1));
    }

    public function testFormat_value()
    {
        $string = parser::format_value(chr(0x0F));
        $this->assertEquals(15, $string);

        $string = parser::format_value(chr(0x0F).chr(0xFF));
        $this->assertEquals(0xFF0F, $string);

        $string = amb_p3_parser::format_value(chr(0x0F).chr(0xFF), false, false);
        $this->assertEquals(0x0FFF, $string);

        $string = parser::format_value(chr(0x0F).chr(0xFF), true);
        $this->assertEquals('ff0f', $string);
    }

    public function testGet_records()
    {
        $data = file_get_contents(__DIR__.'/test_data/multi');
        $records = parser::get_records($data);
        $this->assertCount(10, $records);
    }

    public function testFind_start()
    {
        $data = file_get_contents(__DIR__.'/test_data/need_trimming');
        $this->assertEquals(9, parser::find_start($data));
    }

    public function testFind_end()
    {
        $data = file_get_contents(__DIR__.'/test_data/multi');
        $this->assertEquals(50, parser::find_end($data));
    }

    public function testFind_last_end()
    {
        $data = file_get_contents(__DIR__.'/test_data/multi');
        $this->assertEquals(509, parser::find_last_end($data));
    }
}

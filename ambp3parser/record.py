import datetime
import logging
import struct
from abc import ABC
from decimal import Decimal
from typing import List

logger = logging.getLogger(__name__)


class AMBRecord(ABC):
    fields: dict
    # Header fields
    VERSION: int
    LENGTH: int
    CRC: int
    FLAGS: int
    TOR: int
    # Common fields
    DECODER_ID: int
    CONTROLLER_ID: int
    REQUEST_ID: int

    fields_common = {
        0x81: 'DECODER_ID',
        0x83: 'CONTROLLER_ID',
        0x85: 'REQUEST_ID'
    }

    @classmethod
    def parse_record(cls, data: bytes):
        if not cls.valid(data):
            raise RuntimeError('Invalid record')
        data = cls.unescape(data)
        tor, = struct.unpack('<H', data[8:10])
        return record_classes.get(tor, cls)(data)

    @classmethod
    def parse_multiple_records(cls, data: bytes) -> List[AMBRecord]:
        records = []
        pos = 0
        while pos < len(data):
            if data[pos] == 0x8e:  # Start of record
                end = data[pos:].find(b'\x8f')
                record_bytes = data[pos:pos + end + 1]
                records.append(cls.parse_record(record_bytes))
                pos = pos + end + 1
            else:
                pos += 1
        return records

    def __init__(self, data: bytes):
        self.data = data
        self.VERSION, self.LENGTH, self.CRC, self.FLAGS, self.TOR = self.parse_header(data)
        if hasattr(self, 'fields'):
            self.fields.update(self.fields_common)
            self.parse_fields()
        else:
            logger.warning('Unknown record type %d' % self.TOR)
            self.fields = self.fields_common

    @staticmethod
    def unescape(data: bytes):
        pos = 0
        data_out = bytearray()
        while pos < len(data):
            char = data[pos]
            if char == 0x8d:
                data_out.append(data[pos + 1] - 0x20)
                pos += 2
            else:
                data_out.append(char)
                pos += 1
        return data_out

    @staticmethod
    def valid(data):
        return data[0] == 0x8e and data[-1] == 0x8f

    @staticmethod
    def parse_header(record: bytes):
        return struct.unpack('<BHHHH', record[1:10])

    def parse_fields(self):
        formats = {
            1: '<B',
            2: '<H',
            4: '<I',
            8: '<Q',
        }
        pos = 0xa
        while pos < len(self.data) - 1:
            field_id, length = struct.unpack('<BB', self.data[pos:pos + 2])
            field_name = self.fields.get(field_id)
            if field_name:
                value_raw = self.data[pos + 2:pos + 2 + length]
                data_format = formats.get(length)
                if not data_format:
                    raise RuntimeError('Invalid field length %d' % length)
                value, = struct.unpack(data_format, value_raw)
                setattr(self, field_name, value)
            else:
                logger.warning('Unknown field %d' % field_id)
            pos = pos + 2 + length
            pass


class Passing(AMBRecord):
    def __init__(self, data: bytes):
        super().__init__(data)

    timestamp: int
    TRANSPONDER: int
    STRENGTH: int
    HITS: int
    PASSING_NUMBER: int
    RTC_TIME: int
    DRIVER_ID: int
    VOLTAGE: int
    TEMPERATURE: int

    fields = {
        0x01: 'PASSING_NUMBER',
        0x03: 'TRANSPONDER',
        0x04: 'RTC_TIME',
        0x10: 'UTC_TIME',
        0x05: 'STRENGTH',
        0x06: 'HITS',
        0x08: 'FLAGS',
        0x0a: 'TRAN_CODE',
        0x0e: 'USER_FLAG',
        0x0f: 'DRIVER_ID',
        0x14: 'SPORT',
        0x30: 'VOLTAGE',
        0x31: 'TEMPERATURE',
    }

    @property
    def timestamp(self):
        return Decimal(self.RTC_TIME) / 1000000

    @property
    def timestamp_ms(self):
        return Decimal(self.RTC_TIME) / 1000

    @property
    def datetime(self):
        return datetime.datetime.fromtimestamp(int(self.timestamp))


class Status(AMBRecord):
    fields = {
        0x01: 'NOISE',
        0x06: 'GPS',
        0x07: 'TEMPERATURE',
        0x0A: 'SATINUSE',
        0x0B: 'LOOP_TRIGGERS',
        0x0C: 'INPUT_VOLTAGE'
    }
    NOISE: int
    GPS: int
    TEMPERATURE: int
    SATINUSE: int
    LOOP_TRIGGERS: int
    INPUT_VOLTAGE: int

    @property
    def voltage(self):
        return Decimal(self.INPUT_VOLTAGE) / 10


record_classes = {
    0x01: Passing,
    0x02: Status,
}

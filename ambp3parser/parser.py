import struct

record_types = {
    0x00: 'RESET',
    0x01: 'PASSING',
    0x02: 'STATUS',
    0x45: 'FIRST_CONTACT',
    0xFFFF: 'ERROR',
    0x03: 'VERSION',
    0x04: 'RESEND',
    0x05: 'CLEAR_PASSING',
    0x18: 'WATCHDOG',
    0x20: 'PING',
    0x2d: 'SIGNALS',
    0x13: 'SERVER_SETTINGS',
    0x15: 'SESSION',
    0x28: 'GENERAL_SETTINGS',
    0x2f: 'LOOP_TRIGGER',
    0x30: 'GPS_INFO',
    0x4a: 'TIMELINE',
    0x24: 'GET_TIME',
    0x16: 'NETWORK_SETTINGS'
}

messages = {
    0x01: {
        0x01: 'PASSING_NUMBER',
        0x03: 'TRANSPONDER',
        0x04: 'RTC_TIME',
        0x05: 'STRENGTH',
        0x06: 'HITS',
        0x08: 'FLAGS'
    },
    0x02: {
        0x01: 'NOISE',
        0x06: 'GPS',
        0x07: 'TEMPERATURE',
        0x0A: 'SATINUSE',
        0x0B: 'LOOP_TRIGGERS',
        0x0C: 'INPUT_VOLTAGE'
    },
    'general': {
        0x81: 'DECODER_ID',
        0x83: 'CONTROLLER_ID',
        0x85: 'REQUEST_ID'
    }
}


def trim_data(data):
    start = find_start(data)
    end = find_last_end(data)


def valid(data: bytes) -> bool:
    """
    Verify that the provided string is a complete record
    :param data:
    :return:
    """
    return data[0] == 0x8e and data[-1] == 0x8f


def parse_header(record: bytes):
    version, length, crc, flags, tor = struct.unpack('<bhhhh', record[1:10])

    return {
        'version': version,
        'length': length,
        'crc': crc,
        'flags': flags,
        'tor': tor,
    }
    pass

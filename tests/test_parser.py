import unittest
import datetime

from ambp3parser import parser
from ambp3parser import record


class TestParser(unittest.TestCase):

    def test_passing(self):
        with open('test_data/good_passing', 'rb') as fp:
            data = fp.read()

        passing = record.Passing.parse_record(data)
        self.assertIsInstance(passing, record.Passing)
        self.assertEqual(2, passing.VERSION)
        self.assertEqual(51, passing.LENGTH)
        self.assertEqual(0xc79e, passing.CRC)
        self.assertEqual(113736, passing.PASSING_NUMBER)
        self.assertEqual(2906141, passing.TRANSPONDER)
        self.assertEqual(passing.RTC_TIME, 1437769372993000)
        self.assertEqual(passing.timestamp_ms, 1437769372993)
        self.assertEqual(106, passing.STRENGTH)
        self.assertEqual(50, passing.HITS)
        self.assertEqual(135059, passing.DECODER_ID)

        self.assertEqual(datetime.date(2015, 7, 24), passing.datetime.date())

    def test_passing_rc4(self):
        with open('test_data/passing_rc4', 'rb') as fp:
            data = fp.read()

        passing = record.Passing.parse_record(data)
        self.assertIsInstance(passing, record.Passing)
        self.assertEqual(2, passing.VERSION)
        self.assertEqual(51, passing.LENGTH)
        self.assertEqual(0x00019bb2, passing.PASSING_NUMBER)
        self.assertEqual(7404583, passing.TRANSPONDER)
        self.assertEqual(0x000405fc, passing.DECODER_ID)
        self.assertEqual(16, passing.HITS)
        self.assertEqual(0x0004758abde619e8, passing.RTC_TIME)
        self.assertEqual(datetime.date(2009, 10, 10), passing.datetime.date())

    def test_unescape(self):
        with open('test_data/1437857960_0', 'rb') as fp:
            data = fp.read()
        passing = record.Passing.parse_record(data)
        self.assertEqual(0x8f, passing.data[5])
        self.assertIsInstance(passing, record.Status)
        self.assertEqual(37, passing.TEMPERATURE)

    def test_multiple_records(self):
        with open('test_data/multi', 'rb') as fp:
            data = fp.read()
        records = record.AMBRecord.parse_multiple_records(data)
        self.assertIsInstance(records[0], record.Passing)
        self.assertIsInstance(records[-1], record.Passing)
        self.assertEqual(10, len(records))

    def test_multiple_records2(self):
        with open('test_data/amb-2022-05-01 145557-10.33.11.50.log', 'rb') as fp:
            data = fp.read()
        records = record.AMBRecord.parse_multiple_records(data)
        self.assertIsInstance(records[0], record.Passing)
        self.assertIsInstance(records[-1], record.Passing)
        self.assertEqual(67, len(records))


if __name__ == '__main__':
    unittest.main()

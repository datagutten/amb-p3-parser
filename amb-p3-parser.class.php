<?Php
class amb_p3_parser
{
	//Record types
	public $record_types=array(	0x00=>'RESET',
								0x01=>'PASSING',
								0x02=>'STATUS',
								0x45=>'FIRST_CONTACT',
								0xFFFF=>'ERROR',
								0x03=>'VERSION',
								0x04=>'RESEND',
								0x05=>'CLEAR_PASSING',
								0x18=>'WATCHDOG',
								0x20=>'PING',
								0x2d=>'SIGNALS',
								0x13=>'SERVER_SETTINGS',
								0x15=>'SESSION',
								0x28=>'GENERAL_SETTINGS',
								0x2f=>'LOOP_TRIGGER',
								0x30=>'GPS_INFO',
								0x4a=>'TIMELINE',
								0x24=>'GET_TIME',
								0x16=>'NETWORK_SETTINGS');
	//Message field definitions
	public $messages=array(0x01=>array(	0x01=>'PASSING_NUMBER',
										0x03=>'TRANSPONDER',
										0x04=>'RTC_TIME',
										0x05=>'STRENGTH',
										0x06=>'HITS',
										0x08=>'FLAGS'),
							0x02=>array(0x01=>'NOISE',
										0x06=>'GPS',
										0x07=>'TEMPERATURE',
										0x0A=>'SATINUSE',
										0x0B=>'LOOP_TRIGGERS',
										0x0C=>'INPUT_VOLTAGE'),
					'general'=>array(	0x81=>'DECODER_ID',
										0x83=>'CONTROLLER_ID',
										0x85=>'REQUEST_ID')
									);

	function trim_data($data,$debug=false) //Trim data to only contain complete records
	{
		//var_dump(dechex(ord(substr($data,0,1))));
		//var_dump(dechex(ord(substr($data,-1,1))));
		$start=strpos($data,chr(0x8E));
		$end=strrpos($data,chr(0x8F));
		return substr($data,$start,$end-$start+1); //Trim the string to get only complete messages
	}
	function unescape($record) //Subtract 0x20 from any byte prefixed with 0x8D
	{
		$pos=-2;
		while($pos=strpos($record,0x8D,$pos+2)) //Search from previous position +2 in case the unescaped byte is 0x8D
		{
			$escaped_char=ord(substr($record,$pos+1,1)); //Get the escaped char
			$record=substr_replace($record,chr($escaped_char-0x20),$pos,2); //Remove the escape char and subtract 0x20 from the escaped char
		}
		return $record;
	}

    /**
     * Get all records from a string
     * @param string $data Data from decoder
     * @return array
     */
	public static function get_records($data)
	{
		preg_match_all('/'.chr(0x8E).'.+?'.chr(0x8F).'/',$data,$records_preg);
		return $records_preg[0];
	}

	function format_value($string,$return_as_hex=false,$reverse=true) //Convert a string to character codes
	{
		if($reverse)
			$string=strrev($string); //The values need to be reversed
		$chars=str_split($string); //Split the string into an array
		$chars_dec=array_map('ord',$chars); //Get the character code for each char
		$chars_hex=array_map('dechex',$chars_dec); //Convert the character codes to hex
		foreach($chars_hex as $key=>$char)
			$chars_hex[$key]=str_pad($char,2,'0',STR_PAD_LEFT); //Pad each hex char

		$number_hex=implode('',$chars_hex); //Merge the chars to a string

		if($return_as_hex===false)
			return hexdec($number_hex); //Return as decimal integer
		else
			return $number_hex; //Return as hex string
	}

	function parse_header($record) //Parse the record header
	{
		//Header
		$info['version']=ord(substr($record,0x1,1));
		$info['length']=$this->format_value(substr($record,0x2,2));
		$info['crc']=$this->format_value(substr($record,0x4,2),true);
		$info['flags_header']=$this->format_value(substr($record,0x6,2),true); //Indicates the field length
		$info['type']=$this->format_value(substr($record,0x8,2)); //Get record type
		$info['record_hex']=$this->format_value($record,true,false); //Get the complete record as a readable hex string
		return $info;
	}
	function read_fields($record,$fields) //Read the fields of a record according to an array with field definations
	{
		$record_key=0;
		$strlen=strlen($record);

		for($pos=0xA; $pos<strlen($record)-1; $pos++) //Parse body
		{
			$field_id=ord($record[$pos]); //The first byte of a message is the field ID
			if(!isset($fields[$field_id]))
			{
				//$info['error']="Unknown message id: $field";
				trigger_error("Unknown field ID at position ".dechex($pos).": ".dechex($field_id));
				$fields[$field_id]='unkown_'.dechex($pos);
			}
			$field_name=$fields[$field_id]; //Get the field name
			$length=ord(substr($record,$pos+1,1)); //After the field ID we find the message length
			//echo "Length: $length\n";
			$messages[$field_name]=$this->format_value(substr($record,$pos+2,$length));
			$pos=$pos+$length+1;
		}
			
		return $messages;
	}
	function parse($record,$typefilter=false) //Parse a record
	{
		//Returns an array for a valid record
		//Returns bool false for errors
		//Returns bool true for records skipped by type filter
		$record=$this->unescape($record);
		if(ord(substr($record,0,1))!=0x8E || ord(substr($record,-1,1))!=0x8F) //Verify that the provided string is a complete record
		{
			trigger_error("Invalid record");
			return false;
		}
		$header=$this->parse_header($record);
		
		if(!isset($this->record_types[$header['type']]))
		{
			trigger_error("Unkown record type: ".dechex($header['type']));
			return false;
		}
		//echo "Type is {$this->record_types[$header['type']]}\n";

		if($typefilter!==false && $header['type']!=$typefilter) //Type is not wanted
			return true;

		if(isset($this->messages[$header['type']])) //Check if the message can be parsed
		{
			$fields=$this->messages[$header['type']] + $this->messages['general'];
			$message=$this->read_fields($record,$fields) ;//Read the fields of the record
			$record_parsed=array_merge($header,$message); //Merge the header and the the message body
		}
		else
			$record_parsed=$header;
		
		if($record_parsed['length']!=$strlen=strlen($record))
			$record_parsed['error']="Length is not matching. Strlen=$strlen, length={$info['length']}";

		return $record_parsed;
	}

	public static function find_start($record)
    {
        return strpos($record,chr(0x8E));
    }

    public static function find_end($record)
    {
        return strpos($record,chr(0x8F));
    }

    public static function find_last_end($record)
    {
        return strrpos($record,chr(0x8F));
    }

}
<?php
	class TelnetDataClass {
		private $ip;
		private $port;
		private $socket;
		
		public function __construct($ip) {
			$this->ip = $ip;
			$this->port = 23;
		}
		
		public function ReadTo($array_of_stops) {
			$this->socket = @pfsockopen($this->ip, $this->port, $errno, $errorstr, 9999);
			stream_set_timeout($this->socket, 0, 380000);
			stream_set_blocking($this->socket, TRUE);	
			
			$ret = array();
			$max_empty_lines = 3;
			$count_empty_lines = 0;
	
			while(!feof($this->socket)) {
				$read = fgets($this->socket);
				$ret[] = $read;

				// Stop reading after (int)"$max_empty_lines" empty lines
				if(trim($read) == "") {
					if($count_empty_lines++ > $max_empty_lines) break;
				} else {
					$count_empty_lines = 0;
				}

				// Does last line of readed data contain any of "Stop" strings ??
				$found = false;
				foreach($array_of_stops as $stop) {	
					if(stristr($stop, "more")) {
						if( strpos($read, $stop) !== FALSE ) {
							$found = true;
							break;
						}					
					} else if(substr($read, strlen($read)-1, strlen($read)) == $stop) { // substr($read, strlen($read)-1, strlen($read)) strlen($read)-1
						$found = true;
						break;
					}

				}
				// If so, stop reading
				if($found) {
					break;
				}
			}
			fputs($this->socket,chr(0xff).chr(0xfb).chr(0x1f).chr(0xff).chr(0xfb).chr(0x20).chr(0xff).chr(0xfb).chr(0x18).chr(0xff).chr(0xfb).chr(0x27).chr(0xff).chr(0xfd).chr(0x01).chr(0xff).chr(0xfb).chr(0x03).chr(0xff).chr(0xfd).chr(0x03));
			fputs($this->socket,chr(0xff).chr(0xfa).chr(0x1f).chr(0x00).chr(0x50).chr(0x03).chr(0xe8).chr(0xff).chr(0xf0).chr(0x0d).chr(0x0a));
			//fputs($this->socket,chr(0xff).chr(0xfc).chr(0x01).chr(0x0d).chr(0x0a));			
			return $ret;
		}
		
		public function RawLoginReadTo($userName, $userPass, $array_of_stops) {
			$this->socket = @pfsockopen($this->ip, $this->port, $errno, $errorstr, 9999);
			stream_set_timeout($this->socket, 0, 380000);
			stream_set_blocking($this->socket, TRUE);	
			fputs($this->socket, $userName."\r\n");
			fputs($this->socket, $userPass."\r\n");
			
			$ret = array();
			$max_empty_lines = 3;
			$count_empty_lines = 0;
	
			while(!feof($this->socket)) {
				$read = fgets($this->socket);
				$ret[] = $read;

				// Stop reading after (int)"$max_empty_lines" empty lines
				if(trim($read) == "") {
					if($count_empty_lines++ > $max_empty_lines) break;
				} else {
					$count_empty_lines = 0;
				}

				// Does last line of readed data contain any of "Stop" strings ??
				$found = false;
				foreach($array_of_stops as $stop) {	
					if(stristr($stop, "more")) {
						if( strpos($read, $stop) !== FALSE ) {
							$found = true;
							break;
						}					
					} else if(substr($read, strlen($read)-1, strlen($read)) == $stop) { // substr($read, strlen($read)-1, strlen($read)) strlen($read)-1
						$found = true;
						break;
					}

				}
				// If so, stop reading
				if($found) {
					break;
				}
			}
			//fputs($this->socket,chr(0xff).chr(0xfb).chr(0x1f).chr(0xff).chr(0xfb).chr(0x20).chr(0xff).chr(0xfb).chr(0x18).chr(0xff).chr(0xfb).chr(0x27).chr(0xff).chr(0xfd).chr(0x01).chr(0xff).chr(0xfb).chr(0x03).chr(0xff).chr(0xfd).chr(0x03));
			//fputs($this->socket,chr(0xff).chr(0xfa).chr(0x1f).chr(0x00).chr(0x50).chr(0x03).chr(0xe8).chr(0xff).chr(0xf0).chr(0x0d).chr(0x0a));
			//fputs($this->socket,chr(0xff).chr(0xfc).chr(0x01).chr(0x0d).chr(0x0a));			
			return $ret;
		}		
		
		public function SendCmdReadTo($cmd, $array_of_stops) {
			$this->socket = @pfsockopen($this->ip, $this->port, $errno, $errorstr, 9999);
			stream_set_timeout($this->socket, 0, 380000);
			stream_set_blocking($this->socket, TRUE);			
			fputs($this->socket, $cmd."\r\n");
			
			$ret = array();
			$max_empty_lines = 3;
			$count_empty_lines = 0;

			while(!feof($this->socket)) {
				$read = fgets($this->socket);
				$ret[] = $read;

				// Stop reading after (int)"$max_empty_lines" empty lines
				if(trim($read) == "") {
					if($count_empty_lines++ > $max_empty_lines) break;
				} else {
					$count_empty_lines = 0;
				}

				// Does last line of readed data contain any of "Stop" strings ??
				$found = false;
				foreach($array_of_stops as $stop) {
					if(stristr($stop, "more")) {
						if( strpos($read, $stop) !== FALSE ) {
							$found = true;
							break;
						}					
					} else if(substr($read, strlen($read)-1, strlen($read)) == $stop) { // substr($read, strlen($read)-1, strlen($read)) strlen($read)-1
						$found = true;
						break;
					}

				}
				// If so, stop reading
				if($found) {
					break;
				}
			}
			return $ret;
		}		
	}
?>
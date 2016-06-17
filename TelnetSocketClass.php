<?php
  class TelnetSocketClass {
		private $port;
		private $socket;
		
		public function __construct() {
			$this->port = 23;
		}
		
		public function connect($ip) {
			/*
			 * If you need to set a timeout for reading/writing data over the socket, use stream_set_timeout(), 
			 * as the timeout parameter to fsockopen() only applies while connecting the socket.
			 */
			if(($this->socket = @pfsockopen($ip, $this->port, $errno, $errorstr, 9999)) !== FALSE) {
				stream_set_timeout($this->socket, 0, 380000);
				stream_set_blocking($this->socket, TRUE);
				/*
				 * FF FB 01 FF FB 03 FF FD 18 FF FD 1F 0D 0A
				 * http://mars.netanya.ac.il/~unesco/cdrom/booklet/HTML/NETWORKING/node300.html
				 * IAC           255 FF     Interpret as command.
				 * Will Use      251 FB     Agreement to use the specified option. 
				 * Echo Data       1 01     Causes server to echo back all keystrokes.
				 * IAC           255 FF     Interpret as command.
				 * Will Use      251 FB     Agreement to use the specified option.
				 * Suppress GA     3 03     Suppress GA Disables Go Ahead! command.
				 * IAC           255 FF     Interpret as command.
				 * Start use     253 FD     Request to start using specified option.
				 * Term Type      24 18     Allows exchange of terminal type information.
				 * IAC           255 FF     Interpret as command.
				 * Start use     253 FD     Request to start using specified option.
				 * Window Size    31 1F     Conveys window size for emulation screen.
				 * CR character   13 0D
				 * LF character   10 0A
				 *
				 * http://c353616.r16.cf1.rackcdn.com/Biamp_Tech_Note_Telnet_session_negotiation_in_Tesira.pdf
				 * FF FE 01 FF FE 03 FF FC 18 FF FC 1F 0D 0A
				 */
				//fputs($this->socket,chr(0xff).chr(0xfb).chr(0x1f).chr(0xff).chr(0xfb).chr(0x20).chr(0xff).chr(0xfb).chr(0x18).chr(0xff).chr(0xfb).chr(0x27).chr(0xff).chr(0xfd).chr(0x01).chr(0xff).chr(0xfb).chr(0x03).chr(0xff).chr(0xfd).chr(0x03));
				//fputs($this->socket,chr(0xff).chr(0xfc).chr(0x01).chr(0x0d).chr(0x0a));
				return 'connected';
			} else {
				return $errorstr;
			}
		}
		
/*
 * http://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
 */ 
public function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}
/*
 * http://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
 */
function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}
	}
?>
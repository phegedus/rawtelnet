<!DOCTYPE html>
<?php
	include_once 'dataini.php';

	date_default_timezone_set('Europe/Budapest');
	$today = date("Y-m-d");
	$logFile = $today.'.txt';
	$lfh = fopen($logFile, 'a+') or die("can't open '".$logFile."' file");
	
	if(!isset($_SESSION)) {
		session_start();
	}
	if($_GET['act'] == 'login') {
		$telnetData = new TelnetDataClass($_SESSION['ip']);
		$returnStr = $telnetData->ReadTo(array('#','>','%'));
	} else if($_GET['act'] == 'user') {
		$telnetData = new TelnetDataClass($_SESSION['ip']);
		$returnStr = $telnetData->SendCmdReadTo($_SESSION['user'], array('--More--','More:','#','>','%'));
	} else if($_GET['act'] == 'pass') {
		$telnetData = new TelnetDataClass($_SESSION['ip']);
		$returnStr = $telnetData->SendCmdReadTo($_SESSION['pass'], array('--More--','More:','#','>','%'));
	} else if($_GET['act'] == 'more') {
		$telnetData = new TelnetDataClass($_SESSION['ip']);
		$returnStr = $telnetData->SendCmdReadTo(' ', array('--More--','More:','#','>','%'));
	} else if($_GET['act'] == 'cmd') {
		$telnetData = new TelnetDataClass($_SESSION['ip']);
		$returnStr = $telnetData->SendCmdReadTo('show version', array('--More--','More:','#','>','%'));
	}
	
	/*
	 * http://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
	 */ 
	function strToHex($string){
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
?>
<html>
<head>
<title>RAW TELNET LOOP</title>
<link href="css/frame.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php
	$response = null;
	echo 'Return size: '.count($returnStr)."<br>";
	for($i=0; $i<count($returnStr); $i++) {
	  fputs($lfh, strToHex($returnStr[$i]));
		fputs($lfh, "\r");
		fputs($lfh, trim($returnStr[$i]));
		fputs($lfh, "\r");
		/*
		 * Filtering out not ASCII characters is done by sanitizing.
		 * http://php.net/manual/en/filter.filters.sanitize.php
		 */
		$sanitized = trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), " \t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A");
		if($sanitized != NULL) {
			$response = $sanitized;
		}
	}
	echo 'RESPONSE: '.$response."<br>";
	if(stristr($response, 'login') || stristr($response, 'user')) {
?>
		<script type="text/javascript">
			var refUrl = window.location.href;
			var refUrlSplit = refUrl.split("?");
			url = refUrlSplit[0] + "?act=user";
			window.location.href = url;
		</script>
<?php
	}	else if(stristr($response, 'assword')) {
?>
		<script type="text/javascript">
			var refUrl = window.location.href;
			var refUrlSplit = refUrl.split("?");
			url = refUrlSplit[0] + "?act=pass";
			window.location.href = url;
		</script>
<?php
	} else if(stristr($response, 'more')) {
?>
		<script type="text/javascript">
			var refUrl = window.location.href;
			var refUrlSplit = refUrl.split("?");
			url = refUrlSplit[0] + "?act=more";
			window.location.href = url;
		</script>
<?php		
	} else if(stristr($response, '#') || stristr($response, '>') || stristr($response, '%')) {
?>
		<script type="text/javascript">
			var refUrl = window.location.href;
			var refUrlSplit = refUrl.split("?");
			url = refUrlSplit[0] + "?act=cmd";
			window.location.href = url;
		</script>
<?php		
	}
?>
</body>
</html>
<?php
  $telnetsocketclass = fopen('TelnetSocketClass.phb','r');
  bcompiler_read($telnetsocketclass);
	
	$telnetdataclass = fopen('TelnetDataClass.phb','r');
  bcompiler_read($telnetdataclass);
  
	$arpprocessclass = fopen('ArpProcessClass.phb','r');
  bcompiler_read($arpprocessclass);  
?>
<?php
	echo 'Compile TelnetSocketClass<br>';
  $fh = fopen("TelnetSocketClass.phb", "w");
  bcompiler_write_header($fh);
  bcompiler_write_file($fh, "TelnetSocketClass.php");
  bcompiler_write_footer($fh);
  fclose($fh);	
	
	echo 'Compile TelnetDataClass<br>';
  $fh = fopen("TelnetDataClass.phb", "w");
	bcompiler_write_header($fh);
  bcompiler_write_file($fh, "TelnetDataClass.php");
  bcompiler_write_footer($fh);
  fclose($fh);	
  
	echo 'Compile ArpProcessClass<br>';
  $fh = fopen("ArpProcessClass.phb", "w");
  bcompiler_write_header($fh);
  bcompiler_write_file($fh, "ArpProcessClass.php");
  bcompiler_write_footer($fh);
  fclose($fh);  
?>
<?php
  class ArpProcessClass {
    /**
     * Call this method to get singleton of ArpProcessClass
     * @return ArpProcessClass
     */
    public static function Instance() {
      static $inst = null;
      if ($inst === null) {
        $inst = new ArpProcessClass();
      }
      return $inst;
    }
    
    /**
     * Private constructor so nobody else can instantiate it
     */
    private function __construct() {}
		
		public function doArpProcess($hostName, $datacollection, $arpcollection) {
			$outputHeader = array('Hostname','IP-Address','MAC-Address','Interface','VLAN-ID','Aging','Type');
			$dataFileHandle = @fopen($datacollection, 'r');
			$arpFileHandle = @fopen($arpcollection, 'a');
			if($dataFileHandle) {
				$isArpProcess = false;
				$arpBuffer = null;
				$header = array();
				$index = array();
				$data = array();
				$domain = null;			
        while(($buffer = fgets($dataFileHandle, 4096)) !== false) {		
					if($isArpProcess && stristr($buffer, $hostName) && stristr($buffer, ' arp')) {
						$arpBuffer = null;
						$header = array();
						$index = array();
						$data = array();
						$domain = null;
					} else if(!$isArpProcess && stristr($buffer, $hostName) && stristr($buffer, ' arp')) {
						$isArpProcess = true;
						$arpBuffer = null;
						$header = array();
						$index = array();
						$data = array();
						$domain = null;						
					} else if(stristr($buffer, $hostName) && !stristr($buffer, ' arp')) {
						$isArpProcess = false;
						$arpBuffer = null;
						$header = array();
						$index = array();
						$data = array();
						$domain = null;	
					}
					if($isArpProcess && !preg_match('/-{5,}/', $buffer)) {
						if(stristr($buffer, 'address')) {
							$arpBuffer = 'header';
						} else if($arpBuffer == 'header' && preg_match('/\d{1,}.\d{1,}.\d{1,}.\d{1,}/', $buffer)) {
							$arpBuffer = 'data';
						} else if($arpBuffer == 'data' && !preg_match('/\d{1,}.\d{1,}.\d{1,}.\d{1,}/', $buffer)) {
							$arpBuffer = null;
							$isArpProcess = false;
						}					
						if($arpBuffer == 'header') {
							$header = preg_split('/\s{2,}+/', trim($buffer));
							for($i=0; $i<count($header); $i++) {
								if(trim($header[$i]) == 'Address' || trim($header[$i]) == 'IP address' || trim($header[$i]) == 'IP Address') {
									$index['IP-Address'] = $i;
								} else if(trim($header[$i]) == 'MAC Address' || trim($header[$i]) == 'Hardware Addr' || trim($header[$i]) == 'HWAddress' || trim($header[$i]) == 'HW address') {
									$index['MAC-Address'] = $i;
								} else if(trim($header[$i]) == 'Interface' || trim($header[$i]) == 'Port Name / AL ID' || trim($header[$i]) == 'Iface') {
									$index['Interface'] = $i;
								} else if(trim($header[$i]) == 'VLAN ID' || trim($header[$i]) == 'VLAN') {
									$index['VLAN-ID'] = $i;
								} else if(trim($header[$i]) == 'Aging' || trim($header[$i]) == 'Age (min)') {
									$index['Aging'] = $i;
								} else if(trim($header[$i]) == 'Type' || trim($header[$i]) == 'HWtype') {
									$index['Type'] = $i;
								}
							}
							for($i=0; $i<count($outputHeader); $i++) {
								if(trim($outputHeader[$i]) != 'Interface') {
									@fputs($arpFileHandle, $outputHeader[$i]);
									if($i<count($outputHeader) - 1) {
										@fputs($arpFileHandle, ',');
									}
								}
							}
							@fputs($arpFileHandle, "\n");
						} else if($arpBuffer == 'data') {
							$data = preg_split('/\s{2,}+/', $buffer);
							$domain = gethostbyaddr(trim($data[$index['IP-Address']]));				
							for($i=0; $i<count($outputHeader); $i++) {
								if(trim($outputHeader[$i]) != 'Interface') {
									if(trim($outputHeader[$i]) == 'Hostname') {
										@fputs($arpFileHandle, $domain);
									} else if(isset($index[trim($outputHeader[$i])]) && isset($data[$index[trim($outputHeader[$i])]])) {
										@fputs($arpFileHandle, $data[$index[trim($outputHeader[$i])]]);
									}
									if($i<count($outputHeader) - 1) {
										@fputs($arpFileHandle, ',');
									}
								}
							}
							@fputs($arpFileHandle, "\n");
						}
					}
				}
				foreach($index as $k => $d) {
					echo $k.' => '.$d."<br>";
				}
        if(!feof($dataFileHandle)) {
          echo "Error: unexpected fgets() fail<br>";
        }
				@fclose($dataFileHandle);
				@fclose($arpFileHandle);
			}
		}
	}
?>
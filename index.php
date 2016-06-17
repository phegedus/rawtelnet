<!DOCTYPE html>
<?php
if (!isset($_SESSION)) {
    session_start();
}
if (isset($_SESSION['ip'])) {
    $socket = @pfsockopen($_SESSION['ip'], 23, $errno, $errorstr);
    @fflush($socket);
    @fclose($socket);
}
?>
<html>
    <head>
        <title>RAW TELNET LOOP</title>
        <link href="css/frame.css" rel="stylesheet" type="text/css">
        <link href="css/parent.css" rel="stylesheet" type="text/css">
        <script type="text/javascript" src="js/parent.js"></script>
    </head>
    <body>
        <?php
        include_once 'phpini.php';

        $telnetSocket = new TelnetSocketClass();

        date_default_timezone_set('Europe/Budapest');
        $today = date("Y-m-d");
        $logFile = $today . '.txt';
        $lfh = fopen($logFile, 'a+') or die("can't open '" . $logFile . "' file");
        $today = date("Ymd");
        $todayConfig = 'config/' . $today;
        $todayDatacollection = 'datacollection/' . $today;
        $todayArpProcess = 'arpcollection/' . $today;
        $isArpProcess = false;
        if (!file_exists($todayConfig)) {
            mkdir($todayConfig, 0777, true);
        }
        if (!file_exists($todayDatacollection)) {
            mkdir($todayDatacollection, 0777, true);
        }

        $dictionaryHandle = @fopen('CSVs/dictionary.csv', 'r') or die("can't open CSVs/dictionary.csv file");
        $dictionary = array();
        while (($buffer = fgets($dictionaryHandle, 4096)) !== false) {
            array_push($dictionary, $buffer);
        }
        @fclose($dictionaryHandle);

        echo 'Dictionary Counts: ' . count($dictionary) . "<br>";
        for ($d = 0; $d < count($dictionary); $d++) {
            echo $d . ' ::::: ' . $dictionary[$d] . "<br>";
        }

        $parancsokFileHandle = @fopen('CSVs/parancsok.csv', 'r') or die("can't open CSVs/parancsok.csv file");
        $parancsok = array();
        $vendor = null;
        $command = array();
        while (($buffer = fgets($parancsokFileHandle, 4096)) !== false) {
            if (isset($buffer) && $buffer != null && $buffer != '') {
                if (stristr($buffer, 'vendor')) {
                    if (isset($vendor)) {
                        $parancsok[trim($vendor)] = $command;
                    }
                    $vendor = trim(substr($buffer, strlen('vendor:'), strlen($buffer)));
                    $command = array();
                } else {
                    array_push($command, $buffer);
                }
            }
        }
        if (isset($vendor)) {
            $parancsok[$vendor] = $command;
        }
        if (!feof($parancsokFileHandle)) {
            echo "Error: unexpected fgets() fail<br>";
        }
        @fclose($parancsokFileHandle);
        foreach ($parancsok as $k => $p) {
            echo "<br>" . $k . "<br>";
            print_r($p);
        }
        $credentialFileHandle = @fopen('CSVs/credential.csv', 'r') or die("can't open CSVs/credential.csv file");
        if ($credentialFileHandle) {
            $credential = array();
            /*
             * Most outer loop is according to the credentials in form as 
             * "192.168.0.111;switch-2;telnet;admin;helloo;cisco".
             * That means all of the hosts will be scanned.
             */
            while (($buffer = fgets($credentialFileHandle, 4096)) !== false) {
                $connect = null;
                $credential = explode(';', $buffer);
                $connect = $telnetSocket->connect($credential[0]);
                if ($connect == 'connected') {
                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    $_SESSION['ip'] = trim($credential[0]);
                    $_SESSION['host'] = trim($credential[1]);
                    $_SESSION['protocol'] = trim($credential[2]);
                    $dictionaryCounter = 0;
                    if (isset($credential[3]) && $credential[3] != null && $credential[3] != '' && isset($credential[4]) && $credential[4] != null && $credential[4] != '') {
                        fputs($lfh, 'CREDENTIALS FILE ENTRIES: ' . $credential[3] . ' ' . $credential[4]);
                        fputs($lfh, "\r");
                        $_SESSION['user'] = trim($credential[3]);
                        $_SESSION['pass'] = trim($credential[4]);
                    } else {
                        $userAndPass = explode(';', $dictionary[$dictionaryCounter]);
                        fputs($lfh, 'DICTIONARY FILE ENTRIES: ' . $userAndPass[0] . ' ' . $userAndPass[1]);
                        $_SESSION['user'] = trim($userAndPass[0]);
                        $_SESSION['pass'] = trim($userAndPass[1]);
                        $dictionaryCounter++;
                    }
                    $_SESSION['show'] = trim($credential[5]);
                    echo 'Connected to ' . $credential[0] . ':23' . "<br>";
                    echo $_SESSION['user'] . ' ' . $_SESSION['pass'] . "<br>";
                    fputs($lfh, 'THE ORIGINAL USER NAD PASSWORD: ' . $_SESSION['user'] . ' ' . $_SESSION['pass']);
                    fputs($lfh, "\r");
                    $loggedIn = false;
                    $authenticate = true;
                    while (!$loggedIn && $dictionaryCounter < count($dictionary)) {
                        fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER JUST CONNECT');
                        fputs($lfh, "\r");
                        $telnetData = new TelnetDataClass($_SESSION['ip']);
                        $returnStr = $telnetData->ReadTo(array('#', '>', '%'));
                        $response = null;
                        for ($i = 0; $i < count($returnStr); $i++) {
                            fputs($lfh, strToHex($returnStr[$i]));
                            fputs($lfh, "\r");
                            fputs($lfh, trim($returnStr[$i]));
                            fputs($lfh, "\r");
                            /*
                             * Filtering out not ASCII characters is done by sanitizing.
                             * http://php.net/manual/en/filter.filters.sanitize.php
                             */
                            $sanitized = trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), " \t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B");
                            if ($sanitized != NULL) {
                                $response = $sanitized;
                            }
                        }
                        //echo 'RESPONSE: '.$response."<br>";
                        $promptCounter = 0;
                        $commandCounter = 0;
                        $addressDatabaseCounter = 0;
                        $prompt = null;
                        $commandOutput = false;

                        $arpcollectionFileName = $todayArpProcess . '/' . $_SESSION['host'] . '.csv';
                        $datacollectionFileName = $todayDatacollection . '/' . $_SESSION['host'] . '.txt';
                        $datacollectionFileHandle = @fopen($datacollectionFileName, 'w');
                        $configFileName = $todayConfig . '/' . $_SESSION['host'] . '.txt';
                        $configFileHandle = @fopen($configFileName, 'w');
                        /*
                         * Timestep format:
                         * --- cmdShow log 2014.10.10 12:35:18 ---
                         */
                        $timeStamp = date("Y.m.d H:i:s");
                        @fputs($datacollectionFileHandle, '--- cmdShow log ' . $timeStamp . ' ---' . "\n");
                        @fputs($configFileHandle, '--- cmdShow log ' . $timeStamp . ' ---' . "\n");
                        $show = null;
                        $menu = false;
                        if ($_SESSION['show'] == '3comsuperstack') {
                            $menu = true;
                        }
                        $cummulatedCmd = '';
                        while ($authenticate && count($parancsok[$_SESSION['show']]) > $commandCounter) {
                            $menuOutput = false;
                            if (stristr($response, 'login') || stristr($response, 'user')) {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER LOGIN PROMPT');
                                fputs($lfh, "\r");
                                $returnStr = $telnetData->SendCmdReadTo($_SESSION['user'], array('--More--', 'More:', '#', '>', '%'));
                            } else if (stristr($response, 'assword')) {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER PASSWORD PROMPT');
                                fputs($lfh, "\r");
                                $returnStr = $telnetData->SendCmdReadTo($_SESSION['pass'], array('--More--', 'More:', '#', '>', '%'));
                            } else if (substr($response, strlen($response) - 1, strlen($response)) == '#' || substr($response, strlen($response) - 1, strlen($response)) == '>' || substr($response, strlen($response) - 1, strlen($response)) == '%') {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER TELNET PROMPT');
                                fputs($lfh, "\r");
                                $loggedIn = true;
                                $show = $parancsok[$_SESSION['show']][$commandCounter];
                                $commandCounter++;
                                $commandOutput = true;
                                echo 'COMMAND FROM SHOW: ' . $show . "<br>";
                                //$prompt = $response;
                                if ($show != '-' && (stristr($show, 'show conf') || stristr($show, 'show startup') || stristr($show, 'display current') || stristr($show, 'show run'))) {
                                    fputs($configFileHandle, $credential[1] . '#' . $show);
                                } else if ($show != '-') {
                                    fputs($datacollectionFileHandle, $credential[1] . '#' . $show);
                                    if (stristr($show, 'arp')) {
                                        $isArpProcess = true;
                                    }
                                }
                                $returnStr = $telnetData->SendCmdReadTo($show, array('--More--', 'More:', '#', '>', '%'));
                                $promptCounter++;
                            } else if (stristr($response, 'Select')) {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== SELECT - MENU OPTION');
                                fputs($lfh, "\r");
                                $loggedIn = true;
                                if (!stristr($parancsok[$_SESSION['show']][$commandCounter], 'quit') && !stristr($parancsok[$_SESSION['show']][$commandCounter], 'logout')) {
                                    $cummulatedCmd .= trim($parancsok[$_SESSION['show']][$commandCounter]) . ' ';
                                }
                                if ($cummulatedCmd != '' && (stristr($parancsok[$_SESSION['show']][$commandCounter + 1], 'quit') || stristr($parancsok[$_SESSION['show']][$commandCounter + 1], 'logout'))) {
                                    fputs($datacollectionFileHandle, "\r" . $_SESSION['host'] . '#' . $cummulatedCmd . "\r\r");
                                    $menuOutput = true;
                                    $commandOutput = true;
                                }
                                $show = trim($parancsok[$_SESSION['show']][$commandCounter]);
                                $commandCounter++;
                                echo 'HARD CODED COMMAND: ' . $show . "<br>";
                                $returnStr = $telnetData->SendCmdReadTo($show, array('Select', '--More--', 'More:', '#', '>', '%'));
                                $promptCounter++;
                            } else if (stristr($response, 'more')) {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER JUST MORE');
                                fputs($lfh, "\r");
                                $menuOutput = true;
                                $returnStr = $telnetData->SendCmdReadTo(' ', array('Select', '--More--', 'More:', '#', '>', '%'));
                            } else if (stristr($response, 'authentication failed')) {
                                if (isset($_SESSION['ip'])) {
                                    $socket = @pfsockopen($_SESSION['ip'], 23, $errno, $errorstr);
                                    @fflush($socket);
                                    @fclose($socket);
                                }
                                fputs($lfh, 'AUTHENTICATION FAILED - DISCONNECTED FROM ' . $_SESSION['ip']);
                                fputs($lfh, "\r");
                                $loggedIn = false;
                                $authenticate = false;
                                $connect = $telnetSocket->connect($_SESSION['ip']);
                            } else if (stristr($response, 'Incorrect password')) {
                                $loggedIn = false;
                                $authenticate = false;
                            } else {
                                fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== AFTER JUST EMPTY RETURN VALUE');
                                fputs($lfh, "\r");
                                $returnStr = $telnetData->RawLoginReadTo($_SESSION['user'], $_SESSION['pass'], array('--More--', 'More:', '#', '>', '%'));
                                $loggedIn = false;
                                $authenticate = false;
                            }
                            fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== PROMPT: ' . $prompt . ' ==========');
                            fputs($lfh, "\r");
                            for ($i = 0; $i < count($returnStr); $i++) {
                                fputs($lfh, strToHex($returnStr[$i]));
                                fputs($lfh, "\r");
                                fputs($lfh, trim($returnStr[$i]));
                                fputs($lfh, "\r");
                                if (!isset($prompt) && (substr($returnStr[$i], strlen($returnStr[$i]) - 1, strlen($returnStr[$i])) == '#' || substr($returnStr[$i], strlen($returnStr[$i]) - 1, strlen($returnStr[$i])) == '>' || substr($returnStr[$i], strlen($returnStr[$i]) - 1, strlen($returnStr[$i])) == '%')) {
                                    /*
                                     * Filtering out not ASCII characters is done by sanitizing.
                                     * http://php.net/manual/en/filter.filters.sanitize.php
                                     */
                                    $prompt = trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), " \t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B");
                                    echo 'THIS HOST PROMPT IS: ' . $prompt . "<br>";
                                }
                                if ($commandOutput && $menu && $menuOutput && trim($returnStr[$i]) != NULL && trim($returnStr[$i]) != '' && !stristr(trim($returnStr[$i]), 'all') && !stristr(trim($returnStr[$i]), 'for more or') && !stristr(trim($returnStr[$i]), 'Select menu option')) {
                                    @fputs($datacollectionFileHandle, trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), " \t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B") . "\n");
                                }
                                if ($commandOutput && !$menu && !$menuOutput && trim($returnStr[$i]) != $prompt) {
                                    if ($show != '-' && (stristr($show, 'show conf') || stristr($show, 'show startup') || stristr($show, 'display current') || stristr($show, 'show run'))) {
                                        fputs($configFileHandle, trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), "\t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B") . "\n");
                                    } else if ($show != '-') {
                                        fputs($datacollectionFileHandle, trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), "\t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B") . "\n");
                                        if (stristr($show, 'arp')) {
                                            $isArpProcess = true;
                                        }
                                    }
                                }
                                /*
                                 * Filtering out not ASCII characters is done by sanitizing.
                                 * http://php.net/manual/en/filter.filters.sanitize.php
                                 */
                                $sanitized = trim(filter_var($returnStr[$i], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), " \t\n\r\0\x0B\xFF\xFB\x01\x03\xFD\x18\x1F\x0D\x0A\x1B\x5B\x4B");
                                if ($sanitized != NULL) {
                                    $response = $sanitized;
                                }
                            }
                            if (isset($parancsok[$_SESSION['show']][$commandCounter]) && $menu && (stristr($parancsok[$_SESSION['show']][$commandCounter], 'quit') || stristr($parancsok[$_SESSION['show']][$commandCounter], 'logout'))) {
                                $cummulatedCmd = '';
                            }
                        }
                        if ($loggedIn) {
                            fputs($lfh, 'USER LOGGED IN: ' . $_SESSION['user'] . ' OF ' . $_SESSION['pass']);
                            fputs($lfh, "\r");
                        } else {
                            fputs($lfh, 'USER COULD NOT LOGGED IN: ' . $_SESSION['user'] . ' OF ' . $_SESSION['pass']);
                            fputs($lfh, "\r");
                        }
                        if (!$loggedIn && $dictionaryCounter < count($dictionary)) {
                            $userAndPass = explode(';', $dictionary[$dictionaryCounter]);
                            $_SESSION['user'] = $userAndPass[0];
                            $_SESSION['pass'] = $userAndPass[1];
                            $dictionaryCounter++;
                            fputs($lfh, 'USER AND PASS ITERATION: ' . $dictionaryCounter . ' OF ' . count($dictionary));
                            fputs($lfh, "\r");
                            fputs($lfh, 'USER AND PASS PAIR: ' . $_SESSION['user'] . ' AND ' . $_SESSION['pass']);
                            fputs($lfh, "\r");
                            $authenticate = true;
                        }
                        if ($show == 'logout') {
                            fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== LOG OUT FROM MENU ');
                            fputs($lfh, "\r");
                            break;
                        }
                        if ($isArpProcess) {
                            if (!file_exists($todayArpProcess)) {
                                mkdir($todayArpProcess, 0777, true);
                            }
                            $arpcollectionFileHandle = @fopen($arpcollectionFileName, 'w');
                            @fclose($arpcollectionFileHandle);
                            ArpProcessClass::Instance()->doArpProcess($credential[1], $datacollectionFileName, $arpcollectionFileName);
                            $isArpProcess = false;
                        }
                        @fclose($datacollectionFileHandle);
                        @fclose($arpcollectionFileHandle);
                        @fclose($configFileHandle);
                    }
                    if (isset($_SESSION['ip'])) {
                        fputs($lfh, '========== ' . $_SESSION['ip'] . ' ========== CLOSING THIS IP SESSION');
                        fputs($lfh, "\r");
                        $socket = @pfsockopen($_SESSION['ip'], 23, $errno, $errorstr);
                        @fflush($socket);
                        @fclose($socket);
                    }
                } else {
                    echo '<div class="socket">Cannot connect to ' . $credential[0] . ':23</div>' . "<br>";
                }
            }
            if (!feof($credentialFileHandle)) {
                echo "Error: unexpected fgets() fail<br>";
            }
            @fclose($credentialFileHandle);
            @fclose($lfh);
        }

        /*
         * http://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
         */

        function strToHex($string) {
            $hex = '';
            for ($i = 0; $i < strlen($string); $i++) {
                $ord = ord($string[$i]);
                $hexCode = dechex($ord);
                $hex .= substr('0' . $hexCode, -2);
            }
            return strToUpper($hex);
        }

        /*
         * http://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
         */

        function hexToStr($hex) {
            $string = '';
            for ($i = 0; $i < strlen($hex) - 1; $i+=2) {
                $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
            }
            return $string;
        }
        ?>
        <img id="logo" src="img/logo.png" />
    </body>
</html>
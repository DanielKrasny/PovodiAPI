<?php
/**
 * PovodiAPI <https://github.com/DanielKrasny/PovodiAPI>
 * @version 1.0
 * @author Daniel Krásný <https://github.com/DanielKrasny>
 * 
 * Working with: Povodí Labe, Povodí Odry, Povodí Ohře, Povodí Vltavy
 * For Povodí Moravy use dedicated pmoAPI <https://github.com/DanielKrasny/pmoAPI>
 * 
 * Required values:
 * website - domain. Available: pla, pod, poh, pvl
 * channel - Available: nadrze, sap, srazky
 * station - ID of weather station (available from https://raw.githubusercontent.com/DanielKrasny/PovodiAPI/master/stations/[pla/pod/poh/pvl]_[nadrze/sap/srazky].txt)
 * response - method of responding. Available: json, rss
 * [for channels 'nadrze', 'sap'] values - do you want the latest value or all values available? Choose from: all, latest
 * Optional values:
 * [for channel 'srazky'] values - do you want only total value, all values available or the latest and total value? Choose from: total (default), all, latest
 * [for channel 'srazky', RSS response and set "values" to all] temp - allow showing temperature in RSS. Available = yes, no (default)
 * 
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
$site = $_GET["website"];
$sites = array('pla' => 'Labe', 'pod' => 'Odry', 'poh' => 'Ohře', 'pvl' => 'Vltavy');
$domains = array('pla' => 'http://www.pla.cz', 'pod' => 'https://pod.cz', 'poh' => 'http://sap.poh.cz', 'pvl' => 'http://www.pvl.cz');
if ($sites[$site]) {
$channel = $_GET["channel"];
if ($channel != 'nadrze' && $channel != 'sap' && $channel != 'srazky'){
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid channel. Allowed channels are nadrze, sap, srazky.', 'thanks-to' => 'pmoAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/pmoAPI'));
} else {
$station = $_GET["station"];
if ($station == null or '') {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid station number. Please check https://raw.githubusercontent.com/DanielKrasny/PovodiAPI/master/stations/'.$site.'_'.$channel.'.txt', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
} else {
$response = $_GET["response"];
$values = $_GET["values"];
if ($values != 'all' && $values != 'latest' && $channel != 'srazky'){
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid value. Available options: all, latest', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
} else {
$channelfix = array('nadrze' => 'Nadrze', 'sap' => 'SaP', 'srazky' => 'Srazky');
$povodi = file_get_contents($domains[$site].'/portal/'.$channelfix[$channel].'/cz/text/Mereni.aspx?oid=2&id='.$station.'&z=vse');
$dom = new DOMDocument;
@$dom->loadHTML($povodi);
if ($dom->getElementById('ContentPlaceHolder1_ChybaLbl')) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid station number. Please check https://raw.githubusercontent.com/DanielKrasny/PovodiAPI/master/stations/'.$site.'_'.$channel.'.txt', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
} else if ($dom->getElementsByTagName('table')->item(1)->getElementsByTagName('span')[0]->nodeValue == 'Žádná data měření nejsou k dispozici') {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid station number. Please check https://raw.githubusercontent.com/DanielKrasny/PovodiAPI/master/stations/'.$site.'_'.$channel.'.txt', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
} else if ($dom->getElementsByTagName('table')->item(2)->getElementsByTagName('span')[0]->nodeValue == 'Žádná data měření nejsou k dispozici') {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Station number is not matching the channel. Please check https://raw.githubusercontent.com/DanielKrasny/PovodiAPI/master/stations/'.$site.'_'.$channel.'.txt', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
} else {
$tables = $dom->getElementsByTagName('table');
$data = $tables->item(2)->getElementsByTagName('tr');
$infos = $tables->item(1)->getElementsByTagName('tr');
$td[0] = $infos[0]->getElementsByTagName('td');
$td[1] = $infos[1]->getElementsByTagName('td');
if ($response == 'json') {
    $arr = array();
} else if ($response == 'rss') {
    echo "<?xml version='1.0' encoding='UTF-8'?>\n";
    echo "<rss version='2.0'>\n";
    echo "<channel>\n";
    echo "<title>Povodí ".$sites[$site]."</title>\n";
    echo "<link>".$domains[$site]."/</link>\n";
    echo "<description>Aktuální data z meteorologických stanic. RSS vytvořil skript PovodiAPI od @DanielKrasny.</description>\n";
    echo "<language>cs-cz</language>\n";
    echo "<item>\n";
    echo "<title>Data z meteorologické stanice ".$td[0][1]->nodeValue." na toku ".$td[1][1]->nodeValue."</title>\n";
    echo "<link>".$domains[$site]."/</link>\n";
    echo "<description></description>\n";
    echo "</item>\n";
}
if ($channel == 'srazky'){
    $minmax = array();
    if($response == 'rss' && $values == 'all'){
    $temp = $_GET["temp"];
    }
}
foreach ($data as $i => $string) {
    if ($i > 0) {
        $cols = $string->getElementsByTagName('td');
        if ($channel == 'srazky'){
        foreach($cols as $nej){
            if($nej->getAttribute('title') == 'Minimální telplota'){
                $minmax["minimum"] = $nej->nodeValue;
            }
            if($nej->getAttribute('title') == 'Maximální teplota'){
                $minmax["maximum"] = $nej->nodeValue;
            }
        }
        if ($response == 'json') {
            if($cols[0]->nodeValue == 'Úhrn srážek za 24h: '){$arr[] = array('totalrain' => $cols[1]->nodeValue, 'minimal-temperature' => $minmax["minimum"], 'maximum-temperature' => $minmax["maximum"]);} else {
                if($values == 'all'){
                $arr[] = array(
                    'date' => strtotime($cols[0]->nodeValue),
                    'rain' => trim($cols[1]->nodeValue).' mm/hod',
                    'temperature' => trim($cols[2]->nodeValue).' °C'
                );
            } else if ($values == 'latest' && $i == 1) {
                $arr[] = array(
                    'date' => strtotime($cols[0]->nodeValue),
                    'rain' => trim($cols[1]->nodeValue).' mm/hod',
                    'temperature' => trim($cols[2]->nodeValue).' °C'
                );
            }
            }
        } else if ($response == 'rss') {
            if ($cols[0]->nodeValue == 'Úhrn srážek za 24h: ') {
                echo "<item>\n";
                echo "<title>Úhrn srážek za posledních 24 hodin je ".$cols[1]->nodeValue.". Minimální teplota byla ".$minmax["minimum"]."°C a maximální teplota byla ".$minmax["maximum"]."°C.</title>\n";
                echo "<link>".$domains[$site]."/</link>\n";
                echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
                echo "</item>\n";
            } else {
                if ($values == 'all') {
                echo "<item>\n";
                if ($temp == 'yes') {
                    echo "<title>Srážky z ".$cols[0]->nodeValue." byly ".$cols[1]->nodeValue." mm/h, teplota ".$cols[2]->nodeValue."°C</title>\n";
                } else {
                    echo "<title>Srážky z ".$cols[0]->nodeValue." byly ".$cols[1]->nodeValue." mm/h</title>\n";
                }
                echo "<link>".$domains[$site]."/</link>\n";
                echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
                echo "</item>\n";
                } else if ($values == 'latest' && $i == 1) {
                    echo "<item>\n";
                    echo "<title>Aktuální informace o srážkách z ".$cols[0]->nodeValue." jsou ".$cols[1]->nodeValue." mm/h, byla naměřena teplota ".$cols[2]->nodeValue."°C.</title>\n";
                    echo "<link>".$domains[$site]."/</link>\n";
                    echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
                    echo "</item>\n";
                }
        }
    }
} else if ($channel == 'sap') {
    if ($response == 'json') {
            if($values == 'all'){
            $arr[] = array(
                'date' => strtotime($cols[0]->nodeValue),
                'water-status' => trim($cols[1]->nodeValue).' cm',
                'flow' => trim($cols[2]->nodeValue).' m³.s¯¹'
            );
        } else if ($values == 'latest' && $i == 1) {
            $arr[] = array(
                'date' => strtotime($cols[0]->nodeValue),
                'water-status' => trim($cols[1]->nodeValue).' cm',
                'flow' => trim($cols[2]->nodeValue).' m³.s¯¹'
            );
        }
    } else if ($response == 'rss') {
            if ($values == 'all') {
            echo "<item>\n";
            echo "<title>Hladina vody z ".$cols[0]->nodeValue." byla ".trim($cols[1]->nodeValue)." cm, průtok ".trim($cols[2]->nodeValue)." m³.s¯¹.</title>\n";
            echo "<link>".$domains[$site]."/</link>\n";
            echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
            echo "</item>\n";
            } else if ($values == 'latest' && $i == 1) {
                echo "<item>\n";
                echo "<title>Aktuální informace o hladině vody z ".$cols[0]->nodeValue.": ".trim($cols[1]->nodeValue)."  cm, průtok ".trim($cols[2]->nodeValue)." m³.s¯¹.</title>\n";
                echo "<link>".$domains[$site]."/</link>\n";
                echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
                echo "</item>\n";
            }
}
} else if ($channel == 'nadrze') {
    if ($response == 'json') {
        if($values == 'all'){
        $arr[] = array(
            'date' => strtotime($cols[0]->nodeValue),
            'surface' => trim($cols[1]->nodeValue).' m n. m.',
            'outflow-rate' => trim($cols[2]->nodeValue).' m³.s¯¹'
        );
    } else if ($values == 'latest' && $i == 1) {
        $arr[] = array(
            'date' => strtotime($cols[0]->nodeValue),
            'surface' => trim($cols[1]->nodeValue).' m n. m.',
            'outflow-rate' => trim($cols[2]->nodeValue).' m³.s¯¹'
        );
    }
} else if ($response == 'rss') {
        if ($values == 'all') {
        echo "<item>\n";
        echo "<title>Hladina vody z ".$cols[0]->nodeValue." byla ".trim($cols[1]->nodeValue)." m n. m., odtok ".trim($cols[2]->nodeValue)." m³.s¯¹.</title>\n";
        echo "<link>".$domains[$site]."/</link>\n";
        echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
        echo "</item>\n";
        } else if ($values == 'latest' && $i == 1) {
            echo "<item>\n";
            echo "<title>Aktuální informace o hladině vody z ".$cols[0]->nodeValue.": ".trim($cols[1]->nodeValue)."  m n. m., odtok ".trim($cols[2]->nodeValue)." m³.s¯¹.</title>\n";
            echo "<link>".$domains[$site]."/</link>\n";
            echo "<description>Data z meteorologické stanice ".$td[0][1]->nodeValue."</description>\n";
            echo "</item>\n";
        }
}
}
}
}
if ($response == 'rss') {
    ?>
</channel>
</rss>
<?php
}
if ($response == 'json') { print(json_encode(array('success' => true, 'info' => array('source' => 'Povodí '.$sites[$site], 'station' => $td[0][1]->nodeValue, 'watercourse' => $td[1][1]->nodeValue, 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'), 'data' => $arr))); }
if ($response != 'json' && $response != 'rss') {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Response method is not valid. Allowed methods are json, rss.', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
}
}
}
}
}
} else {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Invalid website. Allowed domains: pla, pod, poh, pvl', 'thanks-to' => 'PovodiAPI by DanielKrasny', 'script-link' => 'https://github.com/DanielKrasny/PovodiAPI'));
}
?>
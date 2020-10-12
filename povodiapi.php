<?php
/**
 * PovodiAPI <https://github.com/DanielKrasny/PovodiAPI>
 * @version 2.0
 * @author Daniel Krásný <https://github.com/DanielKrasny>
 * 
 * Working with: Povodí Moravy, Povodí Labe, Povodí Odry, Povodí Ohře, Povodí Vltavy
 */

namespace DanielKrasny;
define("POVODI", ["pmo", "pla", "pod", "poh", "pvl"]);
define("SERVERS", ["pmo" => "http://www.pmo.cz/portal/", "pla" => "http://www.pla.cz/portal/", "pod" => "https://www.pod.cz/portal/", "poh" => "https://sap.poh.cz/portal/", "pvl" => "http://www.pvl.cz/portal/"]);
define("SERVICES", ["nadrze" => "Nadrze", "sap" => "SaP", "srazky" => "Srazky"]);
Class PovodiAPI {
    public function stations(string $type, string $service) {
        if (!in_array($type, POVODI))
            throw new \Error("Invalid server type.");
        if (!in_array(strtolower($service), ["nadrze", "sap", "srazky"]))
            throw new \Error("Invalid service type. Allowed options: nadrze/sap/srazky");
        if ($type !== "pmo")
            $url = SERVERS[$type].SERVICES[strtolower($service)]."/cz/PC/";
        else $url = SERVERS[$type].strtolower($service)."/cz/menu.htm";
        $result = $this->curl_request($url);
        $dom = new \DOMDocument();
        @$dom->loadHTML($result);
        $xpath = new \DOMXPath($dom);
        $element = $xpath->query("//select[@id='MonitorovaciStaniceDDL' or @name='StaniceSelect']");
        if ($element->length === 0)
            throw new \Error("Can't get station list.");
        $FinalArray = array();
        foreach ($xpath->query("//option[not(@value='nic' or @value='-1')]", $element[0]) as $options) 
            $FinalArray[] = array("id" => $options->getAttribute("value"), "station" => $options->nodeValue);
        return ["info" => ["source" => SERVERS[$type]], "data" => $FinalArray];
    }

    public function sap (string $type, string $stationID) {
        if (!in_array($type, POVODI))
            throw new \Error("Invalid server type.");
        if (strtolower($type) !== "pmo") {
            $station = explode("|", $stationID);
            if (count($station) !== 2)
                throw new \Error("Invalid station input. Supported method is a string id|oid, read more at https://github.com/DanielKrasny/PovodiAPI");
                $url = SERVERS[$type]."SaP/cz/PC/Mereni.aspx?".http_build_query(array("id" => $station[0], "oid" => $station[1]));
        } else $url = SERVERS[$type]."sap/cz/mereni_{$stationID}.htm";
        $result = $this->curl_request($url, true);
        if ($result["code"] !== 200)
            throw new \Error("Station not found.");
        $dom = new \DOMDocument();
        @$dom->loadHTML($result["data"]);
        $xpath = new \DOMXPath($dom);
        $data = $xpath->query("//table[@id='ObsahCPH_DataMereniGV']/tr[not(@class='text1')] | //table[@width='270']/tr[preceding-sibling::*]");
        $station = $xpath->query("(//td[@width='225']/font[@class='text1bold'])[1] | //*[@id='ObsahCPH_UdajeStaniceFW_NazevStaniceLbl']");
        $watercourse = $xpath->query("(//td[@width='225']/font[@class='text1bold'])[last()] | //*[@id='ObsahCPH_UdajeStaniceFW_NazevTokuLbl']");
        $img = $xpath->query("//img[@id='ObsahCPH_GrafImg'] | //img[contains(@src, 'grafy')]");
        if ($data->length === 0 || $station->length === 0 || $watercourse->length === 0 || $img->length === 0)
            throw new \Error("Parsing failed.");
        $images = array();
        $FinalArray = array();
        foreach ($img as $obrazky)
            $images[] = str_replace(((strtolower($type) === "pmo") ? "../" : "../../"), SERVERS[$type].((strtolower($type) === "pmo") ? "sap/" : "SaP/"), $obrazky->getAttribute("src"));
        foreach ($data as $string) {
            $cols = $xpath->query("td", $string);
                if ($cols->length === 4)
                    $FinalArray[] = array(
                        'date' => strtotime(str_replace('.'.date('y').' ', '.'.date('Y').' ', html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[0]->nodeValue, null, 'utf-8'))))),
                        'water-status' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[1]->nodeValue, null, 'utf-8'))))),
                        'flow' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[2]->nodeValue)))))
                    );
        }
        return ["info" => ["source" => SERVERS[$type], "station" => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($station[0]->nodeValue, null, 'utf-8')))), 'watercourse' => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($watercourse[0]->nodeValue, null, 'utf-8')))), "data" => $FinalArray, "images" => $images]];
    }

    public function nadrze (string $type, string $stationID) {
        if (!in_array($type, POVODI))
            throw new \Error("Invalid server type.");
        if (strtolower($type) !== "pmo") {
            $station = explode("|", $stationID);
            if (count($station) !== 2)
                throw new \Error("Invalid station input. Supported method is a string id|oid, read more at https://github.com/DanielKrasny/PovodiAPI");
                $url = SERVERS[$type]."Nadrze/cz/PC/Mereni.aspx?".http_build_query(array("id" => $station[0], "oid" => $station[1]));
        } else $url = SERVERS[$type]."nadrze/cz/mereni_{$stationID}.htm";
        $result = $this->curl_request($url, true);
        if ($result["code"] !== 200)
            throw new \Error("Station not found.");
        $dom = new \DOMDocument();
        @$dom->loadHTML($result["data"]);
        $xpath = new \DOMXPath($dom);
        $data = $xpath->query("//table[@id='dataMereni24hGV']/tr[not(@class='bunkaHlavicky')] | //table[@width='300']/tr[preceding-sibling::*]");
        $station = $xpath->query("//*[@class='text3bold'] | //*[@id='nazevLbl']");
        $watercourse = $xpath->query("(//*[@class='text5'])[1] | //*[@id='povodiLbl']");
        $img = $xpath->query("//img[@id='GrafTydenniImg'] | //img[@id='schemaNadrzeImg'] | //img[contains(@src, 'grafy')]");
        if ($data->length === 0 || $station->length === 0 || $watercourse->length === 0 || $img->length === 0)
            throw new \Error("Parsing failed.");
        $images = array();
        $FinalArray = array();
        foreach ($img as $obrazky) {
            $src = str_replace(((strtolower($type) === "pmo") ? "../" : "../../"), SERVERS[$type].((strtolower($type) === "pmo") ? "nadrze/" : "Nadrze/"), $obrazky->getAttribute("src"));
            if (!in_array($src, $images))
                $images[] = $src;
        }
        foreach ($data as $string) {
            $cols = $xpath->query("td", $string);
                if ($cols->length === 4 || $cols->length === 3)
                    $FinalArray[] = array(
                        'date' => strtotime(str_replace('.'.date('y').' ', '.'.date('Y').' ', html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[0]->nodeValue, null, 'utf-8'))))),
                        'surface' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[1]->nodeValue, null, 'utf-8'))))),
                        'outflow-rate' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[2]->nodeValue)))))
                    );
        }
        return ["info" => ["source" => SERVERS[$type], "station" => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($station[0]->nodeValue, null, 'utf-8')))), 'watercourse' => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($watercourse[0]->nodeValue, null, 'utf-8')))), "data" => $FinalArray, "images" => $images]];
    }

    public function srazky (string $type, string $stationID) {
        if (!in_array($type, POVODI))
            throw new \Error("Invalid server type.");
        if (strtolower($type) !== "pmo") {
            $station = explode("|", $stationID);
            if (count($station) !== 2)
                throw new \Error("Invalid station input. Supported method is a string id|oid, read more at https://github.com/DanielKrasny/PovodiAPI");
                $url = SERVERS[$type]."Srazky/cz/PC/Mereni.aspx?".http_build_query(array("id" => $station[0], "oid" => $station[1]));
        } else $url = SERVERS[$type]."srazky/cz/mereni_{$stationID}.htm";
        $result = $this->curl_request($url, true);
        if ($result["code"] !== 200)
            throw new \Error("Station not found.");
        $dom = new \DOMDocument();
        @$dom->loadHTML($result["data"]);
        $xpath = new \DOMXPath($dom);
        $data = $xpath->query("//table[@id='ObsahCPH_dataMereni24hGV']/tr[not(@class='text1') and not(@class='bunkaGridu')] | //table[@width='300']/tr[preceding-sibling::*]");
        $station = $xpath->query("(//*[@class='text3bold'])[1] | //*[@id='ObsahCPH_hlavickaFormView_nazevLbl']");
        $watercourse = $xpath->query("(//*[@class='text5'])[1] | //*[@id='ObsahCPH_hlavickaFormView_povodiLbl']");
        $img = $xpath->query("//img[@id='ObsahCPH_GrafImg'] | //img[contains(@src, 'grafy')]");
        if ($data->length === 0 || $station->length === 0 || $watercourse->length === 0 || $img->length === 0)
            throw new \Error("Parsing failed.");
        $images = array();
        $FinalArray = array();
        foreach ($img as $obrazky)
            $images[] = str_replace(((strtolower($type) === "pmo") ? "../" : "../../"), SERVERS[$type].((strtolower($type) === "pmo") ? "srazky/" : "Srazky/"), $obrazky->getAttribute("src"));
        foreach ($data as $string) {
            $cols = $xpath->query("td", $string);
                if ($cols->length === 3)
                    $FinalArray[] = array(
                        'date' => strtotime(str_replace('.'.date('y').' ', '.'.date('Y').' ', html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[0]->nodeValue, null, 'utf-8'))))),
                        'rain' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[1]->nodeValue, null, 'utf-8'))))),
                        'temperature' => (float) str_replace(',', '.', trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($cols[2]->nodeValue)))))
                    );
        }
        return ["info" => ["source" => SERVERS[$type], "station" => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($station[0]->nodeValue, null, 'utf-8')))), 'watercourse' => trim(html_entity_decode(str_replace('&nbsp;', ' ', htmlentities($watercourse[0]->nodeValue, null, 'utf-8')))), "data" => $FinalArray, "images" => $images]];
    }

    private function curl_request (string $url, bool $returnCode = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code !== 200 && $returnCode === false)
            throw new \Error("Invalid response code: {$code}");
        return ($returnCode) ? ["code" => $code, "data" => $output] : $output;
    }
}

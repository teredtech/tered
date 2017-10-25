<?php
/**
 * Special version of htmlspecialchars
 * @param  string $string 
 * @return string 
 */
function htmlchars($string = "")
{
    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}



/**
 * Truncate string
 * @param  string  $string     
 * @param  integer $max_length Max length of result
 * @param  string  $ellipsis   
 * @param  boolean $trim       
 * @return string              
 */
function truncate_string($string = "", $max_length = 50, $ellipsis = "...", $trim = true)
{
    $max_length = (int)$max_length;
    if ($max_length < 1) {
        $max_length = 50;
    }

    if (!is_string($string)) {
        $string = "";
    }

    if ($trim) {
        $string = trim($string);
    }

    if (!is_string($ellipsis)) {
        $ellipsis = "...";
    }

    $string_length = mb_strlen($string);
    $ellipsis_length = mb_strlen($ellipsis);
    if($string_length > $max_length){
        if ($ellipsis_length >= $max_length) {
            $string = mb_substr($ellipsis, 0, $max_length);
        } else {
            $string = mb_substr($string, 0, $max_length - $ellipsis_length)
                    . $ellipsis;
        }
    }

    return $string;
}



/**
 * Create SEO friendly url slug from string
 * @param  string $string 
 * @return string         
 */
function url_slug($string = "")
{
    if (!is_string($string)) {
        $string = "";
    }

    $s = trim(mb_strtolower($string));
    
    // Replace azeri characters
    $s = str_replace(
        array("ü", "ö", "ğ", "ı", "ə", "ç", "ş"), 
        array("u", "o", "g", "i", "e", "c", "s"), 
        $s);
    
    // Replace cyrilic characters
    $cyr = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м',
                 'н','о','п','р','с','т','у', 'ф','х','ц','ч','ш','щ','ъ', 
                 'ы','ь', 'э', 'ю','я');
    $lat = array('a','b','v','g','d','e','io','zh','z','i','y','k','l',
                 'm','n','o','p','r','s','t','u', 'f', 'h', 'ts', 'ch',
                 'sh', 'sht', 'a', 'i', 'y', 'e','yu', 'ya');
    $s = str_replace($cyr, $lat, $s);

    // Replace all other characters
    $s = preg_replace("/[^a-z0-9]/", "-", $s);

    // Replace consistent dashes
    $s = preg_replace("/-{2,}/", "-", $s);

    return trim($s, "-");
}


/**
 * Delete file or folder (with content)
 * @param string $path Path to file or folder
 */
function delete($path)
{
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            delete(realpath($path) . '/' . $file);
        }

        return rmdir($path);
    } else if (is_file($path) === true) {
        return unlink($path);
    }

    return false;
}



/**
 * Format price
 * @param  decimal $price 
 * @return string        
 */
function format_price($price){
    $price = number_format($price, 2, ".", "");
    $parts = explode(".", $price, 2);

    $price = $parts[0] . ".<sup>".$parts[1]."</sup>";
    return $price;
}



/**
 * Get an array of timezones
 * @return array
 */
function getTimezones()
{
    $timezoneIdentifiers = DateTimeZone::listIdentifiers();
    $utcTime = new DateTime('now', new DateTimeZone('UTC'));
 
    $tempTimezones = array();
    foreach ($timezoneIdentifiers as $timezoneIdentifier) {
        $currentTimezone = new DateTimeZone($timezoneIdentifier);
 
        $tempTimezones[] = array(
            'offset' => (int)$currentTimezone->getOffset($utcTime),
            'identifier' => $timezoneIdentifier
        );
    }
 
    // Sort the array by offset,identifier ascending
    usort($tempTimezones, function($a, $b) {
        return ($a['offset'] == $b['offset'])
            ? strcmp($a['identifier'], $b['identifier'])
            : $a['offset'] - $b['offset'];
    });
 
    $timezoneList = array();
    foreach ($tempTimezones as $tz) {
        $sign = ($tz['offset'] > 0) ? '+' : '-';
        $offset = gmdate('H:i', abs($tz['offset']));
        $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
            $tz['identifier'];
    }
 
    return $timezoneList;
}


/**
 * Validate date
 * @param  string  $date   date string
 * @param  string  $format 
 * @return boolean         
 */
function isValidDate($date, $format = 'Y-m-d H:i:s')
{
    $d = \DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}


/**
 * Check if FFMPEG and FFPROBE extensions are installed
 * @return boolean 
 */
function isVideoExtenstionsLoaded()
{
    \InstagramAPI\Utils::$ffmpegBin = FFMPEGBIN;
    \InstagramAPI\Utils::$ffprobeBin = FFPROBEBIN;


    if (\InstagramAPI\Utils::checkFFMPEG() && 
        \InstagramAPI\Utils::checkFFPROBE()) {
        return true;
    }

    return false;
}


/**
 * textInitials
 * @param  string  $text   
 * @param  integer $length result length
 * @return string          
 */
function textInitials($text, $length=1)
{
    $text = (string)$text;
    $length = (int)$length;

    if (mb_strlen($text) < $length || $length < 1) {
        return $text;
    }

    $parts = explode(" ", $text);
    foreach ($parts as &$p) {
        if (trim($p) == "") {
            unset($p);
        }
    }

    if (count($parts) >= $length) {
        $res = "";
        for ($i = 0; $i < $length; $i++) {
            $res .= mb_substr($parts[$i], 0, 1);
        }
    } else {
        if ($length == 1) {
            $res =  mb_substr($text, 0, 1);
        } else if ($length == 2) {
            $res =  mb_substr($text, 0, 1).mb_substr($text, -1, 1);
        } else {
            $res =  mb_substr($text, 0, $length);
        }
    }

    return $res;
}


/**
 * Generate human readable random text
 * @param  integer $length length of the returned string
 * @return string          Random string
 */
function readableRandomString($length = 6)
{  
    $string     = '';
    $vowels     = array("a","e","i","o","u");  
    $consonants = array(
        'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 
        'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
    );  
    // Seed it
    srand((double) microtime() * 1000000);
    $max = $length/2;
    for ($i = 1; $i <= $max; $i++)
    {
        $string .= $consonants[rand(0,19)];
        $string .= $vowels[rand(0,4)];
    }
    return $string;
}

/**
 * Convert size in byte to human readable text
 * 
 * @param  integer  $size      size in bytes
 * @param  integer $precision 
 * @return string|bool             
 */
function readableFileSize($size, $precision = 2) {
    if ($size < 0) {
        $size = 0;
    }

    $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $step = 1024;
    $i = 0;

    $max = count($units) - 1;

    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;

        if ($i > $max) {
            return false;
        }
    }

    return round($size, $precision). $units[$i];
}


/**
 * Convert numbers to human readable formats (Ex: 3K, 3.4M)
 * @param  integer $numbers Number to convert
 * @return string          
 */
function readableNumber($numbers, $precision = 2)
{
   $readable = ["",  "K", "M", "B"];
   $index = 0;

   while($numbers > 1000){
      $numbers /= 1000;
      $index++;
   }

   return round($numbers, $precision) ." ". $readable[$index];
}



/**
 * Validates proxy address
 * @param  string  $proxy [description]
 * @return boolean        [description]
 */
function isValidProxy($proxy)
{
    if (!is_string($proxy) && !is_array($proxy)) {
        return false;        
    }

    try {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', 'http://www.instagram.com', 
                                [
                                    "verify" => SSL_ENABLED,
                                    "timeout" => 10,
                                    "proxy" => $proxy
                                ]);
        $code = $res->getStatusCode();
    } catch (\Exception $e) {
        return false;
    }

    return $code == 200;
}






/**
 * Get data from general data
 * @param  string $name  data identifier
 * @param  string $field 
 * @return string        
 */
function general_data($name, $field = null)
{
    if (!is_string($name)) {
        return null;
    }


    if (!isset($GLOBALS["General_Data"]) || !is_array($GLOBALS["General_Data"])) {
        $GLOBALS["General_Data"] = array();
    }

    if (isset($GLOBALS["General_Data"][$name])) {
        $settings = $GLOBALS["General_Data"][$name];
    } else {
        $settings = Controller::model("GeneralData", $name);
        $GLOBALS["General_Data"][$name] = $settings;
    }


    if (is_string($field)) {
        return htmlchars($settings->get("data.".$field));
    } 

    return $settings;
}


/**
 * Get settings 
 * @return string
 */
function site_settings($field = null)
{
    return general_data("settings", $field);
}


/**
 * Get integrations settings 
 * @return string
 */
function integrations($field = null)
{
    return general_data("integrations", $field);
}



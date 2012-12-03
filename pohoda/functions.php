<?php

set_time_limit(1800);

function createUrlSlug($url)
{
    $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
    $url = trim($url, "-");
    $url = strtr(
            $url,
            array("\xc3\xa1"=>"a","\xc3\xa4"=>"a","\xc4\x8d"=>"c","\xc4\x8f"=>"d","\xc3\xa9"=>"e","\xc4\x9b"=>"e","\xc3\xad"=>"i","\xc4\xbe"=>"l","\xc4\xba"=>"l","\xc5\x88"=>"n","\xc3\xb3"=>"o","\xc3\xb6"=>"o","\xc5\x91"=>"o","\xc3\xb4"=>"o","\xc5\x99"=>"r","\xc5\x95"=>"r","\xc5\xa1"=>"s","\xc5\xa5"=>"t","\xc3\xba"=>"u","\xc5\xaf"=>"u","\xc3\xbc"=>"u","\xc5\xb1"=>"u","\xc3\xbd"=>"y","\xc5\xbe"=>"z","\xc3\x81"=>"A","\xc3\x84"=>"A","\xc4\x8c"=>"C","\xc4\x8e"=>"D","\xc3\x89"=>"E","\xc4\x9a"=>"E","\xc3\x8d"=>"I","\xc4\xbd"=>"L","\xc4\xb9"=>"L","\xc5\x87"=>"N","\xc3\x93"=>"O","\xc3\x96"=>"O","\xc5\x90"=>"O","\xc3\x94"=>"O","\xc5\x98"=>"R","\xc5\x94"=>"R","\xc5\xa0"=>"S","\xc5\xa4"=>"T","\xc3\x9a"=>"U","\xc5\xae"=>"U","\xc3\x9c"=>"U","\xc5\xb0"=>"U","\xc3\x9d"=>"Y","\xc5\xbd"=>"Z")
    );
    //$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
    $url = strtolower($url);
    $url = preg_replace('~[^-a-z0-9_]+~', '', $url);
    return $url;
}

function addSlashesToArray(&$array)
{
    foreach ($array as $key => &$val) {
        if (is_string($val)) {
            $val = addslashes($val);
        }
    }
}

/**
 * Validator EAN kodu
 * @see http://en.wikipedia.org/wiki/International_Article_Number_(EAN)
 *
 * Validujeme EAN13, ktery je standard pro EU
 * Defined by Zend_Validate_Interface
 *
 * @param  string $value
 * @return boolean
 */
function isValidEan13($value)
{
    if (strlen($value) !== 13) {
        return false;
    }

    $barcode = strrev(substr($value, 0, -1));
    $oddSum  = 0;
    $evenSum = 0;

    for ($i = 0; $i < 12; $i++) {
        if ($i % 2 === 0) {
            $oddSum += $barcode[$i] * 3;
        } elseif ($i % 2 === 1) {
            $evenSum += $barcode[$i];
        }
    }

    $calculation = ($oddSum + $evenSum) % 10;
    $checksum    = ($calculation === 0) ? 0 : 10 - $calculation;

    if ($value[12] != $checksum) {
        return false;
    }

    return true;
}

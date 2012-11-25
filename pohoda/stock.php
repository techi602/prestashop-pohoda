<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 7310 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

//define('_PS_DEBUG_SQL_', true);

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

// Get data

$number = ((int)(Tools::getValue('n')) ? (int)(Tools::getValue('n')) : 10);
$orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
$orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
$id_category = ((int)(Tools::getValue('id_category')) ? (int)(Tools::getValue('id_category')) : Configuration::get('PS_HOME_CATEGORY'));
$products = Product::getProducts((int)Context::getContext()->language->id, 0, ($number > 10 ? 10 : $number), $orderBy, $orderWay, $id_category, true);
$currency = new Currency((int)Context::getContext()->currency->id);
$affiliate = (Tools::getValue('ac') ? '?ac='.(int)(Tools::getValue('ac')) : '');

//$xml = file_get_contents("php://input");
//file_put_contents("stock.xml", $xml);

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

$dom = new DomDocument();
$dom->load('stock.xml');

//echo $dom->saveXml();

$xpath = new DOMXPath($dom);


//$roots = $xpath->query('//rsp:responsePackItem[@state=\'ok\']/lst:listStock[@state=\'ok\']');

$roots = $xpath->query('//rsp:responsePackItem/lStk:listStock');

//var_dump($roots->length);
//var_dump($roots);

$shopId = 1; // administration

$db = DbCore::getInstance();

$query = new DbQuery();
$query->select("id_lang");
$query->from("lang");
$query->where("iso_code = 'cs'");

$langId = $db->getValue($query);
$date = date('Y-m-d H:i:s');


if ($roots->length > 0) {
    for ($i = 0; $i < $roots->length; $i++) {
        $products = $xpath->query('./lStk:stock', $roots->item($i));
        
        for ($j = 0; $j < $products->length; $j++) {
            $node = $products->item($j);
    
            $id = $xpath->query('./stk:stockHeader/stk:id', $node)->item(0)->nodeValue;
            
    		$name = $xpath->query('./stk:stockHeader/stk:name', $node)->item(0)->nodeValue;
    		$shortName = @$xpath->query('./stk:stockHeader/stk:shortName', $node)->item(0)->nodeValue;
    		$code = @$xpath->query('./stk:stockHeader/stk:code', $node)->item(0)->nodeValue;
    		$ean = @$xpath->query('./stk:stockHeader/stk:EAN', $node)->item(0)->nodeValue;
    		$isSales = $xpath->query('./stk:stockHeader/stk:isSales', $node)->item(0)->nodeValue;
    		$isInternet = @$xpath->query('./stk:stockHeader/stk:isInternet', $node)->item(0)->nodeValue;
    		$mass = @$xpath->query('./stk:stockHeader/stk:mass', $node)->item(0)->nodeValue;
    		$quantity = @$xpath->query('./stk:stockHeader/stk:count', $node)->item(0)->nodeValue;
    		$sellingPrice = $xpath->query('./stk:stockHeader/stk:sellingPrice', $node)->item(0)->nodeValue;
    		$producer = @$xpath->query('./stk:stockHeader/stk:producer', $node)->item(0)->nodeValue;
    		$availability = @$xpath->query('./stk:stockHeader/stk:availability', $node)->item(0)->nodeValue;
    		$description = @$xpath->query('./stk:stockHeader/stk:description', $node)->item(0)->nodeValue;
    		$description2 = @$xpath->query('./stk:stockHeader/stk:description2', $node)->item(0)->nodeValue;

    		
    		/*
    		$query = new DbQuery();
    		$query->select(implode(', ', array('p.`id_product`', 'p.`upc`', 'p.`ean13`')));
    		$query->from('product', 'p');
    		$query->where("p.ean13 = '" . $db->escape($ean) . "'");
    		$result = $db->getRow($query);
    		*/
    		

    		$data = array();
    		$data['id_product'] = $id;
    		$data['ean13'] = $ean;
    		$data['upc'] = '';
    		$data['reference'] = $code;
    		$data['quantity'] = $quantity;
    		$data['weight'] = $mass;
    		$data['active'] = $isSales == 'true';
    		$data['available_for_order'] = $isSales == 'true';
    		$data['price'] = $sellingPrice;
    		$data['id_category_default'] = 1; // default
    		$data['id_shop_default'] = 1;
    		$data['id_tax_rules_group'] = 1;
    		$data['on_sale'] = 1;
    		$data['show_price'] = 1;
    		$data['indexed'] = 1;
    		$data['cache_default_attribute'] = 1;
    		addSlashesToArray($data);
    		
    		

    		$langdata = array();
    		$langdata['id_shop'] = $shopId;
    		$langdata['id_lang'] = $langId;
    		$langdata['description'] = $description2;
    		$langdata['description_short'] = $description;
    		$langdata['link_rewrite'] = createUrlSlug($name);
    		$langdata['name'] = $name;
    		$langdata['available_now'] = 'Skladem';
    		$langdata['available_later'] = $availability;
    		$langdata['id_shop'] = $shopId;
    		$langdata['id_product'] = $id;
    		addSlashesToArray($langdata);

    		$shopdata = array();
    		$shopdata['price'] = $sellingPrice;
    		$shopdata['id_shop'] = $shopId;
    		$shopdata['on_sale'] = 1;
    		$shopdata['id_product'] = $id;
    		$shopdata['active'] = $isSales == 'true';
    		$shopdata['id_category_default'] = 1;
    		$shopdata['id_tax_rules_group'] = 1;
    		$shopdata['indexed'] = 1;
    		$shopdata['cache_default_attribute'] = 0;
    		addSlashesToArray($shopdata);
    		
    		$table = 'product';
    		$query = new DbQuery();
    		$query->select('COUNT(*)');
    		$query->from($table, 'p');
    		$query->where("p.id_product = '" . $db->escape($id) . "'");
    		$productExists = (int) $db->getValue($query);
    		
    		if ($productExists) {
    		    $where = "id_product = '" . $db->escape($id) . "'";
    		    $db->update($table, $data, $where);
    		    
    		    if ($db->Affected_Rows()) {
    		        $db->update($table, array('date_upd' => $date), $where);
    		    }
    		} else {
    		    $data['date_upd'] = $date;
    		    $data['date_add'] = $date;
    		    
    		    $db->insert($table, $data);
    		}
    		
    		$table = 'product_lang';
    		$query = new DbQuery();
    		$query->select('COUNT(*)');
    		$query->from($table, 'p');
    		$query->where("p.id_product = '" . $db->escape($id) . "'");
    		$query->where("p.id_shop = '" . $db->escape($shopId) . "'");
    		$query->where("p.id_lang = '" . $db->escape($langId) . "'");
    		$productLangExists = (int) $db->getValue($query);
    		
    		if ($productLangExists) {
    		    $where = "id_product = '" . $db->escape($id) . "' AND id_lang = '" . $db->escape($langId) . "' AND id_shop = '" . $db->escape($shopId) . "'";
    		    $db->update($table, $langdata, $where);
    		} else {
    		    $db->insert($table, $langdata);
    		}
    		
    		$table = 'product_shop';
    		$query = new DbQuery();
    		$query->select('COUNT(*)');
    		$query->from($table, 'p');
    		$query->where("p.id_product = '" . $db->escape($id) . "'");
    		$query->where("p.id_shop = '" . $db->escape($shopId) . "'");
    		$productShopExists = (int) $db->getValue($query);
    		
    		if ($productShopExists) {
    		    $where = "id_product = '" . $db->escape($id) . "' AND id_shop = '" . $db->escape($shopId) . "'";
    		    $db->update($table, $shopdata, $where);
    		    
    		    if ($db->Affected_Rows()) {
    		        $db->update($table, array('date_upd' => $date),  $where);
    		    }
    		    
    		} else {
    		    $shopdata['date_upd'] = $date;
    		    $shopdata['date_add'] = $date;
    		    $db->insert($table, $shopdata);
    		}
    		
    		
    		
    		

/*
echo $name ."\n";
echo $code ."\n";
echo $ean ."\n";
echo "\n";
*/




            
    //        foreach ($node->childNodes as $node2) {
//                var_dump($node2->nodeName);
  //              foreach ($node2->childNodes as $node3) {
  //                  var_dump($node3->nodeName);
//                    var_dump($node3->nodeValue);
//                }
//            }
            
        }
    }
}

// Send feed
//header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/response.xsd" version="2.0" id="00000001" state="ok" application="Prestashop" note="Prestashop import"></rsp:responsePack>

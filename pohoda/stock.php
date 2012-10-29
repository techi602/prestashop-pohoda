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



$dom = new DomDocument();
$dom->load('stock.xml');

//echo $dom->saveXml();

$xpath = new DOMXPath($dom);


//$roots = $xpath->query('//rsp:responsePackItem[@state=\'ok\']/lst:listStock[@state=\'ok\']');

$roots = $xpath->query('//rsp:responsePackItem/lStk:listStock');

//var_dump($roots->length);
//var_dump($roots);

$shopId = 1; // administration

$langId = 7; // detect ID with cs flag in database

$db = DbCore::getInstance();


$date = date('Y-m-d H:i:s');

if ($roots->length > 0) {
    for ($i = 0; $i < $roots->length; $i++) {
        $products = $xpath->query('./lStk:stock', $roots->item($i));
        
        for ($j = 0; $j < $products->length; $j++) {
            $node = $products->item($j);
    
    		$name = $xpath->query('./stk:stockHeader/stk:name', $node)->item(0)->nodeValue;
    		$shortName = $xpath->query('./stk:stockHeader/stk:shortName', $node)->item(0)->nodeValue;
    		$code = @$xpath->query('./stk:stockHeader/stk:code', $node)->item(0)->nodeValue;
    		$ean = @$xpath->query('./stk:stockHeader/stk:EAN', $node)->item(0)->nodeValue;
    		$isSales = $xpath->query('./stk:stockHeader/stk:isSales', $node)->item(0)->nodeValue;
    		$isInternet = $xpath->query('./stk:stockHeader/stk:isInternet', $node)->item(0)->nodeValue;
    		$mass = $xpath->query('./stk:stockHeader/stk:mass', $node)->item(0)->nodeValue;
    		$quantity = $xpath->query('./stk:stockHeader/stk:count', $node)->item(0)->nodeValue;
    		$sellingPrice = $xpath->query('./stk:stockHeader/stk:sellingPrice', $node)->item(0)->nodeValue;
    		$producer = $xpath->query('./stk:stockHeader/stk:producer', $node)->item(0)->nodeValue;
    		$availability = $xpath->query('./stk:stockHeader/stk:availability', $node)->item(0)->nodeValue;
    		$description = $xpath->query('./stk:stockHeader/stk:description', $node)->item(0)->nodeValue;
    		$description2 = $xpath->query('./stk:stockHeader/stk:description2', $node)->item(0)->nodeValue;

    		
    		$query = new DbQuery();
    		$query->select(implode(', ', array('p.`id_product`', 'p.`upc`', 'p.`ean13`')));
    		$query->from('product', 'p');
    		$query->where("p.ean13 = '" . $db->escape($ean) . "'");
    		
    		
    		
    		$result = $db->getRow($query);
    		
    		
    		$data = array();
    		$data['ean13'] = $ean;
    		$data['upc'] = $code;
    		$data['quantity'] = $quantity;
    		$data['weight'] = $mass;
    		$data['active'] = $isSales;
    		$data['price'] = $sellingPrice;
    		
    		$data['date_upd'] = $date;
    		
    		$langdata = array();
    		$langdata['id_product'] = $id;
    		$langdata['id_shop'] = $shopId;
    		$langdata['id_lang'] = $langId;
    		$langdata['description'] = $description2;
    		$langdata['description_short'] = $description;
    		$langdata['link_rewrite'] = strtolower($name);
    		$langdata['name'] = $name;
    		$langdata['available_now'] = 'Skladem';
    		$langdata['available_later'] = $availability;
    		
    		$shopdata = array();
    		
    		$langdata['id_shop'] = $shopId;
    		$langdata['date_upd'] = $date;
    		
    		
    		if (!$result) {
    		    
    		    $data['date_add'] = $date;
    		    
    		    

    		    $db->insert('product', $data);
    		    
    		    $id = $db->Insert_ID();
    		    
    		    
    		    $langdata['date_add'] = $date;
    		    $langdata['id_product'] = $id;
    		    $db->insert('product_lang', $langdata);
    		    
    		    
    		    echo "Inserting product $id $ean<br>\n";
    		    
    		   
    		    
    		    
    		} else {
    		    
    		    $db->insert('product', $data, 'product_id = ' . (int) $result['id_product']);
    		    
    		    echo "Updating product $id $ean<br>\n";
    		    
    		    $langdata['product_id'] = $result['id_product'];
    		    
    		    
    		    $db->insert('product_lang', $langdata, false, false, Db::REPLACE);
    		    
    		    
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
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/response.xsd" version="2.0" id="00000001" state="ok" application="Prestashop" note="Prestashop import"></rsp:responsePack>

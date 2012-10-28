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


// Send feed
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>


$dom = new DomDocument();
$dom->load('stock.xml');

//echo $dom->saveXml();

$xpath = new DOMXPath($dom);


//$roots = $xpath->query('//rsp:responsePackItem[@state=\'ok\']/lst:listStock[@state=\'ok\']');

$roots = $xpath->query('//rsp:responsePackItem/lStk:listStock');

//var_dump($roots->length);
//var_dump($roots);

if($roots->length > 0){
    for($i=0; $i < $roots->length; $i++){
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



echo $name ."\n";
echo $code ."\n";
echo $ean ."\n";
echo "\n";





            
    //        foreach ($node->childNodes as $node2) {
//                var_dump($node2->nodeName);
  //              foreach ($node2->childNodes as $node3) {
  //                  var_dump($node3->nodeName);
//                    var_dump($node3->nodeValue);
//                }
//            }
            
        }
    }
die;
}

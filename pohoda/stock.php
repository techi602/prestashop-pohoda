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
require_once(dirname(__FILE__).'/functions.php');

// Get data
$rootCategory = ((int)(Tools::getValue('id_category')) ? (int)(Tools::getValue('id_category')) : Configuration::get('PS_HOME_CATEGORY')); // 1

define('IMAGE_IMPORT_FOLDER', _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);

$headers = getallheaders();
$debug = true;

$log = array();
function logResponse($message)
{
    $GLOBALS['log'][] = $message;
}

$content = file_get_contents("php://input");
$file = "stock.xml";

if (!empty($content)) {
    $file = "stock" . date("_Y-m-d_H-i-s") . ".xml";
    file_put_contents($file, $content);
}
$dom = new DomDocument();
$dom->load($file);


$xpath = new DOMXPath($dom);

// version 1.0
//$roots = $xpath->query('//rsp:responsePackItem[@state=\'ok\']/lst:listStock[@state=\'ok\']');

// version 2.0
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
            $isSales = $xpath->query('./stk:stockHeader/stk:isSales', $node)->item(0)->nodeValue == 'true';
            $isInternet = @$xpath->query('./stk:stockHeader/stk:isInternet', $node)->item(0)->nodeValue == 'true';
            $mass = @$xpath->query('./stk:stockHeader/stk:mass', $node)->item(0)->nodeValue;
            $quantity = @$xpath->query('./stk:stockHeader/stk:count', $node)->item(0)->nodeValue;
            $sellingPrice = $xpath->query('./stk:stockHeader/stk:sellingPrice', $node)->item(0)->nodeValue;
            $purchasingPrice = $xpath->query('./stk:stockHeader/stk:sellingPrice', $node)->item(0)->nodeValue;
            $producer = @$xpath->query('./stk:stockHeader/stk:producer', $node)->item(0)->nodeValue;
            $availability = @$xpath->query('./stk:stockHeader/stk:availability', $node)->item(0)->nodeValue;
            $description = @$xpath->query('./stk:stockHeader/stk:description', $node)->item(0)->nodeValue;
            $description2 = @$xpath->query('./stk:stockHeader/stk:description2', $node)->item(0)->nodeValue;
            $vatRate = @$xpath->query('./stk:stockHeader/stk:purchasingRateVAT', $node)->item(0)->nodeValue;
            //$defaultPicture = @$xpath->query('./stk:stockHeader/stk:pictures/stk:picture[@default="true"]/stk:filepath', $node)->item(0)->nodeValue;
            $pictures = @$xpath->query('./stk:stockHeader/stk:pictures/stk:picture', $node);
            $parameters = @$xpath->query('./stk:stockHeader/stk:intParameters/stk:intParameter', $node);
            $recommended = @$xpath->query('./stk:stockHeader/stk:recommended', $node)->item(0)->nodeValue == 'true';
            $sale = @$xpath->query('./stk:stockHeader/stk:sale', $node)->item(0)->nodeValue == 'true';
            $news = @$xpath->query('./stk:stockHeader/stk:news', $node)->item(0)->nodeValue == 'true';

            $categoryDefaultId = 1;
            $categoryIds = array();
            $categories = @$xpath->query('./stk:stockHeader/stk:categories/stk:idCategory', $node);
            if ($categories) {
                for ($i = 0; $i < $categories->length; $i++) {
                    $category = (int) $categories->item($i)->nodeValue;
                    if ($category) {
                        $categoryIds[] = $category;
                    }
                }
            }

            if ($categoryIds) {
                // find deepest
                $table = 'category';
                $query = new DbQuery();
                $query->select('id_category');
                $query->from($table, 'p');
                $query->where("p.id_category IN (" . implode(', ', $categoryIds) . ")");
                $query->orderBy('level_depth DESC');

                $categoryDefaultId = (int) $db->getValue($query);
            }

            switch ($vatRate) {
                case 'none':
                    $taxId = 0;
                    break;

                case 'low':
                    $taxId = 2;
                    break;

                case 'high':
                    $taxId = 1;
                    break;

                default:
                    $taxId = 0;
            }

            if (!empty($shortName)) {
                $name = $shortName;
            }

            $manufacturerId = 0;
            if (!empty($producer)) {
                $table = 'manufacturer';
                $query = new DbQuery();
                $query->select('id_manufacturer');
                $query->from($table, 'p');
                $query->where("p.name LIKE '" . $db->escape($producer) . "'");
                $manufacturerId = (int) $db->getValue($query);

                if (empty($manufacturerId)) {
                    $manufacturer = new Manufacturer();
                    $manufacturer->name = $producer;
                    $manufacturer->active = true;
                    $manufacturer->save();

                    $manufacturerId = $manufacturer->id;
                }
            }

            // ean check
            if ($ean) {
                if (!isValidEan13($ean)) {
                    $ean = null;
                }
            }


            $active = (int) $isInternet;
            $data = array();
            $data['id_product'] = $id;
            $data['id_manufacturer'] = $manufacturerId;
            $data['ean13'] = $ean;
            $data['upc'] = '';
            $data['reference'] = $code;
            $data['quantity'] = $quantity;
            $data['weight'] = $mass;
            $data['active'] = $active;
            $data['available_for_order'] = $active;
            $data['price'] = $sellingPrice;
            $data['wholesale_price'] = $purchasingPrice;
            $data['id_category_default'] = 1; // default
            $data['id_shop_default'] = 1;
            $data['id_tax_rules_group'] = 1;
            $data['on_sale'] = (int) $sale;
            $data['show_price'] = 1;
            $data['indexed'] = (int) $active;
            $data['cache_default_attribute'] = 1;
            $data['id_tax_rules_group'] = $taxId;
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
            $shopdata['on_sale'] = $sale;
            $shopdata['id_product'] = $id;
            $shopdata['active'] = $active;
            $shopdata['id_category_default'] = $categoryDefaultId;
            $shopdata['id_tax_rules_group'] = $taxId;
            $shopdata['indexed'] = 1;
            $shopdata['cache_default_attribute'] = 0;
            addSlashesToArray($shopdata);

            $stockdata = array();
            $stockdata['id_shop'] = $shopId;
            $stockdata['id_product'] = $id;
            $stockdata['quantity'] = $quantity;
            $stockdata['id_product_attribute'] = 0;
            $stockdata['id_shop_group'] = 0;
            $stockdata['out_of_stock'] = 2; // default

            // novninky hack
            if ($news) {
                $data['date_add'] = $date;
                $shopdata['date_add'] = $date;
            }

            // product data
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

            // language data
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

            // shop data
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

            // stockdata
            $table = 'stock_available';
            $query = new DbQuery();
            $query->select('COUNT(*)');
            $query->from($table, 'p');
            $query->where("p.id_product = '" . $db->escape($id) . "'");
            $query->where("p.id_shop = '" . $db->escape($shopId) . "'");
            $productStockExists = (int) $db->getValue($query);

            if ($productStockExists) {
                $where = "id_product = '" . $db->escape($id) . "' AND id_shop = '" . $db->escape($shopId) . "'";
                $db->update($table, $stockdata, $where);

            } else {
                $db->insert($table, $stockdata);
            }

            // categories
            $defaultCategory = 2;
            $table = 'category_product';
            $where = "id_product = '" . $db->escape($id) . "'";
            $db->delete($table, $where);

            if ($recommended) {
                $db->insert($table, array('id_product' => $id, 'id_category' => $defaultCategory));
            }

            foreach ($categoryIds as $categoryId) {
                $db->insert($table, array('id_product' => $id, 'id_category' => $categoryId));
            }

            // parameters
            if ($parameters) {
                foreach ($parameters as $parameter) {
                    $mynode = new DOMXPath($parameter->parentNode->ownerDocument);

                    $paramId = $mynode->query('./stk:intParameterID', $parameter)->item(0)->nodeValue;
                    $paramName = $mynode->query('./stk:intParameterName', $parameter)->item(0)->nodeValue;
                    $paramOrder = $mynode->query('./stk:intParameterOrder', $parameter)->item(0)->nodeValue;
                    $paramType = $mynode->query('./stk:intParameterType', $parameter)->item(0)->nodeValue;
                    $paramValue = @$mynode->query('./stk:intParameterValues/stk:intParameterValue/stk:parameterValue', $parameter)->item(0)->nodeValue;

                    if ($paramValue) {
                        if ($paramType == 'numberValue') {
                            $paramValue = (float) str_replace(',', '.', $paramValue);
                        }
                    }
                    /*
                    $feature = new Feature();
                    $feature->id = $paramId;
                    $feature->position = $paramOrder;
                    $feature->save();

                    $featureValue = new FeatureValue();
                    $featureValue->id_feature = $paramId;
                    $featureValue->value = $paramValue;
                    */

                    //var_dump($paramValue);
                }
            }

            // images
            if ($pictures) {
                foreach ($pictures as $picture) {
                    $mynode = new DOMXPath($picture->parentNode->ownerDocument);
                    $cover =  $picture->getAttribute('default') == 'true';
                    $imageId =  $mynode->query('./typ:id', $picture)->item(0)->nodeValue;
                    $filepath =  $mynode->query('./stk:filepath', $picture)->item(0)->nodeValue;
                    $imageDesc = $mynode->query('./stk:description', $picture)->item(0)->nodeValue;

                    $table = 'image';
                    $query = new DbQuery();
                    $query->select('COUNT(*)');
                    $query->from($table, 'p');
                    $query->where("p.id_image = '" . $db->escape($imageId) . "'");
                    $productHasImage = (int) $db->getValue($query);

                    if (!$productHasImage) {
                        $db->insert('image', array('id_image' => $imageId, 'id_product' => $id));
                    }

                    $imgFile = IMAGE_IMPORT_FOLDER . $filepath;

                    $fileExists = file_exists($imgFile);
                    if (!$fileExists && !$productHasImage) {
                        logResponse('Image not found ' . $imgFile);
                    } else {
                        $image = new Image();
                        $image->id_product = (int) ($id);
                        $image->cover = (int) $cover;
                        $image->id = $imageId;
                        $image->id_image = $imageId;
                        $image->save();
                        $db->update('image_lang', array('legend' => addslashes($imageDesc)), "id_image = '" . $db->escape($image->id_image) . "'");

                        if ($fileExists && !is_dir(_PS_IMG_DIR_ . 'p' . DIRECTORY_SEPARATOR . $imageId)) {
                            $new_path = $image->getPathForCreation();
                            if (file_exists($imgFile)) {
                                $imagesTypes = ImageType::getImagesTypes('products');
                                foreach ($imagesTypes as $imageType) {
                                    if (!ImageManager::resize($imgFile, $new_path . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                                        logResponse('An error occurred while copying image:' . ' ' . stripslashes($imageType['name']));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Send feed
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/response.xsd" version="2.0" id="00000001" state="ok" application="Prestashop" note="Prestashop import">
<?php foreach ($log as $message): ?>
<message><?php echo htmlspecialchars($message) ?></message>
<?php endforeach ?>
</rsp:responsePack>

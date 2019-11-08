<?php
$BASE = new stdClass();
$BASE->fileNameXML = 'test.xml';

if(!file_exists($BASE->fileNameXML) || filesize($BASE->fileNameXML) == 0){
    foo_create_xml($BASE->fileNameXML);
}

$BASE->API = '0882dd91347958773d48ed01bce01396a66b9ce6d8';
$BASE->BASE_URL = 'http://market.apisystem.name/v2/';
$BASE->fields_for_model = 'MODEL_CATEGORY,MODEL_FACTS,MODEL_LINK,MODEL_PHOTOS,MODEL_PRICE,MODEL_SPECIFICATION,MODEL_VENDOR';
//MODEL_ACTIVE_FILTERS
$BASE->count = 30;
$BASE->page = 1;

$BASE->categories_id = '91009';

//получить описание подкатегорий
// $category = getCategory($BASE);
// sleep(2);
//
// $child = getCategoryChild($BASE );
// sleep(2);
//получить модели
for ($i = 1; $i <= 1; ++$i) {
    $items = getItem($BASE, $i );

    sleep(2);
}
// $prop = getItemParam($BASE, '14224708');
// var_dump($prop);


function getItem($BASE, $page ){
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->categories_id . '/search?';
    $URL_FOR_ITEMS .= 'count='.$BASE->count;
    $URL_FOR_ITEMS .= '&page='.$page;
    $URL_FOR_ITEMS .= '&fields='.$BASE->fields_for_model;
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    var_dump($URL_FOR_ITEMS);
    //$json  = file_get_contents('./tmp/categories_10604359_search_p1.json');

    $json = getJson($URL_FOR_ITEMS);
    $items = $json["items"];

    if(isset($items) || count($items)> 0){
        foreach ($items as  $value) {
            sleep(1);
            $prop = getItemParam($BASE, $value["id"]);
            foo_add_items_xml($BASE->fileNameXML, $value, $prop);
        }
    }

    return;
}

function getItemParam($BASE, $id_model ){
    $BASE_URL = 'http://market.apisystem.name/v1/';
    $URL = $BASE_URL . 'model/' . $id_model . '/details.json?';
    $URL .= 'api_key='.$BASE->API;

    $json = getJson($URL);
    $items = $json["modelDetails"];

    $properties = array();

    if(isset($items) || count($items)> 0){
        foreach ($items as  $detail) {
            if(isset($detail) && isset($detail["params"]) && count($detail)> 0){
                    foreach ($detail["params"] as  $param) {
                        $properties[$param["name"]] = $param["value"];
                    }
            }
        }
    }
    $properties = (object)$properties;

    return $properties;
}

function getCategory($BASE ){
    $URL = $BASE->BASE_URL . 'categories/' . $BASE->categories_id . '?';
    $URL .= '&api_key='.$BASE->API;

    var_dump($URL);

    $json = getJson($URL);
    $category = $json["category"];

    $prop = new stdClass;
    $prop->id = $category["id"];
    foo_add_category_xml($BASE->fileNameXML, $category["fullName"], $prop);
    return;
}


function getCategoryChild($BASE ){
    $URL = $BASE->BASE_URL . 'categories/' . $BASE->categories_id . '/children?';
    $URL .= 'count='.$BASE->count;
    $URL .= '&api_key='.$BASE->API;

    var_dump($URL);

    $json = getJson($URL);
    $items = $object["categories"];

    foreach ($items as  $value) {
        $prop = new stdClass;
        $prop->id = $value["id"];
        $prop->parentId = $BASE->categories_id;
        foo_add_category_xml($BASE->fileNameXML, $value["fullName"], $prop);
    }
    return;
}

function foo_create_xml($fileNameXML){
    $newsXML = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><yml_catalog date="'.date("m.d.y").'"></yml_catalog>');

    $shop = $newsXML->addChild('shop');

    $newsIntro = $shop->addChild('categories');
    $newsIntro = $shop->addChild('offers');
    Header('Content-type: text/xml');

    $newsXML->asXML($fileNameXML);
}

function foo_add_category_xml($fileNameXML, $propName, $prop){

   $xml_doc = simplexml_load_file($fileNameXML);
   $categories = $xml_doc->shop->categories;

   $category = $categories->addChild('category', $propName);

   foreach($prop as $key=>$value) {
       $category->addAttribute($key, $value);
   }
   $done = $xml_doc->asXML($fileNameXML);
}

function foo_add_items_xml($fileNameXML, $model, $properties){

   $xml_doc = simplexml_load_file($fileNameXML);
   $categories = $xml_doc->shop->offers;

   $offer = $categories->addChild('offer');
   $offer->addAttribute('id', $model["id"]);

   $offer->addChild('vendor', $model["vendor"]["name"]);
   $offer->addChild('categoryId', $model["category"]["id"]);
   $offer->addChild('description', $model["description"]);
   $offer->addChild('name', $model["name"]);

   foreach($model["photos"] as $value) {
          $param = $offer->addChild('picture',  $value["url"]);
     }

   foreach($properties as $key=>$value) {
        $param = $offer->addChild('param',  $value);
        $param->addAttribute('name', $key);
   }

// краткие характеристики
//    foreach($model["activeFilters"] as $value) {
//        if( isset($value["value"]) && isset($value["value"][0]) ){
//         $param = $offer->addChild('param', $value["value"][0]["name"] );
//         $param->addAttribute('name', $value["name"]);
//        }
//    }
   $done = $xml_doc->asXML($fileNameXML);
}

function getJson($URL){
    $json  = file_get_contents($URL);

    $json = trim($json);
    $object = json_decode($json, true);

    //var_dump(count($object));

    if(count($object) > 0){
        return $object;
    }

    sleep(10);
    $json  = file_get_contents($URL);
    $json = trim($json);
    $object = json_decode($json, true);
    return $object;

}

switch (json_last_error()) {
    case JSON_ERROR_NONE:
        echo ' - JSON_ERROR_NONE';
    break;
    case JSON_ERROR_DEPTH:
        echo ' - JSON_ERROR_DEPTH';
    break;
    case JSON_ERROR_STATE_MISMATCH:
        echo ' - JSON_ERROR_STATE_MISMATCH';
    break;
    case JSON_ERROR_CTRL_CHAR:
        echo ' -  JSON_ERROR_CTRL_CHAR';
    break;
    case JSON_ERROR_SYNTAX:
        echo "\r\n\r\n - SYNTAX ERROR \r\n\r\n";
    break;
    case JSON_ERROR_UTF8:
        echo ' - JSON_ERROR_UTF8';
    break;
    default:
        echo ' - Unknown erro';
    break;
}


<?php
$BASE = new stdClass();

// имя файла куда будет проходить выгрузка. Если файла нет - он создаться в текущей папке
$BASE->fileNameXML = 'test.xml';

// id категории
$BASE->categories_id = '91009';

// Минимальная\максимальная цена и шаг выборки по цене.
// Для цен меньше мин и больше макс отдельные запросы.
$BASE->min_price = 0;
$BASE->max_price = 200000;
$BASE->step_price = 2000;


// не менять
$BASE->API = '0882dd91347958773d48ed01bce01396a66b9ce6d8';
$BASE->BASE_URL = 'http://market.apisystem.name/v2/';
$BASE->fields_for_model = 'MODEL_CATEGORY,MODEL_FACTS,MODEL_LINK,MODEL_PHOTOS,MODEL_PRICE,MODEL_SPECIFICATION,MODEL_VENDOR';

//MODEL_ACTIVE_FILTERS
$BASE->count = 30;
$BASE->page = 1;

if(!file_exists($BASE->fileNameXML) || filesize($BASE->fileNameXML) == 0){
    foo_create_xml($BASE->fileNameXML);
}


//получить описание подкатегорий
$category = getCategory($BASE);
sleep(3);

$child = getCategoryChild($BASE );
sleep(3);

// получить модели
// цена меньше минимальной
$items = getModelsCategory($BASE, '~'.($BASE->min_price) );
// модели с ценой по шагам
sleep(1);

for($k = $BASE->min_price; $k <= $BASE->max_price; $k += $BASE->step_price){
    getModelsCategory($BASE, ($k.'~'.($k+$BASE->step_price)) );
}

// цена больше макимальной
$items = getModelsCategory($BASE, '~'.($BASE->min_price) );

var_dump('The good end');


function getModelsCategory($BASE, $price){
    $count = getPagesCount($BASE, 1, $price );

    $pagesCount = (0 < $count && $count <  51) ? $count : 50;

    for ($i = 2; $i <= $pagesCount; ++$i) {
        getItem($BASE, $i, $price );
        sleep(1);
    }
}

function getPagesCount($BASE, $page, $price){
    $json = getItem($BASE, $page, $price);
    $page = $json["context"]["page"]["total"];

    return $page;
}

function getItem($BASE, $page, $price){
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->categories_id . '/search?';
    if(isset($price)){
            $URL_FOR_ITEMS .= '-1='.$price;
    }
    $URL_FOR_ITEMS .= '&count='.$BASE->count;
    $URL_FOR_ITEMS .= '&page='.$page;
    $URL_FOR_ITEMS .= '&fields='.$BASE->fields_for_model;
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["items"])){
         return;
    }

    writeItem($BASE, $json);
    return $json;
}

function writeItem($BASE, $json){
    $items = $json["items"];

    if(isset($items) || count($items) > 1){
        foreach ($items as  $value) {
            sleep(1);
            $prop = getItemParam($BASE, $value["id"]);
            foo_add_items_xml($BASE->fileNameXML, $value, $prop);
        }
    }
}

function getItemParam($BASE, $id_model ){
    $BASE_URL = 'http://market.apisystem.name/v1/';
    $URL = $BASE_URL . 'model/' . $id_model . '/details.json?';
    $URL .= 'api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["modelDetails"])){
        return;
    }

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

    $json = getJson($URL);

    if(!isset($json) || !isset($json["category"])){
        return;
    }

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

    $json = getJson($URL);

    if(!isset($json) || !isset($json["categories"])){
        return;
    }

    $items = $json["categories"];

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

   if(isset($properties) && count($properties) > 0 ){
       foreach($properties as $key=>$value) {
           $param = $offer->addChild('param',  $value);
           $param->addAttribute('name', $key);
       }
   }


// краткие характеристики из v2
//    foreach($model["activeFilters"] as $value) {
//        if( isset($value["value"]) && isset($value["value"][0]) ){
//         $param = $offer->addChild('param', $value["value"][0]["name"] );
//         $param->addAttribute('name', $value["name"]);
//        }
//    }
   $done = $xml_doc->asXML($fileNameXML);
}

function getJson($URL){

    try {
        $json = file_get_contents($URL);

        if ($json === false) {
            var_dump('$json === false');

            sleep(20);
            $json  = file_get_contents($URL);
        }
    } catch (Exception $e) {
        var_dump('Exception http', $e);
        return false;
    }

    var_dump($URL);

    //$json  = file_get_contents('./tmp/params-models-v2.json');

    $json = trim($json);
    $object = json_decode($json, true);

    if(count($object) > 0){
        return $object;
    }

    return false;

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


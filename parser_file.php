<?php
$BASE = new stdClass();

// id категории
$BASE->categories_id = '91491';

// Минимальная\максимальная цена и шаг выборки по цене.
// Для цен меньше мин и больше макс отдельные запросы.
$BASE->min_price = 0;
$BASE->max_price = 200000;
$BASE->step_price = 500;


// не менять
$BASE->API = '0882dd91347958773d48ed01bce01396a66b9ce6d8';
$BASE->BASE_URL = 'http://market.apisystem.name/v2/';
$BASE->fields_for_model = 'MODEL_CATEGORY,MODEL_PHOTOS,MODEL_VENDOR';

//MODEL_ACTIVE_FILTERS
$BASE->count = 30;
$BASE->page = 1;

$BASE->allCountFromCategory = 0;
$BASE->currentCountFromCategory = 0;
$BASE->fileNameXML = 'test.xml';
$BASE->current_categories_id = '';

init();

function init(){
    global $BASE;



    $BASE->current_categories_id = $BASE->categories_id;

    $manufacturers = getManufacturers();
    var_dump(count($manufacturers));
    if(!count($manufacturers)){
        var_dump('ERROR. No manufacturers');
    }
    foreach($manufacturers as  $factory){
        //getItem($page, $factory, $price)

            $itemJson = getItem( 1, $factory["id"], false );
            //writeItem($itemJson);
            $count = getPagesCount($itemJson);

            var_dump($count);

//         $items = getModelsCategory($factory["id"] )
    }

    return getAllModelsCategoryFromPrice();

//     $BASE->current_categories_id = $BASE->categories_id;
//     $count = getItemCount();
//     var_dump('All in id=' . $BASE->current_categories_id. ' have ' . $BASE->allCountFromCategory.' models');
//
//     if($count < 20000){
//         return getAllModelsCategoryFromPrice();
//     }
//
//     $childrensJson = getCategoryChild();
//     $childrens = $childrensJson["categories"];
//     if($childrens && count($childrens)> 0){
//
//         foreach ($childrens as  $child) {
//                 $BASE->current_categories_id = $child["id"];
//                 getAllModelsCategoryFromPrice();
//         }
//
//         return;
//     }
//     else{
//         return getAllModelsCategoryFromPrice();
//     }
}



function getAllModelsCategoryFromPrice(){
    global $BASE;
    //получить описание подкатегорий

    sleep(3);

    $category = getCategory();
    sleep(3);

    $childCategory = getCategoryChild();
    getCategoryChildWrite($childCategory);
    sleep(3);

    $count = getItemCount();
    var_dump('All '.$BASE->allCountFromCategory.' models');
    sleep(3);


    // получить модели
    // цена меньше минимальной
    $items = getModelsCategory('~'.($BASE->min_price) );
    // модели с ценой по шагам
    sleep(1);

    for($k = $BASE->min_price; $k <= $BASE->max_price; $k += $BASE->step_price){
        getModelsCategory(($k.'~'.($k+$BASE->step_price)) );
        var_dump($BASE->currentCountFromCategory.' out of '.$BASE->allCountFromCategory.' models received... loading...');
    }

    // цена больше макимальной
    $items = getModelsCategory('~'.($BASE->min_price) );

    var_dump( $BASE->currentCountFromCategory.' out of '.$BASE->allCountFromCategory.' models received');
    var_dump('The good end');

}

function getManufacturers(){
    global $BASE;
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/filters?';
    $URL_FOR_ITEMS .= 'fields=ALLVENDORS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["filters"])){
        var_dump( 'No get Manufacturers. Reload.' );
        sleep(2);
        return getManufacturers();
    }

    $filters = $json["filters"];
    $manufacturers;

    foreach($filters as  $filter){
        if($filter["id"] === '-11') {
            $manufacturers = $filter["value"];
            return $manufacturers;
        }
    }

    return false;
}

function getModelsCategory($factory, $price){
    global $BASE;
    $itemJson = getItem( $i, $factory, $price );
    writeItem($itemJson);
    $count = getPagesCount($itemJson);
    $pagesCount = (0 < $count && $count <  51) ? $count : 50;

    for ($i = 2; $i <= $pagesCount; ++$i) {
        $itemJson = getItem( $i, $factory, $price );
        writeItem($itemJson);
        var_dump($BASE->currentCountFromCategory.' out of '.$BASE->allCountFromCategory.' models received... loading...');
        sleep(1);
    }
}

function getPagesCount($json){
    $page = $json["context"]["page"]["total"];
    return $page;
}

function getItem($page, $factory, $price){
    global $BASE;
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/search?';
    $URL_FOR_ITEMS .= '&count='.$BASE->count;
        $URL_FOR_ITEMS .= '&page='.$page;
    if($price){
            $URL_FOR_ITEMS .= '&-1='.$price;
    }
    if($factory){
            $URL_FOR_ITEMS .= '&-11='.$factory;
    }
    $URL_FOR_ITEMS .= '&fields='.$BASE->fields_for_model;
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["items"])){
        var_dump('No get models. Reload.');
        sleep(2);
         return getItem($page, $price);
    }

    return $json;
}

function getItemCount(){
    global $BASE;

    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/search?';
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["context"])){
         return;
    }

    $page = $json["context"]["page"]["totalItems"];
    $BASE->allCountFromCategory = $page;

    return $json;
}


function writeItem($json){
    global $BASE;
    $items = $json["items"];

    if(isset($items) && count($items) > 1){
        foreach ($items as  $value) {
//             sleep(1);
//             $prop = getItemParam($value["id"]);
            $prop = new stdClass();
            if(foo_add_items_xml($BASE->fileNameXML, $value, $prop)){
                 $BASE->currentCountFromCategory += 1;
            };
        }
    }
}

function getItemParam($id_model){
    global $BASE;
    $BASE_URL = 'http://market.apisystem.name/v1/';
    $URL = $BASE_URL . 'model/' . $id_model . '/details.json?';
    $URL .= 'api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["modelDetails"])){
        sleep(5);
        return getItemParam($id_model);
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

function getCategory(){
    global $BASE;
    $URL = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '?';
    $URL .= '&api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["category"])){
        var_dump('reload...');
        sleep(2);
        return getCategory();
    }

    $category = $json["category"];

    $BASE->fileNameXML = textlat($category["fullName"]).'_'.$category["id"].'.xml';

    if(!file_exists($BASE->fileNameXML) || filesize($BASE->fileNameXML) == 0){
        foo_create_xml($BASE->fileNameXML);
    }

    $prop = new stdClass;
    $prop->id = $category["id"];
    foo_add_category_xml($BASE->fileNameXML, $category["fullName"], $prop);
    return;
}

function getCategoryChild(){
    global $BASE;
    $URL = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/children?';
    $URL .= 'count='.$BASE->count;
    $URL .= '&api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["categories"])){
        return;
    }
    return $json;
}

function getCategoryChildWrite($json){
    global $BASE;

    $items = $json["categories"];

    foreach ($items as  $value) {
            $prop = new stdClass;
            $prop->id = $value["id"];
            $prop->parentId = $BASE->current_categories_id;
            foo_add_category_xml($BASE->fileNameXML, $value["fullName"], $prop);
    }
    return;
}

function foo_create_xml($fileNameXML){
    $newsXML = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><yml_catalog date="'.date("m.d.y").'"></yml_catalog>');

    $shop = $newsXML->addChild('shop');

    $newsIntro = $shop->addChild('categories');
    $newsIntro = $shop->addChild('offers');

    $newsXML->asXML($BASE->fileNameXML);
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

   if(isset($properties) && ((count((array) $properties)) > 0) ){
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
   return true;
}

function getJson($URL){

    try {
        $json = file_get_contents($URL);

        if ($json === false) {
            //var_dump('$json === false');
             var_dump('Server error. Load 30sec and return');

            sleep(30);
            return getJson($URL);
        }
    } catch (Exception $e) {
        var_dump('Server error. Load 30sec and return');

        sleep(30);
        return getJson($URL);
    }

    var_dump($URL);
    //$json  = file_get_contents('./tmp/params-models-v2.json');

    $json = trim($json);
    $object = json_decode($json, true);

    if(count($object) > 0){
        return $object;
    }
}

function textlat($textcyr){
        $cyr = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',' '
        ];
        $lat = [
            'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
            'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya','_'
        ];
        $textcyr = str_replace($cyr, $lat, $textcyr);
        return $textcyr;
}

// switch (json_last_error()) {
//     case JSON_ERROR_NONE:
//         echo ' - JSON_ERROR_NONE';
//     break;
//     case JSON_ERROR_DEPTH:
//         echo ' - JSON_ERROR_DEPTH';
//     break;
//     case JSON_ERROR_STATE_MISMATCH:
//         echo ' - JSON_ERROR_STATE_MISMATCH';
//     break;
//     case JSON_ERROR_CTRL_CHAR:
//         echo ' -  JSON_ERROR_CTRL_CHAR';
//     break;
//     case JSON_ERROR_SYNTAX:
//         echo "\r\n\r\n - SYNTAX ERROR \r\n\r\n";
//     break;
//     case JSON_ERROR_UTF8:
//         echo ' - JSON_ERROR_UTF8';
//     break;
//     default:
//         echo ' - Unknown erro';
//     break;
// }


<?php
$BASE = new stdClass();

// id категории
$BASE->categories_id = '90572';

// Минимальная\максимальная цена и шаг выборки по цене.
// Для цен меньше мин и больше макс отдельные запросы.
$BASE->min_price = 0;
$BASE->max_price = 100000;
$BASE->step_price = 10000;

// не менять
$BASE->API = '0882dd91347958773d48ed01bce01396a66b9ce6d8';
$BASE->BASE_URL = 'http://market.apisystem.name/v2/';
$BASE->fields_for_model = 'MODEL_CATEGORY,MODEL_VENDOR';

//MODEL_ACTIVE_FILTERS
$BASE->count = 30;
$BASE->page = 1;
$BASE->maxPage = 50; // ограничение апи на кол-во страниц
$BASE->maxCount = (int)($BASE->count * 50);

$BASE->allCountFromCategory = 0;
$BASE->currentCountFromCategory = 0;
$BASE->fileNameXML = '';
$BASE->current_categories_id = '';


init();


function init(){
    global $BASE;

    $BASE->current_categories_id = $BASE->categories_id;

    // получить всех производителей в категории
    $manufacturers = getManufacturers();
    if(!count($manufacturers)){
        echo "ERROR. No manufacturers \n\r";
        return;
    }
    echo 'Find manufacturers:' , count($manufacturers), " \n\r";

    //  создать  xml и записать подкатегории
    getCategoryAndInitFile();

    // получить количество моделей каждого производителя в файле
    $countModelsFactoryInFile = categoriesCount();

    foreach($manufacturers as  $factory){
        //DEBUG
        //if($factory["name"] === 'Acer' || $factory["name"] === 'ASUS'){continue;}
        loadFromFactory($factory, $countModelsFactoryInFile);
        echo $BASE->currentCountFromCategory, "  out of " , $BASE->allCountFromCategory, " models received... loading...\n\r";
    }

    echo  $BASE->currentCountFromCategory, ' out of ', $BASE->allCountFromCategory, " models received. \n\r";
    echo "The END;  \n\r";
}

function loadFromFactory($factory, $countModelsFactoryInFile){
    global $BASE;

    $itemJson = getItem( 1, $factory["id"], false );

    $count = (int)getPagesCount($itemJson);
    $countModel = getModelsCount($itemJson);
    echo "Find pages models from ", $factory["name"], ": " , $count, " models: ", $countModel, " \n\r";

    if(!empty($countModelsFactoryInFile)){
        echo "All model in file: ", $countModelsFactoryInFile[$factory["name"]], "\n\r";
    }
    if(
        !empty($countModelsFactoryInFile) &&
        !empty($countModelsFactoryInFile[$factory["name"]]) &&
        $countModelsFactoryInFile[$factory["name"]] > (int)($countModel - $countModel * 0.005)
    ){
        echo "All factory", $factory["name"], " skip.\n\r";
        return;
    }

    $maxPage = (int)$BASE->maxPage * 2;

    if($count > 0 && $count <= $maxPage){
        getModelsCategory($factory["id"], false);
    } else if($count > $maxPage){
        getModelsCategoryFromPriceAndFactory($factory["id"]);
    }
}

function getLastVendorNameFromXml(){
    global $BASE;
    $xml = simplexml_load_string( file_get_contents ($BASE->fileNameXML));
    $offerCount = $xml->shop->offers->offer->count();
    $BASE->currentCountFromCategory = $offerCount;
    echo "Моделей в документе: ", $offerCount, "\n\r";
    return (string) $xml->xpath("//offer[last()]")[0]->vendor;
}

function getCategoryAndInitFile(){
    global $BASE;
    //получить описание подкатегорий

    getCategory(); //Получает инфо о категории, инициализирует файл xml
    sleep(2); //часто зависает после

    $childCategory = getCategoryChild();
    getCategoryChildWrite($childCategory);

    getAllModelCount();
    echo "All  " , $BASE->allCountFromCategory, "  models\n\r";
}

function getModelsCategoryFromPriceAndFactory($factory){
    global $BASE;

    // получить модели
    // цена меньше минимальной
    getModelsCategory($factory, '~'.($BASE->min_price) );
    // модели с ценой по шагам

    for($k = $BASE->min_price; $k <= $BASE->max_price; $k += $BASE->step_price){
        getModelsCategory($factory, ($k.'~'.($k+$BASE->step_price)) );
        echo $BASE->currentCountFromCategory, "  out of " , $BASE->allCountFromCategory, " models received... loading...\n\r";
    }

    // цена больше макимальной
    getModelsCategory($factory, '~'.($BASE->min_price) );
    echo $BASE->currentCountFromCategory, "  out of " , $BASE->allCountFromCategory, " models received... loading...\n\r";
}

function getManufacturers(){
    global $BASE;
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/filters?';
    $URL_FOR_ITEMS .= 'fields=ALLVENDORS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    /// !!!DEBUG
//    $json  = file_get_contents('./Note/Noutbuki_91013_ALLVENDORS.json');
//    $json = trim($json);
//    $json = json_decode($json, true);
    /// !!!DEBUG-END
    $json = getJson($URL_FOR_ITEMS);


    if(!isset($json) || !isset($json["filters"])){
        echo "No get Manufacturers. Reload. \n\r" ;
        return getManufacturers();
    }

    $filters = $json["filters"];
    $manufacturers = array();

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
    $itemJson = getItem( 1, $factory, $price );
    writeItem($itemJson);
    $count = (int) getPagesCount($itemJson);
    if(!$count || $count === 0){
        return ;
    }

    $pagesCount = ($count <= ($BASE->maxPage * 2)) ? $count : $BASE->maxPage * 2;

    for ($i = 2; $i <= $pagesCount; ++$i) {
        $itemJson = getItem( $i, $factory, $price );
        writeItem($itemJson);

        echo $BASE->currentCountFromCategory," out of ",$BASE->allCountFromCategory," models received... loading...  \n\r";
    }
}

function getPagesCount($json){
    $page = $json["context"]["page"]["total"];
    return $page;
}
function getModelsCount($json){
    $page = $json["context"]["page"]["totalItems"];
    return $page;
}

function getItem($page, $factory, $price, $count=false, $limit=0){
    global $BASE;
    if(!$count){
        $count = $BASE->count;
    }
    $how = ($page <= $BASE->maxPage) ? "ASC" : "DESC";
    $pageHow = ($page <= $BASE->maxPage) ? $page : ($page - $BASE->maxPage);
    $limit++;

    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/search?';
    $URL_FOR_ITEMS .= 'count='.$count;
    $URL_FOR_ITEMS .= '&page='.$pageHow;
    if($price){
            $URL_FOR_ITEMS .= '&-1='.$price;
    }
    if($factory){
            $URL_FOR_ITEMS .= '&-11='.$factory;
    }
    $URL_FOR_ITEMS .= '&fields='.$BASE->fields_for_model;
    $URL_FOR_ITEMS .= '&sort=PRICE';
    $URL_FOR_ITEMS .= '&how='.$how;
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["items"])){
        if($limit > 10){
                return false;
        }
        echo "No get models. Reload..  \n\r";

        return getItem($page, $factory, $price, false, $limit);
    }

    return $json;
}

function getAllModelCount(){
    global $BASE;

    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/search?';
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["context"])){
         return getAllModelCount();
    }

    $page = $json["context"]["page"]["totalItems"];
    $BASE->allCountFromCategory = $page;

    return $json;
}

function writeItem($json){
    global $BASE;
    $items = $json["items"];

    if(isset($items) && count($items) > 0){
        foreach ($items as  $value) {
            if(!findIdInDocument($value["id"])){

                $prop = getItemParam($value["id"]); // получить детали из первой версии апи
                $photo =  getItemPhoto($value["id"]); // получить фото
                // DEBUG!!
                //$prop = new stdClass();
                if(foo_add_items_xml($BASE->fileNameXML, $value, $prop, $photo)){
                     $BASE->currentCountFromCategory += 1;
                };
            }
        }
    }
}

function findIdInDocument($id){
    global $BASE;
    $xml = simplexml_load_string( file_get_contents ($BASE->fileNameXML));
    $elements = $xml->xpath("//offer[@id='".$id."']");
    if(count($elements)){
        echo 'find duplicate: ', $id, " \n\r";
        return true;
    }

    return false;
}

function getItemParam($id_model){
    global $BASE;
    $BASE_URL = 'http://market.apisystem.name/v1/';
    $URL = $BASE_URL . 'model/' . $id_model . '/details.json?';
    $URL .= 'api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["modelDetails"])){
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

    return (object)$properties;
}

function getItemPhoto($id_model){
    global $BASE;
    $URL = $BASE->BASE_URL . 'models/' . $id_model . '?';
    $URL .= 'fields=PHOTOS';
    $URL .= '&api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["model"])){
        return getItemPhoto($id_model);
    }

    $items = $json["model"]["photos"];

    $photos = array();

    if(isset($items) || count($items)> 0){
        foreach ($items as  $photo) {
            if(isset($photo) && isset($photo["url"]) ){
                array_push($photos, $photo["url"]);
            }
        }
    }

    return $photos;
}

function getCategory(){
    global $BASE;
    $URL = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '?';
    $URL .= '&api_key='.$BASE->API;

    $json = getJson($URL);

    if(!isset($json) || !isset($json["category"])){

        echo "reload...  \n\r";
        return getCategory();
    }

    $category = $json["category"];

    $BASE->fileNameXML = textlat($category["fullName"]).'_'.$category["id"].'.xml';

    createFile();

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

function findCategoryInDocument($id){
    global $BASE;
    $xml = simplexml_load_string( file_get_contents ($BASE->fileNameXML));
    $elements = $xml->xpath("//category[@id='".$id."']");
    if(count($elements)){
        echo 'find duplicate Category Child: ', $id, " \n\r";
        return true;
    }

    return false;
}

function getCategoryChildWrite($json){
    global $BASE;

    $items = $json["categories"];

    foreach ($items as  $value) {
        if(!findCategoryInDocument($value["id"])){
            $prop = new stdClass;
            $prop->id = $value["id"];
            $prop->parentId = $BASE->current_categories_id;
            foo_add_category_xml($BASE->fileNameXML, $value["fullName"], $prop);
        }
    }
    return;
}

function foo_create_xml($fileNameXML){
    $newsXML = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><yml_catalog date="'.date("m.d.y").'"></yml_catalog>');
    $shop = $newsXML->addChild('shop');
    $shop->addChild('categories');
    $shop->addChild('offers');

    $domxml = new \DOMDocument('1.0');
    $domxml->preserveWhiteSpace = false;
    $domxml->formatOutput = true;
    $domxml->loadXML($newsXML->asXML());
    $domxml->save($fileNameXML);


}

function foo_add_category_xml($fileNameXML, $propName, $prop){

   $xml_doc = simplexml_load_string( file_get_contents ($fileNameXML));
   $categories = $xml_doc->shop->categories;

   $category = $categories->addChild('category', validateValueForWrite($propName));

   foreach($prop as $key=>$value) {
       $category->addAttribute($key, $value);
   }
   $xml_doc->asXML($fileNameXML);
}

function foo_add_items_xml($fileNameXML, $model, $properties, $photos){
   $xml_doc = simplexml_load_string( file_get_contents ($fileNameXML));
   $categories = $xml_doc->shop->offers;

   $offer = $categories->addChild('offer');
   $offer->addAttribute('id', $model["id"]);

   $offer->addChild('vendor', validateValueForWrite($model["vendor"]["name"]));
   $offer->addChild('categoryId', $model["category"]["id"]);
   $offer->addChild('description', validateValueForWrite($model["description"]));
   $offer->addChild('name', validateValueForWrite($model["name"]));

    if(isset($photos) && ((count((array) $photos)) > 0) ){
        foreach($photos as $photo) {
            $offer->addChild('picture',  (string) $photo);
        }
    }

   if(isset($properties) && ((count((array) $properties)) > 0) ){
       foreach($properties as $key=>$value) {
           $param = $offer->addChild('param',  validateValueForWrite($value));
           $param->addAttribute('name', validateValueForWrite($key));
       }
   }

// краткие характеристики из v2 (в запрос добавить параметр)
//    foreach($model["activeFilters"] as $value) {
//        if( isset($value["value"]) && isset($value["value"][0]) ){
//         $param = $offer->addChild('param', $value["value"][0]["name"] );
//         $param->addAttribute('name', $value["name"]);
//        }
//    }
   $xml_doc->asXML($fileNameXML);
   return true;
}

function categoriesCount(){
    global $BASE;
    $xml = simplexml_load_string( file_get_contents ($BASE->fileNameXML));
    $offers = $xml->shop->offers;

    $offerLen=$offers->offer->count();
    $BASE->currentCountFromCategory = $offerLen;

    $factories = array();

    $unique_items_o = array();
    for ( $i = $offerLen-1; $i >= 0; $i-- )
    {
        $sx_item = $offers->offer[$i];
        $hash_key = ((string)$sx_item->vendor);

        if ( !in_array($hash_key, $unique_items_o) )
        {
            array_push($unique_items_o, $hash_key);
            $factories[$hash_key] = 1;

        }else{
            $factories[$hash_key] += 1;
        }

    }

    return $factories;
}

function validateValueForWrite($value){
    $value = htmlspecialchars($value,ENT_COMPAT,'UTF-8');
    $value = str_replace(PHP_EOL,"&#10;",$value);
    $value = str_replace(chr(13),"&#13;",$value);
    $value = str_replace("\t","&#9;",$value);
    return $value;
}

function createFile(){
    global $BASE;
    if(!file_exists($BASE->fileNameXML) || filesize($BASE->fileNameXML) == 0){
        foo_create_xml($BASE->fileNameXML);
    }
}

function getJson($URL, $limit = 0){

    echo $URL, " \n\r";
    sleep(1);
    try {
        $json = file_get_contents($URL);

        if ($json === false) {
             echo "Server error. Load 7sec and return  \n\r";

            sleep(7);
            if($limit > 20){
                return false;
            }
            return getJson($URL, ++$limit);
        }
    } catch (Exception $e) {
        echo "Server error. Load 30sec and return  \n\r";

        sleep(30);
        return getJson($URL);
    }

    // $json  = file_get_contents('./tmp/params-models-v2_2.json');

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


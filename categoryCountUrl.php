<?php
// Имя файла
$BASE = new stdClass();



//$name = "models_04092019";
$name = "Noutbuki_91013";

$BASE->current_categories_id = '91013';
$BASE->filenameJson = $name."_url.json";
$BASE->API = '0882dd91347958773d48ed01bce01396a66b9ce6d8';
$BASE->BASE_URL = 'http://market.apisystem.name/v2/';
$BASE->count = 30; // кратно 10 должно быть
$BASE->page = 1;

$manufacturers = getManufacturers();
$factories = array();


if(!count($manufacturers)){
    echo " ERROR. No manufacturers \n\r";
    return;
}
echo "Find manufacturers:  " , count($manufacturers), " \n\r";

foreach($manufacturers as $key => $factory){

    echo $key, "\r\n";

    $json = getItem( $factory["id"]);
    $factories[$factory["name"]] = $json["context"]["page"]["totalItems"];
    echo $factory["name"], "->", $json["context"]["page"]["totalItems"], "\r\n";

    if($key%10 === 0){
        writeToJson($factories);
    }

}
writeToJson($factories);

$controlCount = 0;
foreach ($factories as $value) {
    $controlCount += $value;
}

echo  " All: ", $len,"(",$controlCount,")", " vendor: ", count($unique_items_o), "\n\r";

//if(!file_exists($filenameJson) || filesize($filenameJson) == 0){
//    foo_create_xml($filenameJson);
//}
//file_put_contents($filenameJson, json_encode($factories));

//var_dump($factories);
function writeToJson($factories){
    global $BASE;
    $fp = fopen($BASE->filenameJson, 'w');
    fwrite($fp, json_encode($factories));
    fclose($fp);
}

function getManufacturers(){
    global $BASE;
    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/filters?';
    $URL_FOR_ITEMS .= 'fields=ALLVENDORS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    //$json = getJson($URL_FOR_ITEMS);
    $json  = file_get_contents('./Noutbuki_91013_ALLVENDORS.json');
    $json = trim($json);
    $json = json_decode($json, true);

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

function getItem( $factory, $limit=0){
    global $BASE;
    $limit++;

    $URL_FOR_ITEMS = $BASE->BASE_URL . 'categories/' . $BASE->current_categories_id . '/search?';
    $URL_FOR_ITEMS .= 'count='.$BASE->count;
    if($factory){
        $URL_FOR_ITEMS .= '&-11='.$factory;
    }
    $URL_FOR_ITEMS .= '&result_type=MODELS';
    $URL_FOR_ITEMS .= '&api_key='.$BASE->API;

    $json = getJson($URL_FOR_ITEMS);

    if(!isset($json) || !isset($json["items"])){
        if($limit > 5){
            return false;
        }
        echo "No get models. Reload..  \n\r";

        return getItem( $factory,  $limit);
    }

    return $json;
}

function getJson($URL, $limit = 0){

    echo $URL, " \n\r";
    sleep(3);
    try {
        $json = file_get_contents($URL);

        if ($json === false) {
            echo "Server error. Load 10sec and return  \n\r";

            sleep(6);
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

    $json = trim($json);
    $object = json_decode($json, true);

    if(count($object) > 0){
        return $object;
    }
}
<?php
// Имя файла
$fileName="Mobilynie_telefoni_91491.xml";



$xml=simplexml_load_file($fileName);
$offers = $xml->shop->offers;

$len=$offers->offer->count();
var_dump('offers=' . $len);

$unique_items_o = array();
$duplicate = array();
for ( $i = $len-1; $i >= 0; $i-- )
{
    $sx_item = $offers->offer[$i];
    $hash_key = ((int)$sx_item['id']);

    if ( !in_array($hash_key, $unique_items_o) )
    {
        array_push($unique_items_o, $hash_key);
    }
    else{
        array_push($duplicate, $hash_key);
        $node = dom_import_simplexml($sx_item);
        $node->parentNode->removeChild($node);
    };
}

var_dump('duplicate = ' . count($duplicate) . ', unique_items = ' . count($unique_items_o)) ;
var_dump( count($duplicate) + count($unique_items_o) );

$xml->asXML('new'.$fileName);





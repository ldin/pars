<?php
// Имя файла
$name = "Mobilynie_telefoni_91491";

$fileNameXML=$name.".xml";
$filenameJson = $name.".json";


$xml=simplexml_load_file($fileNameXML);
$offers = $xml->shop->offers;

$len=$offers->offer->count();
echo 'offers=', $len, "\n\r";

$factories = array();

$unique_items_o = array();
for ( $i = $len-1; $i >= 0; $i-- )
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

$fp = fopen($filenameJson, 'w');
fwrite($fp, json_encode($factories));
fclose($fp);




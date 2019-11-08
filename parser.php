<?php
$fileName = 'test.xml';

if(!file_exists($fileName) || filesize($fileName) == 0){
    foo_create_xml($fileName);
}

$xmlInfo = new SplFileInfo($fileName);

if((int) $xmlInfo->getSize() > 0){
    $prop = new stdClass;
    $prop->id = '90725';
    $prop->parentId = '10604368';

    foo_add_category_xml($fileName, 'Калькуляторы', $prop );
} else {
    var_dump('error load file');
}

function foo_create_xml($fileName){
    $newsXML = new SimpleXMLElement("<yml_catalog></yml_catalog>");
    $newsXML->addAttribute('date', '11111');
    $newsIntro = $newsXML->addChild('categories');
    $newsIntro = $newsXML->addChild('offers');
    Header('Content-type: text/xml');

    $newsXML->asXML($fileName);
}

function foo_add_category_xml($fileName, $propName, $prop){

   $xml_doc = simplexml_load_file($fileName);
   $categories = $xml_doc->categories;

   $category = $categories->addChild('category', $propName);

   foreach($prop as $key=>$value) {
       $category->addAttribute($key, $value);
   }
   $done = $xml_doc->asXML($fileName);
}


/*
function get_xml(){
    $xml = new SimpleXMLElement('<xml/>');

    $categories = $xml->addChild('categories');
    for ($i = 1; $i <= 8; ++$i) {
        $category = $categories->addChild('category', "Калькуляторы");
        $category->addAttribute('id', 'stars'.$i);
        $category->addAttribute('parentId', $i);
    }

    $offers = $xml->addChild('offers');
    for ($i = 1; $i <= 8; ++$i) {
        $offer = $offers->addChild('offer');
        $offer->addAttribute('id', $i);

        $offer->addChild('vendor', "Калькуляторы");
        $offer->addChild('categoryId', "138608");
        $offer->addChild('vendor', "TWINJET");
        $offer->addChild('description', "TWINJET");
        $offer->addChild('name', "TWINJET");
    }
    return  $xml
}
 */


/*
$xml = new SimpleXMLElement('<xml/>');

$categories = $xml->addChild('categories');
for ($i = 1; $i <= 8; ++$i) {
    $category = $categories->addChild('category', "Калькуляторы");
    $category->addAttribute('id', 'stars'.$i);
    $category->addAttribute('parentId', $i);
}

$offers = $xml->addChild('offers');
for ($i = 1; $i <= 8; ++$i) {
    $offer = $offers->addChild('offer');
    $offer->addAttribute('id', $i);

    $offer->addChild('vendor', "Калькуляторы");
    $offer->addChild('categoryId', "138608");
    $offer->addChild('vendor', "TWINJET");
    $offer->addChild('description', "TWINJET");
    $offer->addChild('name', "TWINJET");

//     for ($i = 1; $i <= 3; ++$i) {
//         $offer->addChild('picture', "1111");
//     }

//     for ($i = 1; $i <= 3; ++$i) {
//             $param = $offer->addChild('param', "принтер");
//             $param->addAttribute('name', $i);
//     }

}

Header('Content-type: text/xml');
print($xml->asXML());
 */


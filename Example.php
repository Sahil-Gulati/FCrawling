<?php
use FCrawling\FCrawling;
use FCrawling\FCrawlingRequest;
require_once 'vendor/autoload.php';

$obj= new FCrawling("test");
$object = new FCrawlingRequest("http://www.google.com?name=sahil&browser=chrome");
$obj->setRequest($object);

$object = new FCrawlingRequest("http://www.google.com?name=sahil&browser=chrome");
$obj->setRequest($object);

$object = new FCrawlingRequest("http://www.google.com?name=sahil&browser=chrome");
$obj->setRequest($object);

$obj->execute("\FCrawling\test");

function test()
{
    print_r(func_get_args());
}

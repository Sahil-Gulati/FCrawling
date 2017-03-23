# FCrawling
FCrawling is a Fast Crawling Multi CURL Library. This library contains some enhanced parameters from execution's efficiency point of view. We have heard most of the times, people taking about sending HTTP request without waiting for response, sending multiple requests parallely at once ,limiting requests or grouping of requests then FCrawling comes into play. Have a look on example.

##Installation
`composer require sahil-gulati/fcrawling`

**OR**

Create composer.json in your project directory

```javascript
{
     "require":{
        "sahil-gulati/fcrawling":"1.0.0"
     }
}
```

`composer install`

##Usage
```php
<?php
require_once 'vendor/autoload.php';
use FCrawling\FCrawling;
use FCrawling\FCrawlingRequest;
try {
    /**
     * Callback function type
     * (String) `callback_function` global function
     * (Array) array => 0 (Object) $classObject array => 1 (String) function_name(public) 
     * (Array) array => 0 (String) class_name array => 1 (String) function_name(public static) 
     **/
    $fcrawlingObj= new FCrawling("callback_function");
    /**
     * Execution type can 'parallel' or 'serial'
     * Defaults to 'parallel'
     **/
    $fcrawlingObj->setExecutionType("parallel");
    /**
     * Setting group size for execution
     * Each group will executed sequencially
     * Defaults to 'none'
     **/
    $fcrawlingObj->setGroupSize(2);
    /**
     * Setting window size for adding requests
     * Each FCrawling object can handle upto window sized requests,
     * else exception is thrown
     * Defaults to '10000'
     **/
    $fcrawlingObj->setWindowSize(100);
    
    /**
     * Setting output reliability, Nothing is returned in case of non reliable output
     * Defaults to 'true'
     **/
    $fcrawlingObj->setOutputReliability(false);
    
    //Request 1
    $fcrawlingRequestObj = new FCrawlingRequest("http://www.example.com?name=sahil&browser=chrome");
    $fcrawlingRequestObj->setOption(array(
        CURLOPT_POSTFIELDS=>array("somekey"=>"somevalue")
    ));
    $fcrawlingObj->setRequest($fcrawlingRequestObj);
    
    //Request 2
    $fcrawlingRequestObj = new FCrawlingRequest("http://www.example.com?name=sahil&browser=chrome");
    $fcrawlingRequestObj->setOption(array(
        CURLOPT_HTTPHEADER=>array("Content-Type: application/json")
    ));
    $fcrawlingObj->setRequest($fcrawlingRequestObj);
    
    //Request 3
    $fcrawlingRequestObj = new FCrawlingRequest();
     $fcrawlingRequestObj->setOption(array(
        CURLOPT_URL=>array("http://www.example.com?name=sahil&browser=chrome")
    ));
    $fcrawlingObj->setRequest($fcrawlingRequestObj);

    $fcrawlingObj->execute("\FCrawling\test");
}
catch(FCrawlingException $fex)
{
    echo $fex->getMessage();
}


function callback_function($response,$responseNo,$info,$groupNo)
{
    print_r(func_get_args());
}

```

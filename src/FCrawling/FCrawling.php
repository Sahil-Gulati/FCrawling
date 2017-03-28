<?php
namespace FCrawling;
/**
 * This class has an enhanced ability to send HTTP request via CURL simultaneously
 * @author Sahil Gulati <sahil.gulati1991@outlook.com>
 * 
 */
class FCrawling
{
    /**
     * @var Int This variable will hold group no. 
     */
    private $groupNo=0;
    /**
     * @var Int Mixed variable will hold callback, This will be a mixed variable. 
     */
    private $callback=false;
    /**
     * @var Int This is a temporary variable contains group counter.
     */
    private $groupCounter=1;
    /**
     *
     * @var Int This variable will hold the group size in case of grouping. 
     */
    private $groupSize=0;
    /**
     *
     * @var Array This variable will hold curl request resources. 
     */
    private $requests=array();
    /**
     * @var Int This variable will contain a window size, that is the no. of 
     * request which can be added in this particular execution 
     */
    private $windowSize=10000;
    /**
     *
     * @var CurlResource This variable will hold multi curl resource.
     * @note This will work in case of parallel execution.
     */
    private $multiRequest=false;
    /**
     *
     * @var Boolean This variable determines whether to wait for curl output or not.
     * @note This will work in case of parallel execution.
     */
    private $reliableOutput=true;
    /**
     *
     * @var String This variable will contain curl execution method which can be 
     * either parallel or serial.
     */
    private $executionType='parallel';
    /**
     * @var Array this array will hold curl requests copies
     * which will be used in further execution in parallel
     */
    private $requestsGroups=array();
    /**
     * @var Array this array will hold curl requests copies
     * which will be used in further execution in serial
     */
    private $requestsCopies=array();
    
    /**
     * @var Array this array will hold curl requests copies in groups
     * which will be used in further execution 
     */
    private $requestsGroupsSerial=array();
    
    public function __construct($callback='')
    {
        if(is_string($callback))
        {
            $this->callback=$callback;
        }
        elseif(is_array($callback) && count($callback)>0)
        {
            $this->callback=$callback;
        }
        $this->multiRequest=  curl_multi_init();
    }
    /**
     * @author Sahil Gulati <sahil.gulati1991@outlook.com>
     * This function sets the type of execution of requests.
     * @param String $type This variable can be set to <b>parallel</b> or <b>serial</b>.
     * @throws FCrawlingException
     */
    public function setExecutionType($type='parallel')
    {
        if(is_string($type))
        {
            if($type=='parallel' || $type=='serial')
            {
                $this->executionType=$type;
            }
            else
            {
                throw new FCrawlingException("IET");
            }
        }
    }
    /**
     * This function sets group size, which will be executed via CURL on group basis.
     * @author Sahil Gulati <sahil.gulati1991@outlook.com>
     * @param Int $groupSize Size of the group
     */
    public function setGroupSize($groupSize=0)
    {
        $groupSize=intval($groupSize);
        if(is_int($groupSize))
        {
            $this->groupSize=$groupSize;
        }
    }
    /**
     * This function sets window size, which is the maximum no. of requests can be gathered
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param Int $windowSize No of requests which can be collected in FCrawling
     * @throws FCrawlingException
     */
    public function setWindowSize($windowSize=10000)
    {
        if(is_int($windowSize))
        {
            $this->windowSize=$windowSize;
        }
        else
        {
            throw new FCrawlingException('IWS');
        }
    }
    /**
     * This function sets output reliability
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param Boolean $reliable Set output reliability
     * @throws FCrawlingException
     */
    public function setOutputReliability($reliable)
    {
        if(is_bool($reliable))
        {
            $this->reliableOutput=$reliable;
        }
    }
    /**
     * This function adds the single request in different groups
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param \FCrawling\FCrawlingRequest This Object will hold single request parameters
     */
    public function setRequest(\FCrawling\FCrawlingRequest $fcrawlingRequestObj)
    {
        if(is_object($fcrawlingRequestObj) && count($this->requests)<$this->windowSize)
        {
            $this->requests[]=$fcrawlingRequestObj;
            if($this->groupSize)
            {
                $currentSize=count($this->requests);
                $this->copyGroupedHandler($fcrawlingRequestObj,$this->groupNo,$this->groupCounter);
                $this->copyGroupedHandlerSerial($fcrawlingRequestObj,$this->groupNo,$this->groupCounter);
                if($this->groupCounter<$this->groupSize)
                {
                    $this->groupCounter+=1;
                }
                else
                {
                    $this->groupNo+=1;
                    $this->groupCounter=1; 
                }
            }
            else
            {
                $this->copyHandler($fcrawlingRequestObj);
                curl_multi_add_handle($this->multiRequest, $fcrawlingRequestObj->curlRequest);
            }
        }
        else
        {
            throw new FCrawlingException("ERL");
        }
    }
    /**
     * This function copies current CURL request handler and its essential parameters in an array
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param \FCrawling\FCrawlingRequest This Object will hold single request parameters
     */
    private function copyHandler(\FCrawling\FCrawlingRequest $fcrawlingRequestObj)
    {
        $requestCount=count($this->requests)-1;
        $this->requestsCopies[$requestCount]['curlRequest']=curl_copy_handle($fcrawlingRequestObj->curlRequest);
        $this->requestsCopies[$requestCount]['url']=$fcrawlingRequestObj->url;
        $this->requestsCopies[$requestCount]['requestParameters']=$fcrawlingRequestObj->requestParameters;
    }
    /**
     * This function copies current CURL request handler and its essential parameters in an array in groups for parallel execution
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param \FCrawling\FCrawlingRequest This Object will hold single request parameters
     * @param Int $groupNo
     * @param Int $groupCounter
     */
    private function copyGroupedHandler(\FCrawling\FCrawlingRequest $fcrawlingRequestObj,$groupNo,$groupCounter)
    {
        $this->requestsGroups[$groupNo][$groupCounter]['curlRequest']=curl_copy_handle($fcrawlingRequestObj->curlRequest);
        $this->requestsGroups[$groupNo][$groupCounter]['url']=$fcrawlingRequestObj->url;
        $this->requestsGroups[$groupNo][$groupCounter]['requestParameters']=$fcrawlingRequestObj->requestParameters;
    }
    /**
     * This function copies current CURL request handler and its essential parameters in an array in groups for serial execution
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @param \FCrawling\FCrawlingRequest This Object will hold single request parameters
     * @param Int $groupNo
     * @param Int $groupCounter
     */
    private function copyGroupedHandlerSerial(\FCrawling\FCrawlingRequest $fcrawlingRequestObj,$groupNo,$groupCounter)
    {
        $this->requestsGroupsSerial[$groupNo][$groupCounter]['curlRequest']=curl_copy_handle($fcrawlingRequestObj->curlRequest);
        $this->requestsGroupsSerial[$groupNo][$groupCounter]['url']=$fcrawlingRequestObj->url;
        $this->requestsGroupsSerial[$groupNo][$groupCounter]['requestParameters']=$fcrawlingRequestObj->requestParameters;
    }
    /**
     * This function will execute all the requests add in this class object
     * @author Sahil Gulati <sahil.gulati1991@outlook.com> 
     * @throws FCrawlingException
     */
    public function execute()
    {
        if(empty($this->groupSize))
        {
            switch($this->executionType)
            {
                case 'parallel':
                    return ($this->reliableOutput) ? $this->parallelExecution(true) : $this->parallelExecution(false);
                case 'serial':
                    return $this->serialExecution();
                default:
                    throw new FCrawlingException('EF');
                    break;
            }
        }
        else
        {
            switch($this->executionType)
            {
                case 'parallel':
                    return ($this->reliableOutput) ? $this->groupedParallelExecution(true) : $this->groupedParallelExecution(false);
                    break;
                case 'serial':
                    return $this->groupedSerialExecution();
                default:
                    throw new FCrawlingException('EF');
                    break;
            }
        }
    }
    /**
     * This function decides whether to execute requests with output or no output
     * @author Sahil Gulati <sahil@getamplify.com>
     * @param Boolean $reliablityBit This is a reliability variable which define whether to execute with output or no output
     * @return boolean
     */
    private function parallelExecution($reliablityBit)
    {
        if($reliablityBit===false)
        {
            $this->noOutputParallel();
            return true;
        }
        $this->outputParallel();
    }
    /**
     * This function decides whether to execute requests with output or no output(For parallel execution in group)
     * @author Sahil Gulati <sahil@getamplify.com>
     * @param Boolean $reliablityBit This is a reliability variable which define whether to execute with output or no output
     * @return boolean
     */
    private function groupedParallelExecution($reliablityBit)
    {
        if(!$reliablityBit)
        {
            $this->noOuputParallelGrouped();
            return true;
        }
        $this->ouputParallelGrouped();
    }
    /**
     * This function execute requests in parallel with no output
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function noOutputParallel()
    {
        $running=count($this->requests);
        while($running!=0)
        {
            curl_multi_exec($this->multiRequest, $running);
        }
    }
    /**
     * This function execute grouped requests in parallel without output.
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function noOuputParallelGrouped()
    {
        foreach($this->requestsGroups as $groupNo => $value)
        {
            $multi=  curl_multi_init();
            foreach($value as $requestNo => $curlRequest)
            {
                curl_multi_add_handle($multi, $curlRequest['_curlRequest']);
            }
            $running=count($this->requestsGroups[$groupNo]);
            while($running!=0)
            {
                curl_multi_exec($multi, $running);
            }
        }
        curl_multi_close($multi);
    }
    /**
     * This function execute requests in parallel with output.
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function outputParallel()
    {
        $running=count($this->requests);
        while($running!=0)
        {
            curl_multi_exec($this->multiRequest, $running);
            foreach($this->requests as $index => $curlObject)
            {
                $response=curl_multi_getcontent($curlObject->curlRequest);
                if(!empty($response))
                {
                    curl_multi_remove_handle($this->multiRequest, $curlObject->curlRequest);
                    $curlObject->curlInfo=  curl_getinfo($curlObject->curlRequest);
                    unset($curlObject->curlRequest);
                    $this->callback($response,$index,$curlObject);
                    unset($this->requests[$index]);
                }
            }
        }
    }
    
    /**
     * This function execute grouped requests in parallel with output.
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function ouputParallelGrouped()
    {
        foreach($this->requestsGroups as $groupNo => $value)
        {
            $multi=  curl_multi_init();
            foreach($value as $requestNo => $curlRequest)
            {
                curl_multi_add_handle($multi, $curlRequest['curlRequest']);
            }
            $running=count($this->requestsGroups[$groupNo]);
            while($running!=0)
            {
                curl_multi_exec($multi, $running);
                foreach($this->requestsGroups[$groupNo] as $requestNo => $requestObj)
                {
                    if(isset($requestObj['curlRequest']))
                    {
                        $response= curl_multi_getcontent($requestObj['curlRequest']);
                        if($response)
                        {
                            curl_multi_remove_handle($multi, $requestObj['curlRequest']);
                            $fCrawlingRequestObj= new \FCrawling\FCrawlingRequest($this->requestsGroups[$groupNo][$requestNo]["url"]);
                            $fCrawlingRequestObj->requestParameters=$this->requestsGroups[$groupNo][$requestNo]["requestParameters"];
                            $fCrawlingRequestObj->curlInfo=curl_getinfo($requestObj['curlRequest']);
                            unset($fCrawlingRequestObj->curlRequest);
                            unset($this->requestsGroups[$groupNo][$requestNo]['curlRequest']);
                            $this->callback($response, $requestNo,$fCrawlingRequestObj, $groupNo);
                        }
                    }
                }
            }
            curl_multi_close($multi);
        }
    }
    /**
     * This function will execute requests serially.
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function serialExecution()
    {
        foreach($this->requestsCopies as $requestNo => $requestObj)
        {
            $response=  curl_exec($requestObj['curlRequest']);
            unset($this->requestsCopies[$requestNo]['curlRequest']);
            $this->callback($response,($requestNo+1), $this->requestsCopies[$requestNo]);
        }
    }
    /**
     * This function will execute requests serially in groups.
     * @author Sahil Gulati <sahil@getamplify.com>
     */
    private function groupedSerialExecution()
    {
        foreach($this->requestsGroupsSerial as $groupNo => $value)
        {
            foreach($value as $requestNo=> $curlRequestData)
            {
                $response=curl_exec($curlRequestData['curlRequest']);
                unset($this->requestsGroupsSerial[$groupNo][$requestNo]['curlRequest']);
                $this->callback($response, $requestNo, $this->requestsGroupsSerial[$groupNo][$requestNo],$groupNo+1);
            }
        }
    }
    /**
     * This function will is responsible for generating callback with basic information related to curl request
     * @author Sahil Gulati <sahil@getamplify.com>
     * @param Mixed $response
     * @param Int $responseNo
     * @param Mixed $infoObj
     * @param Int $groupNo
     * @throws FCrawlingException
     */
    private function callback($response,$responseNo,$infoObj,$groupNo=false)
    {
        if(!empty($this->callback))
        {
            if(is_string($this->callback))
            {
                $function=$this->callback;
                $function($response,$responseNo,$infoObj,$groupNo);
            }
            elseif(is_array($this->callback) && is_object($this->callback[0]) && is_string($this->callback[1]))
            {
                $function=$this->callback[1];
                $this->callback[0]->$function($response,$responseNo,$infoObj,$groupNo);
            }
            elseif(is_array($this->callback) && is_string($this->callback[0]) && is_string($this->callback[1]))
            {
                $evalString=$this->callback[0]."::".$this->callback[1].'($response,$responseNo,$infoObj,$groupNo);';
                eval($evalString);
            }
            else
            {
                throw new FCrawlingException('CE');
            }
        }
    }
}

class FCrawlingRequest
{
    /**
     *
     * @var CURL curl request resource  
     */
    public $curlRequest=null;
    /**
     *
     * @var String Contains the url on which we are going to send request
     */
    public $url="";
    /**
     *
     * @var Array array of parameters of the queue 
     */
    public $requestParameters=array();

    /**
     *
     * @var Array This will hold the complete request info.
     */
    public $curlInfo=array();
    /**
     * 
     * @param String $url This variable will contain URL on which you want request
     */
    public function __construct($url='')
    {
        $this->curlRequest=  curl_init();
        if(!empty($url))
        {
            $this->url=$url;
            $this->requestParameters["query_parameters"]=  self::_get_request_parameters($url);
            $this->requestParameters[CURLOPT_URL]=  $url;
            $this->requestParameters[CURLOPT_RETURNTRANSFER]=  true;
            $this->requestParameters[CURLOPT_FORBID_REUSE]=  true;
            curl_setopt($this->curlRequest, CURLOPT_URL, $url);
            curl_setopt($this->curlRequest, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curlRequest, CURLOPT_FORBID_REUSE, true);
        }
    }
    /**
     * This function is used to set CURL request parameters
     * @author Sahil Gulati <sahil@getamplify.com>
     * @param Array $options This will contain all the parameters required to send with CURL request
     */
    public function setOption($options=array())
    {
        if(is_array($options) && count($options)>0) 
        {
            foreach($options as $option => $value)
            {
                $this->requestParameters[$option]=$value;
                curl_setopt($this->curlRequest, $option, $value);
            }
        }
    }
    /**
     * This function will return an array of parameters from the URL
     * @author Sahil Gulati <sahil@getamplify.com>
     * @param String $url
     * @return array Array of parameters, available in URL
     */
    private static function _get_request_parameters($url)
    {
        if(!empty($url))
        {
            $urlPortions=  explode('?', $url);
            if(!empty($urlPortions[1]))
            {
                $varArray=array();
                $urlPortions[1]=urldecode($urlPortions[1]);
                parse_str($urlPortions[1],$varArray);
                return $varArray;
            }
        }
    }
}
/**
 * This class will hold all kinds of exeception which can be raised from 
 * FCrawling Library
 * @author Sahil Gulati <sahil@getamplify.com>
 * 
 */
class FCrawlingException extends \Exception
{
    public function __construct($code)
    {
        if($code=='ERL')
        {
            $message="Request's limit exceeded!";
        }
        elseif($code=='CE')
        {
            $message="Failed to make callback response!";
        }
        elseif($code=='EF')
        {
            $message="Failed to execute request. Invalid execution type!";
        }
        elseif($code=='IWS')
        {
            $message="'Window size must be an integer not greater than lakhs!";
        }
        elseif($code=='IET')
        {
            $message="Invalid execution type!";
        }
        parent::__construct($message);
    }
}
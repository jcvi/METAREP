<?php
/***********************************************************
* File: gradient.php
* Handles interactions with the KEGG URL based API.
*
* PHP versions 4 and 5
*
* METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
* Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @link http://www.jcvi.org/metarep METAREP Project
* @package metarep
* @version METAREP v 1.4.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class ParallelizationComponent extends Object {

	var $curlOptions = array(
						CURLOPT_HEADER => false, 
						CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"),
						CURLOPT_TIMEOUT => 0,
						CURLOPT_RETURNTRANSFER => true
						);
	var $numParallelProcesses	= 60;							
	
	private function addCurlProcess(&$process,&$processId,&$multiCurlSession,&$curlOptions,&$runningStack,&$waitingStack) {
		
		$postData = '';	
		foreach($process['solrArguments'] as $key=>$value) { 
			$postData .= $key.'='.$value.'&'; 
		}   
 		$postData = rtrim($postData, '&');
		

		$singleCurlHandle = curl_init();   
		$curlOptions[CURLOPT_URL] =  $process['url'];
		$curlOptions[CURLOPT_POSTFIELDS] = $postData;

		
		
        // use global curl options to set options for individual process
        curl_setopt_array($singleCurlHandle, $curlOptions);
        $result = curl_multi_add_handle($multiCurlSession, $singleCurlHandle);
        
        if($result == 0 ){
			$resourceId = str_replace('Resource id #','', (string) $singleCurlHandle);
	        
			//create running stack of size numParallelProcesses that hold pointers
			// from the resource ID to the process IDs 
	        $runningStack[$resourceId] = $processId;;
	        // remove process from the waiting stack
            unset($waitingStack[$processId]);
             
        }
      	else {
      		debug('Failed to add handle.');
      	}
	}
	
	// expects processes [id, url, solrArguments]; global curl options, parallel processes	
	public function execute($processes, $curlOptions = null, $numParallelProcesses = null) {
		if(is_null($curlOptions)) {
			$curlOptions = $this->curlOptions;
		} 
		if(is_null($numParallelProcesses)) {
			$numParallelProcesses = $this->numParallelProcesses;
		}
		 	
        $multiCurlSession = curl_multi_init();
        $runningStack = array();
        $isMultiCurlRunning = 0;

        //init process stack; holds all processes
        $waitingStack = array();  
                   
        foreach($processes as $processId => $value) {       	
        	$waitingStack[$processId] = null;
        } 
        
        if(sizeof($processes) < $numParallelProcesses) {
       		$numParallelProcesses = sizeof($processes);
        }
       #	debug("paral processes".$numParallelProcesses);
        //init process stack equal to the size of max. parallel processes
        $processIds = array_keys($processes);

        for($i =0; $i < $numParallelProcesses; $i++){
        	$processId = $processIds[$i];
            $this->addCurlProcess($processes[$processId],$processId,$multiCurlSession,$curlOptions,$runningStack,$waitingStack);
        }
      
        do {
       		while (($curlMultiExecOut = curl_multi_exec($multiCurlSession,  $isMultiCurlRunning)) == CURLM_CALL_MULTI_PERFORM) ;
        	
       		//debug("curlMultiExecOut:".$curlMultiExecOut." CURLM_OK:".CURLM_OK.":");
       		
       		// handle errors
       		if ($curlMultiExecOut != CURLM_OK) {
       			//debug('curl_multi_exec did not return CURLM_OK');
                break;
       		}
            
            // find process that completed or failed
            while ($curlMultiInfoOut = curl_multi_info_read($multiCurlSession)) {
				//debug($curlMultiInfoOut);
            	$curlHandle = $curlMultiInfoOut['handle'];
            	
                // get information from curl handle
                $info = curl_getinfo($curlHandle);

            	
                //get results from curl handle
                $result = curl_multi_getcontent($curlHandle); 

                debug($result);
                if(empty($result)) {
                	debug(curl_error($curlHandle)) ;
                }
                
                // get resource ID from the curl handle
                $resourceId = str_replace('Resource id #','', (string) $curlHandle);
                
                $processId = $runningStack[$resourceId];

                // remove resource from the processing stack
                unset($runningStack[$resourceId]);

               
                if(count($waitingStack) > 0) {
                	
					// get next process ID
                	$processId = array_shift(array_keys($waitingStack));
                   	$this->addCurlProcess($processes[$processId],$processId,$multiCurlSession,$curlOptions,$runningStack,$waitingStack);
                }

                // remove handle from multi curl
                curl_multi_remove_handle($multiCurlSession, $curlHandle);

            }

            // wait until there is activity on any of the connections or for 5 seconds whatever comes
            // first
            if ($isMultiCurlRunning)
                curl_multi_select($multiCurlSession, 5);

        } while ( $isMultiCurlRunning);
        
        //close store
        curl_multi_close($multiCurlSession);
	}
}
?>
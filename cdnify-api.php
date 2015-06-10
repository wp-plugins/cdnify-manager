<?php

class CDNify_API {
    
    private $api_key  = "";
    private $base_url = "https://cdnify.com/api/v1/";
    
    public function __construct() {
        
    }
    
    public function setAPIKey($api_key) {
        $this->api_key = $api_key;
    }
    
    public function getURL($endpoint, $params=array(), $options="") {
        $url = $this->base_url . $endpoint;
        
        if(count($params) > 0) {
            $url .= "/" . implode("/", $params);
        }
        
        if($options != "") {
            $url .= "?" . $options;
        }
        
        return($url);
    }
    
    public function callAPI($url, $request="GET", $data=array()) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_key . ":x");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        if(count($data) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $result = curl_exec($ch);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return(json_decode($result));
    }
    
    public function getResources() {
        return($this->callAPI($this->getURL("resources")));
    }
    
    public function getResource($resource_id) {
        return($this->callAPI($this->getURL("resources", array($resource_id))));
    }
    
    public function createResource($params=array()) {
        return($this->callAPI($this->getURL("resources"), "POST", $params));
    }
    
    public function updateResource($resource_id, $params=array()) {
        return($this->callAPI($this->getURL("resources", array($resource_id)), "PATCH", $params));
    }
    
    public function deleteResource($resource_id, $params=array()) {
        return($this->callAPI($this->getURL("resources", array($resource_id)), "DELETE", $params));
    }
    
    public function deleteCache($resource_id=null) {
        return($this->callAPI($this->getURL("resources", array($resource_id, "cache")), "DELETE"));
    }
    
    public function getResourceBandwidth($resource_id, $from, $to) {
        return($this->callAPI($this->getURL("stats", array($resource_id, "bandwidth"), "datefrom=" . $from . "&dateto=" . $to)));
    }
    
}

?>
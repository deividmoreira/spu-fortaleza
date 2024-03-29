<?php

require_once('CurlClient/GetAdapters/JsonGetAdapter.php');
require_once('CurlClient/PostAdapters/JsonPostAdapter.php');

class Alfresco_Rest_CurlClient
{

    const FORMAT_STRING = 'string';
    const FORMAT_FORMDATA = 'formdata';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_TEXT_XML = 'text/xml';
    const FORMAT_ATOM = 'atom';
    const DEFAULT_INPUT_FORMAT = self::FORMAT_JSON;
    const DEFAULT_RETURN_FORMAT = self::FORMAT_JSON;

    private function _doRequest($url, $options = array())
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,60); 
        //curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        //curl_setopt($ch, CURLOPT_TRANSFERTEXT, true);
        
        $options[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        $result = $this->_filterStringCaracteresEspeciais($result);

        return $result;
    }

    public function doGetRequest($url, $returnFormat = self::DEFAULT_RETURN_FORMAT)
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'GET';
        $result = $this->_doRequest($url, $options);

        if ($returnFormat == self::FORMAT_JSON) {
            $getAdapterObj = $this->getGetAdapter(self::FORMAT_JSON);
            $result = $getAdapterObj->decode($result, true);
        } elseif ($returnFormat == self::FORMAT_ATOM) {
            $getAdapterObj = $this->getGetAdapter(self::FORMAT_ATOM);
            $result = $getAdapterObj->decode($result);
        }

        return $result;
    }

    public function doPostRequest($url, $postData, $postDataFormat = self::DEFAULT_INPUT_FORMAT, $returnFormat = self::DEFAULT_RETURN_FORMAT)
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';

        // Encoding
        if ($postDataFormat != self::FORMAT_FORMDATA) {
            $postAdapterObj = $this->getPostAdapter($postDataFormat);
            $postData = $postAdapterObj->encode($postData);
            $options = $postAdapterObj->updateOptions($options);
        }

        $options[CURLOPT_POSTFIELDS] = $postData;
        $result = $this->_doRequest($url, $options);

        // Decoding
        if ($returnFormat != self::FORMAT_FORMDATA) {
            $getAdapterObj = $this->getGetAdapter($returnFormat);
            $return = $getAdapterObj->decode($result, true);
        }

        return $return;
    }

    public function doDeleteRequest($url, $options = array())
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        $result = $this->_doRequest($url, $options);

        return $result;
    }

    public function doPutRequest($url, $data, $contentType = self::FORMAT_TEXT_XML)
    {
        $options = array();
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $options[CURLOPT_HTTPHEADER] = array("Content-Type: " . $contentType);
        $options[CURLOPT_POSTFIELDS] = $data;

        $result = $this->_doRequest($url, $options);

        return $result;
    }

    public function getGetAdapter($adapterFormat = self::DEFAULT_INPUT_FORMAT)
    {
        $adapterFormat = ucfirst(strtolower($adapterFormat));
        $classname = "Alfresco_Rest_CurlClient_GetAdapters_" . $adapterFormat . "GetAdapter";

        require_once 'CurlClient/GetAdapters/' . $adapterFormat . 'GetAdapter.php';
        $adapterObject = new $classname();

        return $adapterObject;
    }

    public function getPostAdapter($adapterFormat = self::DEFAULT_RETURN_FORMAT)
    {
        $adapterFormat = ucfirst(strtolower($adapterFormat));
        $classname = "Alfresco_Rest_CurlClient_PostAdapters_" . $adapterFormat . "PostAdapter";

        require_once 'CurlClient/PostAdapters/' . $adapterFormat . 'PostAdapter.php';
        $adapterObject = new $classname();

        return $adapterObject;
    }

    private function _filterStringCaracteresEspeciais($string)
    {
        $string = preg_replace_callback(
            '/(:".*")/', create_function(
                '$matches', '
                    $caracteresEspeciais = array("\u", "\/", "\n", "\r", "\t", "\v", "\f", "\\\", "\\"");
                    $conteudo = substr($matches[0],2,-1);
                    $conteudo .= "|";
                    
                    $res = ""; 
                    
                    for ($i = 0; $i < strlen($conteudo)-1; $i++) {
                        if (in_array($conteudo[$i], $caracteresEspeciais)) {
                            $especial = $conteudo[$i].$conteudo[$i+1];
                            
                            if (!in_array($especial, $caracteresEspeciais)) {
                                $res .= "\\\";
                            }
                        }
                        $res .= $conteudo[$i];
                    }
                    
                    $res .= "\\"";
                    
                    return ":\\"" . $res;
                '
            ), $string
        );

        return str_replace(array("\n", "\r"), "", $string);
    }

}

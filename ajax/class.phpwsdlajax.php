<?php

/*
PhpWsdl - Generate WSDL from PHP
Copyright (C) 2011  Andreas Zimmermann, wan24.de 

This program is free software; you can redistribute it and/or modify it under 
the terms of the GNU General Public License as published by the Free Software 
Foundation; either version 3 of the License, or (at your option) any later 
version. 

This program is distributed in the hope that it will be useful, but WITHOUT 
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 

You should have received a copy of the GNU General Public License along with 
this program; if not, see <http://www.gnu.org/licenses/>.
*/

if(basename($_SERVER['SCRIPT_FILENAME'])==basename(__FILE__))
	exit;

/**
 * This webservice forwards a SOAP request to another SOAP server using PHPs SoapClient class.
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlAjax{
	/**
	 * This may be a list of valid URIs an external SOAP client can call.
	 * Use this to avoid using your AJAX proxy as zombie client, unless 
	 * you really WANT to serve an open proxy.
	 * 
	 * @var string[]
	 */
	public static $Restrict=Array();
	/**
	 * Set this to a filename or an URI to force using only this location as WSDL.
	 * If you use this property, you don't need to set up restrictions. Simply 
	 * give an empty string as first parameter to the PhpWsdlAjax->Forward method.
	 * 
	 * @var string
	 */
	public static $WSDL=null;
	/**
	 * Set this to override the $options parameter of the PhpWsdlAjax->Forward method
	 * 
	 * @var array
	 */
	public static $Options=null;
	/**
	 * Set this to override the $requestHeaders parameter of the PhpWsdlAjax->Forward method
	 * 
	 * @var array
	 */
	public static $RequestHeaders=null;
	
	/**
	 * Will run the webservice
	 * 
	 * @param boolean $useProxy Use the proxy class?
	 */
	public static function Run($useProxy=false){
		if(!class_exists('PhpWsdl'))
			require_once(dirname(__FILE__).'/class.phpwsdl.php');
		PhpWsdl::Debug('Running AJAX proxy');
		if(!$useProxy){
			PhpWsdl::CreateInstance(true,__FILE__);
		}else{
			PhpWsdl::Debug('Using PhpWsdlProxy');
			$soap=PhpWsdl::CreateInstance(null,null,null,__FILE__);
			$soap->RunServer(
				null,
				Array(
					'PhpWsdlAjax',
					new PhpWsdlAjax()
				)
			);
		}
	}
	
	/**
	 * This webservice forwards a SOAP request to another server using PHPs SoapClient class.
	 * This is required when you want to use a SOAP webservice from JavaScript that is located at 
	 * another URI than your website is located. Modern browsers deny AJAX requests to foreign 
	 * servers. So you need this proxy webservice that forwards your request.
	 * 
	 * $parameters needs to be an JSON array - f.e.:
	 * 
	 * ["string Parameter",123,true]
	 * 
	 * $options and $requestHeaders are JSON encoded hash (string key/string value) arrays.
	 * 
	 * For information how to produce JSON encoded objects (or how to decode them), please visit 
	 * http://www.json.org/js.html
	 * 
	 * @param string $wsdl The WSDL filename or URI
	 * @param string $method The method name
	 * @param string $param The parameters array (JSON encoded object)
	 * @param string $options The options array (JSON encoded object)
	 * @param string $requestHeaders The requestHeaders array (JSON encoded object)
	 * @return string JSON encoded response object
	 */
	public function Forward($wsdl,$method,$param,$options,$requestHeaders){
		// Prepare parameters
		list(
			$wsdl,
			$method,
			$param,
			$options,
			$requestHeaders
		)=Array(
			(!is_null(self::$WSDL))?self::$WSDL:utf8_decode($wsdl),
			utf8_decode($method),
			json_decode(utf8_decode($param)),
			(is_null($options)||!is_null(self::$Options))?self::$Options:json_decode(utf8_decode($options)),
			(is_null($requestHeaders)||!is_null(self::$RequestHeaders))?self::$RequestHeaders:json_decode(utf8_decode($requestHeaders))
		);
		if(is_array($options)&&sizeof($options)<1)
			$options=null;
		if(is_array($requestHeaders)&&sizeof($requestHeaders)<1)
			$requestHeaders=null;
		// Check restrictions
		PhpWsdl::Debug('Forward "'.$method.'" to '.$wsdl);
		$rLen=sizeof(self::$Restrict);
		if($rLen>0){
			$i=-1;
			$found=false;
			$temp=strtolower($wsdl);
			while(++$i<$rLen)
				if(substr($temp,0,strlen(self::$Restrict[$i]))==strtolower(self::$Restrict[$i])){
					$found=true;
					break;
				}
			if(!$found){
				PhpWsdl::Debug('Access denied');
				throw(new Exception('Request URI forbidden'));
			}
		}
		// Check the cache
		$file=self::GetWsdlCacheFilename($wsdl);
		if(self::IsCacheValid($file)){
			PhpWsdl::Debug('Using cached WSDL');
			$wsdl=$file;
		}else if(self::WriteWsdlToCache($wsdl,$file)){
			PhpWsdl::Debug('Using WSDL from cache');
			$wsdl=$file;
		}else{
			PhpWsdl::Debug('Cache not available');
		}
		// Forward the request
		PhpWsdl::Debug('Forward the request using WSDL from '.$wsdl);
		$client=new SoapClient($wsdl);
		PhpWsdl::Debug('Client created');
		if(PhpWsdl::$Debugging)
			PhpWsdl::Debug('Parameters: '.print_r($param,true));
		$res=$client->__soapCall($method,$param,$options,$requestHeaders);
		if(PhpWsdl::$Debugging)
			PhpWsdl::Debug('Response: '.print_r($res,true));
		return json_encode($res);
	}
	
	/**
	 * Determine if the cache is valid
	 * 
	 * @param string $file The WSDL cache filename
	 * @return boolean Valid?
	 */
	public static function IsCacheValid($file){
		if(is_null($file))
			return false;
		if(!file_exists($file)||!file_exists($file.'.cache'))
			return false;
		if(PhpWsdl::$CacheTime>=0&&time()-file_get_contents($file.'.cache')>PhpWsdl::$CacheTime)
			return false;
		return true;
	}
	
	/**
	 * Write WSDL to cache
	 * 
	 * @param string $wsdl The WSDL URI
	 * @param string $file The cache filename
	 * @return boolean Succeed?
	 */
	public static function WriteWsdlToCache($wsdl,$file=null){
		if(is_file($wsdl))
			return false;
		if(is_null($file)){
			$file=self::GetWsdlCacheFilename($wsdl);
			if(is_null($file))
				return false;
		}
		PhpWsdl::Debug('Write "'.$wsdl.'" to cache '.$file);
		$xml=file_get_contents($wsdl);
		if($xml===false){
			PhpWsdl::Debug('Could not get WSDL from '.$wsdl);
			return false;
		}
		if(file_put_contents($file,$xml)===false){
			PhpWsdl::Debug('Could not write WSDL to cache');
			return false;
		}
		if(file_put_contents($file.'.cache',time())===false){
			PhpWsdl::Debug('Could not write cache time');
			return false;
		}
		return true;
	}
	
	/**
	 * Get the cache filename
	 * 
	 * @param string $wsdl The WSDL URI
	 * @return string The filename or NULL
	 */
	public static function GetWsdlCacheFilename($wsdl){
		if(is_file($wsdl))
			return null;
		return (is_null(PhpWsdl::$CacheFolder))?null:PhpWsdl::$CacheFolder.'/ajax-'.sha1($wsdl).'.wsdl';
	}
}

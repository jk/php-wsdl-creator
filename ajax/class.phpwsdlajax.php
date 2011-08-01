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

//FIXME Status: Pre-Alpha!

if(basename($_SERVER['SCRIPT_FILENAME'])==basename(__FILE__))
	exit;

/**
 * An array of string
 * 
 * @pw_complex stringArray An array of string
 */

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
	 */
	public static function Run(){
		if(!class_exists('PhpWsdl'))
			require_once(dirname(__FILE__).'/class.phpwsdl.php');
		PhpWsdl::Debug('Running AJAX proxy');
		PhpWsdl::CreateInstance(true,$_SERVER['SCRIPT_FILENAME']);
	}
	
	/**
	 * This webservice forwards a SOAP request to another server using PHPs SoapClient class.
	 * This is required when you want to use a SOAP webservice from JavaScript that is located at 
	 * another URI than your website is located. Modern browsers deny AJAX requests to foreign 
	 * servers. So you need this proxy webservice that forwards your request.
	 * 
	 * @param string $wsdl The WSDL filename or URI
	 * @param string $method The method name
	 * @param stringArray $param The methods parameters
	 * @param stringArray $options The options array
	 * @param string $requestHeaders The requestHeaders (JSON string)
	 * @return string JSON encoded response string
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
			$param,
			(!is_null(self::$Options))?self::$Options:$options,
			(is_null($requestHeaders))?null:json_decode(utf8_decode($requestHeaders))
		);
		if(is_array($options)&&sizeof($options)<1)
			$options=null;
		if(!is_null(self::$RequestHeaders)){
			$requestHeaders=self::$RequestHeaders;
		}else if($requestHeaders===''||(is_array($requestHeaders)&&sizeof($requestHeaders)<1)){
			$requestHeaders=null;
		}
		PhpWsdl::Debug('Forward '.$method.' to '.$wsdl);
		// Check restrictions
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
		// Forward the request
		$client=new SoapClient($wsdl);
		return utf8_encode(json_encode($client->__soapCall($method,$param,$options,$requestHeaders)));
	}
}

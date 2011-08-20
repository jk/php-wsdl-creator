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

PhpWsdlAjax::Init();

/**
 * This webservice forwards a SOAP request to another SOAP server using the PhpWsdlClient
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlAjax{
	/**
	 * The PhpWsdlClient object
	 * 
	 * @var PhpWsdlClient
	 */
	public static $Client=null;
	/**
	 * The default CURL options for PhpWsdlAjax::HttpRequest
	 * 
	 * @var array
	 */
	public static $CurlOptions=null;
	
	/**
	 * This will run the proxy webservice and exit the script execution
	 * 
	 * @param strign $wsdl The WSDL URI
	 */
	public static function RunProxy($wsdl){
		require_once(dirname(__FILE__).'/class.phpwsdlclient.php');
		PhpWsdl::Debug('Run PhpWsdlAjax proxy at '.$wsdl);
		PhpWsdl::RegisterHook('CreatePhpCallHook','ajax','PhpWsdlAjax::CreatePhpCall');
		self::$Client=new PhpWsdlClient($wsdl);
		$server=self::$Client->CreateServerFromWsdl();
		if($server->IsSoapRequest())
			eval($server->OutputPhp(false,false,Array(
				'class'			=>	'PhpWsdlAjaxProxy',
				'openphp'		=>	false
			)));
		$server->RunServer(null,'PhpWsdlAjaxProxy');
	}
	
	/**
	 * This will forward a request to another URI, output the response and exit
	 * 
	 * @param string $targetUri The target URI
	 */
	public static function RunForwarder($targetUri){
		require_once(dirname(__FILE__).'/class.phpwsdl.php');
		PhpWsdl::Debug('Run PhpWsdlAjax forwarder at '.$targetUri);
		if($_SERVER['REQUEST_METHOD']=='GET'){
			PhpWsdl::Debug('Forward GET request');
			ob_start('ob_gzhandler');
			echo self::HttpRequest((isset($_SERVER['QUERY_STRING']))?'?'.$_SERVER['QUERY_STRING']:'',null,$targetUri);
		}else{
			PhpWsdl::Debug('Forward POST request');
			ob_start('ob_gzhandler');
			echo self::HttpRequest(null,file_get_contents('php://input'),$targetUri);
		}
		exit;
	}
	
	/**
	 * Do a http request
	 * 
	 * @param string $get The GET query string or NULL
	 * @param string $post The POST query string or NULL
	 * @param string $targetUri The target URI
	 * @return string The response
	 */
	public static function HttpRequest($get,$post,$targetUri){
		if(is_null($post))
			return file_get_contents($targetUri.(($get!='')?'?'.$get:''));
		$ch=curl_init($targetUri);
		curl_setopt_array($ch,array_merge(
			self::$CurlOptions,
			Array(
				CURLOPT_POSTFIELDS	=>	$post
			)
		));
		$res=curl_exec($ch);
		if(curl_errno($ch)){
			$err=curl_error($ch);
			curl_close($ch);
			throw(new Exception($err));
		}
		curl_close($ch);
		return $res;
	}
	
	/**
	 * Create the PHP code to forward a request
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreatePhpCall($data){
		$res=&$data['res'];
		$res[]="\t\tif(is_null(self::\$_Server))";
		$res[]="\t\t\tself::\$_Server=PhpWsdlAjax::\$Client->GetClient();";
		$res[]="\t\treturn PhpWsdlAjax::\$Client->DoRequest(\$method,\$param);";
		return false;
	}
	
	/**
	 * Init PhpWsdlAjax
	 */
	public static function Init(){
		self::$CurlOptions=Array(
			CURLOPT_POST			=>	true,
			CURLOPT_SSL_VERIFYPEER	=>	false,
			CURLOPT_SSL_VERIFYHOST	=>	false,
			CURLOPT_HEADER			=>	false,
			CURLOPT_RETURNTRANSFER	=>	true
		);
	}
}

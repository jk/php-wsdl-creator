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

PhpWsdl::Debug('Servers extension loaded');

PhpWsdl::RegisterHook('InterpretKeywordpw_restHook','servers','PhpWsdlServers::InterpretRest');
PhpWsdl::RegisterHook('BeforeRunServerHook','servers','PhpWsdlServers::BeforeRunServerHook');
PhpWsdl::RegisterHook('PrepareServerHook','servers','PhpWsdlServers::PrepareServerHook');
PhpWsdl::RegisterHook('RunServerHook','servers','PhpWsdlServers::RunServerHook');
PhpWsdl::RegisterHook('BeginCreatePhpHook','servers','PhpWsdlServers::BeginCreatePhpHook');
PhpWsdl::RegisterHook('OutputPhpHook','servers','PhpWsdlServers::OutputPhpHook');
PhpWsdl::RegisterHook('CreatePhpCallHook','servers','PhpWsdlServers::CreatePhpCallHook');
PhpWsdl::RegisterHook('CreateHtmlGeneralHook','servers','PhpWsdlServers::CreateHtmlGeneralHook');
PhpWsdl::RegisterHook('PdfAttachmentHook','servers','PhpWsdlServers::PdfAttachmentHook');

/**
 * Some http protocol servers (JSON, http and REST)
 * 
 * @author Andreas Zimmermann, wan24.de
 * @version 2.3
 */
class PhpWsdlServers{
	/**
	 * The version number
	 * 
	 * @var string
	 */
	public static $VERSION='2.3';
	/**
	 * Disable the client cache?
	 * 
	 * @var boolean
	 */
	public static $DisableClientCache=true;
	/**
	 * Enable the JSON server
	 * 
	 * @var boolean
	 */
	public static $EnableJson=true;
	/**
	 * Enable the http server
	 * 
	 * @var boolean
	 */
	public static $EnableHttp=true;
	/**
	 * Enable the REST server
	 * 
	 * @var boolean
	 */
	public static $EnableRest=true;
	/**
	 * The webservice handler class name
	 * 
	 * @var string
	 */
	public $ClassName=null;
	/**
	 * The webservice handler object
	 * 
	 * @var object
	 */
	public $Object=null;
	/**
	 * The PhpWsdl object
	 * 
	 * @var PhpWsdl
	 */
	public $Server=null;
	/**
	 * The URI to the PHP client proxy download
	 * 
	 * @var string
	 */
	public $PhpUri=null;
	
	/**
	 * Constructor
	 * 
	 * @param PhpWsdl $server The PhpWsdl object
	 */
	public function PhpWsdlServers($server){
		$this->Server=$server;
	}
	
	/**
	 * Determine if the request is a JSON request
	 * 
	 * @return boolean JSON request?
	 */
	public static function IsJsonRequest(){
		return self::$EnableJson&&!self::HasParam('call')&&(self::HasParam('json')||self::HasParam('JSON'));
	}
	
	/**
	 * Determine if the request is a http request
	 * 
	 * @return boolean http request?
	 */
	public static function IsHttpRequest(){
		return self::$EnableHttp&&self::HasParam('call');
	}
	
	/**
	 * Determine if the request is a REST request
	 * 
	 * @return boolean REST request?
	 */
	public static function IsRestRequest(){
		return self::$EnableRest&&isset($_SERVER['PATH_INFO']);
	}
	
	/**
	 * Determine if the PhpWsdlServers extension can handle this request
	 * 
	 * @return boolean Can handle?
	 */
	public static function CanHandleRequest(){
		return self::IsHttpRequest()||self::IsJsonRequest()||self::IsRestRequest();
	}
	
	/**
	 * Determine if we can handle the request
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function BeforeRunServerHook($data){
		return !(self::HasParam('phpjsonclient')||self::HasParam('PHPJSONCLIENT')||self::CanHandleRequest());
	}
	
	/**
	 * Create a server object
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function PrepareServerHook($data){
		if(!self::CanHandleRequest())
			return true;
		PhpWsdl::Debug('Prepare a JSON/http/REST server');
		$server=&$data['server'];
		$json=new PhpWsdlServers($server);
		$data['soapserver']=$json;
		$useProxy=&$data['useproxy'];
		$class=&$data['class'];
		if($useProxy||!is_object($class)){
			$temp=($useProxy)?'PhpWsdlProxy':$class;
			if(!is_null($temp)){
				PhpWsdl::Debug('Setting server class '.$temp);
				$json->ClassName=$temp;
			}else{
				PhpWsdl::Debug('No server class or object');
			}
		}else{
			PhpWsdl::Debug('Setting server object '.get_class($class));
			$json->Object=$class;
		}
		return false;
	}
	
	/**
	 * Run the server
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function RunServerHook($data){
		if(self::HasParam('phpjsonclient')||self::HasParam('PHPJSONCLIENT')){
			PhpWsdl::Debug('Create PHP client');
			$data['server']->OutputPhp(true,true,Array(),false);
			if($data['andexit'])
				exit;
			return false;
		}
		if(!self::CanHandleRequest())
			return true;
		if(get_class($data['soapserver'])!='PhpWsdlServers'){
			PhpWsdl::Debug('Not a valid server object');
			return true;
		}
		PhpWsdl::Debug('Handle the request');
		if(self::IsJsonRequest())
			return $data['soapserver']->HandleJsonRequest();
		if(self::IsHttpRequest())
			return $data['soapserver']->HandleHttpRequest();
		if(self::IsRestRequest())
			return $data['soapserver']->HandleRestRequest();
		throw(new Exception('Could not handle request'));
	}
	
	/**
	 * Interpret a REST declaration
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function InterpretRest($data){
		if($data['method']=='')
			return true;
		PhpWsdl::Debug('Interpret REST "'.$data['keyword'][1].'" for method "'.$data['method'].'"');
		$info=explode(' ',$data['keyword'][1],3);
		if(sizeof($info)<2){
			PhpWsdl::Debug('Invalid REST definition');
			return true;
		}
		$method=$info[0];
		$path=$info[1];
		$docs=null;
		if(sizeof($info)>2)
			$docs=$info[2];
		$settings=&$data['settings'];
		if(!isset($settings['settings']))
			$settings['settings']=Array();
		$settings=&$settings['settings'];
		if(!isset($settings['rest']))
			$settings['rest']=Array();
		$settings=&$settings['rest'];
		if(!isset($settings[$method]))
			$settings[$method]=Array();
		$settings=&$settings[$method];
		$settings[]=Array(
			'path'			=>	$path,
			'docs'			=>	$docs
		);
		return false;
	}
	
	/**
	 * Create PHP properties
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function BeginCreatePhpHook($data){
		if(
			!self::HasParam('phpjsonclient')&&
			!self::HasParam('PHPJSONCLIENT')
		)
			return true;
		$res=&$data['res'];
		$server=$data['server'];
		$res[]="\t/**";
		$res[]="\t * The endpoint URI";
		$res[]="\t *";
		$res[]="\t * @var string";
		$res[]="\t */";
		$res[]="\tpublic static \$_EndPoint='".$server->EndPoint."';";
		return false;
	}
	
	/**
	 * Change the class name
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function OutputPhpHook($data){
		$options=&$data['options'];
		$server=&$data['server'];
		if(!isset($options['class']))
			$options['class']=$server->Name.'JsonClient';
		return true;
	}
	
	/**
	 * Create PHP code for a server request
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreatePhpCallHook($data){
		if(
			(
				!self::HasParam('phpjsonclient')&&
				!self::HasParam('PHPJSONCLIENT')
			)||
			!self::$EnableJson
		)
			return true;
		PhpWsdl::Debug('Create PhpWsdlServers JSON PHP client code');
		$res=&$data['res'];
		$res[]="\t\t".'$call=Array(';
		$res[]="\t\t\t".'"call"=>$method,';
		$res[]="\t\t\t".'"param"=>$param';
		$res[]="\t\t".');';
		$res[]="\t\t".'return json_decode(file_get_contents($this->EndPoint."?JSON=".urlencode(json_encode($call))';
		return false;
	}
	
	/**
	 * Extend HTML documentation
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreateHtmlGeneralHook($data){
		if(!self::$EnableJson)
			return true;
		PhpWsdl::Debug('Append HTML general information with PhpWsdlServers information');
		$res=&$data['res'];
		$server=$data['server'];
		$url=$server->EndPoint.'?PHPJSONCLIENT';
		$res[]='<p>PHP JSON client download URI: <a href="'.$url.'">'.$url.'</a></p>';
		return true;
	}
	
	/**
	 * Attach the PHP client sources
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function PdfAttachmentHook($data){
		if(!self::$EnableJson)
			return true;
		$temp=&$data['param'];
		$cnt=&$data['cnt'];
		$cnt++;
		$server=$data['server'];
		$temp['attachment_'.$cnt]=$server->Name.'.jsonclient.php:'.$server->EndPoint.'?PHPJSONCLIENT';
		return true;
	}
	
	/**
	 * Handle a REST request
	 * 
	 * @return boolean Response
	 */
	public function HandleRestRequest(){
		// Find the requested method
		$path=$_SERVER['PATH_INFO'];
		$temp=self::GetParam('method');
		$method=($temp=='')?$_SERVER['REQUEST_METHOD']:$temp;
		PhpWsdl::Debug('REST call "'.$method.'" at "'.$path.'"');
		$temp=$this->GetRestMethod($method,$path);
		if(is_null($temp))
			throw(new Exception('Method not found'));
		list($method,$target)=$temp;
    	$req=Array(
    		'call'		=>	$method->Name,
    		'param'		=>	Array()
    	);
    	PhpWsdl::CallHook(
    		'RestCallHook',
    		Array(
    			'server'		=>	$this,
    			'req'			=>	&$req,
    			'path'			=>	&$path,
    			'method'		=>	&$method,
    			'target'		=>	&$target
    		)
    	);
		// Collect the parameters
		if(PhpWsdl::CallHook(
				'RestParametersHook',
				Array(
	    			'server'		=>	$this,
	    			'req'			=>	&$req,
					'path'			=>	&$path,
	    			'method'		=>	&$method,
	    			'target'		=>	&$target
				)
			)
		){
			$pLen=sizeof($method->Param);
			if(is_null($target)){
				// Unknown parameter handling
				PhpWsdl::Debug('Undefined parameter handling');
				$temp=explode('/',$path);
				$tLen=sizeof($temp)-1;
				// Collect parameters from the path info
				$i=-1;
				while(++$i<$pLen){
					$p=$methods->Param[$i];
					if($i>$tLen){
						PhpWsdl::Debug('Collecting parameters stopped at missing "'.$p->Name.'"');
						break;
					}
					$req['param'][]=urldecode((in_array($p->Type,PhpWsdl::$BasicTypes))?$temp[$i+1]:json_decode($temp[$i+1]));
				}
			}else{
				// Declared parameter handling
				PhpWsdl::Debug('Fixed parameter handling');
				if(strpos($target,':')>-1){
					PhpWsdl::Debug('Method with parameters');
					list($mTemp,$pTemp)=explode(':',$target,2);
				}else{
					PhpWsdl::Debug('Method without parameters');
					$mTemp=$target;
					$pTemp=false;
				}
				if($pTemp!==false){
					// Map input parameters to their names
					$temp=explode('/',substr($path,strlen($mTemp)));
					$tLen=sizeof($temp);
					$iMap=explode('/',str_replace(':','',$pTemp));
					$map=Array();
					$i=-1;
					$len=sizeof($iMap);
					while(++$i<$len)
						$map[$iMap[$i]]=$temp[$i];
					// Sort parameters
					$temp=Array();
					$i=-1;
					$lastValue=-1;
					while(++$i<$pLen){
						$p=$method->Param[$i];
						$name=$p->Name;
						if(isset($map[$name])){
							$temp[]=(in_array($p->Type,PhpWsdl::$BasicTypes))?urldecode($map[$name]):json_decode(urldecode($map[$name]));
							$lastValue=$i;
						}else{
							PhpWsdl::Debug('Parameter "'.$p->Name.'" not found');
							$temp[]=null;
						}
					}
					// Remove all parameters that are not present in the request
					if($lastValue>-1&&$lastValue<$pLen){
						$i=$lastValue;
						$req['param']=array_slice($temp,0,$lastValue+1);
					}else{
						$req['param']=$temp;
					}
				}else{
					$i=-1;
				}
			}
			// Collect the last parameter from the request body
			if($i<$pLen&&$pLen>0){
				if(PhpWsdl::CallHook(
						'RestRequestHook',
						Array(
			    			'server'		=>	$this,
			    			'req'			=>	&$req,
			    			'path'			=>	&$path,
			    			'method'		=>	&$method,
			    			'target'		=>	&$target,
							'index'			=>	$i
						)
					)
				){
					$temp=file_get_contents('php://input');
					if($temp!=''){
						PhpWsdl::Debug('Use request body as parameter #'.($i+1));
						$req['param'][]=json_decode($temp);
					}
				}
			}
		}
		if(PhpWsdl::$Debugging)
    		PhpWsdl::Debug('Parameters: '.print_r($req['param'],true));
    	// Execute the method and output the response
    	$temp=$this->HandleRequest($req,$method);
    	if(is_null($method->Return))
    		return false;// No return value -> no output
    	if(PhpWsdl::CallHook(
    			'RestResponseHook',
    			Array(
    				'server'		=>	$this,
    				'req'			=>	&$req,
    				'res'			=>	&$temp,
	    			'path'			=>	&$path,
	    			'method'		=>	&$method,
	    			'target'		=>	&$target
    			)
    		)
    	){
			ob_start('ob_gzhandler');
			$this->PlainTextHeaders();
    		echo (in_array($method->Return->Type,PhpWsdl::$BasicTypes))?$temp:json_encode($temp);
    	}
    	return false;
	}
	
	/**
	 * Handle a JSON request
	 * 
	 * @return boolean Response
	 */
	public function HandleJsonRequest(){
    	// Decode the JSON request
    	$json=(self::HasParam('json'))?'json':'JSON';
    	$req=json_decode(self::GetParam($json));
		if(is_null($req)||!is_object($req))
			throw(new Exception('Invalid JSON object'));
		if(!isset($req->param))
    		$req->param=Array();
    	if(!isset($req->call))
    		throw(new Exception('Invalid JSON request'));
    	PhpWsdl::Debug('JSON call "'.$req->call.'"');
    	if(PhpWsdl::$Debugging)
    		PhpWsdl::Debug('Parameters: '.print_r($req->param,true));
    	$req=Array(
    		'call'			=>	$req->call,
    		'param'			=>	$req->param
    	);
    	// Find the requested method object
    	$method=$this->Server->GetMethod($req['call']);
    	if(is_null($method))
    		throw(new Exception('Method "'.$req['call'].'" not exists'));
    	// Execute the method and output the response
    	$temp=$this->HandleRequest($req,$method);
		ob_start('ob_gzhandler');
		$this->PlainTextHeaders();
		echo json_encode($temp);
    	return false;
	}
	
	/**
	 * Handle a http request
	 * 
	 * @return boolean Response
	 */
	public function HandleHttpRequest(){
    	// Initialize the method call
    	$req=Array(
    		'call'		=>	self::GetParam('call'),
    		'param'		=>	Array()
    	);
    	PhpWsdl::Debug('http call "'.$req['call'].'"');
    	// Find the requested method object and parameters
    	$method=$this->Server->GetMethod($req['call']);
    	if(is_null($method))
    		throw(new Exception('Method "'.$req['call'].'" not exists'));
    	if(PhpWsdl::CallHook(
    			'HttpParametersHook',
    			Array(
    				'server'		=>	$this,
    				'req'			=>	&$req,
    				'method'		=>	&$method
    			)
    		)
    	){
	    	$i=-1;
	    	$len=sizeof($method->Param);
	    	while(++$i<$len){
	    		$p=$method->Param[$i];
	    		if(!self::HasParam($method->Param[$i]->Name)){
	    			PhpWsdl::Debug('Collecting parameters stopped at missing "'.$method->Param[$i]->Name.'"');
	    			break;
	    		}
	    		$temp=self::GetParam($method->Param[$i]->Name);
	    		$req['param'][]=(in_array($p->Type,PhpWsdl::$BasicTypes))?$temp:json_decode($temp);
	    	}
    	}
    	if(PhpWsdl::$Debugging)
    		PhpWsdl::Debug('Parameters: '.print_r($req['param'],true));
    	// Execute the method and output the response
    	$temp=$this->HandleRequest($req,$method);
    	if(is_null($method->Return))
    		return false;// No return value -> no output
    	if(PhpWsdl::CallHook(
    			'HttpResponseHook',
    			Array(
    				'server'		=>	$this,
    				'req'			=>	&$req,
    				'res'			=>	&$temp,
    				'method'		=>	&$method
    			)
    		)
    	){
			ob_start('ob_gzhandler');
			$this->PlainTextHeaders();
    		echo (in_array($method->Return->Type,PhpWsdl::$BasicTypes))?$temp:json_encode($temp);
    	}
    	return false;
	}
	
	/**
	 * Handle a request
	 * 
	 * @param array $req The request data
	 * @param PhpWsdlMethod $method The method object
	 * @return mixed The response
	 */
	private function HandleRequest($req,$method){
    	// Get the handler object
    	$res=null;
    	$obj=$this->Object;
    	if(is_null($obj))
	    	if(!is_null($this->ClassName))
	    		if(class_exists($this->ClassName))
	    			eval("\$obj=new ".$this->ClassName."();");
	    PhpWsdl::Debug('Handler object: '.print_r($obj,true));
	    // Prepare the call
	    $call=($method->IsGlobal)
	    	?$method->Name
	    	:Array(
	    		$obj,
	    		$method->Name
	    	);
	    PhpWsdl::Debug('Call: '.print_r($call,true));
	    // Call the method
        $pLen=sizeof($req['param']);
    	if($pLen<1){
    		return call_user_func($call);
    	}else if($pLen<2){
    		return call_user_func($call,$req['param'][0]);
    	}else{
    		return call_user_func_array($call,$req['param']);
    	}
	}
	
	/**
	 * Output plain text response headers
	 */
	private function PlainTextHeaders(){
		header('Content-Type: text/plain; encoding=UTF-8');
		if(self::$DisableClientCache)
			$this->NoCacheHeaders();
	}
	
	/**
	 * Output headers to disable the client cache
	 */
	private function NoCacheHeaders(){
    	header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');   
    	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    	header('Cache-Control: post-check=0, pre-check=0',false);
    	header('Pragma: no-cache');
	}
	
	/**
	 * Get a http parameter
	 * 
	 * @param string $name The key
	 * @return string The value
	 */
	private static function GetParam($name){
		$name=addslashes($name);
		if(isset($_GET[$name]))
			return stripslashes($_GET[$name]);
		if(isset($_POST[$name]))
			return stripslashes($_POST[$name]);
		return '';
	}
	
	/**
	 * Determine if we got a GET/POST parameter
	 * 
	 * @param string $name
	 * @return boolean Parameter exists?
	 */
	private static function HasParam($name){
		return isset($_GET[$name])||isset($_POST[$name]);
	} 
	
	/**
	 * Find the handler method for a REST request
	 * 
	 * @param string $method The method (GET/POST/...)
	 * @param string $path The REST server path
	 * @return array The handler method and the path or NULL, if not found
	 */
	private function GetRestMethod($method,$path){
		if(substr($path,strlen($path)-1)!='/')
			$path.='/';
		// Find a REST method
		PhpWsdl::Debug('Find REST method "'.$method.'" at "'.$path.'"');
		$i=-1;
		$mLen=sizeof($this->Server->Methods);
		while(++$i<$mLen){
			$m=$this->Server->Methods[$i];
			if(is_null($m->Settings))
				continue;
			if(!isset($m->Settings['rest']))
				continue;
			if(!isset($m->Settings['rest'][$method]))
				continue;
			$rest=$m->Settings['rest'][$method];
			$j=-1;
			$rLen=sizeof($rest);
			while(++$j<$rLen){
				$call=$rest[$j]['path'];
				if(strpos($call,':')>-1)
					list($call,$dummy)=explode(':',$call,2);
				if(substr($call,strlen($call)-1)!='/')
					$call.='/';
				if(substr($path,0,strlen($call))==$call){
					PhpWsdl::Debug('Method found at index #'.$i);
					return Array($m,$rest[$j]['path']);
				}
			}
		}
		// Find a method without REST declaration
		$temp=explode('/',$path);
		$res=$this->Server->GetMethod($temp[1]);
		return (is_null($res))
			?null
			:Array($res,null);
	}
}

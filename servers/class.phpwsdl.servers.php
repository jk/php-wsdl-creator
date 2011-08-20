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
PhpWsdl::RegisterHook('CreateMethodPhpHook','servers','PhpWsdlServers::CreateMethodPhpHook');
PhpWsdl::RegisterHook('CreateHtmlGeneralHook','servers','PhpWsdlServers::CreateHtmlGeneralHook');
PhpWsdl::RegisterHook('CreateMethodHtmlHook','servers','PhpWsdlServers::CreateMethodHtmlHook');
PhpWsdl::RegisterHook('PdfAttachmentHook','servers','PhpWsdlServers::PdfAttachmentHook');
PhpWsdl::RegisterHook('CreateInstanceHook','servers','PhpWsdlServers::ConstructorHook');

/**
 * Some http protocol servers (JSON, http, REST and XML RPC)
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlServers{
	/**
	 * The GUID
	 * 
	 * @var string
	 */
	public $GUID;
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
	 * Enable the RPC server
	 * 
	 * @var boolean
	 */
	public static $EnableRpc=true;
	/**
	 * Enable parameter names in XML RPC requests?
	 * 
	 * @var boolean
	 */
	public static $EnableRpcNamedParameters=false;
	/**
	 * Is this an RPC request?
	 * 
	 * @var boolean|NULL
	 */
	private static $IsRpcRequest=null;
	/**
	 * The server object that will be used to run the server
	 * 
	 * @var PhpWsdlServers
	 */
	public static $UseServer=null;
	/**
	 * Attach JavaScript to PDF?
	 * 
	 * Note: JavaScript can be attached, but the user can't save JavaScript attachments with 
	 * Adobe Acrobat Reader per default. To open them from Windows the registry at (f.e.) 
	 * HKEY_LOCAL_MACHINE\SOFTWARE\Policies\Adobe\Adobe Reader\[VERSION]\FeatureLockDown\cDefaultLaunchAttachmentPerms 
	 * has to be touched manually!
	 * 
	 * @var boolean
	 */
	public static $AttachJsInPdf=false;
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
	 * The cached PHP http client proxy
	 * 
	 * @var string
	 */
	public $HttpPhp=null;
	/**
	 * Set this to the PHP URI, if it's different from your SOAP endpoint + "?PHPHTTPCLIENT"
	 * 
	 * @var string
	 */
	public $HttpPhpUri=null;
	/**
	 * Force sending HTTP PHP (has a higher priority than PhpWsdlServers->ForceNotOutputHttpPhp)
	 * 
	 * @var boolean
	 */
	public $ForceOutputHttpPhp=false;
	/**
	 * Force NOT sending HTTP PHP (disable sending HTTP PHP)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputHttpPhp=false;
	/**
	 * The cached PHP JSON client proxy
	 * 
	 * @var string
	 */
	public $JsonPhp=null;
	/**
	 * Set this to the PHP URI, if it's different from your SOAP endpoint + "?PHPJSPNCLIENT"
	 * 
	 * @var string
	 */
	public $JsonPhpUri=null;
	/**
	 * Force sending JSON PHP (has a higher priority than PhpWsdlServers->ForceNotOutputJsonPhp)
	 * 
	 * @var boolean
	 */
	public $ForceOutputJsonPhp=false;
	/**
	 * Force NOT sending JSON PHP (disable sending JSON PHP)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputJsonPhp=false;
	/**
	 * The cached PHP REST client proxy
	 * 
	 * @var string
	 */
	public $RestPhp=null;
	/**
	 * Set this to the PHP URI, if it's different from your SOAP endpoint + "?PHPRESTCLIENT"
	 * 
	 * @var string
	 */
	public $RestPhpUri=null;
	/**
	 * Force sending REST PHP (has a higher priority than PhpWsdlServers->ForceNotOutputRestPhp)
	 * 
	 * @var boolean
	 */
	public $ForceOutputRestPhp=false;
	/**
	 * Force NOT sending REST PHP (disable sending REST PHP)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputRestPhp=false;
	/**
	 * The cached PHP XML RPC client proxy
	 * 
	 * @var string
	 */
	public $RpcPhp=null;
	/**
	 * Set this to the PHP URI, if it's different from your SOAP endpoint + "?PHPRPCCLIENT"
	 * 
	 * @var string
	 */
	public $RpcPhpUri=null;
	/**
	 * Force sending XML RPC PHP (has a higher priority than PhpWsdlServers->ForceNotOutputRpcPhp)
	 * 
	 * @var boolean
	 */
	public $ForceOutputRpcPhp=false;
	/**
	 * Force NOT sending XML RPC PHP (disable sending XML RPC PHP)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputRpcPhp=false;
	/**
	 * The cached JavaScript JSON client proxy
	 * 
	 * @var string
	 */
	public $JsonJs=null;
	/**
	 * Set this to the JavaScript URI, if it's different from your SOAP endpoint + "?JSJSONCLIENT"
	 * 
	 * @var string
	 */
	public $JsonJsUri=null;
	/**
	 * Force sending JSON JS (has a higher priority than PhpWsdlServers->ForceNotOutputJsonJs)
	 * 
	 * @var boolean
	 */
	public $ForceOutputJsonJs=false;
	/**
	 * Force NOT sending JSON JS (disable sending JSON JS)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputJsonJs=false;
	/**
	 * The cached and compressed JavaScript JSON client proxy
	 * 
	 * @var string
	 */
	public $JsonJsMin=null;
	/**
	 * Force the JSON JS compression (has a higher priority than PhpWsdlServers->ForceNotJsonJsMin)
	 * 
	 * @var boolean
	 */
	public $ForceJsonJsMin=false;
	/**
	 * Force NOT JSON JS compression (disable compression)
	 * 
	 * @var booleean
	 */
	public $ForceNotJsonJsMin=false;
	
	/**
	 * Constructor
	 * 
	 * @param PhpWsdl $server The PhpWsdl object
	 */
	public function PhpWsdlServers($server){
		$this->GUID=uniqid();
		PhpWsdl::Debug('New PhpWsdlServers "'.$this->GUID.'"');
		$this->Server=$server;
		self::$UseServer=$this;
		PhpWsdl::RegisterHook('ReadCacheHook','servers',Array($this,'ReadCacheHook'));
		PhpWsdl::RegisterHook('WriteCacheHook','servers',Array($this,'WriteCacheHook'));
	}
	
	/**
	 * Determine if the request is a JSON request
	 * 
	 * @return boolean JSON request?
	 */
	public static function IsJsonRequest(){
		return self::$EnableJson&&function_exists('json_encode')&&!self::HasParam('call')&&(self::HasParam('json')||self::HasParam('JSON'));
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
	 * Determine if the request is a RPC request
	 * 
	 * @return boolean RPC request?
	 */
	public static function IsRpcRequest(){
		if(!self::$EnableRpc||!is_null(self::$IsRpcRequest))
			return self::$EnableRpc&&self::$IsRpcRequest;
		if(function_exists('xmlrpc_server_create')){
			$in=file_get_contents('php://input');
			$xml=new DOMDocument();
			if($xml->loadXML($in)){
				$x=new DOMXPath($xml);
				$temp=$x->query('/*');
				self::$IsRpcRequest=$temp->length>0&&$temp->item(0)->nodeName=='methodCall';
			}else{
				self::$IsRpcRequest=false;
			}
		}else{
			self::$IsRpcRequest=false;
		}
		return self::$IsRpcRequest;
	}
	
	/**
	 * Determine if the PhpWsdlServers extension can handle this request
	 * 
	 * @return boolean Can handle?
	 */
	public static function CanHandleRequest(){
		return 
			self::IsHttpRequest()||
			self::IsJsonRequest()||
			self::IsRestRequest()||
			self::IsRpcRequest()||
			self::IsJsonPhpRequest()||
			self::IsJsonJsRequest()||
			self::IsHttpPhpRequest()||
			self::IsRestPhpRequest()||
			self::IsRpcPhpRequest();
	}
	
	/**
	 * Determine if the PHP JSON client proxy is requested
	 * 
	 * @return boolean Requested?
	 */
	public static function IsJsonPhpRequest(){
		return self::$EnableJson&&(self::$UseServer->ForceOutputJsonPhp||((self::HasParam('phpjsonclient')||self::HasParam('PHPJSONCLIENT'))&&!self::$UseServer->ForceNotOutputJsonPhp));
	}
	
	/**
	 * Determine if the JavaScript JSON client proxy is requested
	 * 
	 * @return boolean Requested?
	 */
	public static function IsJsonJsRequest(){
		return self::$EnableJson&&(self::$UseServer->ForceOutputJsonJs||((self::HasParam('jsjsonclient')||self::HasParam('JSJSONCLIENT'))&&!self::$UseServer->ForceNotOutputJsonJs));
	}
	
	/**
	 * Determine if the PHP XML RPC client proxy is requested
	 * 
	 * @return boolean Requested?
	 */
	public static function IsRpcPhpRequest(){
		return self::$EnableRpc&&(self::$UseServer->ForceOutputRpcPhp||((self::HasParam('phprpcclient')||self::HasParam('PHPRPCCLIENT'))&&!self::$UseServer->ForceNotOutputRpcPhp));
	}

	/**
	 * Determine if the PHP http client proxy is requested
	 * 
	 * @return boolean Requested?
	 */
	public static function IsHttpPhpRequest(){
		return self::$EnableHttp&&(self::$UseServer->ForceOutputHttpPhp||((self::HasParam('phphttpclient')||self::HasParam('PHPHTTPCLIENT'))&&!self::$UseServer->ForceNotOutputHttpPhp));
	}

	/**
	 * Determine if the PHP REST client proxy is requested
	 * 
	 * @return boolean Requested?
	 */
	public static function IsRestPhpRequest(){
		return self::$EnableRest&&(self::$UseServer->ForceOutputRestPhp||((self::HasParam('phprestclient')||self::HasParam('PHPRESTCLIENT'))&&!self::$UseServer->ForceNotOutputRestPhp));
	}
	
	/**
	 * Determine if we can handle the request
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function BeforeRunServerHook($data){
		if(!self::CanHandleRequest())
			return true;
		PhpWsdl::Debug('PhpWsdlServers will handle the request');
		$server=$data['server'];
		$server->GetWsdlFromCache();
		$srv=self::$UseServer;
		$jsonPhp=self::IsJsonPhpRequest();
		$jsonJs=self::IsJsonJsRequest();
		$rpcPhp=self::IsRpcPhpRequest();
		$httpPhp=self::IsHttpPhpRequest();
		$restPhp=self::IsRestPhpRequest();
		if(!$jsonPhp&&!$jsonJs&&!$rpcPhp&&!$httpPhp&&!$restPhp)
			return false;
		if($jsonJs){
			PhpWsdl::Debug('Output JavaScript requested');
			$srv->OutputJs();
		}else{
			PhpWsdl::Debug('Output PHP requested');
			$php=null;
			if($jsonPhp){
				$php=$srv->JsonPhp;
				$ext='json';
			}else if($rpcPhp){
				$php=$srv->RpcPhp;
				$ext='rpc';
			}else if($httpPhp){
				$php=$srv->HttpPhp;
				$ext='http';
			}else{
				$php=$srv->RestPhp;
				$ext='rest';
			}
			$cache=is_null($php);
			$php=$server->OutputPhp(
				true,
				true,
				Array(
					'serversext'	=>	$ext
				)
			);
			if($cache){
				switch($ext){
					case 'json':
						$srv->JsonPhp=$php;
						break;
					case 'http':
						$srv->HttpPhp=$php;
						break;
					case 'rest':
						$srv->RestPhp=$php;
						break;
					case 'rpc':
						$srv->RpcPhp=$php;
						break;
				}
				$server->WriteWsdlToCache(null,null,null,true);
			}
		}
		PhpWsdl::Debug('Quit script execution');
		exit;
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
		PhpWsdl::Debug('Prepare a JSON/http/REST/RPC server');
		$server=&$data['server'];
		$json=self::$UseServer;
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
		if(!self::CanHandleRequest())
			return true;
		if(get_class($data['soapserver'])!='PhpWsdlServers'){
			PhpWsdl::Debug('Not a valid server object');
			return true;
		}
		PhpWsdl::Debug('Handle the request');
		if(self::IsJsonRequest()){
			$res=$data['soapserver']->HandleJsonRequest();
		}else if(self::IsHttpRequest()){
			return $data['soapserver']->HandleHttpRequest();
		}else if(self::IsRestRequest()){
			return $data['soapserver']->HandleRestRequest();
		}else if(self::IsRpcRequest()){
			return $data['soapserver']->HandleRpcRequest();
		}else{
			throw(new Exception('Could not handle request'));
		}
		if($data['andexit']){
			PhpWsdl::Debug('Stopping script execution');
			exit;
		}
		return $res;
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
		$iLen=sizeof($info);
		if($iLen<2){
			PhpWsdl::Debug('Invalid REST definition');
			return true;
		}
		$method=$info[0];
		$path=$info[1];
		$docs=($iLen>2)?$info[2]:null;
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
		if(!isset($data['options']['serversext']))
			return true;
		$res=&$data['res'];
		$res[]="\t/**";
		$res[]="\t * The endpoint URI";
		$res[]="\t *";
		$res[]="\t * @var string";
		$res[]="\t */";
		$res[]="\tpublic static \$_EndPoint='".$data['server']->EndPoint."';";
		return false;
	}
	
	/**
	 * Change the class name
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function OutputPhpHook($data){
		if(!isset($data['options']['serversext'])||isset($options['class']))
			return true;
		switch($data['options']['serversext']){
			case 'http':
				$ext='Http';
				break;
			case 'json':
				$ext='Json';
				break;
			case 'rest':
				$ext='Rest';
				break;
			case 'rpc':
				$ext='XmlRpc';
				break;
			default:
				return true;
				break;
		}
		$data['options']['class']=$data['server']->Name.$ext.'Client';
		return true;
	}
	
	/**
	 * Create PHP code for a server request
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreatePhpCallHook($data){
		if(!isset($data['options']['serversext']))
			return true;
		$res=&$data['res'];
		switch($data['options']['serversext']){
			case 'json':
				PhpWsdl::Debug('Create PhpWsdlServers JSON PHP client code');
				$res[]="\t\t".'$call=Array(';
				$res[]="\t\t\t".'"call"=>$method,';
				$res[]="\t\t\t".'"param"=>$param';
				$res[]="\t\t".');';
				$res[]="\t\t".'return json_decode(file_get_contents(self::$_EndPoint."?JSON=".urlencode(json_encode($call))));';
				break;
			case 'rpc':
				PhpWsdl::Debug('Create PhpWsdlServers XML RPC PHP client code');
				$res[]="\t\t".'self::$_Server=stream_context_create(Array(';
				$res[]="\t\t\t".'"http"			=>	Array(';
				$res[]="\t\t\t\t".'"method"			=>	"POST",';
				$res[]="\t\t\t\t".'"header"			=>	"Content-Type: text/xml",';
				$res[]="\t\t\t\t".'"content"			=>	xmlrpc_encode_request($method,$param)';
				$res[]="\t\t\t".')';
				$res[]="\t\t".'));';
				$res[]="\t\t".'$res=xmlrpc_decode(file_get_contents(self::$_EndPoint,false,self::$_Server));';
				$res[]="\t\t".'if(is_array($res)&&xmlrpc_is_fault($res))';
				$res[]="\t\t\t".'throw(new Exception($res["faultString"],$res["faultCode"]));';
				$res[]="\t\t".'return $res;';
				break;
			case 'http':
				PhpWsdl::Debug('Create PhpWsdlServers http PHP client code');
				$res[]="\t\t".'$param["call"]=$method;';
				$res[]="\t\t".'$temp=Array();';
				$res[]="\t\t".'$keys=array_keys($param);';
				$res[]="\t\t".'$i=-1;';
				$res[]="\t\t".'$len=sizeof($keys);';
				$res[]="\t\t".'while(++$i<$len)';
				$res[]="\t\t\t".'$temp[]=urlencode($keys[$i])."=".urlencode($param[$keys[$i]]);';
				$res[]="\t\t".'return file_get_contents(self::$_EndPoint."?".implode("&",$temp));';
				break;
			case 'rest':
				PhpWsdl::Debug('Create PhpWsdlServers REST PHP client code');
				$res[]="\t\t".'$keys=array_keys($param);';
				$res[]="\t\t".'$i=-1;';
				$res[]="\t\t".'$len=sizeof($keys);';
				$res[]="\t\t".'while(++$i<$len)';
				$res[]="\t\t\t".'$method=str_replace(" ".$keys[$i]."/",urlencode($param[$keys[$i]])."/",$method);';
				$res[]="\t\t".'return file_get_contents(self::$_EndPoint.$method);';
				break;
			default:
				return true;
				break;
		}
		return false;
	}

	/**
	 * Create PHP code for a method call
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreateMethodPhpHook($data){
		if(!isset($data['options']['serversext']))
			return true;
		$server=$data['server'];
		$m=$data['method'];
		$res=&$data['res'];
		switch($data['options']['serversext']){
			case 'rest':
			case 'http':
				PhpWsdl::Debug('Create PhpWsdlServers http/REST PHP method code');
				$basicType=true;
				if(!is_null($m->Return))
					$basicType=in_array($m->Return->Type,PhpWsdl::$BasicTypes);
				$pLen=sizeof($m->Param);
				if($data['options']['serversext']!='rest'){
					$name=$m->Name;
				}else{
					$temp=Array('/'.$m->Name);
					$i=-1;
					while(++$i<$pLen)
						$temp[]=' '.$m->Param[$i]->Name;
					$name=implode('/',$temp).'/';
				}
				$res[]="\t\treturn ".(($basicType)?"":"json_decode(")."self::_Call('".$name."',Array(";
				$i=-1;
				while(++$i<$pLen){
					$p=$m->Param[$i];
					$res[]="\t\t\t'".$p->Name."'=>".((in_array($p->Type,PhpWsdl::$BasicTypes))?"\$".$p->Name:"json_encode(\$".$p->Name.")");
				}
				$res[]="\t\t))".(($basicType)?"":")").";";
				break;
			case 'rpc':
				if(!self::$EnableRpcNamedParameters)
					return true;
				PhpWsdl::Debug('Create PhpWsdlServers XML RPC PHP method code for named parameters');
				$res[]="\t\treturn self::_Call('".$server->Name."',Array(";
				$i=-1;
				$len=sizeof($m->Param);
				while(++$i<$len)
					$res[]="\t\t\t".'"'.$m->Param[$i]->Name.'"=>$'.$m->Param[$i]->Name;
				$res[]="\t\t));";
				break;
			default:
				return true;
				break;
		}
		return false;
	}
	
	/**
	 * Extend general HTML documentation
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreateHtmlGeneralHook($data){
		if(!self::$EnableHttp&&!self::$EnableJson&&!self::$EnableRest&&!self::$EnableRpc)
			return true;
		PhpWsdl::Debug('Append HTML general information with PhpWsdlServers information');
		$server=$data['server'];
		$res=&$data['res'];
		$temp=Array('SOAP');
		if(self::$EnableJson){
			$temp[]='JSON';
			$url=self::$UseServer->GetJsonPhpUri();
			$res[]='<p>PHP JSON client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
			$url=self::$UseServer->GetJsonJsUri();
			$res[]='<p>JavaScript JSON client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
			if(self::IsJsPackerAvailable()){
				$url=self::$UseServer->GetJsonJsUri().'&min';
				$res[]='<p>Compressed JavaScript JSON client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
			}
		}
		if(self::$EnableRpc){
			$temp[]='XML RPC';
			$url=self::$UseServer->GetRpcPhpUri();
			$res[]='<p>PHP XML RPC client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
		}
		if(self::$EnableHttp){
			$temp[]='http';
			$url=self::$UseServer->GetHttpPhpUri();
			$res[]='<p>PHP http client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
		}
		if(self::$EnableRest){
			$temp[]='REST';
			$url=self::$UseServer->GetRestPhpUri();
			$res[]='<p>PHP REST client download URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
		}
		if(sizeof($temp)>1)
			$res[]='<p>The PhpWsdlServers extension allows PhpWsdl to serve these protocols: '.implode(', ',$temp).'</p>';
		return true;
	}

	/**
	 * Extend method HTML documentation
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function CreateMethodHtmlHook($data){
		if(!self::$EnableRest)
			return true;
		PhpWsdl::Debug('Append HTML method information with PhpWsdlServers information');
		$res=&$data['res'];
		$server=$data['server'];
		$m=$data['method'];
		if(!is_null($m->Settings))
			if(isset($m->Settings['rest'])){
				$temp=$m->Settings['rest'];
				$keys=array_keys($temp);
				$i=-1;
				$len=sizeof($keys);
				while(++$i<$len){
					$method=$keys[$i];
					$rest=$temp[$keys[$i]];
					$rTemp=Array();
					$j=-1;
					$rLen=sizeof($rest);
					while(++$j<$rLen){
						$url=$server->EndPoint.$rest[$j]['path'];
						$rTemp[]='<span class="bold">'.$method.'</span> REST URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span>';
						if(!is_null($rest[$j]['docs']))
							$rTemp[]='('.nl2br(htmlentities($rest[$j]['docs'])).')';
					}
					$res[]='<p>'.implode("<br>\n",$rTemp).'</p>';
				}
			}
		$url=$server->EndPoint.'/'.$m->Name.'/';
		$pLen=sizeof($m->Param);
		if($pLen>0){
			$i=-1;
			while(++$i<$pLen)
				$url.=':'.$m->Param[$i]->Name.'/';
		}
		$res[]='<p>Default <span class="bold">GET</span> REST URI: <span class="pre"><a href="'.$url.'">'.$url.'</a></span></p>';
		return true;
	}
	
	/**
	 * Attach the PHP client sources
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function PdfAttachmentHook($data){
		$temp=&$data['param'];
		$cnt=&$data['cnt'];
		$server=$data['server'];
		if(self::$EnableJson){
			$cnt++;
			$temp['attachment_'.$cnt]=$server->Name.'.jsonclient.php:'.self::$UseServer->GetJsonPhpUri();
			if(self::$AttachJsInPdf){
				$cnt++;
				$temp['attachment_'.$cnt]=$server->Name.'.jsonclient.js:'.self::$UseServer->GetJsonJsUri();
				if(self::$UseServer->IsJsPackerAvailable()){
					$cnt++;
					$temp['attachment_'.$cnt]=$server->Name.'.jsonclient.min.js:'.self::$UseServer->GetJsonJsUri().'&min';
				}
			}
		}
		if(self::$EnableRpc){
			$cnt++;
			$temp['attachment_'.$cnt]=$server->Name.'.xmlrpcclient.php:'.self::$UseServer->GetRpcPhpUri();
		}
		if(self::$EnableHttp){
			$cnt++;
			$temp['attachment_'.$cnt]=$server->Name.'.httpclient.php:'.self::$UseServer->GetHttpPhpUri();
		}
		if(self::$EnableRest){
			$cnt++;
			$temp['attachment_'.$cnt]=$server->Name.'.restclient.php:'.self::$UseServer->GetRestPhpUri();
		}
		return true;
	}
	
	/**
	 * Constructor hook
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public static function ConstructorHook($data){
		new PhpWsdlServers($data['server']);
		return true;
	}
	
	/**
	 * Get the HTTP PHP download URI
	 * 
	 * @return string The URI
	 */
	public function GetHttpPhpUri(){
		return ((is_null($this->HttpPhpUri))?$this->Server->EndPoint:$this->HttpPhpUri).'?PHPHTTPCLIENT';
	}

	/**
	 * Get the JSON PHP download URI
	 * 
	 * @return string The URI
	 */
	public function GetJsonPhpUri(){
		return ((is_null($this->JsonPhpUri))?$this->Server->EndPoint:$this->JsonPhpUri).'?PHPJSONCLIENT';
	}
	
	/**
	 * Get the REST PHP download URI
	 * 
	 * @return string The URI
	 */
	public function GetRestPhpUri(){
		return ((is_null($this->RestPhpUri))?$this->Server->EndPoint:$this->RestPhpUri).'?PHPRESTCLIENT';
	}
	
	/**
	 * Get the XML RPC PHP download URI
	 * 
	 * @return string The URI
	 */
	public function GetRpcPhpUri(){
		return ((is_null($this->RpcPhpUri))?$this->Server->EndPoint:$this->RpcPhpUri).'?PHPRPCCLIENT';
	}
	
	/**
	 * Get the JSON JavaScript download URI
	 * 
	 * @return string The URI
	 */
	public function GetJsonJsUri(){
		return ((is_null($this->JsonJsUri))?$this->Server->EndPoint:$this->JsonJsUri).'?JSJSONCLIENT';
	}
	
	/**
	 * Output the JavaScript JSON client source for this webservice
	 * 
	 * @param boolean $withHeaders Send JavaScript headers? (default: TRUE)
	 * @param boolean $echo Print source (default: TRUE)
	 * @param boolean $cache Cache the result (default: TRUE);
	 * @return string JavaScript source
	 */
	public function OutputJs($withHeaders=true,$echo=true,$cache=true){
		PhpWsdl::Debug('Output JavaScript');
		if(sizeof($this->Server->Methods)<1)
			$this->Server->CreateWsdl();
		if($withHeaders)
			$this->JavaScriptHeaders();
		if(is_null($this->JsonJs)){
			$res=Array();
			$data=Array(
				'server'		=>	$this,
				'res'			=>	&$data,
				'withheaders'	=>	&$withHeaders,
				'echo'			=>	&$echo,
				'cache'			=>	&$cache
			);
			if(PhpWsdl::CallHook('ServersBeginOutputJsHook',$data)){
				$res[]='var '.$this->Server->Name.'JsonClient=function(){';
				$res[]='	this._EndPoint="'.$this->Server->EndPoint.'";';
				$res[]='	this._Call=function(method,param,cb){';
				$res[]='		var server=(window.XMLHttpRequest)?new XMLHttpRequest():new ActiveXObject("Microsoft.XMLHTTP");';
				$res[]='		server.open("POST",this._EndPoint,cb!=null);';
				$res[]='		server.setRequestHeader("Content-Type","application/x-www-form-urlencoded");';
				$res[]='		var req="JSON="+encodeURIComponent(JSON.stringify({call:method,param:(param)?param:[]}));';
				$res[]='		if(cb){';
				$res[]='			server.onreadystatechange=this._EndCall;';
				$res[]='			server.cb=cb;';
				$res[]='			server.send(req);';
				$res[]='			return server;';
				$res[]='		}else{';
				$res[]='			server.send(req);';
				$res[]='			var res=JSON.parse(server.responseText);';
				$res[]='			delete(server);';
				$res[]='			return res;';
				$res[]='		}';
				$res[]='	};';
				$res[]='	this._EndCall=function(e){';
				$res[]='		var server=e.currentTarget;';
				$res[]='		if(server.readyState!=4)';
				$res[]='			return;';
				$res[]='		if(server.status!=200)';
				$res[]='			throw(new Exception("AJAX error "+server.status+": "+server.statusText));';
				$res[]='		server.cb(JSON.parse(server.responseText));';
				$res[]='		server.cb=null;';
				$res[]='		delete(server);';
				$res[]='	};';
			}
			$i=-1;
			$len=sizeof($this->Server->Methods);
			while(++$i<$len){
				$m=$this->Server->Methods[$i];
				if(PhpWsdl::CallHook(
						'ServersOutputMethodJsHook',
						array_merge(
							$data,
							Array(
								'method'		=>	&$m
							)
						)
					)
				){
					$res[]='	this.'.$m->Name.'=function(';
					$j=-1;
					$pLen=sizeof($m->Param);
					while(++$j<$pLen)
						$res[]='		'.$m->Param[$j]->Name.',';
					$res[]='		_cb';
					$res[]='	){';
					$res[]='		return this._Call(';
					$res[]='			"'.$m->Name.'",';
					$res[]='			[';
					$j=-1;
					while(++$j<$pLen)
						$res[]='			'.$m->Param[$j]->Name.(($j<$eLen-1)?',':'');
					$res[]='			],';
					$res[]='			(_cb)?_cb:null';
					$res[]='		);';
					$res[]='	};';
				}
			}
			$i=-1;
			$len=sizeof($this->Server->Types);
			while(++$i<$len){
				$t=$this->Server->Types[$i];
				if(PhpWsdl::CallHook(
						'ServersOutputTypeJsHook',
						array_merge(
							$data,
							Array(
								'type'			=>	&$t
							)
						)
					)
				){
					$eLen=sizeof($t->Elements);
					if($eLen<1)
						continue;
					$res[]='	this.'.$t->Name.'=function(){';
					$j=-1;
					while(++$j<$eLen)
						$res[]='		this.'.$t->Elements[$j]->Name.'=null;';
					$res[]='	};';
				}
			}
			if(PhpWsdl::CallHook('ServersEndOutputJsHook',$data)){
				$res[]='};';
			}
			$res=utf8_encode(implode("\n",$res));
			$this->JsonJs=$res;
		}
		$min=$this->ForceJsonJsMin||(self::HasParam('min')&&self::IsJsPackerAvailable()&&!$this->ForceNotJsonJsMin);
		if($min){
			if(is_null($this->JsonJsMin))
				$this->JsonJsMin=$this->PackJavaScript($this->JsonJs);
			$res=$this->JsonJsMin;
		}else if(!$min){
			$res=$this->JsonJs;
		}
		if($cache)
			$this->Server->WriteWsdlToCache(null,null,null,true);
		if($echo)
			echo $res;
		return $res;
	}
	
	/**
	 * Get data from cache
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public function ReadCacheHook($data){
		$d=&$data['data'];
		$this->HttpPhp=$d['phphttp'];
		$this->JsonPhp=$d['phpjson'];
		$this->JsonJs=$d['jsjson'];
		$this->JsonJsMin=$d['jsjsonmin'];
		$this->RestPhp=$d['phprest'];
		$this->RpcPhp=$d['phprpc'];
		return true;
	}
	
	/**
	 * Write data to the cache
	 * 
	 * @param array $data The event data
	 * @return boolean Response
	 */
	public function WriteCacheHook($data){
		$d=&$data['data'];
		$d['phphttp']=$this->HttpPhp;
		$d['phpjson']=$this->JsonPhp;
		$d['jsjson']=$this->JsonJs;
		$d['jsjsonmin']=$this->JsonJsMin;
		$d['phprest']=$this->RestPhp;
		$d['phprpc']=$this->RpcPhp;
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
				$tLen=sizeof($temp)-2;
				// Collect parameters from the path info
				$i=-1;
				while(++$i<$pLen){
					$p=$method->Param[$i];
					if($i>$tLen)
						break;
					PhpWsdl::Debug('Found parameter "'.$p->Name.'"');
					PhpWsdl::Debug('I '.$i);
					PhpWsdl::Debug('tLen '.$tLen);
					PhpWsdl::Debug(urldecode($temp[$i+2]));
					$req['param'][]=(in_array($p->Type,PhpWsdl::$BasicTypes))?urldecode($temp[$i+2]):json_decode(urldecode($temp[$i+2]));
				}
			}else{
				// Fixed parameter handling
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
			$in=file_get_contents('php://input');
			if($in!=''&&$i<$pLen&&$pLen>0){
				if(PhpWsdl::CallHook(
						'RestRequestHook',
						Array(
			    			'server'		=>	$this,
			    			'req'			=>	&$req,
			    			'path'			=>	&$path,
			    			'method'		=>	&$method,
			    			'target'		=>	&$target,
							'in'			=>	&$in,
							'index'			=>	$i
						)
					)
				){
					PhpWsdl::Debug('Use request body as parameter #'.($i+1));
					$req['param'][]=json_decode($temp);
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
	 * Handle a RPC request
	 * 
	 * @return boolean Response
	 */
	public function HandleRpcRequest(){
		if(!self::IsRpcRequest())
			return true;
		PhpWsdl::Debug('Run XML RPC server');
		$rpc=xmlrpc_server_create();
		$i=-1;
		$len=sizeof($this->Server->Methods);
		while(++$i<$len)
			xmlrpc_server_register_method($rpc,$this->Server->Methods[$i]->Name,Array($this,'RpcCallHandler'));
		$temp=xmlrpc_server_call_method($rpc,file_get_contents('php://input'),null);
		$this->XmlHeaders();
		ob_start('ob_gzhandler');
		echo $temp;
		return false;
	}
	
	/**
	 * (Internal) handle a RPC call
	 * 
	 * @param string $method The method name
	 * @param array $param The parameters
	 * @return mixed The response
	 */
	public function RpcCallHandler($method,$param){
    	PhpWsdl::Debug('XML RPC call "'.$method.'"');
		$m=$this->Server->GetMethod($method);
		if(is_null($m))
			throw(new Exception('Method "'.$method.'" not exists'));
		if(!self::$EnableRpcNamedParameters){
			// Unnamed parameters
			PhpWsdl::Debug('Unnamed parameters');
			$req=Array(
				'call'			=>	$m->Name,
				'param'			=>	$param
			);
		}else{
			// Named parameters
			PhpWsdl::Debug('Named parameters');
			$req=Array(
				'call'			=>	$m->Name,
				'param'			=>	Array()
			);
			$map=Array();
			$i=-1;
			$pLen=sizeof($param);
			while(++$i<$pLen){
				$p=$param[$i];
				$keys=array_keys($p);
				PhpWsdl::Debug('Found parameter "'.$keys[0].'"');
				$map[$keys[0]]=$p[$keys[0]];
			}
			$lastValue=-1;
			$i=-1;
			$pLen=sizeof($m->Param);
			while(++$i<$pLen){
				$p=$m->Param[$i];
				if(isset($map[$p->Name])){
					$req['param'][]=$map[$p->Name];
					$lastValue=$i;
				}else{
					PhpWsdl::Debug('Missing parameter "'.$p->Name.'"');
					$req['param'][]=null;
				}
			}
			if($lastValue<$pLen-1){
				PhpWsdl::Debug('Slice parameters array from index #'.$lastValue);
				$req['param']=array_slice($req['param'],0,$lastValue+1);
			}
		}
    	if(PhpWsdl::$Debugging)
    		PhpWsdl::Debug('Parameters: '.print_r($req['param'],true));
		return $this->HandleRequest($req,$m);
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
	 * Output XML response headers
	 */
	private function XmlHeaders(){
		header('Content-Type: text/xml; encoding=UTF-8');
		if(self::$DisableClientCache)
			$this->NoCacheHeaders();
	}

	/**
	 * Output JavaScript response headers
	 */
	private function JavaScriptHeaders(){
		header('Content-Type: text/javascript; encoding=UTF-8');
		header('Content-Disposition: attachment; filename='.$this->Server->Name.'JsonClient.js');
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
				$param=null;
				if(strpos($call,':')>-1)
					list($call,$param)=explode(':',$call,2);
				if(substr($call,strlen($call)-1)!='/')
					$call.='/';
				if((!is_null($param)&&substr($path,0,strlen($call))==$call)||$path==$call){
					PhpWsdl::Debug('Method found at index #'.$i.' with target "'.$rest[$j]['path'].'"');
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
	
	/**
	 * Compress a JavaScript
	 * 
	 * @param string $js The uncompressed JavaScript
	 * @return string The compressed JavaScript
	 */
	private function PackJavaScript($js){
		if(!self::IsJsPackerAvailable())
			return $js;
		PhpWsdl::Debug('Compress a JavaScript');
		if(PhpWsdl::HasHookHandler('ServersPackJsHook'))
			return PhpWsdl::CallHook(
				'ServersPackJsHook',
				Array(
					'server'		=>	$this,
					'js'			=>	&$js
				)
			);
		$packer=new PhpWsdlJavaScriptPacker(utf8_decode($js),62,true,true);
		return utf8_encode($packer->pack());
	}
	
	/**
	 * Determine if the JavaScript packer is available
	 * 
	 * @return boolean Available?
	 */
	public static function IsJsPackerAvailable(){
		return PhpWsdl::HasHookHandler('ServersPackJsHook')||class_exists('PhpWsdlJavaScriptPacker');
	}
}

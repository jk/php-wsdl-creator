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

// You don't require class.phpwsdlelement.php and class.phpwsdlcomplex.php, 
// as long as you don't use complex types. So you may comment those two 
// requires out.
// You may also disable loading the class.phpwsdlproxy.php, if you don't plan 
// to use the proxy class for your webservice.
require_once(dirname(__FILE__).'/class.phpwsdlparam.php');
require_once(dirname(__FILE__).'/class.phpwsdlmethod.php');
require_once(dirname(__FILE__).'/class.phpwsdlelement.php');
require_once(dirname(__FILE__).'/class.phpwsdlcomplex.php');
require_once(dirname(__FILE__).'/class.phpwsdlproxy.php');
require_once(dirname(__FILE__).'/class.phpwsdlparser.php');

// Initialize PhpWsdl
PhpWsdl::Init();

/**
 * PhpWsdl class
 * 
 * @author Andreas Zimmermann
 * @copyright ©2011 Andreas Zimmermann, wan24.de
 * @version 2.0
 */
class PhpWsdl{
	/**
	 * Global static configuration
	 * 
	 * @var array
	 */
	public static $Config=Array();
	/**
	 * The webservice handler object
	 * 
	 * @var object
	 */
	public static $ProxyObject=null;
	/**
	 * The current PhpWsdl server
	 * 
	 * @var PhpWsdl
	 */
	public static $ProxyServer=null;
	/**
	 * The namespace
	 * 
	 * @var string
	 */
	public $NameSpace=null;
	/**
	 * The name of the webservice
	 * 
	 * @var string
	 */
	public $Name=null;
	/**
	 * The SOAP endpoint URI
	 * 
	 * @var string
	 */
	public $EndPoint=null;
	/**
	 * Set this to the WSDL URI, if it's different from your SOAP endpoint + "?WSDL"
	 * 
	 * @var string
	 */
	public $WsdlUri=null;
	/**
	 * Set this to the HTML documentation URI, if it's different from your SOAP endpoint
	 * 
	 * @var string
	 */
	public $DocUri=null;
	/**
	 * The options for the PHP SoapServer
	 * Note: "actor" and "uri" will be set at runtime
	 * 
	 * @var array
	 */
	public $SoapServerOptions=null;
	/**
	 * An array of file names to parse
	 * 
	 * @var string[]
	 */
	public $Files=Array();
	/**
	 * An array of complex types
	 * 
	 * @var PhpWsdlComplex[]
	 */
	public $Types=null;
	/**
	 * An array of method
	 * 
	 * @var PhpWsdlMethod[]
	 */
	public $Methods=null;
	/**
	 * Remove tabs and line breaks?
	 * Note: Unoptimized WSDL won't be cached
	 * 
	 * @var boolean
	 */
	public $Optimize=true;
	/**
	 * UTF-8 encoded WSDL from the last CreateWsdl method call
	 * 
	 * @var string
	 */
	public $WSDL=null;
	/**
	 * An array of basic types (these are just some of the XSD defined types 
	 * (see http://www.w3.org/TR/2001/PR-xmlschema-2-20010330/)
	 * 
	 * @var string[]
	 */
	public static $BasicTypes=Array(
		'anyURI',
		'base64Binary',
		'boolean',
		'byte',
		'date',
		'decimal',
		'double',
		'duration',
		'dateTime',
		'float',
		'gDay',
		'gMonthDay',
		'gYearMonth',
		'gYear',
		'hexBinary',
		'int',
		'integer',
		'long',
		'NOTATION',
		'number',
		'QName',
		'short',
		'string',
		'time'
	);
	/**
	 * Set this to a writeable folder to enable caching the WSDL in files
	 * 
	 * @var string
	 */
	public static $CacheFolder=null;
	/**
	 * The cache timeout in seconds (set to zero to disable caching, too)
	 * If you set the value to -1, the cache will never expire. Then you have 
	 * to use the PhpWsdl->TidyCache method for cleaning up the cache once 
	 * you've made changes to your webservice handler class.
	 * 
	 * @var int
	 */
	public static $CacheTime=3600;
	/**
	 * Parse documentation?
	 * 
	 * @var boolean
	 */
	public $ParseDocs=true;
	/**
	 * Include documentation tags in WSDL, if the optimizer is disabled?
	 * 
	 * @var boolean
	 */
	public $IncludeDocs=true;
	/**
	 * Force sending WSDL (has a higher priority than PhpWsdl->ForceNotOutputWsdl)
	 * 
	 * @var boolean
	 */
	public $ForceOutputWsdl=false;
	/**
	 * Force NOT sending WSDL (disable sending WSDL, has a higher priority than ?WSDL f.e.)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputWsdl=false;
	/**
	 * Force sending HTML (has a higher priority than PhpWsdl->ForceNotOutputHtml)
	 * 
	 * @var boolean
	 */
	public $ForceOutputHtml=false;
	/**
	 * Force NOT sending HTML (disable sending HTML)
	 * 
	 * @var boolean
	 */
	public $ForceNotOutputHtml=false;
	/**
	 * Regular expression parse a class name
	 * 
	 * @var string
	 */
	public $classRx='/^.*class\s+([^\s]+)\s*\{.*$/is';
	/**
	 * The HTML2PDF license key (see www.htmltopdf.de)
	 * 
	 * @var string
	 */
	public static $HTML2PDFLicenseKey=null;
	/**
	 * The URI to the HTML2PDF http API
	 * 
	 * @var string
	 */
	public static $HTML2PDFAPI='http://online.htmltopdf.de/';
	/**
	 * The HTML2PDF settings (only available when using a valid license key)
	 * 
	 * @var array
	 */
	public static $HTML2PDFSettings=Array();
	/**
	 * Saves if the sources have been parsed
	 * 
	 * @var boolean
	 */
	public $SourcesParsed=false;
	/**
	 * Saves if the configuration has already been determined
	 * 
	 * @var boolean
	 */
	public $ConfigurationDetermined=false;
	
	/**
	 * PhpWsdl constructor
	 * 
	 * @param string|boolean $nameSpace Namespace or NULL to let PhpWsdl determine it, or TRUE to run everything by determining all configuration -> quick mode (default: NULL)
	 * @param string|string[] $endPoint Endpoint URI or NULL to let PhpWsdl determine it - or, in quick mode, the webservice class filename(s) (default: NULL)
	 * @param string $cacheFolder The folder for caching WSDL or NULL to use the systems default (default: NULL)
	 * @param string|string[] $file Filename or array of filenames or NULL (default: NULL)
	 * @param string $name Webservice name or NULL to let PhpWsdl determine it (default: NULL)
	 * @param PhpWsdlMethod[] $methods Array of methods or NULL (default: NULL)
	 * @param PhpWsdlComplex[] $types Array of complex types or NULL (default: NULL)
	 * @param boolean $outputOnRequest Output WSDL on request? (default: FALSE)
	 * @param boolean|string|object|array $runServer Run SOAP server? (default: FALSE)
	 */
	public function PhpWsdl(
		$nameSpace=null,
		$endPoint=null,
		$cacheFolder=null,
		$file=null,
		$name=null,
		$methods=null,
		$types=null,
		$outputOnRequest=false,
		$runServer=false
		){
		// Quick mode
		$quickRun=false;
		if($nameSpace===true){
			$quickRun=true;
			$nameSpace=null;
			if(!is_null($endPoint)&&is_null($file)){
				$file=$endPoint;
				$endPoint=null;
			}
		}
		// SOAP server options
		$this->SoapServerOptions=Array(
			'soap_version'	=>	SOAP_1_1|SOAP_1_2,
			'encoding'		=>	'UTF-8',
			'compression'	=>	SOAP_COMPRESSION_ACCEPT|SOAP_COMPRESSION_GZIP|9
		);
		// Optimizer settings
		$this->Optimize=!isset($_GET['readable']);// Call with "?WSDL&readable" to get human readable WSDL
		// Cache settings
		if(!is_null($cacheFolder))
			PhpWsdl::$CacheFolder=$cacheFolder;
		// Namespace
		$this->NameSpace=(is_null($nameSpace))?'http://'.$_SERVER['SERVER_NAME'].str_replace(basename($_SERVER['SCRIPT_NAME']),'',$_SERVER['SCRIPT_NAME']):$nameSpace;
		// Webservice handler class name
		if(!is_null($name))
			$this->Name=$name;
		// Endpoint
		$this->EndPoint=((!is_null($endPoint)))?$endPoint:((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
		// Source files
		if(!is_null($file))
			$this->Files=(is_array($file))?$file:Array($file);
		// Methods
		$this->Methods=(!is_null($methods))?$methods:Array();
		// Types
		$this->Types=(!is_null($types))?$types:Array();
		// Constructor hook
		PhpWsdl::CallHook(
			'ConstructorHook',
			Array(
				'server'		=>	$this,
				'output'		=>	&$outputOnRequest,
				'run'			=>	&$runServer,
				'quickmode'		=>	&$quickRun
			)
		);
		// WSDL output
		if($outputOnRequest&&!$runServer)
			$this->OutputWsdlOnRequest();
		// Run the server
		if($quickRun||$runServer)
			$this->RunServer(null,(is_bool($runServer))?null:$runServer);
	}
	
	/**
	 * Determine if WSDL was requested by the client
	 * 
	 * @return boolean WSDL requested?
	 */
	public function IsWsdlRequested(){
		return $this->ForceOutputWsdl||((isset($_GET['wsdl'])||isset($_GET['WSDL']))&&!$this->ForceNotOutputWsdl);
	}
	
	/**
	 * Determine if HTML was requested by the client
	 * 
	 * @return boolean HTML requested?
	 */
	public function IsHtmlRequested(){
		return $this->ForceOutputHtml||(strlen(file_get_contents('php://input'))<1&&!$this->ForceNotOutputHtml);
	}
	
	/**
	 * Disble caching
	 * 
	 * @param bool $allCaching Do not only set the timeout to zero? (default: TRUE)
	 */
	public static function DisableCache($allCaching=true){
		if($allCaching)
			self::$CacheFolder=null;
		if(!$allCaching)
			self::$CacheTime=0;
	}
	
	/**
	 * Enable caching
	 * 
	 * @param string $folder The cache folder or NULL to use a system temporary directory (default: NULL)
	 * @param int $timeout The caching timeout in seconds or NULL to use the previous value or the default (3600) (default: NULL)
	 */
	public static function EnableCache($folder=null,$timeout=null){
		if(is_null($folder))
			$folder=sys_get_temp_dir();
		if(is_null($timeout))
			$timeout=(self::$CacheTime>0)?self::$CacheTime:3600;
		self::$CacheFolder=$folder;
		self::$CacheTime=$timeout;
	}
	
	/**
	 * Determine the configuration
	 * 
	 * @return boolean Succeed?
	 */
	public function DetermineConfiguration(){
		$this->ParseSource();
		$mLen=sizeof($this->Methods);
		$tLen=sizeof($this->Types);
		$fLen=sizeof($this->Files);
		if($this->ConfigurationDetermined)
			return ($mLen>0||$tLen>0)&&!is_null($this->Name);
		$this->ConfigurationDetermined=true;
		// No methods or types? Try to parse them from the current script
		if($mLen<1&&$tLen<1){
			$this->Files[]=$_SERVER['SCRIPT_FILENAME'];
			$fLen++;
			$this->SourcesParsed=false;
			$this->ParseSource();
			$mLen=sizeof($this->Methods);
			$tLen=sizeof($this->Types);
			// Try to load from class.webservice.php
			if($mLen<1&&$tLen<1&&file_exists('class.webservice.php')){
				$this->Files[$fLen-1]='class.webservice.php';
				$this->SourcesParsed=false;
				$this->ParseSource();
				$mLen=sizeof($this->Methods);
				$tLen=sizeof($this->Types);
				if($mLen<1&&$tLen<1){
					unset($this->Files[$fLen-1]);
					$fLen--;
					return false;
				}
			}else if($mLen<1&&$tLen<1){
				unset($this->Files[$fLen-1]);
				$fLen--;
				return false;
			}
		}
		// No class name? Try to parse one from the current files
		if(is_null($this->Name)){
			$class=null;
			$i=-1;
			while(++$i<$fLen){
				$temp=file_get_contents($this->Files[$i]);
				if(!preg_match($this->classRx,$temp))
					continue;
				$class=preg_replace($this->classRx,"$1",$temp);
				break;
			}
			$this->Name=$class;
			return !is_null($class);
		}
		return true;
	}
	
	/**
	 * Create the WSDL
	 * 
	 * @param boolean $reCreate Don't use the cached WSDL? (default: FALSE)
	 * @param boolean $optimize If TRUE, override the Optimizer property and force optimizing (default: FALSE)
	 * @return string The UTF-8 encoded WSDL as string
	 */
	public function CreateWsdl($reCreate=false,$optimizer=false){
		// Ask the cache
		if(!$reCreate&&($this->Optimize||$optimizer)){
			$wsdl=$this->GetWsdlFromCache();
			if(!is_null($wsdl))
				return $wsdl;
		}
		// Prepare the WSDL generator
		if(!$this->DetermineConfiguration()){
			if(is_null($this->Name))
				throw(new Exception('Could not determine webservice handler class name'));
			$mLen=sizeof($this->Methods);
			$tLen=sizeof($this->Types);
			if($mLen<1&&$tLen<1)
				throw(new Exception('No methods and no complex types are available'));
		}
		$res=Array();
		// Create the XML Header
		PhpWsdl::CallHook(
			'CreateWsdlHeaderHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Create types
		PhpWsdl::CallHook(
			'CreateWsdlTypeSchemaHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Create messages
		PhpWsdl::CallHook(
			'CreateWsdlMessagesHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Create port types
		PhpWsdl::CallHook(
			'CreateWsdlPortsHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Create bindings
		PhpWsdl::CallHook(
			'CreateWsdlBindingsHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Create the service
		PhpWsdl::CallHook(
			'CreateWsdlServiceHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Finish the WSDL XML string
		PhpWsdl::CallHook(
			'CreateWsdlFooterHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Run the optimizer
		PhpWsdl::CallHook(
			'CreateWsdlOptimizeHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'optimizer'		=>	&$optimizer
			)
		);
		// Fill the cache
		if($optimizer||$this->Optimize)
			$this->WriteWsdlToCache($res);
		return $this->WSDL;
	}

	/**
	 * Create header
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlHeader($data){
		$res=&$data['res'];
		$server=$data['server'];
		$res[]='<?xml version="1.0" encoding="UTF-8"?>';
		$res[]='<wsdl:definitions xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:tns="'.$server->NameSpace.'" xmlns:s="http://www.w3.org/2001/XMLSchema" targetNamespace="'.$server->NameSpace.'" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/">';
	}
	
	/**
	 * Create type schema
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlTypeSchema($data){
		$res=&$data['res'];
		$server=$data['server'];
		$tLen=sizeof($server->Types);
		if($tLen>0){
			$res[]="\t".'<wsdl:types>';
			$res[]="\t\t".'<s:schema elementFormDefault="qualified" targetNamespace="'.$server->NameSpace.'">';
			$i=-1;
			while(++$i<$tLen)
				$res[]=$server->Types[$i]->CreateType($server);
			PhpWsdl::CallHook(
				'CreateWsdlTypes',
				$data
			);
			$res[]="\t\t".'</s:schema>';
			$res[]="\t".'</wsdl:types>';
		}
		return false;
	}
	
	/**
	 * Create messages
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlMessages($data){
		$res=&$data['res'];
		$server=$data['server'];
		$i=-1;
		$mLen=sizeof($server->Methods);
		while(++$i<$mLen)
			$res[]=$server->Methods[$i]->CreateMessages($server);
		return false;
	}
	
	/**
	 * Create port types
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlPorts($data){
		$res=&$data['res'];
		$server=$data['server'];
		$res[]="\t".'<wsdl:portType name="'.$server->Name.'Soap">';
		$i=-1;
		$mLen=sizeof($server->Methods);
		while(++$i<$mLen)
			$res[]=$server->Methods[$i]->CreatePortType($server);
		$res[]="\t".'</wsdl:portType>';
		return false;
	}
	
	/**
	 * Create bindings
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlBindings($data){
		$res=&$data['res'];
		$server=$data['server'];
		$res[]="\t".'<wsdl:binding name="'.$server->Name.'Soap" type="tns:'.$server->Name.'Soap">';
		$res[]="\t\t".'<soap:binding transport="http://schemas.xmlsoap.org/soap/http" style="rpc" />';
		$i=-1;
		$mLen=sizeof($server->Methods);
		while(++$i<$mLen)
			$res[]=$server->Methods[$i]->CreateBinding($server);
		$res[]="\t".'</wsdl:binding>';
		return false;
	}
	
	/**
	 * Create service port
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlService($data){
		$res=&$data['res'];
		$server=$data['server'];
		$res[]="\t".'<wsdl:service name="'.$server->Name.'">';
		$res[]="\t\t".'<wsdl:port name="'.$server->Name.'Soap" binding="tns:'.$server->Name.'Soap">';
		$res[]="\t\t\t".'<soap:address location="'.$server->EndPoint.'" />';
		$res[]="\t\t".'</wsdl:port>';
		$res[]="\t".'</wsdl:service>';
		return false;
	}
	
	/**
	 * Create footer
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlFooter($data){
		$res=&$data['res'];
		$res[]='</wsdl:definitions>';
		return false;
	}

	/**
	 * Optimize WSDL
	 * 
	 * @param array $data Data array
	 * @return boolean Response
	 */
	public static function CreateWsdlOptimize($data){
		$res=&$data['res'];
		$server=$data['server'];
		$optimizer=&$data['optimizer'];
		$res=implode("\n",$res);
		if($optimizer||$server->Optimize)
			$res=preg_replace('/[\n|\t]/','',$res);
		$res=utf8_encode($res);
		$server->WSDL=$res;
		return false;
	}
	
	/**
	 * Parse source files for WSDL definitions in comments
	 * 
	 * @param boolean $init Empty the Methods and the Types properties? (default: FALSE)
	 */
	public function ParseSource($init=false){
		if($init){
			$this->Methods=Array();
			$this->Types=Array();
			$this->SourcesParsed=false;
		}
		if($this->SourcesParsed)
			return;
		$this->SourcesParsed=true;
		$fLen=sizeof($this->Files);
		if($fLen<1)
			return;
		// Load the source
		$src=Array();
		$i=-1;
		while(++$i<$fLen)
			$src[]=trim(file_get_contents($this->Files[$i]));
		// Parse the source
		$parser=new PhpWsdlParser($this);
		$parser->Parse(implode("\n",$src));
	}
	
	/**
	 * Output the WSDL to the client
	 */
	public function OutputWsdl(){
		if(!PhpWsdl::CallHook(
				'OutputWsdlHook',
				Array(
					'server'		=>	$this
				)
			)
		)
			return;
		header('Content-Type: text/xml; charset=UTF-8',true);
		echo $this->CreateWsdl();
	}

	/**
	 * Output the WSDL to the client, if requested
	 * 
	 * @param boolean $andExit Exit after sending WSDL? (default: TRUE)
	 * @return boolean Has the WSDL been sent to the client?
	 */
	public function OutputWsdlOnRequest($andExit=true){
		if(!$this->IsWsdlRequested())
			return false;
		$this->OutputWsdl();
		if($andExit)
			exit;
		return true;
	}
	
	/**
	 * Output the HTML to the client
	 */
	public function OutputHtml(){
		if(sizeof($this->Methods)<1)
			$this->CreateWsdl();
		if(!PhpWsdl::CallHook(
				'OutputHtmlHook',
				Array(
					$this
				)
			)
		)
			return;
		// Header
		header('Content-Type: text/html; charset=UTF-8',true);
		$res=Array();
		$res[]='<html>';
		$res[]='<head>';
		$res[]='<title>'.$this->Name.' interface description</title>';
		$res[]='<style type="text/css" media="all">';
		$res[]='body{font-family:Calibri,Arial;background-color:#fefefe;}';
		$res[]='.pre{font-family:Courier;}';
		$res[]='.normal{font-family:Calibri,Arial;}';
		$res[]='.bold{font-weight:bold;}';
		$res[]='h1,h2,h3{font-family:Verdana,Times;}';
		$res[]='h1{border-bottom:1px solid gray;}';
		$res[]='h2{border-bottom:1px solid silver;}';
		$res[]='h3{border-bottom:1px dashed silver;}';
		$res[]='a{text-decoration:none;}';
		$res[]='a:hover{text-decoration:underline;}';
		$res[]='.blue{color:#3400FF;}';
		$res[]='.lightBlue{color:#5491AF;}';
		if(!is_null(PhpWsdl::$HTML2PDFLicenseKey)&&PhpWsdl::$HTML2PDFSettings['attachments']=='1')
			$res[]='.print{display:none;}';
		PhpWsdl::CallHook(
			'CreateHtmlCssHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res
			)
		);
		$res[]='</style>';
		$res[]='<style type="text/css" media="print">';
		$res[]='.noprint{display:none;}';
		if(!is_null(PhpWsdl::$HTML2PDFLicenseKey)&&PhpWsdl::$HTML2PDFSettings['attachments']=='1')
			$res[]='.print{display:block;}';
		PhpWsdl::CallHook(
			'CreateHtmlCssPrintHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res
			)
		);
		$res[]='</style>';
		$res[]='</head>';
		$res[]='<body>';
		$types=$this->SortObjectsByName($this->Types);
		$methods=$this->SortObjectsByName($this->Methods);
		// General information
		PhpWsdl::CallHook(
			'CreateHtmlGeneralHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'methods'		=>	&$methods,
				'types'			=>	&$types
			)
		);
		// Index
		PhpWsdl::CallHook(
			'CreateHtmlIndexHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'methods'		=>	&$methods,
				'types'			=>	&$types
			)
		);
		// Complex types
		PhpWsdl::CallHook(
			'CreateHtmlComplexTypesHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'methods'		=>	&$methods,
				'types'			=>	&$types
			)
		);
		// Methods
		PhpWsdl::CallHook(
			'CreateHtmlMethodsHook',
			Array(
				'server'		=>	$this,
				'res'			=>	&$res,
				'methods'		=>	&$methods,
				'types'			=>	&$types
			)
		);
		// HTML2PDF link
		$param=Array(
			'plain'			=>	'1',
			'filename'		=>	$this->Name.'-SOAP.pdf',
			'print'			=>	'1'
		);
		if(!is_null(PhpWsdl::$HTML2PDFLicenseKey)){
			// Use advanced HTML2PDF API
			$temp=array_merge(PhpWsdl::$HTML2PDFSettings,Array(
				'url'			=>	(is_null($this->DocUri))?$this->EndPoint:$this->DocUri
			));
			if($temp['attachments']=='1'){
				$temp['attachment_1']=$this->Name.'.wsdl:'.((is_null($this->WsdlUri))?$this->EndPoint.'?WSDL':$this->WsdlUri);
				if($this->ParseDocs&&$this->IncludeDocs)
					$temp['attachment_2']=$this->Name.'-doc.wsdl:'.((is_null($this->WsdlUri))?$this->EndPoint.'?WSDL':$this->WsdlUri).'&readable';
			}
			$options=Array();
			$keys=array_keys($temp);
			$i=-1;
			$len=sizeof($keys);
			while(++$i<$len)
				$options[]=$keys[$i].'='.$temp[$keys[$i]];
			$options='$'.base64_encode(implode("\n",$options));
			$license=sha1(PhpWsdl::$HTML2PDFLicenseKey.PhpWsdl::$HTML2PDFLicenseKey).'-'.sha1($options.PhpWsdl::$HTML2PDFLicenseKey);
			$param['url']=$options;
			$param['license']=$license;
		}
		$temp=$param;
		$param=Array();
		$keys=array_keys($temp);
		$i=-1;
		$len=sizeof($keys);
		while(++$i<$len)
			$param[]=urlencode($keys[$i]).'='.urlencode($temp[$keys[$i]]);
		$pdfLink=PhpWsdl::$HTML2PDFAPI.'?'.implode('&',$param);
		// Footer
		$res[]='<hr>';
		$res[]='<p><small>Powered by <a href="http://code.google.com/p/php-wsdl-creator/">PhpWsdl</a><span class="noprint"> - PDF download: <a href="'.$pdfLink.'">Download this page as PDF</a></span></small></p>';
		$res[]='</body>';
		$res[]='</html>';
		// Clean up the generated HTML and send it
		$res=implode("\n",$res);
		$res=str_replace('<br />','<br>',$res);// nl2br will produce XHTML (and nothing if the second parameter is FALSE!?)
		if(!PhpWsdl::CallHook(
				'SendHtmlHook',
				Array(
					'server'		=>	$this,
					'res'			=>	&$res
				)
			)
		)
			return;
		echo utf8_encode($res);
	}
	
	/**
	 * Create general information
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlGeneral($data){
		$res=&$data['res'];
		$server=$data['server'];
		$res[]='<h1>'.$server->Name.' SOAP WebService interface description</h1>';
		$res[]='<p>Endpoint URI: <span class="pre">'.$server->EndPoint.'</span></p>';
		$res[]='<p>WSDL URI: <span class="pre"><a href="'.$server->EndPoint.'?WSDL&readable">'.$server->EndPoint.'?WSDL</a></span></p>';
		if(!is_null(self::$HTML2PDFLicenseKey)&&PhpWsdl::$HTML2PDFSettings['attachments']=='1')
			$res[]='<p class="print">The WSDL files are attached to this PDF documentation: One with inline-documentation and one without.</p>';
		return false;
	}
	
	/**
	 * Create table of contents
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlIndex($data){
		$res=&$data['res'];
		$types=&$data['types'];
		$methods=&$data['methods'];
		$tLen=sizeof($types);
		$mLen=sizeof($methods);
		if(!is_null(PhpWsdl::$HTML2PDFLicenseKey))
			$res[]='<div class="noprint">';
		$res[]='<h2>Index</h2>';
		if($tLen>0){
			$res[]='<p>Complex types:</p>';
			$i=-1;
			$res[]='<ul>';
			while(++$i<$tLen)
				$res[]='<li><a href="#'.$types[$i]->Name.'"><span class="pre">'.$types[$i]->Name.'</span></a></li>';
			$res[]='</ul>';
		}
		if($mLen>0){
			$res[]='<p>Public methods:</p>';
			$i=-1;
			$res[]='<ul>';
			while(++$i<$mLen)
				$res[]='<li><a href="#'.$methods[$i]->Name.'"><span class="pre">'.$methods[$i]->Name.'</span></a></li>';
			$res[]='</ul>';
		}
		if(!is_null(PhpWsdl::$HTML2PDFLicenseKey))
			$res[]='</div>';
		return false;
	}
	
	/**
	 * Create method list
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlMethods($data){
		$res=&$data['res'];
		$methods=&$data['methods'];
		$mLen=sizeof($methods);
		if($mLen>0){
			$res[]='<h2>Public methods</h2>';
			$i=-1;
			while(++$i<$mLen)
				PhpWsdl::CallHook(
					'CreateHtmlMethodHook',
					array_merge(
						$data,
						Array(
							'method'		=>	$methods[$i]
						)
					)
				);
		}
		return false;
	}
	
	/**
	 * Create method
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlMethod($data){
		$res=&$data['res'];
		$m=&$data['method'];
		$res[]='<h3>'.$m->Name.'</h3>';
		$res[]='<a name="'.$m->Name.'"></a>';
		$res[]='<p class="pre">';
		$o=sizeof($res)-1;
		if(!is_null($m->Return)){
			$type=$m->Return->Type;
			if(in_array($type,PhpWsdl::$BasicTypes)){
				$res[$o].='<span class="blue">'.$type.'</span>';
			}else{
				$res[$o].='<a href="#'.$type.'"><span class="lightBlue">'.$type.'</span></a>';
			}
		}else{
			$res[$o].='void';
		}
		$res[$o].=' <span class="bold">'.$m->Name.'</span> (';
		$pLen=sizeof($m->Param);
		$spacer='';
		if($pLen>1){
			$res[$o].='<br>';
			$spacer='&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$hasDocs=false;
		if($pLen>0){
			$j=-1;
			while(++$j<$pLen){
				$p=$m->Param[$j];
				if(in_array($p->Type,PhpWsdl::$BasicTypes)){
					$res[]=$spacer.'<span class="blue">'.$p->Type.'</span> <span class="bold">'.$p->Name.'</span>';
				}else{
					$res[]=$spacer.'<a href="#'.$p->Type.'"><span class="lightBlue">'.$p->Type.'</span></a> <span class="bold">'.$p->Name.'</span>';
				}
				$o=sizeof($res)-1;
				if($j<$pLen-1)
					$res[$o].=', ';
				if($pLen>1)
					$res[$o].='<br>';
				if(!$hasDocs)
					if(!is_null($p->Docs))
						$hasDocs=true;
			}
		}
		$res[].=')</p>';
		// Method documentation
		if(!is_null($m->Docs))
			$res[]='<p>'.nl2br(htmlentities($m->Docs)).'</p>';
		// Parameters documentation
		if($hasDocs){
			$res[]='<ul>';
			$j=-1;
			while(++$j<$pLen)
				PhpWsdl::CallHook(
					'CreateHtmlParamHook',
					array_merge(
						$data,
						Array(
							'param'			=>	$m->Param[$j]
						)
					)
				);
			$res[]='</ul>';
		}
		// Return value documentation
		if(!is_null($m->Return))
			if(!is_null($m->Return->Docs))
				PhpWsdl::CallHook(
					'CreateHtmlReturnHook',
					$data
				);
		return false;
	}
	
	/**
	 * Create return value
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlReturn($data){
		$res=&$data['res'];
		$m=&$data['method'];
		$res[]='<p>Return value <span class="pre">';
		$o=sizeof($res)-1;
		$type=$m->Return->Type;
		if(in_array($type,PhpWsdl::$BasicTypes)){
			$res[$o].='<span class="blue">'.$type.'</span>';
		}else{
			$res[$o].='<a href="#'.$type.'"><span class="lightBlue">'.$type.'</span></a>';
		}
		$res[$o].='</span>: '.nl2br(htmlentities($m->Return->Docs)).'</p>';
		return false;
	}
	
	/**
	 * Create parameter
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlParam($data){
		$res=&$data['res'];
		$p=&$data['param'];
		if(is_null($p->Docs))
			return true;
		if(in_array($p->Type,PhpWsdl::$BasicTypes)){
			$res[]='<li class="pre"><span class="blue">'.$p->Type.'</span> <span class="bold">'.$p->Name.'</span>';
		}else{
			$res[]='<li class="pre"><a href="#'.$p->Type.'"><span class="lightBlue">'.$p->Type.'</span></a> <span class="bold">'.$p->Name.'</span>';
		}
		$res[sizeof($res)-1].='<br><span class="normal">'.nl2br(htmlentities($p->Docs)).'</span></li>';
		return false;
	}
	
	/**
	 * Create complex types list
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlComplexTypes($data){
		$res=&$data['res'];
		$types=&$data['types'];
		$server=$data['server'];
		$tLen=sizeof($server->Types);
		if($tLen>0){
			$res[]='<h2>Complex types</h2>';
			$i=-1;
			while(++$i<$tLen)
				PhpWsdl::CallHook(
					'CreateHtmlComplexTypeHook',
					array_merge(
						$data,
						Array(
							'type'			=>	$types[$i]
						)
					)
				);
		}
		return false;
	}
	
	/**
	 * Create complex type
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlComplexType($data){
		$res=&$data['res'];
		$t=&$data['type'];
		$res[]='<h3>'.$t->Name.'</h3>';
		$res[]='<a name="'.$t->Name.'"></a>';
		$eLen=sizeof($t->Elements);
		if($t->IsArray){
			// Array type
			$res[]='<p>This is an array type of <span class="pre">';
			$o=sizeof($res)-1;
			$type=substr($t->Name,0,strlen($t->Name)-5);
			if(in_array($type,PhpWsdl::$BasicTypes)){
				$res[$o].='<span class="blue">'.$type.'</span>';
			}else{
				$res[$o].='<a href="#'.$type.'"><span class="lightBlue">'.$type.'</span></a>';
			}
			$res[$o].='</span>.</p>';
			if(!is_null($t->Docs))
				$res[]='<p>'.nl2br(htmlentities($t->Docs)).'</p>';
		}else if($eLen>0){
			// Complex type with elements
			if(!is_null($t->Docs))
				$res[]='<p>'.nl2br(htmlentities($t->Docs)).'</p>';
			$res[]='<ul class="pre">';
			$j=-1;
			while(++$j<$eLen)
				PhpWsdl::CallHook(
					'CreateHtmlElementHook',
					array_merge(
						$data,
						Array(
							'element'		=>	$t->Elements[$j]
						)
					)
				);
			$res[]='</ul>';
		}else{
			// Complex type without elements
			$res[]='<p>This type has no elements.</p>';
		}
		return false;
	}
	
	/**
	 * Create element
	 * 
	 * @param array $data The information object
	 * @return boolean Response
	 */
	public static function CreateHtmlElement($data){
		$res=&$data['res'];
		$e=&$data['element'];
		if(in_array($e->Type,PhpWsdl::$BasicTypes)){
			$res[]='<li><span class="blue">'.$e->Type.'</span> <span class="bold">'.$e->Name.'</span>';
		}else{
			$res[]='<li><a href="#'.$e->Type.'"><span class="lightBlue">'.$e->Type.'</span></a> <span class="bold">'.$e->Name.'</span>';
		}
		$o=sizeof($res)-1;
		$temp=Array(
			'nillable = <span class="blue">'.(($e->NillAble)?'true':'false').'</span>',
			'minoccurs = <span class="blue">'.$e->MinOccurs.'</span>',
			'maxoccurs = <span class="blue">'.$e->MaxOccurs.'</span>',
		);
		$res[$o].=' ('.implode(', ',$temp).')';
		if(!is_null($e->Docs))
			$res[$o].='<br><span class="normal">'.nl2br(htmlentities($e->Docs)).'</span>';
		$res[$o].='</li>';
		return false;
	}
	
	/**
	 * Sort objects by name
	 * 
	 * @param PhpWsdlComplex[]|PhpWsdlMethod[] $obj
	 * @return PhpWsdlComplex[]|PhpWsdlMethod[] Sorted objects
	 */
	public function SortObjectsByName($obj){
		$temp=Array();
		$i=-1;
		$len=sizeof($obj);
		while(++$i<$len)
			$temp[$obj[$i]->Name]=$obj[$i];
		$keys=array_keys($temp);
		sort($keys);
		$res=Array();
		$i=-1;
		while(++$i<$len)
			$res[]=$temp[$keys[$i]];
		return $res;
	}
	
	/**
	 * Output the HTML to the client, if requested
	 * 
	 * @param boolean $andExit Exit after sending HTML? (default: TRUE)
	 * @return boolean Has the HTML been sent to the client?
	 */
	public function OutputHtmlOnRequest($andExit=true){
		if(!$this->IsHtmlRequested())
			return false;
		$this->OutputHtml();
		if($andExit)
			exit;
		return true;
	}
	
	/**
	 * Run the PHP SoapServer
	 * 
	 * @param string $wsdlFile The WSDL file name or NULL to let PhpWsdl decide (default: NULL)
	 * @param string|object|array $class The class name to serve, the classname and class as array or NULL (default: NULL)
	 * @param boolean $andExit Exit after running the server? (default: TRUE)
	 * @return boolean Did the server run?
	 */
	public function RunServer($wsdlFile=null,$class=null,$andExit=true){
		// WSDL requested?
		if($this->OutputWsdlOnRequest($andExit))
			return false;
		// HTML requested?
		if($this->OutputHtmlOnRequest($andExit))
			return false;
		// Load the proxy
		$useProxy=false;
		if(is_array($class)){
			PhpWsdl::$ProxyObject=$class[1];
			PhpWsdl::$ProxyServer=$this;
			$class=$class[0];
			$useProxy=true;
		}
		// Set the handler class name
		if(is_null($class)){
			if(is_null($this->Name))
				if(!$this->DetermineConfiguration())
					throw(new Exception('Could not find the webservice handler class name'));
			$class=$this->Name;
		}else if(is_string($class)){
			$this->Name=$class;
		}
		// Load WSDL
		if(!$useProxy){
			if(is_null($wsdlFile))
				$wsdlFile=$this->GetCacheFileName();
			$this->CreateWsdl(false,true);
		}
		if(!$useProxy&&!is_null($wsdlFile))
			if(!file_exists($wsdlFile))
				$wsdlFile=null;
		// Load the files, if the webservice handler class doesn't exist
		if(!class_exists($class)){
			$i=-1;
			$len=sizeof($this->Files);
			while(++$i<$len)
				require_once($this->Files[$i]);
			if(!class_exists($class))
				throw(new Exception('Could not autoload class "'.$class.'"'));
		}
		// Prepare the SOAP server
		$server=null;
		if(PhpWsdl::CallHook(
				'PrepareServerHook',
				Array(
					$this,
					&$server,// The empty PHP SoapServer object
					&$wsdlFile,
					&$class,
					&$useProxy,// Is the proxy being used?
					&$andExit
				)
			)
		){
			$server=new SoapServer(
				($useProxy)?null:$wsdlFile,
				array_merge($this->SoapServerOptions,Array(
					'actor'			=>	$this->EndPoint,
					'uri'			=>	$this->NameSpace,
				))
			);
			$server->SetClass(($useProxy)?'PhpWsdlProxy':$class);
		}
		// Run the SOAP server
		if(PhpWsdl::CallHook(
				'RunServerHook',
				Array(
					$this,
					&$server,// The PHP SoapServer object
					&$wsdlFile,
					&$class,
					&$useProxy,// Is the proxy being used?
					&$andExit
				)
			)
		){
			$server->handle();
			if($andExit)
				exit;
		}
		return true;
	}
	
	/**
	 * Find a method
	 * 
	 * @param string $name The method name
	 * @return PhpWsdlMethod The method object or NULL
	 */
	public function GetMethod($name){
		$i=-1;
		$len=sizeof($this->Methods);
		while(++$i<$len)
			if($this->Methods[$i]->Name==$name)
				return $this->Methods[$i];
		return null;
	}
	
	/**
	 * Find a complex type
	 * 
	 * @param string $name The type name
	 * @return PhpWsdlComplex The type object or NULL
	 */
	public function GetType($name){
		$i=-1;
		$len=sizeof($this->Types);
		while(++$i<$len)
			if($this->Types[$i]->Name==$name)
				return $this->Types[$i];
		return null;
	}
	
	/**
	 * Get the cache filename
	 * 
	 * @param string $endpoint The endpoint URI or NULL to use the PhpWsdl->EndPoint property (default: NULL)
	 * @return string The cache filename or NULL, if caching is disabled
	 */
	public function GetCacheFileName($endpoint=null){
		return (is_null(PhpWsdl::$CacheFolder))?null:PhpWsdl::$CacheFolder.'/'.sha1((is_null($endpoint))?$this->EndPoint:$endpoint).'.wsdl';
	}

	/**
	 * Determine if the cache file exists
	 * 
	 * @param string $file The WSDL cache filename or NULL to use the default (default: NULL)
	 * @return boolean Are the cache files present?
	 */
	public function CacheFileExists($file=null){
		if(is_null($file))
			$file=$this->GetCacheFileName();
		return file_exists($file)&&file_exists($file.'.cache')&&file_exists($file.'.obj');
	}
	
	/**
	 * Determine if the existing cache files are still valid
	 * 
	 * @param string $file The WSDL cache filename or NULL to use the default (default: NULL)
	 * @return boolean Valid?
	 */
	public function IsCacheValid($file=null){
		if(is_null($file))
			$file=$this->GetCacheFileName();
		if(!$this->CacheFileExists($file))
			return false;
		return PhpWsdl::$CacheTime<0||time()-file_get_contents($file.'.cache')<=PhpWsdl::$CacheTime;
	}
	
	/**
	 * Get the WSDL from the cache
	 * 
	 * @param string $file The WSDL cache filename or NULL to use the default (default: NULL)
	 * @param boolean $force Force this even if the cache is timed out? (default: FALSE)
	 * @return string The cached WSDL
	 */
	public function GetWsdlFromCache($file=null,$force=false){
		if(!is_null($this->WSDL))
			return $this->WSDL;
		if(is_null($file))
			$file=$this->GetCacheFileName();
		if(!$force)
			if(!$this->IsCacheValid($file))
				return null;
		if(!$this->CacheFileExists($file))
			return null;
		$this->WSDL=file_get_contents($file);
		$data=unserialize(file_get_contents($file.'.obj'));
		$this->Methods=$data['methods'];
		$this->Types=$data['types'];
		$this->Files=$data['files'];
		$this->Name=$data['name'];
		return $this->WSDL;
	}
	
	/**
	 * Write WSDL to cache
	 * 
	 * @param string $wsdl The UTF-8 encoded WSDL string (default: NULL)
	 * @param string $endpoint The SOAP endpoint or NULL to use the default (default: NULL)
	 * @param string $file The target filename or NULL to use the default (default: NULL)
	 * @return boolean Succeed?
	 */
	public function WriteWsdlToCache($wsdl=null,$endpoint=null,$file=null){
		if(is_null($endpoint))
			$endpoint=$this->EndPoint;
		if($endpoint==$this->EndPoint&&!is_null($wsdl))
			$this->WSDL=$wsdl;
		if(is_null($wsdl)){
			if(is_null($this->WSDL))
				return false;// WSDL not defined
			$wsdl=$this->WSDL;
		}
		if(is_null($file))
			$file=$this->GetCacheFileName($endpoint);
		if(is_null($file))
			return false;// No cache file
		$temp=substr($file,0,1);
		if($temp!='/'&&$temp!='.'){
			if(is_null(PhpWsdl::$CacheFolder))
				return false;// No cache folder
			$file=PhpWsdl::$CacheFolder.'/'.$file;
		}
		if($this->IsCacheValid($file))
			return true;// Existing cache is still valid
		if(file_put_contents($file,$wsdl)===false)
			return false;// Error writing to cache
		if(file_put_contents($file.'.cache',time())===false)
			return false;// Error writing to cache
		$data=Array(
			'methods'		=>	$this->Methods,
			'types'			=>	$this->Types,
			'files'			=>	$this->Files,
			'name'			=>	$this->Name
		);
		return file_put_contents($file.'.obj',serialize($data))!==false;
	}
	
	/**
	 * Determine if the cache is different from the current version of your webservice handler class.
	 * 
	 * @return boolean Differences detected?
	 */
	public function IsCacheDifferent(){
		// Load the cache
		$temp=new PhpWsdl(null,$this->EndPoint);
		$temp->GetWsdlFromCache();
		if(is_null($temp->WSDL))
			return true;// Not cached yet
		// Initialize this instance
		$this->DetermineConfiguration();
		$this->ParseSource();
		// Compare the cache with this instance
		return serialize(
				Array(
					$this->Methods,
					$this->Types
				)
			)!=serialize(
				Array(
					$temp->Methods,
					$temp->Types
				)
			);
	}
	
	/**
	 * Delete cache files from the cache folder
	 * 
	 * @param boolean $mineOnly Only delete the cache files for this definition? (default: FALSE)
	 * @return string[] The deleted filenames
	 */
	public function TidyCacheFolder($mineOnly=false){
		if(is_null(PhpWsdl::$CacheFolder))
			return Array();
		$deleted=Array();
		if($mineOnly){
			$file=$this->GetCacheFileName();
			if(file_exists($file))
				if(unlink($file))
					$deleted[]=$file;
			if(file_exists($file.'.cache'))
				if(unlink($file.'.cache'))
					$deleted[]=$file.'.cache';
			if(file_exists($file.'.obj'))
				if(unlink($file.'.obj'))
					$deleted[]=$file.'.obj';
		}else{
			$files=glob(PhpWsdl::$CacheFolder.'/*.wsd*');
			if($files!==false){
				$i=-1;
				$len=sizeof($files);
				while(++$i<$len){
					if(!preg_match('/\.wsdl(\.cache|\.obj)?$/',$files[$i]))
						continue;
					$file=PhpWsdl::$CacheFolder.'/'.$files[$i];
					if(unlink($files[$i]))
						$deleted[]=$files[$i];
				}
			}
		}
		return $deleted;
	}
	
	/**
	 * Translate a type name for WSDL
	 * 
	 * @param string $type The type name
	 * @return string The translates type name
	 */
	public static function TranslateType($type){
		return (in_array($type,PhpWsdl::$BasicTypes))?'s:'.$type:'tns:'.$type;
	}
	
	/**
	 * Call a hook function
	 * 
	 * @param string $name The hook name
	 * @param mixed $data The parameter (default: NULL)
	 * @return boolean Response
	 */
	public static function CallHook($name,$data=null){
		if(!isset(self::$Config['extensions'][$name]))
			return true;
		$keys=array_keys(self::$Config['extensions'][$name]);
		$i=-1;
		$len=sizeof($keys);
		while(++$i<$len)
			if(!call_user_func(self::$Config['extensions'][$name][$keys[$i]],$data))
				return false;
		return true;
	}
	
	/**
	 * Register a hook
	 * 
	 * @param string $jook The hook name
	 * @param string $name The call name
	 * @param mixed $data The hook call data
	 */
	public static function RegisterHook($hook,$name,$data){
		if(!isset(self::$Config['extensions'][$hook]))
			self::$Config['extensions'][$hook]=Array();
		self::$Config['extensions'][$hook][$name]=$data;
	}
	
	/**
	 * Unregister a hook
	 * 
	 * @param string $hook The hook name
	 * @param string $name The call name or NULL to unregister the whole hook
	 */
	public static function UnregisterHook($hook,$name=null){
		if(!isset(self::$Config['extensions'][$hook]))
			return;
		if(!is_null($name)){
			if(!isset(self::$Config['extensions'][$hook][$name]))
				return;
		}else{
			unset(self::$Config['extensions'][$hook]);
			return;
		}
		unset(self::$Config['extensions'][$hook][$name]);
	}

	/**
	 * Initialize PhpWsdl
	 */
	public static function Init(){
		// Configuration
		PhpWsdl::$HTML2PDFSettings=Array(
			'attachments'	=>	'1',
			'outline'		=>	'1'
		);
		self::$CacheFolder=sys_get_temp_dir();
		self::$Config['extensions']=Array();
		// Parser hooks
		self::RegisterHook('InterpretKeywordparamHook','internal','PhpWsdlParam::InterpretParam');
		self::RegisterHook('InterpretKeywordreturnHook','internal','PhpWsdlParam::InterpretReturn');
		self::RegisterHook('InterpretKeywordpw_elementHook','internal','PhpWsdlElement::InterpretElement');
		self::RegisterHook('InterpretKeywordpw_complexHook','internal','PhpWsdlComplex::InterpretComplex');
		self::RegisterHook('InterpretKeywordpw_omitfncHook','internal','PhpWsdlMethod::InterpretOmit');
		self::RegisterHook('InterpretKeywordpw_setHook','internal','PhpWsdlMethod::InterpretSetting');
		self::RegisterHook('CreateObjectHook','internalmethod','PhpWsdlMethod::CreateMethodObject');
		self::RegisterHook('CreateObjectHook','internalcomplex','PhpWsdlComplex::CreateComplexTypeObject');
		// WSDL hooks
		self::RegisterHook('CreateWsdlHeaderHook','internal','PhpWsdl::CreateWsdlHeader');
		self::RegisterHook('CreateWsdlTypeSchemaHook','internal','PhpWsdl::CreateWsdlTypeSchema');
		self::RegisterHook('CreateWsdlMessagesHook','internal','PhpWsdl::CreateWsdlMessages');
		self::RegisterHook('CreateWsdlPortsHook','internal','PhpWsdl::CreateWsdlPorts');
		self::RegisterHook('CreateWsdlBindingsHook','internal','PhpWsdl::CreateWsdlBindings');
		self::RegisterHook('CreateWsdlServiceHook','internal','PhpWsdl::CreateWsdlService');
		self::RegisterHook('CreateWsdlFooterHook','internal','PhpWsdl::CreateWsdlFooter');
		self::RegisterHook('CreateWsdlOptimizeHook','internal','PhpWsdl::CreateWsdlOptimize');
		// HTML hooks
		self::RegisterHook('CreateHtmlGeneralHook','internal','PhpWsdl::CreateHtmlGeneral');
		self::RegisterHook('CreateHtmlIndexHook','internal','PhpWsdl::CreateHtmlIndex');
		self::RegisterHook('CreateHtmlMethodsHook','internal','PhpWsdl::CreateHtmlMethods');
		self::RegisterHook('CreateHtmlMethodHook','internal','PhpWsdl::CreateHtmlMethod');
		self::RegisterHook('CreateHtmlReturnHook','internal','PhpWsdl::CreateHtmlReturn');
		self::RegisterHook('CreateHtmlParamHook','internal','PhpWsdl::CreateHtmlParam');
		self::RegisterHook('CreateHtmlComplexTypesHook','internal','PhpWsdl::CreateHtmlComplexTypes');
		self::RegisterHook('CreateHtmlComplexTypeHook','internal','PhpWsdl::CreateHtmlComplexType');
		self::RegisterHook('CreateHtmlElementHook','internal','PhpWsdl::CreateHtmlElement');
		// Extensions
		$path=dirname(__FILE__).'/';
		$files=glob($path.'class.phpwsdl.*.php');
		if($files!==false){
			$i=-1;
			$len=sizeof($files);
			while(++$i<$len)
				require_once($path.$files[$i]);
		}
	}
}

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

/**
 * PhpWsdl class
 * 
 * @author Andreas Zimmermann
 * @copyright ©2011 Andreas Zimmermann, wan24.de
 * @version 1.0.1
 */
class PhpWsdl{
	/**
	 * The namespace
	 * 
	 * @var string
	 */
	public $NameSpace='http://tempuri.org/';
	/**
	 * The name of the webservice
	 * 
	 * @var string
	 */
	public $Name='SoapAPI';
	/**
	 * The SOAP endpoint URI
	 * 
	 * @var string
	 */
	public $EndPoint='http://tempuri.org/soapapi.php';
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
	public $BasicTypes=Array(
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
	 * Hook call definition when parsing an unknown line format (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $UnknownDefinitionHook=null;
	/**
	 * Hook call definition at the beginning of the WSDL rendering code (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $RenderWsdlBeginHook=null;
	/**
	 * Hook call definition at the end of the WSDL rendering code (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $RenderWsdlEndHook=null;
	/**
	 * Hook call definition when sending WSDL to the client (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $OutputWsdlHook=null;
	/**
	 * Hook call definition when sending HTML to the client (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $OutputHtmlHook=null;
	/**
	 * Hook call definition when running a SOAP server (function name of Array(class,function))
	 * 
	 * @var string|string[]
	 */
	public $RunServerHook=null;
	/**
	 * Set this to a writeable folder to enable caching the WSDL in files
	 * 
	 * @var string
	 */
	public $CacheFolder=null;
	/**
	 * The cache timeout in seconds (set to zero to disable caching, too)
	 * 
	 * @var int
	 */
	public $CacheTime=3600;
	/**
	 * Regular expression to filter WSDL definition relevant lines in the PHP source
	 * 
	 * @var string
	 */
	public $lineRx='/^\s*(\*\s*\@|public\s+function\s+[^\s|\(]+)/i';
	/**
	 * Regular expression to parse the omit next public method flag
	 * 
	 * @var string
	 */
	public $omitfncRx='/^\s*\*\s*\@pw_omitfnc.*$/i';
	/**
	 * Regular expression to parse the return value definition
	 * 
	 * @var string
	 */
	public $returnRx='/^\s*\*\s*(\@return\s.*)$/i';
	/**
	 * Regular expression to parse the parameter definition
	 * 
	 * @var string
	 */
	public $paramRx='/^\s*\*\s*(\@param\s.*)$/i';
	/**
	 * Regular expression to parse the complex type element definition
	 * 
	 * @var string
	 */
	public $elRx='/^\s*\*\s*(\@pw_element\s.*)$/i';
	/**
	 * Regular expression to parse the complex type definition
	 * 
	 * @var string
	 */
	public $complexRx='/^\s*\*\s*(\@pw_complex\s.*)$/i';
	/**
	 * Regular expression to parse a setting
	 * 
	 * @var string
	 */
	public $setRx='/^\s*\*\s*(\@pw_set\s.*)$/i';
	/**
	 * Regular expression to parse the type name
	 * 
	 * @var string
	 */
	public $typeRx='/^\@[^\s]+\s+([^\s|;]+);?(\s.*)?$/';
	/**
	 * Regular expression to parse the name
	 * 
	 * @var string
	 */
	public $nameRx='/^\@[^\s]+\s+[^\s]+\s+\$([^\s|;]+);?(\s.*)?$/';
	/**
	 * Regular expression to parse the public SOAP method definition
	 * 
	 * @var string
	 */
	public $fncRx='/^\s*public\s+function\s+([^\s|\(]+)\s*\(.*$/i';
	
	/**
	 * PhpWsdl constructor
	 * 
	 * @param string $nameSpace Namespace or NULL (default: NULL)
	 * @param string $endPoint Endpoint URI or NULL to let PhpWsdl determine it (default: NULL)
	 * @param string $cacheFolder The folder for caching WSDL or NULL to use the systems default (default: NULL)
	 * @param string|string[] $file Filename or array of filenames or NULL (default: NULL)
	 * @param string $name Webservice name or NULL (default: NULL)
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
		$this->Optimize=!isset($_GET['readable']);// Call with "?WSDL&readable" to get human readable WSDL
		$this->CacheFolder=(is_null($cacheFolder))?sys_get_temp_dir():$cacheFolder;
		if(!is_null($nameSpace))
			$this->NameSpace=$nameSpace;
		if(!is_null($name))
			$this->Name=$name;
		$this->EndPoint=((!is_null($endPoint)))?$endPoint:((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
		if(!is_null($file))
			$this->Files=(is_array($file))?$file:Array($file);
		$this->Methods=(!is_null($methods))?$methods:Array();
		$this->Types=(!is_null($types))?$types:Array();
		if($outputOnRequest&&!$runServer)
			$this->OutputWsdlOnRequest();
		if($runServer)
			$this->RunServer(null,(is_bool($runServer))?null:$runServer);
	}
	
	/**
	 * Determine if WSDL was requested by the client
	 * 
	 * @return boolean WSDL requested?
	 */
	public function IsWsdlRequested(){
		return isset($_GET['wsdl'])||isset($_GET['WSDL']);
	}
	
	/**
	 * Determine if HTML was requested by the client
	 * 
	 * @return boolean HTML requested?
	 */
	public function IsHtmlRequested(){
		return strlen(file_get_contents('php://input'))<1;
	}
	
	/**
	 * Create the WSDL
	 * 
	 * @param boolean $reCreate Don't use the cached WSDL? (default: FALSE)
	 * @return string The UTF-8 encoded WSDL as string
	 */
	public function CreateWsdl($reCreate=false){
		// Ask the cache
		if(!$reCreate&&!is_null($this->WSDL))
			return $this->WSDL;
		$cacheFile=$this->GetCacheFileName();
		if(!$reCreate&&!is_null($cacheFile))
			if(file_exists($cacheFile.'.cache'))
				if(time()-file_get_contents($cacheFile.'.cache')<=$this->CacheTime){
					$this->WSDL=file_get_contents($cacheFile);
					return $this->WSDL;
				}
		// Prepare the WSDL generator
		$this->ParseSource();
		$mLen=sizeof($this->Methods);
		// Create the XML Header
		$res=Array();
		$res[]='<?xml version="1.0" encoding="UTF-8"?>';
		$res[]='<wsdl:definitions xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:tns="'.$this->NameSpace.'" xmlns:s="http://www.w3.org/2001/XMLSchema" targetNamespace="'.$this->NameSpace.'" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/">';
		// Call the user hook
		if(!is_null($this->RenderWsdlBeginHook))
			call_user_func(
				$this->RenderWsdlBeginHook,
				Array(
					$this,
					&$res,		// The result WSDL string array (NOT UTF-8 encoded yet!)
					&$cacheFile,// The path to the cache file
					$reCreate
				));
		// Create types
		$tLen=sizeof($this->Types);
		if($tLen>0){
			$res[]="\t".'<wsdl:types>';
			$res[]="\t\t".'<s:schema elementFormDefault="qualified" targetNamespace="'.$this->NameSpace.'">';
			$i=-1;
			while(++$i<$tLen)
				$res[]=$this->Types[$i]->CreateType($this);
			$res[]="\t\t".'</s:schema>';
			$res[]="\t".'</wsdl:types>';
		}
		// Create messages
		$i=-1;
		while(++$i<$mLen)
			$res[]=$this->Methods[$i]->CreateMessages($this);
		// Create port types
		$res[]="\t".'<wsdl:portType name="'.$this->Name.'Soap">';
		$i=-1;
		while(++$i<$mLen)
			$res[]=$this->Methods[$i]->CreatePortType();
		$res[]="\t".'</wsdl:portType>';
		// Create bindings
		$res[]="\t".'<wsdl:binding name="'.$this->Name.'Soap" type="tns:'.$this->Name.'Soap">';
		$res[]="\t\t".'<soap:binding transport="http://schemas.xmlsoap.org/soap/http" style="rpc" />';
		$i=-1;
		while(++$i<$mLen)
			$res[]=$this->Methods[$i]->CreateBinding($this);
		$res[]="\t".'</wsdl:binding>';
		// Create the service
		$res[]="\t".'<wsdl:service name="'.$this->Name.'">';
		$res[]="\t\t".'<wsdl:port name="'.$this->Name.'Soap" binding="tns:'.$this->Name.'Soap">';
		$res[]="\t\t\t".'<soap:address location="'.$this->EndPoint.'" />';
		$res[]="\t\t".'</wsdl:port>';
		$res[]="\t".'</wsdl:service>';
		// Call the user hook
		if(!is_null($this->RenderWsdlEndHook))
			call_user_func(
				$this->RenderWsdlEndHook,
				Array(
					$this,
					&$res,		// The result WSDL string array (NOT UTF-8 encoded yet!)
					&$cacheFile,// The path to the cache file
					$reCreate
				));
		// Finish the WSDL XML string
		$res[]='</wsdl:definitions>';
		$res=implode("\n",$res);
		// Run the optimizer
		if($this->Optimize)
			$res=preg_replace('/[\n|\t]/','',$res);
		$this->WSDL=utf8_encode($res);
		// Fill the cache
		if(!is_null($cacheFile)){
			file_put_contents($cacheFile,$this->WSDL);
			file_put_contents($cacheFile.'.cache',time());
		}
		return $this->WSDL;
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
		}
		$fLen=sizeof($this->Files);
		if($fLen<1)
			return;
		// Load the source
		$lines=Array();
		$i=-1;
		while(++$i<$fLen)
			$lines[]=trim(file_get_contents($this->Files[$i]));
		$names=Array();
		$param=Array();
		$el=Array();
		$set=Array();
		$return=null;
		$omit=false;
		$lines=explode("\n",implode("\n",$lines));
		// Parse each line of the source
		$i=-1;
		$len=sizeof($lines);
		while(++$i<$len){
			$line=$lines[$i];
			if(!preg_match($this->lineRx,$line))
				continue;
			if(preg_match($this->paramRx,$line)){
				// Parameter definition found
				$temp=preg_replace($this->paramRx,"$1",$line);
				$param[]=Array(
					'type'			=>	preg_replace($this->typeRx,"$1",$temp),
					'name'			=>	preg_replace($this->nameRx,"$1",$temp),
					'settings'		=>	$set
				);
				$set=Array();
				continue;
			}else if(preg_match($this->elRx,$line)){
				// Complex type element definition found
				$temp=preg_replace($this->elRx,"$1",$line);
				$el[]=Array(
					'type'			=>	preg_replace($this->typeRx,"$1",$temp),
					'name'			=>	preg_replace($this->nameRx,"$1",$temp),
					'settings'		=>	$set
				);
				$set=Array();
				continue;
			}else if(preg_match($this->returnRx,$line)){
				// Return value definition found
				$temp=preg_replace($this->returnRx,"$1",$line);
				$return=Array(
					'type'			=>	preg_replace($this->typeRx,"$1",$temp),
					'settings'		=>	$set
				);
				$set=Array();
				continue;
			}else if(preg_match($this->setRx,$line)){
				// Setting found
				$temp=explode('=',preg_replace($this->typeRx,"$1",preg_replace($this->setRx,"$1",$line)),2);
				if(sizeof($temp)==2)
					$set[$temp[0]]=$temp[1];
				continue;
			}else if(preg_match($this->complexRx,$line)){
				// Complex type definition found
				$name=preg_replace($this->typeRx,"$1",preg_replace($this->complexRx,"$1",$line));
				$temp=Array();
				$j=-1;
				$pLen=sizeof($el);
				while(++$j<$pLen)
					$temp[]=new PhpWsdlElement($el[$j]['name'],$el[$j]['type'],$el[$j]['settings']);
				$this->Types[]=new PhpWsdlComplex($name,$temp,$set);
				$el=Array();
				$set=Array();
				continue;
			}else if(preg_match($this->omitfncRx,$line)){
				// Omit next public method definition
				$omit=true;
				continue;
			}else if(preg_match($this->fncRx,$line)){
				// SOAP method definition found
				if($omit){
					$param=Array();
					$return=null;
					$set=Array();
					$omit=false;
					continue;
				}
				$name=preg_replace($this->fncRx,"$1",$line);
				if($names[$name])
					continue;// This class don't support overloading what keeps it compatible to COM clients f.e.
				$names[$name]=true;
				$temp=Array();
				$j=-1;
				$pLen=sizeof($param);
				while(++$j<$pLen)
					$temp[]=new PhpWsdlParam($param[$j]['name'],$param[$j]['type'],$param[$j]['settings']);
				$this->Methods[]=new PhpWsdlMethod($name,$temp,(is_null($return))?null:new PhpWsdlParam($name.'Result',$return['type']),$set);
				$param=Array();
				$return=null;
				$set=Array();
				continue;
			}else{
				// Try hooking
				if(is_null($this->UnknownDefinitionHook))
					continue;
				if(call_user_func(
						$this->UnknownDefinitionHook,
						Array(
							$this,
							&$line,		// The currently parsed line
							&$param,	// The current list of parameters
							&$el,		// The current list of elements
							&$set,		// The current list of settings
							&$return,	// The current return value definition
							&$omit,		// Omit next public method definition?
							&$names,	// A list of already parsed public SOAP method names
							&$lines,	// PHP code array
							&$i,		// The current line number
							&$cacheFile	// The path to the cache file
						)
					))
					continue;
			}
		}
	}
	
	/**
	 * Output the WSDL to the client
	 */
	public function OutputWsdl(){
		header('Content-Type: text/xml; charset=UTF-8',true);
		if(!is_null($this->OutputWsdlHook))
			call_user_func(
				$this->OutputWsdlHook,
				Array(
					$this
				));
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
			$this->CreateWsdl(true);
		header('Content-Type: text/html; charset=UTF-8',true);
		if(!is_null($this->OutputHtmlHook))
			call_user_func(
				$this->OutputHtmlHook,
				Array(
					$this
				));
		$res=Array();
		$res[]='<html>';
		$res[]='<head>';
		$res[]='<title>'.$this->Name.' interface description</title>';
		$res[]='<style type="text/css">';
		$res[]='body{font-family:Calibri,Arial;background-color:#f6f6f6;}';
		$res[]='.pre{font-family:Courier;}';
		$res[]='.bold{font-weight:bold;}';
		$res[]='h1,h2,h3{font-family:Verdana,Times;}';
		$res[]='h1{border-bottom:1px solid gray;}';
		$res[]='h2{border-bottom:1px solid silver;}';
		$res[]='h3{border-bottom:1px dashed silver;}';
		$res[]='a{text-decoration:none;}';
		$res[]='a:hover{text-decoration:underline;}';
		$res[]='.blue{color:#3400FF;}';
		$res[]='.lightBlue{color:#5491AF;}';
		$res[]='</style>';
		$res[]='</head>';
		$res[]='<body>';
		$res[]='<h1>'.$this->Name.' SOAP WebService interface description</h1>';
		$res[]='<p>Endpoint URI: <span class="pre">'.$this->EndPoint.'</span></p>';
		$res[]='<p>WSDL URI: <span class="pre"><a href="'.$this->EndPoint.'?WSDL&readable">'.$this->EndPoint.'?WSDL</a></span></p>';
		$tLen=sizeof($this->Types);
		if($tLen>0){
			$res[]='<h2>Complex types</h2>';
			$i=-1;
			while(++$i<$tLen){
				$t=$this->Types[$i];
				$res[]='<h3>'.$t->Name.'</h3>';
				$res[]='<a name="'.$t->Name.'"></a>';
				$eLen=sizeof($t->Elements);
				if($t->IsArray){
					$res[]='<p>This is an array type of <span class="pre">';
					$type=substr($t->Name,0,strlen($t->Name)-5);
					if(in_array($type,$this->BasicTypes)){
						$res[sizeof($res)-1].='<span class="blue">'.$type.'</span>';
					}else{
						$res[sizeof($res)-1].='<a href="#'.$type.'"><span class="lightBlue">'.$type.'</span></a>';
					}
					$res[sizeof($res)-1].='</span>.</p>';
				}else if($eLen>0){
					$res[]='<ul class="pre">';
					$j=-1;
					while(++$j<$eLen){
						$e=$t->Elements[$j];
						if(in_array($e->Type,$this->BasicTypes)){
							$res[]='<li><span class="blue">'.$e->Type.'</span> <span class="bold">'.$e->Name.'</span>';
						}else{
							$res[]='<li><a href="#'.$e->Type.'"><span class="lightBlue">'.$e->Type.'</span></a> <span class="bold">'.$e->Name.'</span>';
						}
						$temp=Array(
							'nillable=<span class="blue">'.(($e->NillAble)?'true':'false').'</span>',
							'minoccurs=<span class="blue">'.$e->MinOccurs.'</span>',
							'maxoccurs=<span class="blue">'.$e->MaxOccurs.'</span>',
						);
						$res[sizeof($res)-1].=' ('.implode(', ',$temp).')</li>';
					}
					$res[]='</ul>';
				}else{
					$res[]='<p>This type has no elements.</p>';
				}
			}
		}
		$mLen=sizeof($this->Methods);
		if($mLen>0){
			$res[]='<h2>Public methods</h2>';
			$i=-1;
			while(++$i<$mLen){
				$m=$this->Methods[$i];
				$res[]='<h3>'.$m->Name.'</h3>';
				$res[]='<p class="pre">';
				if(!is_null($m->Return)){
					$type=$m->Return->Type;
					if(in_array($type,$this->BasicTypes)){
						$res[sizeof($res)-1].='<span class="blue">'.$type.'</span>';
					}else{
						$res[sizeof($res)-1].='<a href="#'.$type.'"><span class="lightBlue">'.$type.'</span></a>';
					}
				}else{
					$res[sizeof($res)-1].='void';
				}
				$res[sizeof($res)-1].=' <span class="bold">'.$m->Name.'</span>(';
				$pLen=sizeof($m->Param);
				if($pLen>0){
					$j=-1;
					while(++$j<$pLen){
						$p=$m->Param[$j];
						if(in_array($p->Type,$this->BasicTypes)){
							$res[sizeof($res)-1].='<span class="blue">'.$p->Type.'</span> <span class="bold">'.$p->Name.'</span>';
						}else{
							$res[sizeof($res)-1].='<a href="#'.$p->Type.'"><span class="lightBlue">'.$p->Type.'</span></a> <span class="bold">'.$p->Name.'</span>';
						}
						if($j<$pLen-1)
							$res[sizeof($res)-1].=', ';
					}
				}
				$res[sizeof($res)-1].=')</p>';
			}
		}
		$res[]='<hr>';
		$res[]='<p><small>Powered by <a href="http://code.google.com/p/php-wsdl-creator/">PhpWsdl</a></small></p>';
		$res[]='</body>';
		$res[]='</html>';
		echo utf8_encode(implode("\n",$res));
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
			global $PhpWsdlProxyClass,$PhpWsdlProxyServer;
			$PhpWsdlProxyClass=$class[1];
			$PhpWsdlProxyServer=$this;
			$class=$class[0];
			$useProxy=true;
		}
		// Set the handler class name
		if(is_null($class)){
			$class=$this->Name;
		}else if(is_string($class)){
			$this->Name=$class;
		}
		// Load WSDL
		if(!$useProxy&&!is_null($this->CacheFolder)){
			if(is_null($wsdlFile))
				$wsdlFile=$this->GetCacheFileName();
			$this->CreateWsdl();
		}
		if(!$useProxy&&!is_null($wsdlFile))
			if(!file_exists($wsdlFile))
				$wsdlFile=null;
		// Initialize the SOAP server
		$server=new SoapServer(
			($useProxy)?null:$wsdlFile,
			Array(
				'soap_version'	=>	SOAP_1_2,
				'actor'			=>	$this->EndPoint,
				'uri'			=>	$this->NameSpace,
				'encoding'		=>	'UTF-8',
				'compression'	=>	SOAP_COMPRESSION_ACCEPT|SOAP_COMPRESSION_GZIP|9
			)
		);
		$server->SetClass(($useProxy)?'PhpWsdlProxy':$class);
		// Call the user hook
		if(!is_null($this->RunServerHook))
			call_user_func(
				$this->RunServerHook,
				Array(
					$this,
					&$server,// The PHP SoapServer object
					&$wsdlFile,
					&$class,
					&$useProxy,// Is the proxy being used?
					&$andExit
				));
		// Run the SOAP server
		$server->handle();
		if($andExit)
			exit;
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
	 * @return string The cache filename or NULL, if caching is disabled
	 */
	public function GetCacheFileName(){
		return (is_null($this->CacheFolder))?null:$this->CacheFolder.'/'.sha1($this->EndPoint).'.wsdl';
	}
	
	/**
	 * Delete cache files from the cache folder
	 * 
	 * @param boolean $mineOnly Only delete the cache files for this definition? (default: FALSE)
	 * @return string[] The deleted filenames
	 */
	public function TidyCacheFolder($mineOnly=false){
		if(is_null($this->CacheFolder))
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
		}else{
			foreach(glob($this->CacheFolder.'/*.wsd*') as $file){
				if(!preg_match('/\.wsdl(\.cache)?$/',$file))
					continue;
				$file=$this->CacheFolder.'/'.$file;
				if(unlink($file))
					$deleted[]=$file;
			}
		}
		return $deleted;
	}
}

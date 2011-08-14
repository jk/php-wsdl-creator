<?php

if(basename($_SERVER['SCRIPT_FILENAME'])==basename(__FILE__))
	exit;

/**
 * This demo webservice shows you how to work with PhpWsdl
 * 
 * @service SoapDemo
 */
class SoapDemo{
	/**
	 * Get a complex type object
	 * 
	 * @return ComplexTypeDemo The object
	 * @pw_rest GET /GetComplexType This is the REST server path (see the PhpWsdlServers extension)
	 */
	public function GetComplexType(){
		return new ComplexTypeDemo();
	}
	
	/**
	 * Print an object
	 * 
	 * @param ComplexTypeDemo $obj The object
	 * @return string The result of print_r
	 * @pw_rest GET /PrintComplexType/:obj This is the REST server path (see the PhpWsdlServers extension)
	 */
	public function PrintComplexType($obj){
		return utf8_encode($this->PrintVariable($obj));
	}
	
	/**
	 * Print an array of objects
	 * 
	 * @param ComplexTypeDemoArray $arr A ComplexTypeDemo array
	 * @return stringArray The results of print_r
	 * @pw_rest GET /ComplexTypeArrayDemo/:arr This is the REST server path (see the PhpWsdlServers extension)
	 */
	public function ComplexTypeArrayDemo($arr){
		$res=Array();
		$i=-1;
		$len=sizeof($arr);
		while(++$i<$len)
			$res[]=$this->PrintVariable($arr[$i]);
		return $res;
	}
	
	/**
	 * Say hello demo
	 * 
	 * @param string $name Some name (or an empty string)
	 * @return string Response string
	 * @pw_rest GET /SayHello/blubber This is the REST server path (see the PhpWsdlServers extension) for telling hello to unknown
	 * @pw_rest GET /SayHello/:name This is the REST server path (see the PhpWsdlServers extension)
	 */
	public function SayHello($name=null){
		$name=utf8_decode($name);// Because a string parameter is UTF-8 encoded...
		if($name=='')
			$name='unknown';
		return utf8_encode('Hello '.$name.'!');// Because a string return value should by UTF-8 encoded...
	}

	/**
	 * This method has no parameters and no return value, but it is visible in WSDL, too
	 * 
	 * @pw_rest GET /DemoMethod This is the REST server path (see the PhpWsdlServers extension)
	 */
	public function DemoMethod(){
	}
	
	/**
	 * This method should not be visible in WSDL - but notice:
	 * If the PHP SoapServer doesn't know the WSDL, this method is still accessable for SOAP requests!
	 * 
	 * @pw_omitfnc
	 * @param unknown_type $var
	 * @return string
	 */
	public function PrintVariable($var){
		return print_r($var,true);
	}
}

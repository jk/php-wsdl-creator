<?php

// A quick and dirty SOAP server example

// TRUE as first parameter enables the quick mode. In quick mode you can 
// specify the class filename(s) of your webservice as second parameter, if 
// required. This together means that the SOAP namespace and the endpoint 
// can't be defined in the PhpWsdl constructor when using the quick mode.
require_once('class.phpwsdl.php');
new PhpWsdl(true);// -> Don't waste my time - just run!

// This is the SOAP webservice handler demo class
class SoapDemo{
	/**
	 * Say hello to...
	 * 
	 * @param string $name A name
	 * @return string Response
	 */
	public function SayHello($name){
		$name=utf8_decode($name);
		if($name=='')
			$name='unknown';
		return utf8_encode('Hello '.$name.'!');
	}
}

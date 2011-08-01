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

// I tryed some document/literal experiments which required using this proxy 
// because the PHP SoapServer can't handle document/literal SOAP requests.
// You don't need this class to use PhpWsdl in standard.
// BUT this proxy can handle missing parameters with NULL values. This will 
// only work, if the PhpWsdl class doesn't promote the WSDL to the SoapServer. 
// But then returning complex types won't be easy anymore: You have to encode 
// the return value with PHPs SoapVar object by yourself.
//
// Note: You should NOT use the proxy class in PhpWsdl quick mode!

class PhpWsdlProxy{
	public function __call($method,$param){
		if(PhpWsdl::$Debugging)
			PhpWsdl::Debug('Proxy call method '.$method.': '.print_r($param));
		// Need to parse the source to ensure that the types and methods arrays are present 
		if(sizeof(PhpWsdl::$ProxyServer->Methods)<1)
			PhpWsdl::$ProxyServer->CreateWsdl();
		// Check for missing parameters
		$m=PhpWsdl::$ProxyServer->GetMethod($method);
		if(!is_null($m)){
			$pLen=sizeof($m->Param);
			if($pLen!=sizeof($param)){
				$req=file_get_contents('php://input');
				$temp=$param;
				$param=Array();
				$pos=0;// Current index in the received parameter array
				$i=-1;
				PhpWsdl::Debug('Add NULL parameters');
				while(++$i<$pLen)//FIXME This regular expression is not very reliably in some cases when working with complex types
					if(preg_match('/<([^>]+:)?'.$m->Param[$i]->Name.'>/',$req)){
						// Parameter received -> use received value
						$param[]=$temp[$pos];
						$pos++;
					}else{
						// Missing parameter -> insert NULL value
						PhpWsdl::Debug($m->Param[$i]->Name.' was missing');
						$param[]=null;
					}
			}
		}
		// Call the target method and return the response
		return call_user_func_array(
			Array(
				PhpWsdl::$ProxyObject,
				$method
			),
			$param
		);
	}
}

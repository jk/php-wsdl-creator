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

require_once(dirname(__FILE__).'/class.phpwsdlclient.php');

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
	 * This will run the proxy webservice and exit the script execution
	 * 
	 * @param strign $wsdl The WSDL URI
	 */
	public static function RunProxy($wsdl){
		PhpWsdl::Debug('Run PhpWsdlAjax proxy');
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
}

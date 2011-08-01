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

PhpWsdl::Debug('Zend adapter loaded');

PhpWsdlZend::Init();

//TODO Uncomment to let Zend create the WSDL
//PhpWsdl::RegisterHook('CreateWsdlHeaderHook','zend','PhpWsdlZend::CreateWsdl');
PhpWsdl::RegisterHook('PrepareServerHook','zend','PhpWsdlZend::PrepareServer');
PhpWsdl::RegisterHook('RunServerHook','zend','PhpWsdlZend::RunServer');

// Disable the PhpWsdl WSDL Generator (we need to use the one from Zend)
//TODO Uncomment to let Zend create the WSDL
/*PhpWsdl::UnregisterHook('CreateWsdlHeaderHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlTypeSchemaHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlMessagesHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlPortsHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlBindingsHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlServiceHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlFooterHook','internal');*/

// Comment out this line to get rid of the Zend information in the HTML output
PhpWsdl::RegisterHook('CreateHtmlGeneralHook','zend','PhpWsdlZend::CreateHtmlGeneral');

/**
 * This class will run a Zend SOAP server with PhpWsdl
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlZend{
	/**
	 * The current server object
	 * 
	 * @var Zend_Soap_Server
	 */
	public static $Server=null;
	/**
	 * Options for the Zend server
	 * 
	 * @var array
	 */
	public static $Options;
	
	/**
	 * Create WSDL
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function CreateWsdl($data){
		if(!class_exists('Zend_Soap_Server')){
			PhpWsdl::Debug('Zend not found');
			return true;// Use the default PHP SoapServer because Zend is not available
		}
		//TODO Zend_Soap_Wsdl should produce some WSDL here
		return true;
	}
	
	/**
	 * Prepare the Zend server
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function PrepareServer($data){
		self::$Server=null;
		if(!class_exists('Zend_Soap_Server')){
			PhpWsdl::Debug('Zend not found');
			return true;// Use the default PHP SoapServer because Zend is not available
		}
		// Initialize the Zend server object
		$server=$data['server'];
		if(is_null(self::$Server))
			self::CreateServer($server);
		$data['soapserver']=self::CreateServer($server);
		return false;
	}
		
	/**
	 * Run the Zend server
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function RunServer($data){
		$server=$data['soapserver'];
		if($server!==self::$Server||!class_exists('Zend_Soap_Server')){
			PhpWsdl::Debug('Zend not found or server object changed');
			return true;// We can't handle this server run!
		}
		if(!PhpWsdl::CallHook(
				'ZendRunHook',
				$data
			)
		)
			return false;
		self::$Server->handle();
		return false;
	}
	
	/**
	 * Modify the HTML documentation output
	 * 
	 * @param array $data
	 * @return boolean Response
	 */
	public static function CreateHtmlGeneral($data){
		$res=&$data['res'];
		$res[]='<p><i>Info: This SOAP webservice uses Zend as SOAP server.</i></p>';
		return true;
	}
	
	/**
	 * Create a Zend soap_server object
	 * 
	 * @param PhpWsdl $server The PhpWsdl object
	 * @param array $data Hook data (default: NULL)
	 * @return Zend_Soap_Server The Zend server object
	 */
	public static function CreateServer($server,$data=null){
		if(!is_null(self::$Server))
			return self::$Server;
		self::$Server=new Zend_Soap_Server(
			(!is_null($server->WsdlUri))?$server->WsdlUri:$server->EndPoint.'?WSDL',
			array_merge(
				self::$Options,
				Array(
					'actor'			=>	$server->EndPoint,
					'wsdl'			=>	$server->CreateWsdl(),
					'uri'			=>	$server->NameSpace
				)
			)
		);
		if(!is_null($data))
			if(PhpWsdl::CallHook(
					'ZendConfigHook',
					$data
				)
			)
				if(is_object($data['class'])){
					$this->Server->setObject($data['class']);
				}else{
					$this->Server->setClass($data['class']);
				}
	}
	
	/**
	 * Initialize the Zend adapter
	 */
	public static function Init(){
		self::$Options=Array(
			'soap_version'		=>	SOAP_1_1,
			'encoding'			=>	'UTF-8'
		);
	}
}

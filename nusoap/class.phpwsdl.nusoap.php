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

PhpWsdl::Debug('NuSOAP adapter loaded');

PhpWsdl::RegisterHook('CreateWsdlHeaderHook','nusoap','PhpWsdlNuSOAP::CreateWsdl');
PhpWsdl::RegisterHook('PrepareServerHook','nusoap','PhpWsdlNuSOAP::PrepareServer');
PhpWsdl::RegisterHook('RunServerHook','nusoap','PhpWsdlNuSOAP::RunServer');

// Disable the PhpWsdl WSDL Generator (we need to use the one from NuSOAP)
PhpWsdl::UnregisterHook('CreateWsdlHeaderHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlTypeSchemaHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlMessagesHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlPortsHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlBindingsHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlServiceHook','internal');
PhpWsdl::UnregisterHook('CreateWsdlFooterHook','internal');

// Comment out this line to get rid of the NuSOAP information in the HTML output
PhpWsdl::RegisterHook('CreateHtmlGeneralHook','nusoap','PhpWsdlNuSOAP::CreateHtmlGeneral');

/**
 * This class will run a NuSOAP SOAP server with PhpWsdl
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlNuSOAP{
	/**
	 * The current server object
	 * 
	 * @var soap_server
	 */
	public static $Server=null;
	
	/**
	 * Create WSDL
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function CreateWsdl($data){
		if(!class_exists('soap_server')){
			PhpWsdl::Debug('NuSOAP not found');
			return true;// Use the default PHP SoapServer because NuSOAP is not available
		}
		if(is_null(self::$Server))
			self::CreateServer($data['server'],$data);
		$data['res'][]=self::$Server->wsdl->serialize();
		return false;
	}
	
	/**
	 * Prepare the NuSOAP server
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function PrepareServer($data){
		self::$Server=null;
		if(!class_exists('soap_server')){
			PhpWsdl::Debug('NuSOAP not found');
			return true;// Use the default PHP SoapServer because NuSOAP is not available
		}
		// Initialize the NuSOAP server object
		$server=$data['server'];
		if(is_null(self::$Server))
			self::CreateServer($server);
		$data['soapserver']=self::CreateServer($server,$data);
		return false;
	}
		
	/**
	 * Run the NuSOAP server
	 * 
	 * @param array $data The server data
	 * @return boolean Response
	 */
	public static function RunServer($data){
		$server=$data['soapserver'];
		if($server!==self::$Server||!class_exists('soap_server')){
			PhpWsdl::Debug('NuSOAP not found or server object changed');
			return true;// We can't handle this server run!
		}
		$req=file_get_contents('php://input');
		if(!PhpWsdl::CallHook(
				'NuSOAPRunHook',
				array_merge(
					$data,
					Array(
						'req'			=>	&$req
					)
				)
			)
		)
			return false;
		self::$Server->service(utf8_encode($req));
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
		$res[]='<p><i>Info: This SOAP webservice uses NuSOAP as SOAP server.</i></p>';
		return true;
	}
	
	/**
	 * Create a NuSOAP soap_server object
	 * 
	 * @param PhpWsdl $server The PhpWsdl object
	 * @param array $data Hook data (default: NULL)
	 * @return soap_server The NuSOAP server object
	 */
	public static function CreateServer($server,$data=null){
		if(!is_null(self::$Server))
			return self::$Server;
		self::$Server=new soap_server();
		self::$Server->debug_flag=false;
		self::$Server->soap_defencoding='UTF-8';
		self::$Server->decode_utf8=false;
		// Basic configuration
		self::$Server->configureWSDL($server->Name,$server->NameSpace);
		self::$Server->wsdl->schemaTargetNamespace=$server->NameSpace;
		PhpWsdl::CallHook(
			'NuSOAPConfigHook',
			$data
		);
		// Add types
		$i=-1;
		$len=sizeof($server->Types);
		while(++$i<$len){
			$t=$server->Types[$i];
			PhpWsdl::Debug('Add complex type '.$t->Name);
			if(!PhpWsdl::CallHook(
					'NuSOAPTypeHook',
					array_merge(
						$data,
						Array(
							'type'			=>	&$t
						)
					)
				)
			)
				continue;
			if($t->IsArray){
				$type=substr($t->Name,0,strlen($t->Name)-5);
				self::$Server->wsdl->addComplexType(
					$t->Name,
					'complexType',
					'array',
					'',
					'SOAP-ENC:Array',
					Array(),
					Array(
						Array(
							'ref'			=>	'SOAP-ENC:arrayType',
							'wsdl:arrayType'=>	((in_array($type,PhpWsdl::$BasicTypes))?'xsd:'.$type:'tns:'.$type).'[]'
						)
					),
					(in_array($type,PhpWsdl::$BasicTypes))?'xsd:'.$type:'tns:'.$type
				);
			}else{
				$el=Array();
				$j=-1;
				$eLen=sizeof($t->Elements);
				while(++$j<$eLen){
					$e=$t->Elements[$j];
					$el[$e->Name]=Array(
						'name'			=>	$e->Name,
						'type'			=>	(in_array($e->Type,PhpWsdl::$BasicTypes))?'xsd:'.$e->Type:'tns:'.$e->Type
					);
				}
				self::$Server->wsdl->addComplexType(
					$t->Name,
					'complexType',
					'struct',
					'sequence',
					'',
					$el
				);
			}
		}
		PhpWsdl::CallHook(
			'NuSOAPTypesHook',
			$data
		);
		// Add methods
		$i=-1;
		$len=sizeof($server->Methods);
		while(++$i<$len){
			$m=$server->Methods[$i];
			PhpWsdl::Debug('Register method '.$m->Name);
			if(!PhpWsdl::CallHook(
					'NuSOAPMethodHook',
					array_merge(
						$data,
						Array(
							'method'		=>	&$m
						)
					)
				)
			)
				continue;
			$param=Array();
			$j=-1;
			$pLen=sizeof($m->Param);
			while(++$j<$pLen){
				$p=$m->Param[$j];
				$param[$p->Name]=(in_array($p->Type,PhpWsdl::$BasicTypes))?'xsd:'.$p->Type:'tns:'.$p->Type;
			}
			$r=$m->Return;
			self::$Server->register(
				$server->Name.'.'.$m->Name,
				$param,
				(is_null($r))
					?Array()
					:Array(
						'return'		=>	(in_array($r->Type,PhpWsdl::$BasicTypes))?'xsd:'.$r->Type:'tns:'.$r->Type
					),
				$server->NameSpace,
				$server->NameSpace.$m->Name,
				'rpc',
				'encoded'
			);
		}
		PhpWsdl::CallHook(
			'NuSOAPMethodsHook',
			$data
		);
	}
}

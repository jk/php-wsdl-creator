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

PhpWsdl::RegisterHook('InterpretKeywordpw_complexHook','internal','PhpWsdlComplex::InterpretComplex');
PhpWsdl::RegisterHook('CreateObjectHook','internalcomplex','PhpWsdlComplex::CreateComplexTypeObject');

/**
 * This class creates complex types (classes or arrays)
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlComplex{
	/**
	 * The name
	 * 
	 * @var string
	 */
	public $Name;
	/**
	 * A list of elements, if this type is a class
	 * 
	 * @var PhpWsdlElement[]
	 */
	public $Elements;
	/**
	 * Is this type an array?
	 * 
	 * @var boolean
	 */
	public $IsArray;
	/**
	 * Documentation
	 * 
	 * @var string
	 */
	public $Docs=null;
	
	/**
	 * Constructor
	 * 
	 * @param string $name The name
	 * @param PhpWsdlElement[] $el Optional a list of elements
	 * @param array $settings Optional the settings hash array (default: NULL)
	 */
	public function PhpWsdlComplex($name,$el=Array(),$settings=null){
		$this->IsArray=substr($name,strlen($name)-5,5)=='Array';
		$this->Name=$name;
		$this->Elements=$el;
		if(!is_null($settings))
			if(isset($settings['docs']))
				$this->Docs=$settings['docs'];
	}
	
	/**
	 * Create WSDL for the type
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreateType($pw){
		$res=Array();
		$res[]="\t\t\t".'<s:complexType name="'.$this->Name.'">';
		if($pw->IncludeDocs&&!$pw->Optimize&&!is_null($this->Docs)){
			$res[]="\t\t\t\t".'<s:annotation>';
			$res[]="\t\t\t\t\t".'<s:documentation><![CDATA['.$this->Docs.']]></s:documentation>';
			$res[]="\t\t\t\t".'</s:annotation>';
		}
		if(!$this->IsArray){
			PhpWsdl::Debug('Create WSDL definition for type '.$this->Name.' as type');
			$res[]="\t\t\t\t".'<s:sequence>';
			$i=-1;
			$len=sizeof($this->Elements);
			while(++$i<$len)
				$res[]=$this->Elements[$i]->CreateElement($pw);
			$res[]="\t\t\t\t".'</s:sequence>';
		}else{
			PhpWsdl::Debug('Create WSDL definition for type '.$this->Name.' as array');
			$res[]="\t\t\t\t".'<s:complexContent>';
			$res[]="\t\t\t\t\t".'<s:restriction base="soapenc:Array">';
			$res[]="\t\t\t\t\t\t".'<s:attribute ref="soapenc:arrayType" wsdl:arrayType="';
			$type=substr($this->Name,0,strlen($this->Name)-5);
			$res[sizeof($res)-1].=(in_array($type,PhpWsdl::$BasicTypes))?'s':'tns';
			$res[sizeof($res)-1].=':'.$type.'[]" />';
			$res[]="\t\t\t\t\t".'</s:restriction>';
			$res[]="\t\t\t\t".'</s:complexContent>';
		}
		$res[]="\t\t\t".'</s:complexType>';
		return implode("\n",$res);
	}
	
	/**
	 * Find an element within this type
	 * 
	 * @param string $name The name
	 * @return PhpWsdlElement The element or NULL, if not found
	 */
	public function GetElement($name){
		PhpWsdl::Debug('Find element '.$name);
		$i=-1;
		$len=sizeof($this->Elements);
		while(++$i<$len)
			if($this->Elements[$i]->Name==$name){
				PhpWsdl::Debug('Found element at index '.$i);
				return $this->Elements[$i];
			}
		return null;
	}
	
	/**
	 * Interpret a complex type
	 * 
	 * @param array $data The parser data
	 * @return boolean Response
	 */
	public static function InterpretComplex($data){
		$info=explode(' ',$data['keyword'][1],2);
		if(sizeof($info)<1)
			return true;
		PhpWsdl::Debug('Interpreted complex type '.$info[0]);
		$data['type']=Array(
			'id'			=>	'complex',
			'name'			=>	$info[0],
			'docs'			=>	(sizeof($info)>1)?$info[1]:null
		);
		return false;
	}
	
	/**
	 * Create complex type object
	 * 
	 * @param array $data The parser data
	 * @return boolean Response
	 */
	public static function CreateComplexTypeObject($data){
		if($data['method']!='')
			return true;
		if(!is_null($data['obj']))
			return true;
		if(!is_array($data['type']))
			return true;
		if(!isset($data['type']['id']))
			return true;
		if($data['type']['id']!='complex')
			return true;
		if(!is_null($data['docs'])){
			$data['settings']['docs']=$data['docs'];
		}else{
			$data['settings']['docs']=$data['type']['docs'];
		}
		PhpWsdl::Debug('Add complex type '.$data['type']['name']);
		$data['obj']=new PhpWsdlComplex($data['type']['name'],$data['elements'],$data['settings']);
		$data['settings']=Array();
		$data['server']->Types[]=$data['obj'];
		return true;
	}
}

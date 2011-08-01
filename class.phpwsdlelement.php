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

PhpWsdl::RegisterHook('InterpretKeywordpw_elementHook','internal','PhpWsdlElement::InterpretElement');

/**
 * An element of a complex type
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlElement extends PhpWsdlParam{
	/**
	 * Can the value be NULL?
	 * 
	 * @var boolean
	 */
	public $NillAble=true;
	/**
	 * Minimum number of elements
	 * 
	 * @var int
	 */
	public $MinOccurs=1;
	/**
	 * Maximum number of elements
	 * 
	 * @var int
	 */
	public $MaxOccurs=1;
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
	 * @param string $type The type name
	 * @param array $settings Optional the settings hash array (default: NULL)
	 */
	public function PhpWsdlElement($name,$type,$settings=null){
		PhpWsdl::Debug('New element '.$name);
		parent::PhpWsdlParam($name,$type);
		if(!is_null($settings)){
			if(isset($settings['nillable']))
				$this->NillAble=$settings['nillable']=='1'||$settings['nillable']=='true';
			if(isset($settings['minoccurs']))
				$this->MinOccurs=$settings['minoccurs'];
			if(isset($settings['maxoccurs']))
				$this->MaxOccurs=$settings['maxoccurs'];
			if(isset($settings['docs']))
				$this->Docs=$settings['docs'];
		}
	}
	
	/**
	 * Create the WSDL
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreateElement($pw){
		PhpWsdl::Debug('Create WSDL definition for element '.$this->Name);
		$res="\t\t\t\t\t".'<s:element minOccurs="'.$this->MinOccurs.'" maxOccurs="'.$this->MaxOccurs.'" nillable="'.(($this->NillAble)?'true':'false').'" name="'.$this->Name.'" type="';
		$res.=PhpWsdl::TranslateType($this->Type).'"';
		if($pw->IncludeDocs&&!$pw->Optimize&&!is_null($this->Docs)){
			$res.='>'."\n";
			$res.="\t\t\t\t\t\t".'<s:annotation>'."\n";
			$res.="\t\t\t\t\t\t\t".'<s:documentation><![CDATA['.$this->Docs.']]></s:documentation>'."\n";
			$res.="\t\t\t\t\t\t".'</s:annotation>'."\n";
			$res.="\t\t\t\t\t".'</s:element>';
		}else{
			$res.=' />';
		}
		return $res;
	}
	
	/**
	 * Interpret a element keyword
	 * 
	 * @param array $data The parser data
	 * @return boolean Response
	 */
	public static function InterpretElement($data){
		$info=explode(' ',$data['keyword'][1],3);
		if(sizeof($info)<2)
			return true;
		$name=substr($info[1],1);
		if(substr($name,strlen($name)-1,1)==';')
			$name=substr($name,0,strlen($name)-1);
		PhpWsdl::Debug('Interpret element '.$name);
		if(sizeof($info)>2)
			$data['settings']['docs']=trim($info[2]);
		$data['elements'][]=new PhpWsdlElement($name,$info[0],$data['settings']);
		$data['settings']=Array();
		return false;
	}
}

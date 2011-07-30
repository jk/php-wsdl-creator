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

/**
 * A method definition object
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlMethod{
	/**
	 * The method name
	 * 
	 * @var string
	 */
	public $Name;
	/**
	 * A list of parameters
	 * 
	 * @var PhpWsdlParam[]
	 */
	public $Param=Array();
	/**
	 * The return value
	 * 
	 * @var PhpWsdlParam
	 */
	public $Return=null;
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
	 * @param PhpWsdlParam[] $param Optional the list of parameters (default: NULL)
	 * @param PhpWsdlParam $return Optional the return value (default: NULL)
	 * @param array $settings Optional the settings hash array (default: NULL)
	 */
	public function PhPWsdlMethod($name,$param=null,$return=null,$settings=null){
		$this->Name=$name;
		if(!is_null($param))
			$this->Param=$param;
		if(!is_null($return))
			$this->Return=$return;
		if(!is_null($settings))
			if(isset($settings['docs']))
				$this->Docs=$settings['docs'];
	}
	
	/**
	 * Create the port type WSDL
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreatePortType($pw){
		$res=Array();
		$res[]="\t\t".'<wsdl:operation name="'.$this->Name.'"';
		$o=sizeof($res)-1;
		$pLen=sizeof($this->Param);
		if($pLen>1){
			$temp=Array();
			$i=-1;
			while(++$i<$pLen)
				$temp[]=$this->Param[$i]->Name;
			$res[$o].=' parameterOrder="'.implode(' ',$temp).'"';
		}
		$res[$o].='>';
		if($pw->IncludeDocs&&!$pw->Optimize&&!is_null($this->Docs))
			$res[]="\t\t\t".'<wsdl:documentation><![CDATA['.$this->Docs.']]></wsdl:documentation>';
		$res[]="\t\t\t".'<wsdl:input message="tns:'.$this->Name.'SoapIn" />';
		$res[]="\t\t\t".'<wsdl:output message="tns:'.$this->Name.'SoapOut" />';
		$res[]="\t\t".'</wsdl:operation>';
		return implode("\n",$res);
	}
	
	/**
	 * Create the binding WSDL
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreateBinding($pw){
		$res=Array();
		$res[]="\t\t".'<wsdl:operation name="'.$this->Name.'">';
		$res[]="\t\t\t".'<soap:operation soapAction="'.$pw->NameSpace.$this->Name.'" />';
		$res[]="\t\t\t".'<wsdl:input>';
		$res[]="\t\t\t\t".'<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="'.$pw->NameSpace.'"';
		$pLen=sizeof($this->Param);
		if($pLen>0){
			$temp=Array();
			$i=-1;
			while(++$i<$pLen)
				$temp[]=$this->Param[$i]->Name;
			$res[sizeof($res)-1].=' parts="'.implode(' ',$temp).'"';
		}
		$res[sizeof($res)-1].=' />';
		$res[]="\t\t\t".'</wsdl:input>';
		$res[]="\t\t\t".'<wsdl:output>';
		$res[]="\t\t\t\t".'<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="'.$pw->NameSpace.'"';
		if(!is_null($this->Return))
			$res[sizeof($res)-1].=' parts="'.$this->Return->Name.'"';
		$res[sizeof($res)-1].=' />';
		$res[]="\t\t\t".'</wsdl:output>';
		$res[]="\t\t".'</wsdl:operation>';
		return implode("\n",$res);
	}
	
	/**
	 * Create the input/output messages WSDL
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreateMessages($pw){
		$pLen=sizeof($this->Param);
		$res=Array();
		if($pLen<1){
			$res[]="\t".'<wsdl:message name="'.$this->Name.'SoapIn" />';
		}else{
			$res[]="\t".'<wsdl:message name="'.$this->Name.'SoapIn">';
			$i=-1;
			while(++$i<$pLen)
				$res[]=$this->Param[$i]->CreatePart($pw);
			$res[]="\t".'</wsdl:message>';
		}
		if(is_null($this->Return)){
			$res[]="\t".'<wsdl:message name="'.$this->Name.'SoapOut" />';
		}else{
			$res[]="\t".'<wsdl:message name="'.$this->Name.'SoapOut">';
			$res[]=$this->Return->CreatePart($pw);
			$res[]="\t".'</wsdl:message>';
		}
		return implode("\n",$res);
	}

	/**
	 * Find a parameter of this method
	 * 
	 * @param string $name The parameter name
	 * @return PhpWsdlParam The parameter or NULL, if not found
	 */
	public function GetParam($name){
		$i=-1;
		$len=sizeof($this->Param);
		while(++$i<$len)
			if($this->Param[$i]->Name==$name)
				return $this->Param[$i];
		return null;
	}
}

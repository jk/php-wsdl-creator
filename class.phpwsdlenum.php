<?php

/*
PhpWsdl - Generate WSDL from PHP
Copyright (C) 2011  Andreas Müller-Saala, wan24.de 

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

PhpWsdl::RegisterHook('InterpretKeywordpw_enumHook','internal','PhpWsdlEnum::InterpretEnum');
PhpWsdl::RegisterHook('CreateObjectHook','internalenum','PhpWsdlEnum::CreateEnumTypeObject');

/**
 * This class creates enumerations
 * 
 * @author Andreas Müller-Saala, wan24.de
 */
class PhpWsdlEnum extends PhpWsdlObject{
	/**
	 * The enumeration base type
	 * 
	 * @var string
	 */
	public $Type;
	/**
	 * A list of elements
	 * 
	 * @var array
	 */
	public $Elements;
	/**
	 * An XML string encoding mapping for attributes
	 * 
	 * @var array
	 */
	public static $XmlAttributeEntities=Array(
		'&'			=>	'&amp;',
		'<'			=>	'&lt;',
		'>'			=>	'&gt;',
		'"'			=>	'&quot;'
	);
	
	/**
	 * Constructor
	 * 
	 * @param string $name The name
	 * @param string $type The type name
	 * @param array $el Optional a list of elements
	 * @param array $settings Optional the settings hash array (default: NULL)
	 */
	public function PhpWsdlEnum($name,$type,$el=Array(),$settings=null){
		PhpWsdl::Debug('New enumeration type '.$name.' of '.$type);
		if($type=='boolean')
			throw(new Exception('Boolean enumeration type is not valid'));
		parent::PhpWsdlObject($name,$settings);
		$this->Type=$type;
		$this->Elements=$el;
	}
	
	/**
	 * Create WSDL for the type
	 * 
	 * @param PhpWsdl $pw The PhpWsdl object
	 * @return string The WSDL
	 */
	public function CreateType($pw){
		PhpWsdl::Debug('Create WSDL definition for enumeration '.$this->Name.' as '.$this->Type);
		$res=Array();
		$res[]='<s:simpleType name="'.$this->Name.'">';
		if($pw->IncludeDocs&&!$pw->Optimize&&!is_null($this->Docs)){
			$res[]='<s:annotation>';
			$res[]='<s:documentation><![CDATA['.$this->Docs.']]></s:documentation>';
			$res[]='</s:annotation>';
		}
		$res[]='<s:restriction base="'.PhpWsdl::TranslateType($this->Type).'">';
		$i=-1;
		$len=sizeof($this->Elements);
		while(++$i<$len){
			$temp=explode('=',$this->Elements[$i],2);
			//TODO Is there really no common way to provide a label for an integer value f.e.?
			$res[]='<s:enumeration value="'.self::EncodeXmlAttribute($temp[0]).'" />';
		}
		$res[]='</s:restriction>';
		$res[]='</s:simpleType>';
		return implode('',$res);
	}
	
	/**
	 * Find an element within this type
	 * 
	 * @param string $value The value to search for
	 * @return mixed The element or NULL, if not found
	 */
	public function GetElement($value){
		PhpWsdl::Debug('Find element '.$value);
		$i=-1;
		$len=sizeof($this->Elements);
		while(++$i<$len)
			if($this->Elements[$i]==$value){
				PhpWsdl::Debug('Found element at index '.$i);
				return $this->Elements[$i];
			}
		return null;
	}
	
	/**
	 * Create the HTML documentation for an enumeration
	 * 
	 * @param array $data
	 */
	public function CreateTypeHtml($data){
		PhpWsdl::Debug('CreateTypeHtml for enumeration '.$data['type']->Name);
		$res=&$data['res'];
		$t=&$data['type'];
		$res[]='<h3>'.$t->Name.'</h3>';
		$res[]='<a name="'.$t->Name.'"></a>';
		if(!is_null($t->Docs))
			$res[]='<p>'.nl2br(htmlentities($t->Docs)).'</p>';
		$res[]='<p>Possible ';
		$o=sizeof($res)-1;
		if(in_array($t->Type,PhpWsdl::$BasicTypes)){
			$res[$o].='<span class="blue">'.$t->Type.'</span>';
		}else{
			$res[$o].='<a href="#'.$t->Type.'"><span class="lightBlue">'.$t->Type.'</span></a>';
		}
		$res[$o].=' values:</p>';
		$res[]='<ul class="pre">';
		$j=-1;
		$eLen=sizeof($t->Elements);
		while(++$j<$eLen)
			$res[]='<li>'.nl2br(htmlentities($this->Elements[$j])).'</li>';
		$res[]='</ul>';
		PhpWsdl::CallHook(
			'CreateTypeHtmlHook',
			$data
		);
	}
	
	/**
	 * Create enumeration PHP code
	 * 
	 * @param array $data The event data
	 */
	public function CreateTypePhp($data){
		$server=$data['server'];
		$res=&$data['res'];
		$res[]="/**";
		if(!is_null($this->Docs)){
			$res[]=" * ".implode("\n * ",explode("\n",$this->Docs));
			$res[]=" *";
		}
		$res[]=" * @pw_enum ".$this->Type." ".$this->Name." ".implode(',',$this->Elements);
		$res[]=" */";
		$res[]="abstract class ".$this->Name."{";
		$i=-1;
		$eLen=sizeof($this->Elements);
		while(++$i<$eLen){
			$temp=explode('=',$this->Elements[$i],2);
			if(sizeof($temp)==1) $temp[]=$temp[0];// Use the key as string value, if no value was given
			$res[]="\t/**";
			$res[]="\t * @var ".$this->Type;
			$res[]="\t */";
			$res[]="\tconst \$".$temp[0]."=\"".addslashes($temp[1])."\";";
		}
		$res[]="}";
	}
	
	/**
	 * Interpret an enumeration
	 * 
	 * @param array $data The parser data
	 * @return boolean Response
	 */
	public static function InterpretEnum($data){
		$info=explode(' ',$data['keyword'][1],4);
		if(sizeof($info)<3)
			return true;
		$server=$data['server'];
		$name=$info[1];
		PhpWsdl::Debug('Interpret enumeration "'.$name.'"');
		$type=$info[0];
		$el=explode(',',$info[2]);
		$docs=null;
		if($server->ParseDocs)
			if(sizeof($info)>3)
				$docs=$info[3];
		PhpWsdl::Debug('Enumeration "'.$name.'" type of "'.$type.'" definition');
		$data['type']=Array(
			'id'			=>	'enum',
			'name'			=>	$name,
			'type'			=>	$type,
			'elements'		=>	$el,
			'docs'			=>	$docs
		);
		return false;
	}
	
	/**
	 * Create enumeration object
	 * 
	 * @param array $data The parser data
	 * @return boolean Response
	 */
	public static function CreateEnumTypeObject($data){
		if($data['method']!='')
			return true;
		if(!is_null($data['obj']))
			return true;
		if(!is_array($data['type']))
			return true;
		if(!isset($data['type']['id']))
			return true;
		if($data['type']['id']!='enum')
			return true;
		if(!isset($data['type']['elements']))
			return true;
		if(!is_array($data['type']['elements']))
			return true;
		if(!is_null($data['docs'])){
			$data['settings']['docs']=$data['docs'];
		}else{
			$data['settings']['docs']=$data['type']['docs'];
		}
		PhpWsdl::Debug('Add enumeration '.$data['type']['name']);
		$data['obj']=new PhpWsdlEnum($data['type']['name'],$data['type']['type'],$data['type']['elements'],$data['settings']);
		$data['settings']=Array();
		$data['server']->Types[]=$data['obj'];
		return true;
	}
	
	/**
	 * Encode a string for use as XML attribute
	 * 
	 * @param string $str The string
	 * @return string The encoded string
	 */
	private static function EncodeXmlAttribute($str){
		$keys=array_keys(self::$XmlAttributeEntities);
		$i=-1;
		$len=sizeof($keys);
		while(++$i<$len)
			$str=str_replace($keys[$i],self::$XmlAttributeEntities[$keys[$i]],$str);
		return $str;
	}
}

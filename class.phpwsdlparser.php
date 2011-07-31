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
 * This class will parse the WSDL definitions from PHP source (or any other string)
 * 
 * @author Andreas Zimmermann, wan24.de
 */
class PhpWsdlParser{
	/**
	 * The PhpWsdl object
	 * 
	 * @var PhpWsdl
	 */
	public $Server;
	/**
	 * Regular expression to parse the relevant data from a string
	 * 1: Comment block
	 * 3: Method name
	 * 
	 * @var string
	 */
	public static $ParseRelevantRx='/\/\*\*([^\*]*\*+(?:[^\*\/][^\*]*\*+)*)\/(\s*public\s+function\s+([^\s|\(]+)\s*\()?/is';
	/**
	 * Regular expression to parse keywords from a string
	 * 1: Whole line
	 * 2: Keyword
	 * 3: Parameters
	 * 
	 * @var string
	 */
	public static $ParseKeywordsRx='/^(\s*\*\s*\@([^\s|\n]+)([^\n]*))$/im';
	/**
	 * Regular expression to parse the documentation from the bottom of a comment block string
	 * 1: Documentation
	 * 
	 * @var string
	 */
	public static $ParseDocsRx='/^[^\*|\n]*\*[ |\t]+([^\*|\s|\@|\/|\n][^\n]*)?$/im';
	
	/**
	 * Constructor
	 * 
	 * @param PhpWsdl $server The PhpWsdl object
	 */
	public function PhpWsdlParser($server){
		$this->Server=$server;
	}
	
	/**
	 * Parse a string
	 * 
	 * @param string $str The string to parse
	 */
	public function Parse($str){
		if(!PhpWsdl::CallHook(
				'BeforeParseHook',
				Array(
					'sender'		=>	$this,
					'server'		=>	$this->Server,
					'str'			=>	&$str
				)
			)
		)
			return;
		// Match all relevant strings
		$defs=Array();
		preg_match_all(PhpWsdlParser::$ParseRelevantRx,$str,$defs);
		$i=-1;
		$len=sizeof($defs[0]);
		while(++$i<$len){
			$def=$defs[1][$i];
			$method=$defs[3][$i];
			// Parse keywords
			$keywords=Array();
			$temp=Array();
			preg_match_all(PhpWsdlParser::$ParseKeywordsRx,$def,$temp);
			$j=-1;
			$tLen=sizeof($temp[0]);
			while(++$j<$tLen)
				$keywords[]=Array(
					$temp[2][$j],
					trim($temp[3][$j])
				);
			// Parse documentation
			if($this->Server->ParseDocs){
				$docs=Array();
				$temp=Array();
				preg_match_all(PhpWsdlParser::$ParseDocsRx,$def,$temp);
				$j=-1;
				$tLen=sizeof($temp[0]);
				while(++$j<$tLen)
					$docs[]=trim($temp[1][$j]);
				$docs=trim(implode("\n",$docs));
				if($docs=='')
					$docs=null;
			}else{
				$docs=null;
			}
			// Create objects
			$this->InterpretDefinition($def,$method,$keywords,$docs);
		}
		PhpWsdl::CallHook(
			'AfterParseHook',
			Array(
				'sender'		=>	$this,
				'server'		=>	$this->Server,
				'str'			=>	&$str
			)
		);
	}
	
	/**
	 * Interpret a WSDL definition
	 * 
	 * @param string $def WSDL definition
	 * @param string $method Method name
	 */
	public function InterpretDefinition($def,$method,$keywords,$docs){
		if(!PhpWsdl::CallHook(
				'BeforeInterpretDefinitionHook',
				Array(
					'sender'		=>	$this,
					'server'		=>	$this->Server,
					'def'			=>	&$def,
					'method'		=>	&$method,
					'keywords'		=>	&$keywords,
					'docs'			=>	&$docs
				)
			)
		)
			return null;
		// Initialize some variables
		$param=Array();		// List ob parameter objects
		$return=null;		// The return value object
		$elements=Array();	// List of element objects
		$settings=Array();	// Settings hash
		$omit=false;		// Omit the object
		$type=null;			// Type identifier
		$buffer=Array();	// Other data
		// Interpret keywords
		$i=-1;
		$len=sizeof($keywords);
		while(++$i<$len){
			$keyword=$keywords[$i];
			// Call the global keyword handler
			if(!PhpWsdl::CallHook(
					'InterpretKeywordHook',
					Array(
						'sender'		=>	$this,
						'server'		=>	$this->Server,
						'def'			=>	&$def,
						'method'		=>	&$method,
						'keywords'		=>	&$keywords,
						'docs'			=>	&$docs,
						'param'			=>	&$param,
						'elements'		=>	&$elements,
						'return'		=>	&$return,
						'settings'		=>	&$settings,
						'omit'			=>	&$omit,
						'keyword'		=>	&$keyword,
						'type'			=>	&$type,
						'buffer'		=>	&$buffer,
						'newkeyword'	=>	&$newkeyword
					)
				)
			)
				continue;
			if($omit)
				return null;
			// Call the keyword handler
			if(!PhpWsdl::CallHook(
					'InterpretKeyword'.$keyword[0].'Hook',
					Array(
						'sender'		=>	$this,
						'server'		=>	$this->Server,
						'def'			=>	&$def,
						'method'		=>	&$method,
						'keywords'		=>	&$keywords,
						'docs'			=>	&$docs,
						'param'			=>	&$param,
						'elements'		=>	&$elements,
						'return'		=>	&$return,
						'settings'		=>	&$settings,
						'omit'			=>	&$omit,
						'keyword'		=>	&$keyword,
						'type'			=>	&$type,
						'buffer'		=>	&$buffer,
						'newkeyword'	=>	&$newkeyword
					)
				)
			)
				continue;
			if($omit)
				return null;
		}
		// Create object
		$obj=null;
		if(!PhpWsdl::CallHook(
				'CreateObjectHook',
				Array(
					'sender'		=>	$this,
					'server'		=>	$this->Server,
					'def'			=>	&$def,
					'method'		=>	&$method,
					'keywords'		=>	&$keywords,
					'docs'			=>	&$docs,
					'param'			=>	&$param,
					'elements'		=>	&$elements,
					'return'		=>	&$return,
					'settings'		=>	&$settings,
					'omit'			=>	&$omit,
					'type'			=>	&$type,
					'buffer'		=>	&$buffer,
					'obj'			=>	&$obj
				)
			)
		)
			return null;
		if(!PhpWsdl::CallHook(
				'AfterInterpretDefinitionHook',
				Array(
					'sender'		=>	$this,
					'server'		=>	$this->Server,
					'def'			=>	&$def,
					'method'		=>	&$method,
					'keywords'		=>	&$keywords,
					'docs'			=>	&$docs,
					'param'			=>	&$param,
					'elements'		=>	&$elements,
					'return'		=>	&$return,
					'settings'		=>	&$settings,
					'omit'			=>	&$omit,
					'type'			=>	&$type,
					'buffer'		=>	&$buffer,
					'obj'			=>	&$obj
				)
			)
		)
			return null;
		return $obj;
	}
}

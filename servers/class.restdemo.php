<?php

/**
 * This is a demo object and the webservice handler class at the same time
 * 
 * @pw_element int $aId The ID
 * @pw_element string $aValue The value
 * @pw_complex RestDemo This is a demo object and the webservice handler class at the same time
 */
class RestDemo{
	/**
	 * The ID
	 * 
	 * @var int
	 */
	public $aId=0;
	/**
	 * The value
	 * 
	 * @var string
	 */
	public $aValue='aValue';
	
	/**
	 * Get the first object
	 * 
	 * @return RestDemo The first object
	 * @pw_rest GET /objects/first Get the first object
	 */
	public function GetFirstObject(){
		$ids=$this->GetObjectIds();
		if(sizeof($ids)<1)
			return null;
		return $this->GetObject($ids[0]);
	}
	
	/**
	 * Get the last object
	 * 
	 * @return RestDemo The last object
	 * @pw_rest GET /objects/last Get the last object
	 */
	public function GetLastObject(){
		$ids=$this->GetObjectIds();
		if(sizeof($ids)<1)
			return null;
		return $this->GetObject($ids[sizeof($ids)-1]);
	}
	
	/**
	 * Get a list ob object IDs
	 * 
	 * @return arrayOfInt A list of object IDs
	 * @pw_rest GET /objects Get a list ob object IDs
	 */
	public function GetObjectIds(){
		return Array(1,2,3);
	}
	
	/**
	 * Get an object
	 * 
	 * @param int $id The object ID
	 * @return RestDemo The object
	 * @pw_rest GET /objects/:id Get an object
	 */
	public function GetObject($id=null){
		if(!in_array($id,$this->GetObjectIds()))
			return null;
		$obj=new RestDemo();
		$obj->aId=$id;
		return $obj;
	}
	
	/**
	 * Print an object
	 * 
	 * @param RestDemo $obj The object
	 * @return string The result of print_r
	 * @pw_rest GET /object/print/:obj Print an object
	 */
	public function PrintObject($obj=null){
		return utf8_encode(print_r($obj,true));
	}
}

/**
 * An array of int
 * 
 * @pw_complex arrayOfInt[] int An array of int
 */

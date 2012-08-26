<?php

if(basename($_SERVER['SCRIPT_FILENAME'])==basename(__FILE__))
	exit;

// The @pw_element and @pw_complex are non-standard keywords for documentaion 
// I had to define to support those complex types for WSDL generation. The  
// "pw" stands for "PhpWsdl". But who cares?

/**
 * This is how to define a complex type f.e. - the class ComplexTypeDemo doesn't need to exists, 
 * but it would make it easier for you to return that complex type from a method
 *
 * @pw_element string $StringA A string with a value
 * @pw_element string $StringB A string with a NULL value
 * @pw_element int $Integer An integer
 * @pw_element boolean $Boolean A boolean
 * @pw_element DemoEnum $Enum An enumeration
 * @pw_complex ComplexTypeDemo The complex type name definition
 */
class ComplexTypeDemo{
	/**
	 * A string
	 * 
	 * @var string
	 */
	public $StringA='String A';
	/**
	 * Another string
	 * 
	 * @var string
	 */
	public $StringB=null;
	/**
	 * An integer
	 * 
	 * @var int
	 */
	public $Integer=123;
	/**
	 * A boolean
	 * 
	 * @var boolean
	 */
	public $Boolean=true;
	/**
	 * An enumeration
	 * 
	 * @var DemoEnum
	 */
	public $Enum;
	
	/**
	 * The constructor
	 * 
	 * @ignore
	 */
	public function ComplexTypeDemo(){
		$this->Enum=DemoEnum::ValueB;
	}
}

// Now a demonstration how to implement inherited complex types:

/**
 * This complex type inherits all properties of ComplexTypeDemo
 * 
 * @pw_element string $AdditionalString An additional string
 * @pw_set inherit=ComplexTypeDemo <- To tell PhpWsdl about the base type
 * @pw_complex ComplexTypeDemoB The complex type name definition
 */
class ComplexTypeDemoB extends ComplexTypeDemo{
	/**
	 * An additional string
	 * 
	 * @var string
	 */
	public $AdditionalString='';
	
	/**
	 * The constructor
	 * 
	 * @ignore
	 */
	public function ComplexTypeDemoB(){
		parent::ComplexTypeDemo();
	}
}

// You can also create array types as complex type. Here for the string type and the ComplexTypeDemo complex type.
// As you can see you simply need to add "Array" to the name of the type. Not one line of code.

/**
 * @pw_complex stringArray A string array type
 */
/**
 * @pw_complex ComplexTypeDemoBArray An array of ComplexTypeDemoB
 */

// But you may also create an array without any name restrictions. To use the arrayOfInt[] finally, use the type 
// name without the "[]" (that's only required for parsing the correct target type)

/**
 * @pw_complex arrayOfInt[] int An int array type
 */

// This is how to implement an enumeration with the @pw_enum keyword. An enumeration needs a type, a name 
// and a comma seperated list of enumerateable values of the type. You should only use types that can be interpreted 
// when placing them between single or double quotes (string, int, float, ...). String values can't include a comma 
// because this is the list seperator.

/**
 * This is how to define an enumeration. You don't need the class DemoEnum - it's just to demonstrate how 
 * I handle enumerations in PHP.
 * 
 * @pw_enum string DemoEnum ValueA=ValueA,ValueB=ValueB,ValueC=ValueC A sample enumeration
 */
abstract class DemoEnum{
	/**
	 * A description for ValueA
	 * 
	 * @var string
	 */
	const ValueA='ValueA';
	/**
	 * A description for ValueB
	 * 
	 * @var string
	 */
	const ValueB='ValueB';
	/**
	 * A description for ValueC
	 * 
	 * @var string
	 */
	const ValueC='ValueC';
	
	/**
	 * Constructor that will throw an exception because you can't instance an enumeration
	 * 
	 * @ignore
	 */
	public function DemoEnum(){
		throw(new Exception('This is an enumeration - instances are not supported!'));
	}
}

// This complex type will be used as exception type for all methods

/**
 * This is the exception type for all methods
 * 
 * @pw_element string $message The message
 * @pw_element int $code The code
 * @pw_element string $file The file name
 * @pw_element int $line The line number
 * @pw_complex SoapFault A complex type describing the SoapFault exception
 */

<?php

// This demonstrates the usage of the http server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.
//
// Per default parameter types that are not contained in the 
// PhpWsdl::$BasicTypes array needs JSON encoding. The same is for the return 
// value: If its type isn't declared as basic type, it'll be JSON encoded.
// To implement a different handling, use the HttpParametersHook and the 
// HttpResponseHook (see class.phpwsdl.servers.php -> HandleHttpRequest) and 
// return FALSE in your handler methods to prevent the default handling.

// This is the URI to the http server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

echo '<pre>';

// This will call the method "SayHello"
$param=Array();
$param['call']='SayHello';	// The method name
$param['name']='you';		// The parameter "name"
$res=file_get_contents($endPoint.'?'.encodeParam($param));
echo "SayHello:\n".htmlentities($res)."\n";

// This will call the method "GetComplexType"
$param=Array();
$param['call']='GetComplexType';
$res=file_get_contents($endPoint.'?'.encodeParam($param));
$res=json_decode($res);
echo "\nGetComplexType:\n".htmlentities(print_r($res,true))."\n";

// This will call the method "PrintComplexType"
$param=Array();
$param['call']='PrintComplexType';
$param['obj']=json_encode($res);
$res=file_get_contents($endPoint.'?'.encodeParam($param));
echo "\nPrintComplexType:\n".htmlentities($res)."\n";

echo '</pre>';
exit;

// Encode parameters for the http request
function encodeParam($param){
	$temp=Array();
	$keys=array_keys($param);
	$i=-1;
	$len=sizeof($keys);
	while(++$i<$len)
		$temp[]=urlencode($keys[$i]).'='.urlencode($param[$keys[$i]]);
	$param=implode('&',$temp);
	return $param;
}

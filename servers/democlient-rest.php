<?php

// This demonstrates the usage of the REST server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.
//
// Per default parameter types that are not contained in the 
// PhpWsdl::$BasicTypes array needs JSON encoding. The same is for the return 
// value: If its type isn't declared as basic type, it'll be JSON encoded.
// To implement a different handling, use the RestParametersHook and the 
// RestResponseHook (see class.phpwsdl.servers.php -> HandleRestRequest) and 
// return FALSE in your handler methods to prevent the default handling.

// This is the URI to the http server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

// Call the method "SayHello"
$rest='/SayHello/you';// Note that parameters in the rest path have to be URL encoded!
echo '<pre>';
echo "SayHello:\n";
echo htmlentities(file_get_contents($endPoint.$rest))."\n";
echo "\n";

// Call the method "GetComplexType"
$rest='/GetComplexType';
$temp=json_decode(file_get_contents($endPoint.$rest));
echo "GetComplexType:\n";
echo htmlentities(print_r($temp,true))."\n";
echo "\n";

// Call the method "PrintComplexType"
$rest='/PrintComplexType/'.urlencode(json_encode($temp));
echo "PrintComplexType:\n";
echo htmlentities(file_get_contents($endPoint.$rest));
echo '</pre>';
exit;

<?php

// This demonstrates the usage of the JSON server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// This is the URI to the JSON server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

echo '<pre>';

// This will call the method "SayHello"
$req=Array(
	'call'=>'SayHello',	// The method name to call
	'param'=>Array(		// The parameters array for the method call
		'you'
	)
);
$res=file_get_contents($endPoint.'?json='.urlencode(json_encode($req)));
$res=json_decode($res);
echo "SayHello:\n".htmlentities($res)."\n";

// This will call the method "GetComplexType"
$req=Array(
	'call'=>'GetComplexType',
	'param'=>Array()
);
$res=file_get_contents($endPoint.'?json='.urlencode(json_encode($req)));
$res=json_decode($res);
echo "\nGetComplexType:\n".htmlentities(print_r($res,true))."\n";

// This will call the method "PrintComplexType"
$req=Array(
	'call'=>'PrintComplexType',
	'param'=>Array(
		$res
	)
);
$res=file_get_contents($endPoint.'?json='.urlencode(json_encode($req)));
$res=json_decode($res);
echo "\nPrintComplexType:\n".htmlentities($res)."\n";

echo '</pre>';
exit;

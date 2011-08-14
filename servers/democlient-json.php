<?php

// This demonstrates the usage of the JSON server. It requires the 
// PhpWsdl framework files to be in the same folder as this file.

// This is the URI to the JSON server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver.php';

// This will call the method "SayHello"
$req=Array(
	'call'=>'SayHello',	// The method name to call
	'param'=>Array(		// The parameters for the method call
		'you'
	)
);

// Call the webservice
$res=file_get_contents($endPoint.'?json='.urlencode(json_encode($req)));

// Display the result and quit
echo htmlentities(json_decode($res));
exit;

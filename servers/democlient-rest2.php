<?php

// This demonstration uses the demoserver2.php that is demonstrating 
// specially the REST usage.

// This is the URI to the http server
$endPoint=((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/demoserver2.php';

// Call the method "GetObjectIds"
$rest='/objects';
echo '<pre>';
echo "GetObjectIds:\n";
$ids=json_decode(file_get_contents($endPoint.$rest));
echo htmlentities(print_r($ids,true))."\n";
echo "\n";

// Call the method "GetObject"
$rest='/objects/'.$ids[1];
echo "GetObject:\n";
$temp=json_decode(file_get_contents($endPoint.$rest));
echo htmlentities(print_r($temp,true))."\n";
echo "\n";

// Call the method "GetFirstObject"
$rest='/objects/first';
echo "GetFirstObject:\n";
$temp=json_decode(file_get_contents($endPoint.$rest));
echo htmlentities(print_r($temp,true))."\n";
echo "\n";

// Call the method "GetLastObject"
$rest='/objects/last';
echo "GetLastObject:\n";
$temp=json_decode(file_get_contents($endPoint.$rest));
echo htmlentities(print_r($temp,true))."\n";
echo "\n";

// Call the method "PrintObject"
$temp->aValue='test';
$rest='/object/print/'.urlencode(json_encode($temp));
echo "PrintObject:\n";
echo htmlentities(file_get_contents($endPoint.$rest))."\n";

// Quit
echo '</pre>';
exit;

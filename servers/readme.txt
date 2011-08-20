This PhpWsdl extension enables you to run a JSON, http, REST and XML RPC 
server from one codebase without having to make too much changes. Of course 
the SOAP server will still work, too. All you have to do is to load the 
extension at last. Changes to your code are only required if you want to 
implement a special behavoir of the REST server for example. But everything 
should run without any code modifications, too.

The extension itself is in the file "class.phpwsdl.servers.php". If you want 
to use the JSON webservice, you should also include the file 
"class.phpwsdl.servers-jspacker.php" in the framework folder. Any other file 
in this package is for demonstration purposes only.

You can find the demonstrations that are included in this package here:

http://wan24.de/test/phpwsdl2/demo*.php/html

Please note that they may be down sometimes because I'm testing something 
during development.

These demonstrations are included:

- class.restdemo.php
	A sample webservice that contains special REST server definitions but is 
	also available with SOAP, JSON, XML RPC and http by accessing 
	demoserver2.php

- democlient-http.php
	A sample http client that uses demoserver.php as http webservice

- democlient-json.html
	A sample JSON JavaScript client that uses demoserver.php as JSON webservice

- democlient-json.php
	A sample JSON client that uses demoserver.php as JSON webservice

- democlient-rest.php
	A sample REST client that uses demoserver.php as REST webservice

- democlient-rest2.php
	A sample REST client that uses demoserver2.php as REST webservice

- democlient-rpc.php
	A sample XML RPC client that uses demoserver.php as XML RPC webservice

- demoserver.php
	This server loads the PhpWsdlServers extension and uses the PhpWsdl 
	default demo classes "class.complextypedemo.php" and "class.soapdemo.php" 
	as webservice

- demoserver2.php
	This server loads the PhpWsdlServers extension and uses class.restdemo.php 
	as webservice to demonstrate the usage of the @pw_rest keyword to define 
	the behavoir as REST webservice that may serve different features as other 
	services

If you want to use the PhpWsdlServers extension in your applications, use the 
PhpWsdl framework and simply copy the files "class.phpwsdl.servers.php" and 
"class.phpwsdl.servers-jspacker.php" into its folder. Have a look at 
"democlient-http.php" f.e. how to ensure that the PhpWsdlServers extension was 
loaded.

Read the readme*.txt files, too, for more documentation about the servers.

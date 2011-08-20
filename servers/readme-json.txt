PhpWsdlServers supports running a JSON webservice which may have the best 
compatibility to a lot of clients. The JSON format is thin and easy to create 
or parse. All parameters of a request are JSON encoded, the return value will 
be JSON encoded, too. The webservice can take arguments with the GET or POST 
http method.

Since browsers include native JSON support, it was never be easier to consume 
a JSON webservice with a JavaScript client. PHP has native JSON methods, too. 
PhpWsdlServers can produce a JavaScript JSON client proxy class that can be 
used in your application:

	http://your-server.com/webservice.php?JSJSONCLIENT

This JavaScript code may be compressed by a JavaScript packer. To use 
compressed JavaScript, use this URI:

	http://your-server.com/webservice.php?JSJSONCLIENT&min

As you can see you only have to add "&min" to the URI. See 
democlient-json.html for a demonstration how to use the JavaScript JSON client 
proxy class.

If the file "class.phpwsdlservers-jspacker.php" is not contained in the 
PhpWsdl framework folder, JavaScript compression won't be available.

The JavaScript JSON client proxy class uses AJAX synchron or asynchron - it 
depends on how you call the proxy methods: If you set a callback method as the 
last parameter, the request will be asynchron and your callback method will 
get the JSON decoded return value as single parameter. A missing callback 
method will cause a synchron request. If you have no callback method, but you 
want to send asynchron AJAX requests, simply provide an empty function as 
callback method:

	client.DemoMethod(function(res){});

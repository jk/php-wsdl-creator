With PhpWsdl you can run a simple REST server. Without any modifications of 
your existing code, every method that is accessable with SOAP can be accessed 
with REST, too. For example an REST URI would look like this:

	http://your-server.com/webservice.php/method/parameter1/parameter2/...
	
The URI is case sensitive. The first part after your webservices script name 
is always the method name, followed by the parameters of that method. Please 
be sure to submit all required parameters. Not required are parameters that 
have a default value in their method declaration. Parameters with a type that 
is defined in the PhpWsdl::$BasicTypes list don't need encoding (URL encoding 
is of course required everytime!). All other types must be JSON encoded. The 
last parameter may be submitted in the POST http request body, if there are 
more than one parameters. The return value will be JSON encoded, too, if its 
type isn't a basic type.

To change the REST accessibility of your methods, use the @pw_rest keyword 
additional to the default declarations in comment blocks. The syntax:

	@pw_rest [method] [uri] A single line description

[method] can be any REST method (GET, POST, DELETE, ...). If you have no way 
to modify the http header of your REST request, you can set the method by 
adding "?method=[method]" to the request URI. [uri] is the path to the REST 
method. The URI may contain parameter definitions:

	@pw_rest GET /Name/:Param1/:Param2

A full example with two methods:

/**
 * Get a list of object IDs
 *
 * @return arrayOfInt A list of available object IDs
 * @pw_rest GET /objects Get a list of object IDs
 */
function GetObjectIds(){
	...
}

/**
 * Get an object
 *
 * @param int $id The object ID
 * @return objectType The requested object
 * @pw_rest GET /objects/:id Get an object
 */
function GetObject($id){
	...
}

Those two methods can be accessed by this URIs:

	http://your-server.com/webservice.php/objects
	http://your-server.com/webservice.php/objects/123

The order of the methods is very important because the method GetObjectIds 
would never be reached, if it wasn't defined at first. These two URIs are 
valid, too - even if they're not declared with the @pw_rest keyword:

	http://your-server.com/webservice.php/GetObjectIds
	http://your-server.com/webservice.php/GetObject/123
	
A working demonstration can be found in demoserver2.php, class.restdemo.php 
and democlient-rest2.php.

Please note that my REST implementation is VERY BASIC and doesn't make it 
possible to do a lot of things REST was made for. If you really want to 
create a professional REST webservice, I suggest you to have a look at the 
Recess project (http://www.recessframework.org/) that is really powerful 
(RESTful ;) and would be my first choice for a bigger project.
My REST implementation is for just having a simple REST-looking API with very 
basic REST features - not more.

PhpWsdlServers supports running a XML RPC webservice. With a PHP client you 
may use the native xmlrpc_decode method to decode the server response. In this 
case you should notice that the xmlrpc_decode method will decode complex type 
objects as array. All other demonstration clients will decode them as object.
At the client side I see no way to consume objects instead of arrays. If you 
need objects, you have to recode the arrays manually.

The same for the XML RPC server: The handler method would receive an object 
parameter as hash array normally. But since PhpWsdlServers knows if the method 
wants an object, object parameters will be recoded into objects at the server 
side before calling the webservice handler method.

If you don't want PhpWsdlServers to recode object parameters, set the 
PhpWsdlServers::$NoRpcRecode to TRUE.

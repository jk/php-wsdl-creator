PhpWsdlServers supports running a XML RPC webservice. With a PHP client you 
may use the native xmlrpc_decode method to decode the server response. In this 
case you should notice that the xmlrpc_decode method will decode complex type 
objects as array. All other demonstration clients will decode them as object.

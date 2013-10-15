<?php

namespace nx\core;

/**
 * The Dispatcher handles incoming HTTP requests and sends back responses.
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2012 Nick Sinopoli
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 */
class Dispatcher {

   /**
    * The configuration settings.
    *
    * @var array
    */
    protected $_config = array();

   /**
    * Sets the configuration options for the dispatcher.
    *
    * @param array $config    The configuration options.
    * @return void
    */
    public function __construct(array $config = array()) {
        $defaults = array(
            'response' => new \nx\core\Response(),
            'router'   => new \nx\core\Router()
        );
        $this->_config = $config + $defaults;
    }

   /**
    * Matches an incoming request with the supplied routes, calls the
    * callback associated with the matched route, and sends a response.
    *
    * @param object $request    The incoming request object.
    * @param array $routes      The routes.
    * @return void
    */
    public function handle($request, $routes) {
        $method = $request->request_method;

        $router = $this->_config['router'];
        $parsed = $router->parse($request->url, $method, $routes);

        if ( $parsed['callback'] ) {
            $request->params = $parsed['params'];
            $result = call_user_func($parsed['callback'], $request);
        } else {
            $result = false;
        }

        $response = $this->_config['response'];
        $response->render($result);
    }

}

?>
<?php

namespace nx\core;

/**
 * The Request class is used to handle all data pertaining to an incoming HTTP
 * request.
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2012 Nick Sinopoli
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 */
class Request {

   /**
    * The POST/PUT/DELETE data.
    *
    * @var array
    */
    public $data = array();

   /**
    * The environment variables.
    *
    * @var array
    */
    protected $_env = array();

   /**
    * The GET data.
    *
    * @var array
    */
    public $query = array();

   /**
    * The parameters parsed from the request url.
    *
    * @var array
    */
    public $params;

   /**
    * The url of the request.
    *
    * @var string
    */
    public $url;

   /**
    * Sets the configuration options.
    *
    * @param array $config    The configuration options.  Possible keys
    *                         include:
    *                         'data' - the POST/PUT/DELETE data
    *                         'query' - the GET data
    * @return void
    */
    public function __construct(array $config = array()) {
        $defaults = array(
            'data'  => array(),
            'query' => array()
        );

        $config += $defaults;

        $this->_env = $_SERVER + $_ENV + array(
            'CONTENT_TYPE'   => 'text/html',
            'REQUEST_METHOD' => 'GET'
        );

        if ( isset($this->_env['SCRIPT_URI']) ) {
            $this->_env['HTTPS'] =
                ( strpos($this->_env['SCRIPT_URI'], 'https://') === 0 );
        } elseif ( isset($this->_env['HTTPS']) ) {
            $this->_env['HTTPS'] = (
                !empty($this->_env['HTTPS']) && $this->_env['HTTPS'] !== 'off'
            );
        } else {
            $this->_env['HTTPS'] = false;
        }

        $parsed = parse_url($this->_env['REQUEST_URI']);

        $base = '/' . ltrim(
          str_replace('\\', '/', dirname($this->_env['PHP_SELF'])),
        '/');
        $base = rtrim(str_replace('/app/public', '', $base), '/');
        $pattern = '/^' . preg_quote($base, '/') . '/';
        $this->url = '/' . trim(
            preg_replace($pattern, '', $parsed['path']),
        '/');

        $query = array();
        if ( isset($parsed['query']) ) {
            $query_string = str_replace('%20', '+', $parsed['query']);
            $pairs = explode('&', $query_string);
            foreach ( $pairs as $pair ) {
                list($k, $v) = array_map('urldecode', explode('=', $pair));
                $query[$k] = $v;
            }
        }
        $this->query = $config['query'] + $query;

        $this->data = $config['data'];
        if ( isset($_POST) ) {
            $this->data += $_POST;
        }

        $override ='HTTP_X_HTTP_METHOD_OVERRIDE';
        if ( isset($this->data['_method']) ) {
            $this->_env[$override] = strtoupper($this->data['_method']);
            unset($this->data['_method']);
        }
        if ( !empty($this->_env[$override]) ) {
            $this->_env['REQUEST_METHOD'] = $this->_env[$override];
        }

        $method = strtoupper($this->_env['REQUEST_METHOD']);

        if ( $method == 'PUT' || $method == 'DELETE' ) {
            $stream = fopen('php://input', 'r');
            parse_str(stream_get_contents($stream), $this->data);
            fclose($stream);
        }

    }

   /**
    * Returns an environment variable.
    *
    * @param string $key    The environment variable.
    * @return mixed
    */
    public function __get($key) {
        $key = strtoupper($key);
        return ( isset($this->_env[$key]) ) ? $this->_env[$key] : null;
    }

   /**
    * Checks for request characteristics.
    *
    * The full list of request characteristics is as follows:
    *
    * * 'ajax' - XHR
    * * 'delete' - DELETE REQUEST_METHOD
    * * 'flash' - "Shockwave Flash" HTTP_USER_AGENT
    * * 'get' - GET REQUEST_METHOD
    * * 'head' - HEAD REQUEST_METHOD
    * * 'mobile'  - any one of the following HTTP_USER_AGENTS:
    *
    * 1. 'Android'
    * 1. 'AvantGo'
    * 1. 'Blackberry'
    * 1. 'DoCoMo'
    * 1. 'iPod'
    * 1. 'iPhone'
    * 1. 'J2ME'
    * 1. 'NetFront'
    * 1. 'Nokia'
    * 1. 'MIDP'
    * 1. 'Opera Mini'
    * 1. 'PalmOS'
    * 1. 'PalmSource'
    * 1. 'Plucker'
    * 1. 'portalmmm'
    * 1. 'ReqwirelessWeb'
    * 1. 'SonyEricsson'
    * 1. 'Symbian'
    * 1. 'UP.Browser'
    * 1. 'Windows CE'
    * 1. 'Xiino'
    *
    * * 'options' - OPTIONS REQUEST_METHOD
    * * 'post'    - POST REQUEST_METHOD
    * * 'put'     - PUT REQUEST_METHOD
    * * 'ssl'     - HTTPS
    *
    * @param string $characteristic    The characteristic.
    * @return bool
    */
    public function is($characteristic) {
        switch ( strtolower($characteristic) ) {
            case 'ajax':
                return (
                    $this->http_x_requested_with == 'XMLHttpRequest'
                );
            case 'delete':
                return ( $this->request_method == 'DELETE' );
            case 'flash':
                return (
                    $this->http_user_agent == 'Shockwave Flash'
                );
            case 'get':
                return ( $this->request_method == 'GET' );
            case 'head':
                return ( $this->request_method == 'HEAD' );
            case 'mobile':
                $mobile_user_agents = array(
                    'Android', 'AvantGo', 'Blackberry', 'DoCoMo', 'iPod',
                    'iPhone', 'J2ME', 'NetFront', 'Nokia', 'MIDP', 'Opera Mini',
                    'PalmOS', 'PalmSource', 'Plucker', 'portalmmm',
                    'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\.Browser',
                    'Windows CE', 'Xiino'
                );
                $pattern = '/' . implode('|', $mobile_user_agents) . '/i';
                return (boolean) preg_match(
                    $pattern, $this->http_user_agent
                );
            case 'options':
                return ( $this->request_method == 'OPTIONS' );
            case 'post':
                return ( $this->request_method == 'POST' );
            case 'put':
                return ( $this->request_method == 'PUT' );
            case 'ssl':
                return $this->https;
            default:
                return false;
        }
    }

}

?>
<?php

namespace nx\core;

/**
 * The Response class is used to render an HTTP response.
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2012 Nick Sinopoli
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 */
class Response {

   /**
    * The configuration settings.
    *
    * @var array
    */
    protected $_config = array();

   /**
    *  The HTTP status codes.
    *
    *  @var array
    */
    protected $_statuses = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out'
    );

   /**
    * Sets the configuration options.
    *
    * Possible keys include the following:
    *
    * * 'buffer_size' - The number of bytes each chunk of output should contain
    *
    * @param array $config    The configuration options.
    * @return void
    */
    public function __construct(array $config = array()) {
        $defaults = array(
            'buffer_size'  => 8192
        );
        $this->_config = $config + $defaults;
    }

   /**
    * Converts an integer status to a well-formed HTTP status header.
    *
    * @param int $code    The integer associated with the HTTP status.
    * @return string
    */
    protected function _convert_status($code) {
        if ( isset($this->_statuses[$code]) ) {
            return "HTTP/1.1 {$code} {$this->_statuses[$code]}";
        }
        return "HTTP/1.1 200 OK";
    }

   /**
    * Parses a response.
    *
    * @param mixed $response    The response to be parsed.  Can be an array
    *                           containing 'body', 'headers', and/or 'status'
    *                           keys, or a string which will be used as the
    *                           body of the response.  Note that the headers
    *                           must be well-formed HTTP headers, and the
    *                           status must be an integer (i.e., the one
    *                           associated with the HTTP status code).
    * @return array
    */
    protected function _parse($response) {
        $defaults = array(
            'body'    => '',
            'headers' => array('Content-Type: text/html; charset=utf-8'),
            'status'  => 200
        );
        if ( is_array($response) ) {
            $response += $defaults;
        } elseif ( is_string($response) ) {
            $defaults['body'] = $response;
            $response = $defaults;
        } else {
            $defaults['status'] = 500;
            $response = $defaults;
        }
        return $response;
    }

   /**
    * Renders a response.
    *
    * @param mixed $response    The response to be rendered.  Can be an array
    *                           containing 'body', 'headers', and/or 'status'
    *                           keys, or a string which will be used as the
    *                           body of the response.  Note that the headers
    *                           must be well-formed HTTP headers, and the
    *                           status must be an integer (i.e., the one
    *                           associated with the HTTP status code).  The
    *                           response body is chunked according to the
    *                           buffer_size set in the constructor.
    * @return void
    */
    public function render($response) {
        $response = $this->_parse($response);
        $status = $this->_convert_status($response['status']);
        header($status);
        foreach ( $response['headers'] as $header ) {
            header($header, false);
        }

        $buffer_size = $this->_config['buffer_size'];
        $length = strlen($response['body']);
        for ( $i = 0; $i < $length; $i += $buffer_size ) {
            echo substr($response['body'], $i, $buffer_size);
        }
    }

}

?>
<?php

namespace nx\core;

/**
 * The Router is used to handle url routing.
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2012 Nick Sinopoli
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 */
class Router {

   /**
    * Compiles the regex necessary to capture all match types within a route.
    *
    * @param string $route    The route.
    * @return string
    */
    protected function _compile_regex($route) {
        $pattern = '`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`';

        if ( preg_match_all($pattern, $route, $matches, PREG_SET_ORDER) ) {
            $match_types = array(
                'i'  => '[0-9]++',
                'a'  => '[0-9A-Za-z]++',
                'h'  => '[0-9A-Fa-f]++',
                '*'  => '.+?',
                ''   => '[^/]++'
            );
            foreach ( $matches as $match ) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ( isset($match_types[$type]) ) {
                    $type = $match_types[$type];
                }
                if ( $param ) {
                    $param = "?<{$param}>";
                }
                if ( $optional ) {
                    $optional = '?';
                }

                $replaced = "(?:{$pre}({$param}{$type})){$optional}";
                $route = str_replace($block, $replaced, $route);
            }
        }
        if ( substr($route, strlen($route) - 1) != '/' ) {
            $route .= '/?';
        }
        return "`^{$route}$`";
    }

   /**
    * Parses the supplied request uri based on the supplied routes and
    * the request method.
    *
    * Routes should be of the following format:
    *
    * <code>
    * $routes = array(
    *     array(
    *         mixed $request_method, string $request_uri, callable $callback
    *     ),
    *     ...
    * );
    * </code>
    *
    * where:
    *
    * <code>
    * $request_method can be a string ('GET', 'POST', 'PUT', 'DELETE'),
    * or an array (e.g., array('GET, 'POST')).  Note that $request_method
    * is case-insensitive.
    * </code>
    *
    * <code>
    * $request_uri is a string, with optional match types.  Valid match types
    * are as follows:
    *
    * [i] - integer
    * [a] - alphanumeric
    * [h] - hexadecimal
    * [*] - anything
    *
    * Match types can be combined with parameter names, which will be
    * captured:
    *
    * [i:id] - will match an integer, storing it within the returned 'params'
    * array under the 'id' key
    * [a:name] - will match an alphanumeric value, storing it within the
    * returned 'params' array under the 'name' key
    *
    * Here are some examples to help illustrate:
    *
    * /post/[i:id] - will match on /post/32 (with the returned 'params' array
    * containing an 'id' key with a value of 32), but will not match on
    * /post/today
    *
    * /find/[h:serial] - will match on /find/ae32 (with the returned 'params'
    * array containing a 'serial' key will a value of 'ae32'), but will not
    * match on /find/john
    * </code>
    *
    * <code>
    * $callback is a valid callback function.
    * </code>
    *
    * Returns an array containing the following keys:
    *
    * * 'params'   - The parameters collected from the matched uri
    * * 'callback' - The callback function pulled from the matched route
    *
    * @param string $request_uri       The request uri.
    * @param string $request_method    The request method.
    * @param array $routes             The routes.
    * @return array
    */
    public function parse($request_uri, $request_method, $routes) {
        foreach ( $routes as $route ) {
            list($method, $uri, $callback) = $route;

            if ( is_array($method) ) {
                $found = false;
                foreach ( $method as $value ) {
                    if ( strcasecmp($request_method, $value) == 0 ) {
                        $found = true;
                        break;
                    }
                }
                if ( !$found ) {
                    continue;
                }
            } elseif ( strcasecmp($request_method, $method) != 0 ) {
                continue;
            }

            if ( is_null($uri) || $uri == '*' ) {
                $params = array();
                return compact('params', 'callback');
            }

            $route_to_match = '';
            $len = strlen($uri);

            for ( $i = 0; $i < $len; $i++ ) {
                $char = $uri[$i];
                $is_regex = (
                    $char == '[' || $char == '(' || $char == '.'
                    || $char == '?' || $char == '+' || $char == '{'
                );
                if ( $is_regex ) {
                    $route_to_match = $uri;
                    break;
                } elseif (
                    !isset($request_uri[$i]) || $char != $request_uri[$i]
                ) {
                    continue 2;
                }
                $route_to_match .= $char;
            }

            $regex = $this->_compile_regex($route_to_match);
            if ( preg_match($regex, $request_uri, $params) ) {
                foreach ( $params as $key => $arg ) {
                    if ( is_numeric($key) ) {
                        unset($params[$key]);
                    }
                }
                return compact('params', 'callback');
            }
        }
        return array(
            'params'   => null,
            'callback' => null
        );
    }

}

?>

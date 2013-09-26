<?php
session_start ();

use engine\CFG;

use engine\utils\SU;

use engine\utils\AU;

use engine\RE;
/**
 * Defines full path to the application files
 *
 * @var string
 */
define ( 'APP_ROOT', realpath ( '.' ) );

/**
 * Defines full path to current DF installation.
 *
 * @var string
 */
define ( 'DF_ROOT', realpath ( __DIR__ ) );

/**
 * Defines application base URL.
 *
 * The url of the index.php from where this class was invoked
 *
 * @var string
 */
define ( 'APP_URL', str_replace ( DIRECTORY_SEPARATOR, '/', substr ( APP_ROOT, strlen ( $_SERVER ['DOCUMENT_ROOT'] ) ) ) . '/' );

require_once DF_ROOT . '/engine/utils/SU.php';
/**
 * Main framework class.
 * All the magic starts here. Just include it in your index.php and invoke
 * method {@link DF::go() go()}
 *
 * @author Yuriy
 *        
 */
class DF {
	/**
	 * Main entry method.
	 *
	 *
	 * {@link DF::init() Initializes} this class and routes the request to the
	 * controller/method.
	 * There is no way to define custom routes. There is no need for it.<br />
	 * The route (aka URL mapping) is defined by first four segments of
	 * application path.<br />
	 * The segment is something which goes between slashes after application
	 * root URL.<br />
	 * The routing rules are as follows:
	 * <table>
	 * <tr><th>segment 1/</th><th>Segment 2/</th><th>Segment 3/</th><th>Segment
	 * 4/</th><th></th></tr>
	 * <tr><td>something</td><td></td><td></td><td></td><td>\\app\\controllers\\SomethingController\::doIndex()</td></tr>
	 * <tr><td>something/</td><td>action</td><td></td><td></td><td>\\app\\controllers\\SomethingController\::doAction()</td></tr>
	 * <tr><td>module<span title="s is
	 * optional">s/</span></td><td>some_name</td><td>something</td><td></td><td>\\modules\\SomeName\\app\\controllers\\SomethingController\::doIndex()</td></tr>
	 * <tr><td>module<span title="s is
	 * optional">s</span>/</td><td>some_name</td><td>something/</td><td>action</td><td>\\modules\\SomeName\\app\\controllers\\SomethingController\::doAction()</td></tr>
	 * </table>
	 * So if you have installed your application in server root the segments
	 * referrenced above are:<br />
	 * http://mysite.com/segment1/segment2/segment3/segment4<br />
	 * if you installed your application /to/some/folder, then the segments
	 * referrenced above are:<br />
	 * http://mysite.com/to/some/folde/segment1/segment2/segment3/segment4<br />
	 *
	 * All the other segments are of no meaning and can be used for SEO or
	 * whatever. (In contrary to "some other" frameworks they are not
	 * mapped/passed as arguments. I love standard queries with ? and &quot;)
	 *
	 * DF supports named mappings for arguments in query.<br>
	 * If you have a method in your controlled defined as
	 * MyController::doAction($a,$b,$c), you can pass these arguments as
	 * http://mysite.com/my/action?a=1&c=3&b=5<br />
	 * Notice that the order does not matter.
	 *
	 * @throws E404 there is no mapping between URL and controller/method
	 * @throws E500 something went wrong
	 */
	static function go() {
		try {
			self::init ();
			/*
			 * lets figure out controller cass / method There are few options 1.
			 * We use regular controller (no module) So the path can be /c/m
			 * where c is controller (maybe with namespace /separators: . or -/)
			 * and 'm' is a method. Both can be optional. So '/' => home/index,
			 * '/test' => test/index. Then the controller must be in
			 * /app/controllers/{maybe NS/}SomethingController the ending
			 * Controller is mandatory. Methods must start with 'do' and must be
			 * public. underscoers to CamelCase! So /my/something =>
			 * /app/controllers/MyController::doSomething() 2. the first part is
			 * 'modules' like /modules/name/c/m -- same rules but initial
			 * namespace will be modules/name/controllers. in this case the
			 * second part (the /name/) is mandatory otherwise =>404
			 */
			$path = $_SERVER ['REQUEST_URI'];
			$path = substr ( $path, strlen ( APP_URL ) );
			$qln = strlen ( $_SERVER ['QUERY_STRING'] );
			if ($qln) {
				$path = substr ( $path, 0, - ($qln + 1) );
			}
			if (! $path) {
				$path = 'home/index';
			}
			$path = preg_replace ( '/[^\\w\\.\\-\\/]+/', '', $path );
			$path = explode ( '/', $path );
			$ns = 'app\\controllers\\';
			if (count ( $path ) == 0) {
				$controller = 'home';
				$method = 'index';
			} else {
				if ($path [0] == 'modules' || $path [0] == 'module') {
					
					$module_name = AU::get ( $path, '1' );
					if (SU::isBlank ( $module_name )) {
						throw new E404 ();
					}
					$ns = 'modules\\' . $module_name . '\\app\\controllers\\';
					$controller = AU::get ( $path, '2', 'home' );
					$method = AU::get ( $path, '3', 'index' );
				} else {
					$controller = AU::get ( $path, '0', 'home' );
					$method = AU::get ( $path, '1', 'index' );
				}
			}
			
			$method = 'do' . SU::strtocammel ( $method );
			$controller = preg_split ( '/[_\\.\\-]+/', $controller );
			$class = $controller [count ( $controller ) - 1];
			if (SU::isBlank ( $class )) {
				throw new E404 ();
			}
			$class = SU::strtocammel ( $class ) . 'Controller';
			$controller [count ( $controller ) - 1] = $class;
			$controller = $ns . implode ( '\\', $controller );
			
			try {
				$controllerObj = new $controller ();
			} catch ( E500 $e ) {
				throw $e;
			} catch ( Exception $e ) {
				throw new E404 ();
			}
			
			$is_call_valid = is_callable ( array (
					$controllerObj,
					$method 
			) );
			
			if ($is_call_valid) {
				$method = new \ReflectionMethod ( $controllerObj, $method );
				$args = $method->getParameters ();
				$argVal = array ();
				foreach ( $args as $arg ) {
					$pos = $arg->getPosition ();
					$name = $arg->getName ();
					$val = null;
					if (isset ( $_REQUEST [$name] )) {
						$val = $_REQUEST [$name];
					} else if ($arg->isDefaultValueAvailable ()) {
						continue;
					}
					
					$argVal [$pos] = $val;
				}
				RE::invoke ( $method, $controllerObj, $argVal );
			} else {
				
				RE::renderError ( 404 );
			}
		} catch ( E500 $e ) {
			RE::renderError ( 500, $e->getMessage () );
		} catch ( E404 $e ) {
			RE::renderError ( 404, $e->getMessage () );
		} catch ( Exception $e ) {
			dbg ( $e );
		}
	}
	/**
	 * Initializes the framework.
	 *
	 * No need to call this method. It is done automatically from {@link
	 * DF::go() go()} method.<br />
	 * Currently this method:
	 * <ol>
	 * <li>loads DF_ROOT/engine/lib/functions.php</li>
	 * <li>Registers {@link DF::classLoader()} as class autoloader.</li>
	 * <li>Consequently loads DF_ROOT/app/config/df.php and
	 * APP_ROOT/app/config/app.php (values from app.php will override those from
	 * df.php)</li>
	 */
	private static function init() {
		require_once DF_ROOT . '/engine/lib/functions.php';
		spl_autoload_register ( 'DF::classLoader' );
		CFG::load ( DF_ROOT . '/app/config/df.php' );
		CFG::load ( APP_ROOT . '/app/config/app.php' );
	}
	
	/**
	 * Class auto loader.
	 *
	 * Classes eligible for autoload must have:<ol><li> namespace according to
	 * the folder structure</li>
	 * <li>Case sensitive file namematching class name</li>
	 * </ol>
	 * The class loading secuence is as follow:<br />
	 * (suppose DF is installed in /df and your application is /proj and the
	 * class you wanted is \\app\\my\\Class)
	 * <ol>
	 * <li>try to load /proj/app/my/Class.php</li>
	 * <li>try to load /df/app/my/Class.php</li>
	 * </ol>
	 * In this way you can replace standard DF classes.<br/>
	 * Just create <code>/proj/engine/very/same/path/as/in/df/ThatClass.php</code><br
	 * />
	 * and ofcourse define the class:<br />
	 * <code>
	 * &lt;?<br>
	 * namespace engine\\very\\same\\path\\as\\in\\df;<br>
	 * class ThatClass{}<br>
	 * ?&gt;
	 * </code><br>
	 * Remember--<b>case sensitive!</b>--we do not abuse
	 * CamelCase&lt;-&gt;under_score
	 *
	 * @param string $what
	 *        	full qualified class name
	 * @throws E500 in case of any troubles: class not found, namespace mismatch
	 * @see DF::loadPHP()
	 * @see \\engine\\db\\DB
	 *
	 */
	private static function classLoader($what) {
		$what = str_replace ( '/', '\\', $what );
		if (! DF::loadPHP ( $what )) {
			throw new E500 ( "class '$what' not found" );
		}
	}
	/**
	 * Loads a PHP file either from your project or from DF folders.
	 * <p>
	 * Tries to sanitize the path (<code>$what</code>), ensures ".php" extension
	 * (so you can omit it) then looks first into your project folder then in DF
	 * folder to locate the file.
	 * </p>
	 * <p>
	 * The file is "loaded" using <code>require</code> or
	 * <code>require_once</code> depending on <code>$once</code> argument.
	 * <p>Optionally you can pass associative array <code>$vars</code> which
	 * will be visible in the file loaded.<br>
	 * The array will not be unpacked, so its keys will not become variables.
	 * </p>
	 * <p>
	 * Throws no exception if file was not found. (just returns false)</p>
	 *
	 * @param string $what
	 *        	relative filepath
	 * @param boolean $once
	 *        	require() or require_once()
	 * @param array $vars
	 *        	associative array to be pass some data to the file
	 *        	loaded
	 * @return boolean or mixed false: class failed to load otherwise returns
	 *         whatever
	 *         included file returned
	 */
	static function loadPHP($what, $once = true, $vars = array()) {
		$what = SU::ensureBeginning ( str_replace ( '/', '\\', trim ( $what ) ), '\\' );
		
		$path = str_replace ( '\\', DIRECTORY_SEPARATOR, $what );
		$path = SU::ensureEnding ( $path, '.php' );
		$file1 = APP_ROOT . $path;
		if (file_exists ( $file1 )) {
			$ret = require_once $file1;
		} else {
			$file2 = DF_ROOT . $path;
			
			if (file_exists ( $file2 )) {
				$ret = require_once $file2;
			} else {
				return false;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Determines if current request is Ajax.
	 *
	 * Generral rules are:
	 * <ol><li>if your request has special parameter: <code>_ajax</code> then if
	 * it is === 1 we consider the request to be ajax and disregard all the
	 * other.
	 * This way you can force any request to behave as Ajax</li>
	 * <li>else: examine <code>HTTP_X_REQUESTED_WITH</code>: if it is
	 * "xmlhttprequest" (case insensitive) then it is Ajax request.</li>
	 * </ol>
	 *
	 * @return boolean
	 */
	static function isAjax() {
		if (isset ( $_REQUEST ['_ajax'] )) {
			return $_REQUEST ['_ajax'] == 1;
		}
		if (isset ( $_SERVER ['HTTP_X_REQUESTED_WITH'] )) {
			return strtolower ( $_SERVER ['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest';
		}
		return false;
	}
	/**
	 * Opposite to {@link DF::isAjax()}
	 *
	 * @return boolean
	 * @see DF::isAjax()
	 */
	static function isRegularRequest() {
		return ! self::isAjax ();
	}
}
/**
 * Page not found exception.
 *
 * @author Yuriy
 *        
 */
class E404 extends \Exception {
	public function __construct($message = 'Page not found', $previous = null) {
		parent::__construct ( $message, 404, $previous );
	}
}

/**
 * Server panic exception.
 *
 * @author Yuriy
 *        
 */
class E500 extends \Exception {
	public function __construct($message = 'Server error', $previous = null) {
		parent::__construct ( $message, 500, $previous );
	}
}
?>


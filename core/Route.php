<?php
namespace Core;
use Application\Controllers;
class Route {

	/**
	* load() - loads controller trhu the route url
	* @param string $controller
	* @return mixed
	*/
	public function load($controller, $params='') {

		global $urlParams;

		if ($params) {
			$urlParams = $params;
		}
		$_arr = explode('/', $controller);

		$controllerDir = DOCROOT.'/app/controllers/';
		$controllerAddr = $controllerDir.strtolower($_arr[0]).'_controller.php';

		// check if controller file exists
		if (file_exists($controllerAddr)) {
			// include controller file
			include($controllerAddr);
			$class = "Application\\Controllers\\".ucwords($_arr[0]).'_Controller';
			$obj = new $class;
			$method = isset($_arr[1]) ? @$_arr[1] : 'index';
			// get params, if they exist
			if (count($_arr) > 2) {
				global $urlParams;
				$urlParams = array();
				unset($_arr[0]);
				unset($_arr[1]);
				foreach ($_arr as $key => $value) {
					if ($value == 'true') {
						$urlParams[] = true;
					}
					else if ($value == 'false') {
						$urlParams[] = false;
					}
					else {
						$urlParams[] = $value;
					}
				}
			}
			// check if method exists
			if (method_exists($obj, $method)) {
				// return method with params parsed
				call_user_func_array([$obj, $method], $urlParams);
				return;
				// return $obj->$method(extract($urlParams));
			} else {
				error_page('404');
				//die('<div class="system-error">Method '.$class.'->'.$method.'() not found</div>');
			}
		} else {
			error_page('404');
			//die('<div class="system-error">Controller '.$controllerAddr.' not found</div>');
		}

	}

}
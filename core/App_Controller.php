<?php
namespace Core;
/**
 * App_Controller holds the basic methods used by several 
 * classes around the application scope.
 *
 */
class App_Controller {
	function __construct() {
		// register device_id for every controller loaded
		global $CONFIG;

		// load language file
		$_language_file = DOCROOT.DS.'app'.DS.'languages'.DS.LANGUAGE.'.php';
		if (file_exists($_language_file)) {
			include ($_language_file);
			$this->lang = $_lang;
		}
		else {
			die('Language file not found');
		}
		global $__post;
		$this->post = $__post;

		return $this;
	}

	public function load() {
		return new Loader();
	}

	/**
	* returnJson() - return a formatted json string
	* @param string $status_code
	* @param array $items
	* @return mixed
	*/
	protected function returnJson($httpStatus, $items=array(), $extra=array()) {
		header('Content-type: application/json');
		$http_codes = get_http_codes();
		$return = array(
			'status' => array(
				'code' => $httpStatus,
				'status' => @$http_codes[$httpStatus]
			),
			'items' => $items,
			'extra' => $extra
		);
		echo json_encode($return, JSON_PRETTY_PRINT);
		return true;
	}

	protected function returnError($httpStatus) {
		$ds = DIRECTORY_SEPARATOR;
		$file = DOCROOT.$ds.'app'.$ds.'views'.$ds.'templates'.$ds.$httpStatus.'.phtml';
		if (file_exists($file)) {
			$this->load()->view('templates/'.$httpStatus);
		}
		die();
	}

}

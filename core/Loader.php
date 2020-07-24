<?php
namespace Core;

use Application\Controllers;
use Application\Models;
use Core;
use Core\Helpers\Sql as Sql;

class Loader {
	private $inf;

	function __construct() {
		global $CONFIG;
		$this->inf = new Helpers\Inflector;
	}

	public function view($view, $params=false) {

		global $urlParams;

		// extract params
		if ($params) {
			extract($params);
			unset($params);
		}

		$_language_file = DOCROOT.DS.'app'.DS.'languages'.DS.LANGUAGE.'.php';
		if (file_exists($_language_file)) {
			include ($_language_file);
		}
		else {
			die('Language file not found');
		}

		$view_file = str_replace('/', DS, $view);
		unset($view);
		// views dir
		$viewDir = DOCROOT.DS.'app'.DS.'views'.DS;
		$viewAddr = $viewDir.$view_file.'.phtml';
		// check if file exists and give an exception
		if (file_exists($viewAddr)) {
			unset($viewDir);
			include($viewAddr);
		} else {
			die('<div class="system-error">View '.$viewAddr.' not found</div>');
		}

	}

	public function model($model) {
		global $urlParams;
		if (strstr($model, '_')) {
			$model = str_replace('_', ' ', $model);
			$model = ucwords($this->inf->singularize($model));
			$model = str_replace(' ', '_', $model);
		}
		else {
			$model = ucwords($this->inf->singularize($model));
		}
		$modelDir = DOCROOT.DS.'app'.DS.'models'.DS;
		$modelAddr = $modelDir.$model.'_Model.php';
		$class_name = 'Application\\Models\\'.$model.'_Model';
		// check if file exists and give an exception
		if (file_exists($modelAddr)) {
			if (!class_exists($class_name)) {
				include($modelAddr);
			}
			return new $class_name;
		}
		else {
			die('<div class="system-error">Model '.$modelAddr.' not found</div>');
		}
	}

	public function controller($controller) {

		global $urlParams;

		// load language file
		$_language_file = DOCROOT.DS.'app'.DS.'languages'.DS.LANGUAGE.'.php';
		if (file_exists($_language_file)) {
			include ($_language_file);
		}
		else {
			die('Language file not found');
		}

		$controller = strtolower($this->pluralize($controller));
		$controllerDir = DOCROOT.DS.'app'.DS.'controllers'.DS;
		$controllerAddr = $controllerDir.$controller.'_controller.php';
		// check if file exists and give an exception
		if (file_exists($controllerAddr)) {
			if (class_exists(substr($controllerAddr, 0, strpos($controllerAddr, '.php')))) return false;
			include($controllerAddr);
		} else {
			die('<div class="system-error">Controller '.$controllerAddr.' not found</div>');
		}

	}

	/**
	* loadController() - loads a specific helper
	* @param string $helper
	* @return mixed
	*/
	public function helper($helper) {

		$file = DOCROOT.DS.'app'.DS.'helpers'.DS.$helper.'_helper.php';
		if (file_exists($file))
			include ($file);
	}
}
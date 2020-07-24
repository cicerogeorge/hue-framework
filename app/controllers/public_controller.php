<?php

namespace Application\Controllers;

use Core;

class Public_Controller extends Core\App_Controller {
	public function index() {
		$this->load()->view('public/index');
	}
}
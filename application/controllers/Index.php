<?php
// 院系控制器
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller {
	public function __construct(){
		parent::__construct();
	}

	public function index()
	{
		$this->loadViewhf('back/home.html');
	}

	
}
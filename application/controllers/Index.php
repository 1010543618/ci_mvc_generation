<?php
// 院系控制器
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller {
	public function __construct(){
		parent::__construct();
	}

	public function index()
	{
		// $this->load->view('guide.html');
		$this->loadViewhf('back/home.html');
	}

    protected function loadViewhf($view, $response_data = array()) {
        $data = $response_data;
        if ($cj = $this->_getViewCssjs($view)) {
            $data['css'] = $cj['css'];
            $data['js'] = $cj['js'];
        } else {
            $data['css'] = array();
            $data['js'] = array();
        }
        $this->load->view('back/header.html', $data);
        $this->load->view($view);
        $this->load->view('back/footer.html');
    }

    protected function returnResult($result) {
        header("Content-type: application/json");
        echo json_encode($result);
        die();
    }

    private function _getViewCssjs($path) {
        // 获取全部cssjs
        ob_start();
        require('views/cssjs.json');
        $cssjs_str = ob_get_contents();
        @ob_end_clean();
        $cssjs = json_decode($cssjs_str, TRUE);

        // 获取找出当前cssjs
        if (strrpos($path, '/') === false) {// 在视图根目录
            $result['css'] = $cssjs['/']['css'];
            $result['js'] = $cssjs['/']['js'];
            return $result;
        }
        while (strrpos($path, '/') !== false) {
            $path = str_replace(strrchr($path, '/'), '', $path);
            if (array_key_exists($path, $cssjs)) {
                $result['css'] = $cssjs[$path]['css'];
                $result['js'] = $cssjs[$path]['js'];
                return $result;
            }
        }
        return false;
    }

}
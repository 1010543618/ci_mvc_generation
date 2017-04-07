<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->model_name = strtolower(get_class($this)).'_model';
        $this->load->model($this->model_name);
    }

    public function selectPage(){
        
        $start = $this->input->get('start', true);
        $length = $this->input->get('length', true);

        $result['draw'] = $this->input->get('draw', true);
        $result['data'] = $this->{$this->model_name}->selectPage($start, $length);
        $result['recordsTotal'] = $this->{$this->model_name}->countAll();
        $result['recordsFiltered'] = $result['recordsTotal'];
        
        $this->returnResult($result);
    }

    public function insert(){
        // 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 处理mutilchoice字段
        foreach ($this->bean['multichoice'] as $multichoice) {
            $form_data[$multichoice] = implode(',', $form_data[$multichoice]);
        }
        if ($this->{$this->model_name}->insert($form_data)) {
            $result['status'] = true;
        }else{
            $result['status'] = false;
        }
        $this->returnResult($result);
    }

    public function update(){
        // 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 获取id
        $id = array($this->bean['id'] => $this->input->post($this->bean['id'], TRUE));
        // 处理mutilchoice字段
        foreach ($this->bean['multichoice'] as $multichoice) {
            $form_data[$multichoice] = implode(',', $form_data[$multichoice]);
        }
        // 若有文件update前将文件位置保存
        if ($this->bean['files']) {
            $files = $this->{$this->model_name}->getByIdAndField($id, implode(',', $this->bean['files']));
        }
        // 更新数据
        if ($this->{$this->model_name}->update($form_data, $id)) {
            $result['status'] = true;
        }else{
            $result['status'] = false;
        }
        // 若有文件update后删除删除原文件
        if ($this->bean['files']) {
            foreach ($files as $file) {
                 @unlink('./'.$file);
            }
        }
        $this->returnResult($result);
    }

    public function get_form_data(){
        foreach ($this->bean['tablecolumn_s_m'] as $table_col) {
            $result[$table_col[0]] = $this->{$table_col[0].'_model'}->getAll(array($table_col[1],$table_col[2]));
        }
        $result['status'] = true;
        $this->returnResult($result);
    }

    public function delete()
    {
        // 获取id
        $id = array($this->bean['id'] => $this->input->post($this->bean['id'], TRUE));
        // 若有文件delete前将文件位置保存
        if ($this->bean['files']) {
            $files = $this->{$this->model_name}->getByIdAndField($id, implode(',', $this->bean['files']));
        }
        // 删除数据
        if ($this->{$this->model_name}->delete($id)) {
            $result['status'] = true;
        }else{
            $result['status'] = false;
        }
        // 若有文件delete后删除删除原文件
        if ($this->bean['files']) {
            foreach ($files as $file) {
                 @unlink('./'.$file);
            }
        }
        $this->returnResult($result);
    }

    protected function loadViewhf($view){
        if ($cj = $this->_getViewCssjs($view)) {
            $data['css'] = $cj['css'];
            $data['js'] = $cj['js'];
        }else{
            $data['css'] = array();
            $data['js'] = array();
        }
        $this->load->view('back/header.html',$data);
        $this->load->view($view);
        $this->load->view('back/footer.html');
    }

    protected function returnResult($result){
    	header("Content-type: application/json");
    	echo json_encode($result);
    	// var_dump($result);
    	return;
    }


    private function _getViewCssjs($path){
        // 获取全部cssjs
        ob_start();
        require('views/cssjs.json');
        $cssjs_str = ob_get_contents();
        @ob_end_clean();
        $cssjs = json_decode($cssjs_str, TRUE);

        // 获取找出当前cssjs
        while (strrpos($path,'/') !== false) {
            $path = str_replace(strrchr($path,'/'), '', $path);
            if (array_key_exists($path, $cssjs)) {
                $result['css'] = $cssjs[$path]['css'];
                $result['js'] = $cssjs[$path]['js'];
                return $result;
            }
        }
        return false;
    }

}
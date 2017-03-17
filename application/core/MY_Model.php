<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Model extends CI_Model {
	
	public function __construct(){
		parent::__construct();

		// 获取当前模型对应的表
		$this->model_table = str_replace('_model', '', strtolower(get_class($this)));
	}

	public function selectPage($start, $length)
	{
		// get(表, 取多少, 开始)
		return $this->db->get($this->model_table, $length, $start)->result_array();
	}

	public function countAll(){
		return $this->db->count_all($this->model_table);
	}

	public function insert($form_data){
		// insert：TRUE on success, FALSE on failure
		return $this->db->insert($this->model_table, $form_data);
	}

	public function update($form_data, $id){
		// update：TRUE on success, FALSE on failure
		return $this->db->update($this->model_table, $form_data, $id);
	}

	public function delete($id){
		// delete：TRUE on success, FALSE on failure
		return $this->db->delete($this->model_table, $id);
	}

	public function getAll($field){
		return $this->db->select($field)->get($this->model_table)->result_array();
	}
	
}
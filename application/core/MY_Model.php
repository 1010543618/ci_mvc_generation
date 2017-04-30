<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Model extends CI_Model {
	
	public function __construct(){
		parent::__construct();

		// 获取当前模型对应的表
		$this->model_table = str_replace('_model', '', strtolower(get_class($this)));
	}

	public function selectPage($start, $length, $where = true)
	{
		// get(表, 取多少, 开始)
		return $this->db->where($where)->get($this->model_table, $length, $start)->result_array();
	}

	public function countAll($where = true){
		// count_all：获取记录总数
		if ($where) {
			return $this->db->select('count(1) as count')->where($where)->get($this->model_table)->row()->count;
		}else
			return $this->db->count_all($this->model_table);
	}

	public function insert($form_data){
		// insert：TRUE on success, FALSE on failure
		return $this->db->insert($this->model_table, $form_data);
	}

	public function insert_batch($form_data){
		return $this->db->insert_batch($this->model_table, $form_data);
	}

	public function update($form_data, $id){
		// update：TRUE on success, FALSE on failure
		return $this->db->update($this->model_table, $form_data, $id);
	}

	public function delete($id){
		// delete：TRUE on success, FALSE on failure
		return $this->db->delete($this->model_table, $id);
	}

	/**
	 * 获取全部数据（可以指定字段）
	 * @Author   zjf
	 * @DateTime 2017-04-05
	 * @param    [stirng     $field  查找的字段的字符串]
	 * @return   array               查找的结果
	 */
	public function getAll($field = '*'){
		return $this->db->select($field)->get($this->model_table)->result_array();
	}

	/**
	 * 获取记录通过id和字段名称
	 * @Author   zjf
	 * @DateTime 2017-04-05
	 * @param    string     $id    id条件字符串
	 * @param    string     $field 查找的字段的字符串
	 * @return   array             查找的结果
	 */
	public function getFieldsById($id,$field = '*'){
		return $this->db->select($field)->get_where($this->model_table, $id)->row_array();
	}
	
}
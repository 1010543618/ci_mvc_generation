<?php echo "<?php".PHP_EOL?>
class <?php echo $model_name.'_Model'?> extends MY_Model {
<?php /*----------用外部表作为选项重写selectPage----------*/?>
<?php if ($bean['extras']['model_join']): ?>
	public function selectPage($start, $length, $where = true)
	{
		// get(表, 取多少, 开始)
		return $this->db->select('<?php echo implode(", ", $bean['extras']['model_select_fields']) ?>')-><?php echo implode('->', $bean['extras']['model_join']) ?>->where($where)->group_by('<?php echo implode(", ", $bean['extras']['model_id']) ?>')->get('<?php echo $bean_name?>', $length, $start)->result_array();
	}
	
	public function countAll($where = true){
		// count_all：获取记录总数
		if ($where) {
			return $this->db->select('count(1) as count')-><?php echo implode('->', $bean['extras']['model_join']) ?>->where($where)->get($this->model_table)->row()->count;
		}else
			return $this->db->count_all($this->model_table);
	}
<?php endif ?>
<?php /*----------为连接表重写/selectPage*/?>
}
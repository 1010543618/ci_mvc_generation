<?php echo "<?php".PHP_EOL?>
class <?php echo $model_name?> extends MY_Model {
<?php /*----------用外部表作为选项重写selectPage----------*/?>
<?php if ($bean['extras']['table_s_m']): ?>
	public function selectPage($start, $length)
	{
		// get(表, 取多少, 开始)
		return $this->db->select('<?php echo implode(", ", $bean['extras']['model_select_fields']) ?>')-><?php echo implode('->', $bean['extras']['model_join']) ?>->group_by('<?php echo "$bean_name.{$bean['id']['field']}" ?>')->get('<?php echo $bean_name?>', $length, $start)->result_array();
	}
<?php endif ?>
<?php /*----------为连接表重写/selectPage*/?>
}
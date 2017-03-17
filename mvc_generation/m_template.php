<?php echo "<?php".PHP_EOL?>
class <?php echo $model_name?> extends MY_Model {
<?php if (isset($bean['join']) && is_array($bean['join']) && $bean['join'] != null): ?>
	<?php 
		$select = "'$bean_name.{$bean['id']['field']}";
		$join = '';
		foreach ($bean['col'] as $key => $col) {
			$select .= ",$bean_name.{$col['field']}";
		}
		foreach ($bean['join'] as $join_table_name => $join_table) {
			//select
			$select .= ",$bean_name.{$join_table['pri_field']}";
			foreach ($join_table['col'] as $join_col) {
				$select .= ",$join_table_name.{$join_col['field']}";
			}
			//join
			$join .= "join('$join_table_name', '$bean_name.{$join_table['pri_field']} = $join_table_name.{$join_table['join_field']}', 'left')->";
		}
		$select .= "'";
	?>

	public function selectPage($start, $length)
	{
		// get(表, 取多少, 开始)
		return $this->db->select(<?php echo $select ?>)-><?php echo $join ?>get('<?php echo $bean_name?>', $length, $start)->result_array();
	}
<?php endif ?>
}
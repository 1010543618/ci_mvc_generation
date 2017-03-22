<?php echo "<?php".PHP_EOL?>
class <?php echo $model_name?> extends MY_Model {
<?php /*----------为连接表重写selectPage*/?>
<?php if (isset($bean['join']) && is_array($bean['join']) && $bean['join'] != null): ?>
<?php 
	$select = "'$bean_name.{$bean['id']['field']}";
	$join = '';
	foreach ($bean['col'] as $key => $col) {
		$select .= ",$bean_name.{$col['field']}";
	}
	foreach ($bean['join'] as $join_table_name => $join_table) {
		if (isset($join_table['form_type']) && $join_table['form_type'] == 'multichoice') {
			//multichoice的select和join
			$select .= ",$bean_name.{$join_table['pri_field']}";
			$child_join_select = array();
			foreach ($join_table['col'] as $join_col) {
				$select .= ",GROUP_CONCAT($join_table_name.{$join_col['field']}) AS {$join_col['field']}";
				$child_join_select[] = "GROUP_CONCAT($join_table_name.{$join_col['field']}) AS {$join_col['field']}";
			}
			$child_join_select_str = implode(',', $child_join_select);
			//注意FIND_IN_SET(要找的字符串,被寻找的字符串)
			//通过子查询查找出来连接表要查找的字段合并起来（不这样group_concat多表连接时字段会重复）
			$child_join_table = "'(SELECT {$bean['id']['field']}, $child_join_select_str FROM $bean_name left join $join_table_name ON FIND_IN_SET($join_table_name.{$join_table['join_field']},$bean_name.{$join_table['pri_field']}) != 0 GROUP BY {$bean['id']['field']}) AS $join_table_name'";
			$join .= "JOIN($child_join_table, '$bean_name.{$bean['id']['field']}=$join_table_name.{$bean['id']['field']}', 'left')->";
		}else{
			//其他的select和join
			$select .= ",$bean_name.{$join_table['pri_field']}";
			foreach ($join_table['col'] as $join_col) {
				$select .= ",$join_table_name.{$join_col['field']}";
			}
			$join .= "JOIN('$join_table_name', '$bean_name.{$join_table['pri_field']} = $join_table_name.{$join_table['join_field']}', 'left')->";
		}
		
	}
	$select .= "'";
?>
	public function selectPage($start, $length)
	{
		// get(表, 取多少, 开始)
		return $this->db->select(<?php echo $select ?>)-><?php echo $join ?>group_by('<?php echo "$bean_name.{$bean['id']['field']}" ?>')->get('<?php echo $bean_name?>', $length, $start)->result_array();
	}
<?php endif ?>
<?php /*----------为连接表重写/selectPage*/?>
}
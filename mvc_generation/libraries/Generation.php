<?php
class Generation{
	public function __construct($config){
		$this->_set_config($config);
	}

	/**
	 * 输出mvc文件
	 * @Author   zjf
	 * @DateTime 2017-03-11
	 * @param 	 Array $config 		配置信息
	 * @return   array     			输出的每个文件的信息
	 */
	public function output_mvc_file(){
		
		// 获取配置文件
		$generation_config = remove_json_indent($this->generation_config);

		// 生成bean
		$beans = json_decode($generation_config, TRUE);
		
		if ($beans == null) {
			return_json("配置文件格式有误，请检查配置文件是否符合json格式",false);
		}

		// 处理beans（填入初始值）
		$this->_handle_beans($beans);
		// var_dump($beans);die();
		//循环生成
		foreach ($beans as $bean_name => $bean) {
			
			//models
			$model_name = $this->_ucmodel($bean_name, "_");
			ob_start();
	        require('./m_template.php');
	        $model = ob_get_contents();
	        @ob_end_clean();
	        if (!is_dir($this->folder['m'])) mkdir($this->folder['m']);
	  		file_put_contents("{$this->folder['m']}/$model_name.php", $model);
			
			//views
			ob_start();
	        require('./v_template.php');
	        $view = ob_get_contents();
	        @ob_end_clean();
	        if (!is_dir($this->folder['v'])) mkdir($this->folder['v']);
	  		file_put_contents("{$this->folder['v']}/$bean_name.html", $view);

	  		//controllers
	  		$controller_name = implode('_', array_map('ucfirst', explode('_', $bean_name)));
	  		ob_start();
	        require('./c_template.php');
	        $view = ob_get_contents();
	        @ob_end_clean();
	        if (!is_dir($this->folder['c'])) mkdir($this->folder['c']);
	  		file_put_contents("{$this->folder['c']}/$controller_name.php", $view);
		}

		//header
		ob_start();
		require('./header_template.php');
		$view = ob_get_contents();
		@ob_end_clean();
		file_put_contents("{$this->folder['v']}/header.html", $view);

		//footer
		ob_start();
		require('./footer_template.php');
		$view = ob_get_contents();
		@ob_end_clean();
		file_put_contents("{$this->folder['v']}/footer.html", $view);

		return true;
	}

	/**
	 * 创建表的bean
	 * @Author   zjf
	 * @DateTime 2017-03-10
	 * @return   string     返回表对应的配置（json）字符串
	 */
	public function create_tables_bean(){
		// 1。获取tables数组并处理成规定格式
		$tables_source = $this->_get_tables_info();
		$tables = array();
		// var_dump($tables_source);die();
		foreach ($tables_source as $table_name => $table_source) {
			$tables[$table_name] = array();
			$tables[$table_name]['tbl_comment'] = $table_source['tbl_comment'];
			// 主键
			foreach ($table_source['col'] as $column) {
				if ($column['key'] == "PRI") {
					// 主键
					$col['field'] = $column['field'];
					$col['comment'] = $column['comment'];
					$tables[$table_name]['id'][] = $col;
				}
			}

			// 外键
			// $matchs[1]：这张表连接的字段，$matchs[2]：主表，$matchs[3]：主表的字段
			preg_match_all("/FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)` \(`(.*?)`\)/",$table_source['create_str'],$matchs);
			foreach ($matchs[1] as $key => $value) {
				$join_table_name = $matchs[2][$key];
				$tables[$table_name]['join'][$join_table_name]['pri_field'] = "{$matchs[2][$key]}.{$matchs[3][$key]}";
				$tables[$table_name]['join'][$join_table_name]['join_field'] = "$table_name.{$matchs[1][$key]}";
			}
			

			// 普通字段
			foreach ($table_source['col'] as $column) {
				if ($column['extra'] != 'auto_increment') {
					$col['field'] = $column['field'];
					$col['comment'] = $column['comment'];
					// type和validation
					if ($left_bracket_pos = strpos($column['type'],'(')) {
						// 有左括号
						$type = substr($column['type'], 0, $left_bracket_pos);
						$type_bracket = substr($column['type'], $left_bracket_pos + 1, -1);
					}else{
						$type = $column['type'];
					}
					switch ($type) {
						// 数字
						case 'int':
							$col['type'] = 'input';
							$col['validation'] = 'type="number" ';
							$col['validation'] .= 'maxlength="'.$type_bracket.'" ';
							break;
						// 字符串
						case 'varchar':
							$col['type'] = 'input';
							$col['validation'] = 'maxlength="'.$type_bracket.'" ';
							break;
						case 'text':
							$col['type'] = 'text';
							break;
						// enum和set
						case 'enum':
							$col['type'] = 'select';
							$col['select_options'] = explode(',', str_replace("'", '', $type_bracket));
							break;
						case 'set':
							$col['type'] = 'multichoice';
							$col['multichoice_options'] = explode(',', str_replace("'", '', $type_bracket));
							break;
						// 日期和时间
						case 'datetime':
							$col['type'] = 'datetime';
							break;
						case 'timestamp':
							$col['type'] = 'timestamp';
							break;
						case 'date':
							$col['type'] = 'date';
							break;
						case 'time':
							$col['type'] = 'time';
							break;
						case 'year':
							$col['type'] = 'year';
							break;
						default:
							$col['type'] = 'input';
							break;
					}
					// 是否为null
					$col['validation'] = ($column['is_nullable'] == 'NO' && $column['default'] === null) || $column['key'] == 'PRI' ? 'required ' : '';
					$tables[$table_name]['col'][] = $col;
				}
			}
			
		}
		// foreach ($tables_source[$matchs[1]]['col'] as $column_for_join) {
		// 	$join_col = array();
		// 	$join_col['field'] = $column_for_join['field'];
		// 	$join_col['comment'] = $column_for_join['comment'];
		// 	$tables[$table_name]['join'][$matchs[1]]['col'][] = $join_col;
		// }
		// var_dump($tables);die();

		// 将tables数组转换为json字符串（不自动转换为unicode编码），调整缩进并返回
		return add_json_indent(json_unescaped_unicode_encode($tables));
	}

	/**
	 * 获取数据库中的全部表
	 * @Author   zjf
	 * @DateTime 2017-03-10
	 * @return   Array(
	 				Array('tbl_name'=>tbl_name, 'tbl_comment'=>tbl_comment)
	 			 )     					
	 			 数据库中的全部表
	 */
	public function get_database_tebles(){
		// 1.连接数据库
		$conn = @mysqli_connect($this->host, $this->user, $this->password);
		if (!$conn) {
			return_json('连接数据库失败，请检查该配置是否能连接数据库',false);
		}
		mysqli_query($conn, 'SET NAMES utf8');
		// 2.选择数据库
		if (!mysqli_query($conn, "use {$this->db}")) {
			return_json('选择数据库失败，请检查是否有该数据库',false);
		}
		// 3.获取需要的表表(没有配置获取全部表)
		$result = mysqli_query($conn, "show table status");
		$i=0;
		foreach (mysqli_fetch_all($result) as $value) {
			#0表名，17表注释
			$tables[$i]['tbl_name'] = $value[0];
			$tables[$i]['tbl_comment'] = $value[17];
			++$i;
		}
		return $tables;
	}

	

	

	/**
	 * 读取配置文件
	 * @Author   zjf
	 * @DateTime 2017-03-10
	 * @return   string     配置文件字符串
	 */
	function read_config_file(){

	}



	

	/**
	 * 通过表名创建模型名（将单词大写，加上_Model）
	 * @Author   zjf
	 * @DateTime 2017-04-14
	 * @param    string     $str        表名
	 * @return   string                 模型名
	 */
	private function _ucmodel($str){
		return implode('_', array_map('ucfirst', explode('_', $str))).'_Model';
	}


	/**
	 * 检查bean是否完整
	 * @Author   zjf
	 * @DateTime 2017-03-15
	 * @param    array     $beans 要处理的数组
	 * @return   null             没有返回值
	 */
	private function _handle_beans(&$beans){
		// var_dump($beans);die();
		
		foreach ($beans as $bean_name => &$bean) {
			// var_dump(is_array($bean['id']));die();
			// tbl_comment
			if (!isset($bean['tbl_comment']) || !is_string($bean['tbl_comment'])) {
				return_json($bean_name.'的tbl_comment未设置或不是字符串',false);
			}
			// id
			if (!isset($bean['id'])) {
				$bean['id'] = null;
			}elseif(!is_array($bean['id'])){
				return_json($bean_name.'的id不是数组',false);
			}else{
				$this->_check_bean_id($bean['id'], $bean_name);
				
			}
			// col
			if (!isset($bean['col'])) {
				$bean['col'] = null;
			}elseif(!is_array($bean['col'])){
				return_json($bean_name.'的col不是数组',false);
			}else{
				$this->_check_and_handle_bean_col($bean['col'], $bean_name);
			}
			
			//join
			if (!isset($bean['join'])) {
				$bean['join'] = null;
			}elseif(!is_array($bean['join'])){
				return_json($bean_name.'的join不是数组',false);
			}else{
				$this->_check_and_handle_bean_join($bean['join'], $bean_name);
				
			}

			// extras附加信息：生成mvc时需要的信息
			$this->_create_bean_extras($bean, $bean_name);
		}

		// var_dump($beans);die();
	}


	/**
	 * 获取指定表字段
	 * @Author   zjf
	 * @DateTime 2017-03-10
	 * @param 	 Array $config 					 数据库配置信息
	 * @return   Array(
	 				Array(
	 					'table_name'=>Array(table_infos)
	 				)
	 			 )     
	 			 指定表字段
	 */
	private function _get_tables_info(){
		// 1.连接数据库
		$conn = mysqli_connect($this->host, $this->user, $this->password) or exit('连接数据库失败，请检查该配置是否能连接数据库');
		mysqli_query($conn, 'SET NAMES utf8');
		// 2.选择数据库
		mysqli_query($conn, "use {$this->db}") or exit('选择数据库失败，请检查是否有该数据库');
		// 3.获取需要的表表(没有配置获取全部表)
		$result = mysqli_query($conn, "show table status");
		foreach (mysqli_fetch_all($result) as $value) {
			#0表名，17表注释
			$tables[$value[0]] = array();
			$tables[$value[0]]['tbl_comment'] = $value[17] ? $value[17] : $value[0];
		}
		if ($this->tables) {
			foreach ($tables as $key => $value) {
				if (!in_array($key, $this->tables)) {
					unset($tables[$key]);
				}
			}
		}
		// 4.获取表中的字段信息，存入tables
		foreach ($tables as $table_name => &$table) {
			$result = mysqli_query($conn, "show full fields from $table_name");
			$create_table_result = mysqli_query($conn, "show create table $table_name");
			$fields = mysqli_fetch_fields($result);
			// 表字段
			foreach (mysqli_fetch_all($result) as $value) {
				$col['field'] = $value[0];
				$col['type'] = $value[1];
				$col['is_nullable'] = $value[3];
				$col['key'] = $value[4];
				$col['default'] = $value[5];
				$col['extra'] = $value[6];
				$col['comment'] = $value[8];
				$table['col'][] = $col;
			}
			// 创建表的语句
			foreach (mysqli_fetch_all($create_table_result) as $value) {
				$table['create_str'] = $value[1];
			}
		}
		mysqli_close($conn);
		return $tables;
	}

	/**
	 * 将config设置成类的成员变量
	 * @Author   zjf
	 * @DateTime 2017-04-14
	 * @param    array     $config        配置
	 * @return   null
	 */
	private function _set_config($config){
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
	}

	private function _check_bean_id($ids, $bean_name){
		foreach ($ids as $key => $id) {
			if (!isset($id['field']) || !is_string($id['field'])) {
				$num = $key+1;
				return_json($bean_name."的第{$num}个id的field未设置或不是字符串",false);
			}
			if (!isset($id['comment']) || !is_string($id['comment'])) {
				$num = $key+1;
				return_json($bean_name."的第{$num}个id的comment未设置或不是字符串",false);
			}
		}
	}

	private function _check_and_handle_bean_col(&$cols, $bean_name){
		//col
		foreach ($cols as $col_name => &$column) {
			if (!is_array($cols[$col_name])) {
				return_json($bean_name.'的col中的字段不是数组',false);
			}
			if (!isset($column['field']) || !is_string($column['field'])) {
				return_json($bean_name.'的col中的字段的field未设置或不是字符串',false);
			}
			if (!isset($column['comment']) || !is_string($column['comment'])) {
				return_json($bean_name.'的col中的字段的comment未设置或不是字符串',false);
			}
			if (!isset($column['type'])) {
				$column['type'] = 'input';
			}
			if (!isset($column['validation'])) {
				$column['validation'] = null;
			}
			if ($column['type'] == 'file' && !isset($column['file_path'])) {
				$column['file_path'] = "$bean_name/{$column['field']}";
			}
			if ($column['type'] == 'select' && !isset($column['select_options'])) {
				$column['select_options'] = array();
			}
			if ($column['type'] == 'multichoice' && !isset($column['multichoice_options'])) {
				$column['multichoice_options'] = array();
			}
			if ($column['type'] == 'select' && !isset($column['select_conf'])) {
				$column['select_conf'] = null;
			}
			if ($column['type'] == 'multichoice' && !isset($column['multichoice_conf'])) {
				$column['multichoice_conf'] = null;
			}
		}
		unset($column);
	}

	private function _check_and_handle_bean_join(&$joins, $bean_name){
		foreach ($joins as $join_table_name => &$join_table) {
			if (!is_array($joins[$join_table_name])) {
				return_json('连接表'.$join_table_name.'不是数组',false);
			}
			if (!isset($join_table['pri_field']) || !is_string($join_table['pri_field'])) {
				return_json('连接表'.$join_table_name.'中的pri_field未设置或不是字符串',false);
			}else{
				if (strpos($join_table['pri_field'], '.') === false) {
					// pri_field没有指定表
					$join_table['pri_field'] = $bean_name.'.'.$join_table['pri_field'];
				}
			}
			if (!isset($join_table['join_field']) || !is_string($join_table['join_field'])) {
				return_json('连接表'.$join_table_name.'中的join_field未设置或不是字符串',false);
			}else{
				if (strpos($join_table['join_field'], '.') === false) {
					// join_field没有指定表
					$join_table['join_field'] = $join_table_name.'.'.$join_table['join_field'];
				}
			}
			// join_table的col
			if (!isset($join_table['col'])) {
				$join_table['col'] = array();
			}else{
				foreach ($join_table['col'] as $join_col_name => &$join_col) {
					if (!is_array($join_table['col'][$join_col_name])) {
						return_json('连接表'.$join_table_name.'的col中的字段不是数组',false);
					}
					if (!isset($join_col['field']) || !is_string($join_col['field'])) {
						return_json('连接表'.$join_table_name.'的col中的field未设置或不是字符串',false);
					}
					if (!isset($join_col['comment']) || !is_string($join_col['comment'])) {
						return_json('连接表'.$join_table_name.'的col中的comment未设置或不是字符串',false);
					}
					if (!isset($join_col['is_group_concat'])) {
						$join_col['is_group_concat'] = 'false';
					}
				}
			}
			// join_table的manipulation_col
			if (!isset($join_table['manipulation_col'])) {
				$join_table['manipulation_col'] = array();
			}else{
				foreach ($join_table['manipulation_col'] as $join_mani_col_name => &$join_mani_col) {
					if (!is_array($join_table['manipulation_col'][$join_mani_col_name])) {
						return_json('连接表'.$join_table_name.'的manipulation_col中的字段不是数组',false);
					}
					if (!isset($join_mani_col['field']) || !is_string($join_mani_col['field'])) {
						return_json('连接表'.$join_table_name.'中的manipulation_col中的field未设置或不是字符串',false);
					}
					if (!isset($join_mani_col['comment']) || !is_string($join_mani_col['comment'])) {
						return_json('连接表'.$join_table_name.'中的manipulation_col中的comment未设置或不是字符串',false);
					}
					if (!isset($join_mani_col['option_field_conf']) || !is_array($join_mani_col['option_field_conf'])) {
						return_json('连接表'.$join_table_name.'中的manipulation_col中的option_field_conf未设置或不是数组',false);
					}
					if (!isset($join_mani_col['type'])) {
						$join_mani_col['type'] = 'select';
					}
				}
				if (!isset($join_table['manipulation_pri'])) {
					$join_table['manipulation_pri'] = $bean['id']['field'];
				}
			}
		}
		unset($join_table);
	}

	private function _create_bean_extras(&$bean, $bean_name){

		$id = array();// c接受的id("'id1'","'id2'");
		$form_fields = array();// c接受的字段 array("'col1'","'col2'");
		$join_manipulation = array();// c需要操作的join信息 array('table1'=>'prifield','table2'=>'prifield')
		$files = array();// c处理格式是文件的字段 array("'col1'","'col2'");
		$multichoice = array();// c处理格式是多选的字段 array("'col1'","'col2'");
		$get_form_data = array();// c生成get_form_data array("array('tablename1','optioncol','showcol')", "array('tablename2','optioncol','showcol')")
		$jointable = array();// c生成jointable array('tablename1','tablename2')


		$model_select_fields = array();// m查询的字段 array("col1","col2");
		$model_join = array();// m连接的字段 array("JOIN('table1', 'col1=col2', 'left')", "JOIN('table2', 'col1=col2', 'left')");

		
		$view_show_col = array();// v显示的字段
		$init_form_s_m = array();// v判断是否有select和mutilchoice的字段，并初始化

		$judge = array();
		if ($bean['id']) {
			$judge['has_id'] = true;
			foreach ($bean['id'] as $key => $id) {
				$model_select_fields[] = "$bean_name.{$id['field']}";
				$id[] = "'{$id['field']}'";
			}
		}else{
			$judge['has_id'] = false;
		}
		
		if ($bean['col']) {
			$judge['has_col'] = true;
			foreach ($bean['col'] as $key => $column) {
				$form_fields[] = "'{$column['field']}'";
				// 所有本身的字段都查，因为会用在初始化表单上
				$model_select_fields[] = "$bean_name.{$column['field']}";
				if ($column['type'] == 'file') {
					$files[] = "'{$column['field']}'";
				}elseif($column['type'] == 'multichoice'){
					$multichoice[] = "'{$column['field']}'";
				}
				if ($column['type'] == 'select' && $column['select_conf'] != null) {
					// 从外链接表获得字段信息
					$jointable[] = $column['select_conf'][0];
					$get_form_data[] = "array('".implode("', '", $column['select_conf'])."')";
					$init_form_s_m[] = $column['select_conf'] + array('type' => 'select', 'field' => $column['field']);
					$model_select_fields[] = "{$column['select_conf'][0]}.{$column['select_conf'][2]} AS {$column['select_conf'][2]}";
					$model_join[] = "JOIN('{$column['select_conf'][0]}', '$bean_name.{$column['field']}={$column['select_conf'][0]}.{$column['select_conf'][1]}', 'left')";
					$view_show_col[] = array(
						"field" => $column['select_conf'][2],
						"comment" => $column['comment']
						);
				}elseif($column['type'] == 'multichoice' && $column['multichoice_conf'] != null){
					// 从外链接表获得字段信息
					$jointable[] = $column['multichoice_conf'][0];
					$get_form_data[] = "array('".implode("', '", $column['multichoice_conf'])."')";
					$init_form_s_m[] = $column['multichoice_conf'] + array('type' => 'multichoice', 'field' => $column['field']);
					$model_select_fields[] = "{$column['multichoice_conf'][0]}.{$column['multichoice_conf'][2]}";
					$child_join_table = "'(SELECT {$bean['id']['field']}, GROUP_CONCAT({$column['multichoice_conf'][0]}.{$column['multichoice_conf'][2]}) AS {$column['multichoice_conf'][2]} FROM $bean_name left join {$column['multichoice_conf'][0]} ON FIND_IN_SET({$column['multichoice_conf'][0]}.{$column['multichoice_conf'][1]},$bean_name.{$column['field']}) != 0 GROUP BY {$bean['id']['field']}) AS {$column['multichoice_conf'][0]}'";
					$model_join[] = "JOIN($child_join_table, '$bean_name.{$bean['id']['field']}={$column['multichoice_conf'][0]}.{$bean['id']['field']}', 'left')";
					$view_show_col[] = array(
						"field" => $column['multichoice_conf'][2],
						"comment" => $column['comment']
						);
				}else{
					// 不用从外链接表获得字段信息
					$view_show_col[] = array(
						"field" => $column['field'],
						"comment" => $column['comment']
						);
				}
			}
		}else{
			$judge['has_col'] = false;
		}
		
		if ($bean['join']) {
			$judge['has_join'] = true;
			foreach ($bean['join'] as $join_table_name => $join_table) {
				foreach ($join_table['col'] as $column) {
					$view_show_col[] = array(
						"field" => "T{$join_table_name}C{$column['field']}",
						"comment" => $column['comment']
						);
					if ($column['is_group_concat']) {
						$model_select_fields[] = "GROUP_CONCAT($join_table_name.{$column['field']}) AS T{$join_table_name}C{$column['field']}";
					}else{
						$model_select_fields[] = "$join_table_name.{$column['field']} AS T{$join_table_name}C{$column['field']}";
					}
					
				}
				foreach ($join_table['manipulation_col'] as $mani_col) {
					$form_fields[] = "'$join_table_name'";
					$model_select_fields[] = "GROUP_CONCAT($join_table_name.{$mani_col['field']}) AS T{$join_table_name}C{$mani_col['field']}";
					if ($mani_col['type']=="select") {
						
						$jointable[] = $mani_col['option_field_conf'][0];
						$get_form_data[] = "array('".implode("', '", $mani_col['option_field_conf'])."')";
						$init_form_s_m[] = $mani_col['option_field_conf'] + array('type' => 'select', 'field' => "T$join_table_nameC{$mani_col['field']}", 'name' => "{$join_table_name}[{$mani_col['field']}]");
					}elseif ($mani_col['type']=="multichoice") {
						$jointable[] = $mani_col['option_field_conf'][0];
						$get_form_data[] = "array('".implode("', '", $mani_col['option_field_conf'])."')";
						$init_form_s_m[] = $mani_col['option_field_conf'] + array('type' => 'multichoice', 'field' => "T{$join_table_name}C{$mani_col['field']}", 'name' => "{$join_table_name}[{$mani_col['field']}]");
					}
				}
				if ($join_table['manipulation_col']) {
					$join_manipulation[] = "array('$join_table_name', '{$join_table['manipulation_pri']}')";
				}
				$model_join[] = "JOIN('$join_table_name', '{$join_table['pri_field']}={$join_table['join_field']}', 'left')";
			}
		}else{
			$judge['has_join'] = false;
		}
		

		$bean['extras']['form_fields'] = $form_fields;
		$bean['extras']['files'] = $files;
		$bean['extras']['multichoice'] = $multichoice;
		$bean['extras']['join_manipulation'] = $join_manipulation;
		$bean['extras']['get_form_data'] = $get_form_data;
		$bean['extras']['jointable'] = array_unique($jointable);

		$bean['extras']['model_select_fields'] = $model_select_fields;
		$bean['extras']['model_join'] = $model_join;

		$bean['extras']['init_form_s_m'] = $init_form_s_m;

		$bean['extras']['view_show_col'] = $view_show_col;

		$bean['extras']['judge'] = $judge;
	}
}

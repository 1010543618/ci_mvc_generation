<?php
$config = array();
header("Content-type:text/html;Charset=utf-8");
if ($_POST) {
	switch ($_POST['step']) {
		case '1':
			// 通过配置找全部表
			$config = array(
				'host' => $_POST['host'],
				'user' => $_POST['user'],
				'password' => $_POST['pwd'],
				'db' => $_POST['db']
				);
			if (has_null($config)) {
				return_result('有必填项没填啊！', false);
			}

			return_result(get_database_tebles($config), true);
			break;

		case '2':
			// 通过选择的表生成配置文件
			$config = array(
				'host' => $_POST['host'],
				'user' => $_POST['user'],
				'password' => $_POST['pwd'],
				'db' => $_POST['db'],
				'tables' => isset($_POST['tables']) ? $_POST['tables'] : null
				);
			if ($config['tables'] == null) {
				return_result('请至少选择一张表', false);
			}
			if (has_null($config)) {
				return_result('有必填项没填啊！', false);
			}
			
			$tables_bean = create_tables_bean($config);

			// 将tables_bean输出到config
			if (!file_put_contents('./config.json', $tables_bean))
				return_result('创建配置文件失败', true);
			return_result($tables_bean, true);
			break;

		case '3_4':
			// 使用设置的配置和配置文件的配置生成mvc文件
			$config = array(
				'generation_config' => $_POST['generation_config'],
				//mvc相对于当前目录的路径
				'm_folder' => $_POST['m_folder'],
				//给v_folder，c_folder添加上子路径
				'v_folder' => $_POST['v_folder'] . ($_POST['v_child_folder'] ? '/'.$_POST['v_child_folder'] : ''),
				'c_folder' => $_POST['c_folder'] . ($_POST['c_child_folder'] ? '/'.$_POST['c_child_folder'] : ''),
				//给v_child_folder，c_child_folder添加"/"为了在ci的URL函数中使用
				'v_child_folder' => $_POST['v_child_folder'] ? $_POST['v_child_folder'].'/' : '',
				'c_child_folder' => $_POST['v_child_folder'] ? $_POST['c_child_folder'].'/' : ''
				);
			if (isset($_POST['is_save_config']) &&  $_POST['is_save_config'] == 'true') {
				file_put_contents('./config.json', $config['generation_config']);
			}
			$info = output_mvc_file($config);
			return_result($info, false);
		default:
			break;
	}
}



/**
 * 输出mvc文件
 * @Author   zjf
 * @DateTime 2017-03-11
 * @param 	 Array $config 		配置信息
 * @return   array     			输出的每个文件的信息
 */
function output_mvc_file($config){
	//读配置文件
	$beans = preg_replace('/[\n\t\r]/', '', $config['generation_config']);
	$beans = json_decode($beans, TRUE);
	if ($beans == null) {
		return_result("配置文件格式有误，请检查配置文件是否符合json格式",false);
	}
	//处理beans（填入初始值）
	handle_beans($beans);
	// var_dump($beans);die();
	//循环生成
	foreach ($beans as $bean_name => $bean) {
		
		//models
		$model_name = ucmodel($bean_name, "_");
		ob_start();
        require('./m_template.php');
        $model = ob_get_contents();
        @ob_end_clean();
        if (!is_dir($config['m_folder'])) mkdir($config['m_folder']);
  		file_put_contents("{$config['m_folder']}/$model_name.php", $model);
		
		//views
		ob_start();
        require('./v_template.php');
        $view = ob_get_contents();
        @ob_end_clean();
        if (!is_dir($config['v_folder'])) mkdir($config['v_folder']);
  		file_put_contents("{$config['v_folder']}/$bean_name.html", $view);

  		//controllers
  		$controller_name = implode('_', array_map('ucfirst', explode('_', $bean_name)));
  		ob_start();
        require('./c_template.php');
        $view = ob_get_contents();
        @ob_end_clean();
        if (!is_dir($config['c_folder'])) mkdir($config['c_folder']);
  		file_put_contents("{$config['c_folder']}/$controller_name.php", $view);
	}

	//header
	ob_start();
	require('./header_template.php');
	$view = ob_get_contents();
	@ob_end_clean();
	file_put_contents("{$config['v_folder']}/header.html", $view);

	//footer
	ob_start();
	require('./footer_template.php');
	$view = ob_get_contents();
	@ob_end_clean();
	file_put_contents("{$config['v_folder']}/footer.html", $view);

	return true;
}

/**
 * 创建表的bean
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param 	 Array $config 					  配置信息
 * @return   string     返回表对应的配置（json）字符串
 */
function create_tables_bean($config){
	// 1。获取tables数组并处理成规定格式
	$tables_source = get_tables_info($config);
	// var_dump($tables_source);die();
	$tables = array();
	foreach ($tables_source as $table_name => $table_source) {
		$tables[$table_name] = array();
		$tables[$table_name]['tbl_comment'] = $table_source['tbl_comment'];
		// 当前表是否有主键
		$has_key = false;
		foreach ($table_source['col'] as $column) {
			$col = array();
			if ($column['key'] == "PRI") {
				// 主键
				if ($has_key == true) {
					// 是否为null
					$col['field'] = $column['field'];
					$col['comment'] = $column['comment'];
					$tables[$table_name]['col'][] = $col;
				}else{
					$tables[$table_name]['id']['field'] = $column['field'];
					$tables[$table_name]['id']['comment'] = $column['comment'];
				}
				$has_key = true;
			}elseif($column['key'] == "MUL"){
				// $matchs[1]：连接的表，$matchs[2]：连接的字段
				preg_match("/FOREIGN KEY \(`{$column['field']}`\) REFERENCES `(.*?)` \(`(.*?)`\)/",$table_source['create_str'],$matchs);
				$tables[$table_name]['join'][$matchs[1]]['pri_field'] = "{$matchs[1]}.{$matchs[2]}";
				$tables[$table_name]['join'][$matchs[1]]['join_field'] = "$table_name.{$column['field']}";
				// foreach ($tables_source[$matchs[1]]['col'] as $column_for_join) {
				// 	$join_col = array();
				// 	$join_col['field'] = $column_for_join['field'];
				// 	$join_col['comment'] = $column_for_join['comment'];
				// 	$tables[$table_name]['join'][$matchs[1]]['col'][] = $join_col;
				// }
			}else {
				// 普通字段
				$col['field'] = $column['field'];
				$col['comment'] = $column['comment'];
				// type和validation
				$left_bracket_pos = strpos($column['type'],'(');
				if ($left_bracket_pos) {
					// 有左括号
					$type = substr($column['type'], 0, $left_bracket_pos);
					$type_bracket = substr($column['type'], $left_bracket_pos + 1, -1);
				}else{
					$type = $column['type'];
				}
				switch ($type) {
					//数字
					case 'int':
						$col['type'] = 'input';
						$col['validation'] = 'type="number" ';
						$col['validation'] .= 'maxlength="'.$type_bracket.'" ';
						break;
					//字符串
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
					//日期和时间
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
				$col['validation'] = $column['is_nullable'] == 'NO' && $column['default'] === null ? 'required ' : '';
				$tables[$table_name]['col'][] = $col;
			}
		}
		// die();
	}

	// 2。将tables数组转换为json字符串（不自动转换为unicode编码）
	if (version_compare(PHP_VERSION,'5.4.0','<'))
		$tables_json = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function($matchs){return iconv('UCS-2BE','UTF-8',pack('H4', $matchs[1]));}, $tables);
	else
		$tables_json = json_encode($tables, JSON_UNESCAPED_UNICODE);
	// 3。现在tables是gbk编码，转换为utf8
	// $tables = iconv('GB2312', 'UTF-8', $tables);
	// 4。返回：调整缩进的table
	return reindent_json($tables_json);
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
 * 获取指定表字段
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param 	 Array $config 					 数据库配置信息
 * @return   Array[tablename][tableinfo]     指定表字段
 */
function get_tables_info($config){
	// 1.连接数据库
	$conn = mysqli_connect($config['host'], $config['user'], $config['password']) or exit('连接数据库失败，请检查该配置是否能连接数据库');
	mysqli_query($conn, 'SET NAMES utf8');
	// 2.选择数据库
	mysqli_query($conn, "use {$config['db']}") or exit('选择数据库失败，请检查是否有该数据库');
	// 3.获取需要的表表(没有配置获取全部表)
	$result = mysqli_query($conn, "show table status");
	foreach (mysqli_fetch_all($result) as $value) {
		#0表名，17表注释
		$tables[$value[0]] = array();
		$tables[$value[0]]['tbl_comment'] = $value[17] ? $value[17] : $value[0];
	}
	if ($config['tables']) {
		foreach ($tables as $key => $value) {
			if (!in_array($key, $config['tables'])) {
				unset($tables[$key]);
			}
		}
	}
	// 4.获取表中的字段信息，存入tables
	foreach ($tables as $table_name => &$table) {
		$result = mysqli_query($conn, "show full fields from $table_name");
		$create_table_result = mysqli_query($conn, "show create table $table_name");
		$fields = mysqli_fetch_fields($result);
		// var_dump(mysqli_fetch_all($result));die();
		// 表字段
		foreach (mysqli_fetch_all($result) as $value) {

			// $table[$value[0]]['type'] = $value[1];
			// $table[$value[0]]['is_nullable'] = $value[3];
			// $table[$value[0]]['key'] = $value[4];
			// $table[$value[0]]['default'] = $value[5];
			// $table[$value[0]]['extra'] = $value[6];
			// $table[$value[0]]['comment'] = $value[8];
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
 * 获取数据库中的全部表
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param 	 Array $config 			数据库配置信息
 * @return   Array[tables]     		数据库中的全部表
 */
function get_database_tebles($config){
	// 1.连接数据库
	$conn = @mysqli_connect($config['host'], $config['user'], $config['password']);
	if (!$conn) {
		return_result('连接数据库失败，请检查该配置是否能连接数据库',false);
	}
	mysqli_query($conn, 'SET NAMES utf8');
	// 2.选择数据库
	if (!mysqli_query($conn, "use {$config['db']}")) {
		return_result('选择数据库失败，请检查是否有该数据库',false);
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
 * 重新调整json缩进
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    String     $json json字符串
 * @return   String           处理后的json字符串
 */
function reindent_json($json){
	preg_match_all('/\{|\}|,/',$json,$matches);
	$tab = 0;
	$eol = PHP_EOL;
	foreach ($matches[0] as $key => $value) {
		if ($value == '{') {
			$json = preg_replace('/\{(?!'.$eol.')/', "{".$eol.str_repeat("\t", ++$tab), $json, 1);
		}elseif ($value == '}') {
			$json = preg_replace('/([^\t])\}/', "$1".$eol.str_repeat("\t", --$tab)."}", $json, 1);
		}elseif ($value == ',') {
			$json = preg_replace('/,(?!'.$eol.')/', ",".$eol.str_repeat("\t", $tab), $json, 1);
		}
	}
	return $json;
}

/**
 * 检查bean是否完整
 * @Author   zjf
 * @DateTime 2017-03-15
 * @param    array     $beans 要处理的数组
 * @return   null            没有返回值
 */
function handle_beans(&$beans){
	// var_dump($beans);die();
	
	foreach ($beans as $bean_name => &$bean) {
		// var_dump(is_array($bean['id']));die();
		// 最外层tbl_comment，id，col，join
		if (!isset($bean['tbl_comment']) || !is_string($bean['tbl_comment'])) {
			return_result($bean_name.'的tbl_comment未设置或不是字符串',false);
		}elseif (!isset($bean['id']) || !is_array($bean['id'])) {
			return_result($bean_name.'的id未设置或不是数组',false);
		}elseif (!isset($bean['col']) || !is_array($bean['col'])) {
			return_result($bean_name.'的col未设置或不是数组',false);
		}

		// id
		if (!isset($bean['id']['field']) || !is_string($bean['id']['field'])) {
			return_result($bean_name.'的id的field未设置或不是字符串',false);
		}
		if (!isset($bean['id']['comment']) || !is_string($bean['id']['comment'])) {
			return_result($bean_name.'的id的comment未设置或不是字符串',false);
		}
		
		//col
		foreach ($bean['col'] as $col_name => &$column) {
			if (!is_array($bean['col'][$col_name])) {
				return_result($bean_name.'的col中的字段不是数组',false);
			}
			if (!isset($column['field']) || !is_string($column['field'])) {
				return_result($bean_name.'的col中的字段的field未设置或不是字符串',false);
			}
			if (!isset($column['comment']) || !is_string($column['comment'])) {
				return_result($bean_name.'的col中的字段的comment未设置或不是字符串',false);
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

		unset($column);// 不unset($column);的话extras去循环$bean['col']会将第一个元素重复
		//join
		if (!isset($bean['join']) || !is_array($bean['join'])) {
			$bean['join'] = array();
		}else{
			foreach ($bean['join'] as $join_table_name => &$join_table) {
				if (!is_array($bean['join'][$join_table_name])) {
					return_result('连接表'.$join_table_name.'不是数组',false);
				}
				if (!isset($join_table['pri_field']) || !is_string($join_table['pri_field'])) {
					return_result('连接表'.$join_table_name.'中的pri_field未设置或不是字符串',false);
				}else{
					if (strpos($join_table['pri_field'], '.') === false) {
						// pri_field没有指定表
						$join_table['pri_field'] = $join_table_name.'.'.$join_table['pri_field'];
					}
				}
				if (!isset($join_table['join_field']) || !is_string($join_table['join_field'])) {
					return_result('连接表'.$join_table_name.'中的join_field未设置或不是字符串',false);
				}
				if (!isset($join_table['col'])) {
					// 没有要显示的col
					$join_table['col'] = array();
				}else{
					foreach ($join_table['col'] as $join_col_name => &$join_col) {
						if (!is_array($join_table['col'][$join_col_name])) {
							return_result('连接表'.$join_table_name.'的col中的字段不是数组',false);
						}
						if (!isset($join_col['field']) || !is_string($join_col['field'])) {
							return_result('连接表'.$join_table_name.'的col中的field未设置或不是字符串',false);
						}
						if (!isset($join_col['comment']) || !is_string($join_col['comment'])) {
							return_result('连接表'.$join_table_name.'的col中的comment未设置或不是字符串',false);
						}
						if (!isset($join_col['is_group_concat'])) {
							$join_col['is_group_concat'] = 'false';
						}
					}
				}
				if (!isset($join_table['manipulation_col'])) {
					// 没有要操作的列
					$join_table['manipulation_col'] = array();
				}else{
					foreach ($join_table['manipulation_col'] as $join_mani_col_name => &$join_mani_col) {
						if (!is_array($join_table['manipulation_col'][$join_mani_col_name])) {
							return_result('连接表'.$join_table_name.'的manipulation_col中的字段不是数组',false);
						}
						if (!isset($join_mani_col['field']) || !is_string($join_mani_col['field'])) {
							return_result('连接表'.$join_table_name.'中的manipulation_col中的field未设置或不是字符串',false);
						}
						if (!isset($join_mani_col['comment']) || !is_string($join_mani_col['comment'])) {
							return_result('连接表'.$join_table_name.'中的manipulation_col中的comment未设置或不是字符串',false);
						}
						if (!isset($join_mani_col['option_field_conf']) || !is_array($join_mani_col['option_field_conf'])) {
							return_result('连接表'.$join_table_name.'中的manipulation_col中的option_field_conf未设置或不是数组',false);
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

		// extras：生成时需要的信息
		$form_fields = array();// c接受的字段 array("'col1'","'col2'");
		$join_manipulation = array();// c需要操作的join信息 array('table1'=>'prifield','table2'=>'prifield')
		$files = array();// c处理格式是文件的字段 array("'col1'","'col2'");
		$multichoice = array();// c处理格式是多选的字段 array("'col1'","'col2'");
		$get_form_data = array();// c生成get_form_data array("array('tablename1','optioncol','showcol')", "array('tablename2','optioncol','showcol')")
		$jointable = array();// c生成jointable array('tablename1','tablename2')


		$model_select_fields = array("$bean_name.{$bean['id']['field']}");// m查询的字段 array("col1","col2");
		$model_join = array();// m连接的字段 array("JOIN('table1', 'col1=col2', 'left')", "JOIN('table2', 'col1=col2', 'left')");

		
		$view_show_col = array();// v显示的字段
		$init_form_s_m = array();// v判断是否有select和mutilchoice的字段，并初始化

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
				$view_show_col[] = array(
					"field" => $column['field'],
					"comment" => $column['comment']
					);
			}
		}

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
	}
}

function has_null($config){
	$is_has_null = false;
	foreach ($config as $key => $value) {
		if ($value == null){
			$is_has_null = true;
			break;
		}
	}
	return $is_has_null;
}

function return_result($info, $status){
	$result = array();
	$result['info'] = $info;
	$result['status'] = $status;
	header("Content-type: application/json");
	echo json_encode($result);
	die();
}

/**
 * 通过表名创建模型名（将单词大写，加上_Model）
 * @Author   zjf
 * @DateTime 2017-04-14
 * @param    string     $str        表名
 * @return   string                 模型名
 */
function ucmodel($str){
	return implode('_', array_map('ucfirst', explode('_', $str))).'_Model';
}




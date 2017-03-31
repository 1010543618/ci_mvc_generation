<?php
$config = array();

header("Content-type:text/html;Charset=utf-8");
if ($_GET) {
	switch ($_GET['step']) {
		case '1':
			$config = array(
				'host' => $_GET['host'],
				'user' => $_GET['user'],
				'password' => $_GET['pwd'],
				'db' => $_GET['db']
				// 'tables' => $_GET['tables']
				);
			if (has_null($config)) {
				return_result('有必填项没填啊！', false);
			}

			return_result(get_database_tebles($config), true);
			break;

		case '2':
			$config = array(
				'host' => $_GET['host'],
				'user' => $_GET['user'],
				'password' => $_GET['pwd'],
				'db' => $_GET['db'],
				'tables' => isset($_GET['tables']) ? $_GET['tables'] : null
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

		case '3':
			$config = array(
				//mvc相对于当前目录的路径
				'm_folder' => $_GET['m_folder'],
				//给v_folder，c_folder添加上子路径
				'v_folder' => $_GET['v_folder'] . ($_GET['v_child_folder'] ? '/'.$_GET['v_child_folder'] : ''),
				'c_folder' => $_GET['c_folder'] . ($_GET['c_child_folder'] ? '/'.$_GET['c_child_folder'] : ''),
				//给v_child_folder，c_child_folder添加"/"为了在ci的URL函数中使用
				'v_child_folder' => $_GET['v_child_folder'] ? $_GET['v_child_folder'].'/' : '',
				'c_child_folder' => $_GET['v_child_folder'] ? $_GET['c_child_folder'].'/' : ''
				);
			if(output_mvc_file()){
				echo "生成mvc文件成功";
			}
		default:
			break;
	}
}



/**
 * 输出mvc文件
 * @Author   zjf
 * @DateTime 2017-03-11
 * @return   bool     是否输出成功
 */
function output_mvc_file(){
	global $config;
	//读配置文件
	$beans = preg_replace('/[\n\t\r]/', '', file_get_contents('./config.json'));
	$beans = json_decode($beans, TRUE);
	if ($beans == null) {
		echo "配置文件格式有误，请检查配置文件是否符合json格式";die();
	}
	//将空的名称替换
	replace_null($beans);
	//循环生成
	foreach ($beans as $bean_name => $bean) {
		
		//models
		$model_name = ucfirst($bean_name).'_Model';
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
  		$controller_name = ucfirst($bean_name);
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
 * @return   string     返回表对应的配置（json）字符串
 */
function create_tables_bean($config){
	// 1。获取tables数组并处理成规定格式
	$tables_source = get_tables_info($config);
	$tables = array();
	foreach ($tables_source as $table_name => $table_source) {
		$tables[$table_name] = array();
		$tables[$table_name]['tbl_comment'] = $table_source['tbl_comment'];
		// 当前表是否有主键
		$has_key = false;
		foreach ($table_source['col'] as $column) {
			if ($column['key'] == "PRI") {
				// 主键
				if ($has_key == true) {
					return_result("配置文件中不能有两个主键",false);
				}
				$tables[$table_name]['id']['field'] = $column['field'];
				$tables[$table_name]['id']['comment'] = $column['comment'];
				$has_key = true;
			}else {
				// 普通字段
				$col['field'] = $column['field'];
				$col['comment'] = $column['comment'];
				// type和validation
				$left_bracket_pos = strpos($column['type'],'(');
				$type = substr($column['type'], 0, $left_bracket_pos);
				$type_bracket = substr($column['type'], $left_bracket_pos + 1, -1);
				switch ($type) {
					case 'int':
						$col['type'] = 'text';
						$col['validation'] = 'type="number" ';
						$col['validation'] .= 'maxlength="'.$type_bracket.'" ';
						$col['validation'] .= $column['is_nullable'] == 'NO' && $column['default'] === null ? 'required ' : '';
						break;

					case 'varchar':
						$col['type'] = 'text';
						$col['validation'] = 'maxlength="'.$type_bracket.'" ';
						$col['validation'] .= $column['is_nullable'] == 'NO' && $column['default'] === null ? 'required ' : '';
						break;

					default:
						$col['type'] = 'text';
						$col['validation'] .= $column['is_nullable'] == 'NO' && $column['default'] === null ? 'required ' : '';
						break;
				}
				$tables[$table_name]['col'][] = $col;
			}
		}
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
		$tables[$value[0]]['tbl_comment'] = $value[17];
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
		$fields = mysqli_fetch_fields($result);
		// var_dump(mysqli_fetch_all($result));die();
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
 * 将配置数组中的空名称替换为字符串null
 * @Author   zjf
 * @DateTime 2017-03-15
 * @param    array     &$arr 要处理的数组
 * @return   null           没有返回值
 */
function replace_null(&$arr){
	foreach ($arr as $key => &$value) {
		if (($key == 'tbl_comment' || $key == 'comment') && $value == '') {
			$value = 'null';
		}
		if (is_array($value)) {
			replace_null($value);
		}
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
	// header("Content-type: application/json");
	echo json_encode($result);
	die();
}


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
				);
			if(output_config_file()){
				header("Location:index2.html");
			}
			break;
		case '2':
			$config = array(
				//mvc相对于当前目录的路径
				'm_folder' => $_GET['m_folder'],
				'v_folder' => $_GET['v_folder'],
				'c_folder' => $_GET['c_folder']
				);
			if(output_mvc_file()){
				echo "生成mvc文件成功";
			}
		default:
			break;
	}
}

// if (output_config_file()) {
// 	echo "配置文件创建成功";
// }
// output_mvc_file();



/**
 * 输出mvc文件
 * @Author   zjf
 * @DateTime 2017-03-11
 * @return   bool     是否输出成功
 */
function output_mvc_file(){
	global $config;
	$beans = preg_replace('/[\n\t\r]/', '', file_get_contents('./create_config.json'));
	$beans = json_decode($beans, TRUE);
	if ($beans == null) {
		echo "配置文件格式有误，请检查配置文件是否符合json格式";die();
	}
	foreach ($beans as $bean_name => $bean) {
		//models
		$model_name = ucfirst($bean_name).'_Model';
		ob_start();
        require('./m_template.php');
        $model = ob_get_contents();
        @ob_end_clean();
  		file_put_contents("{$config['m_folder']}/$model_name.php", $model);
		
		//views
		ob_start();
        require('./v_template.php');
        $view = ob_get_contents();
        @ob_end_clean();
  		file_put_contents("{$config['v_folder']}/$bean_name.html", $view);

  		//controllers
  		$controller_name = ucfirst($bean_name);
  		ob_start();
        require('./c_template.php');
        $view = ob_get_contents();
        @ob_end_clean();
  		file_put_contents("{$config['c_folder']}/$controller_name.php", $view);
  		
	}
	return true;
}

/**
 * 输出配置文件
 * @Author   zjf
 * @DateTime 2017-03-10
 * @return   bool     是否输出成功
 */
function output_config_file(){
	// 1。获取tables数组
	$tables = get_tables_info();
	// 2。将tables数组转换为json字符串（不自动转换为unicode编码）
	if (version_compare(PHP_VERSION,'5.4.0','<'))
		$tables = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function($matchs){return iconv('UCS-2BE','UTF-8',pack('H4', $matchs[1]));}, $tables);
	else
		$tables = json_encode($tables, JSON_UNESCAPED_UNICODE);
	// 3。现在tables是gbk编码，转换为utf8
	// $tables = iconv('GB2312', 'UTF-8', $tables);
	// 4。将table调整缩进
	$tables = reindent_json($tables);
	// 5。将table输出到文件
	if (file_put_contents('./create_config.json', $tables)) {
		return true;
	}else{
		return false;
	}
	
}

/**
 * 获取数据库中表的信息
 * @Author   zjf
 * @DateTime 2017-03-10
 * @return   Array[tablename][tableinfo]     据库中表的信息
 */
function get_tables_info(){
	global $config;
	// 1.连接数据库
	$conn = mysqli_connect($config['host'], $config['user'], $config['password']) or exit('连接数据库失败，请检查该配置是否能连接数据库');
	mysqli_query($conn, 'SET NAMES utf8');
	// 2.选择数据库
	mysqli_query($conn, "use {$config['db']}") or exit('选择数据库失败，请检查是否有该数据库');
	// 3.获取全部表
	$result = mysqli_query($conn, "show table status");
	foreach (mysqli_fetch_all($result) as $value) {
		#0表名，17表注释
		$tables[$value[0]] = array();
		$tables[$value[0]]['tbl_comment'] = $value[17];
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
			if ($col['key'] == "PRI") {
				$table['id'] = $col;
			}elseif ($col['extra'] == "auto_increment") {
				continue;
			}else{
				$table['col'][] = $col;
			}
		}
	}
	mysqli_close($conn);
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





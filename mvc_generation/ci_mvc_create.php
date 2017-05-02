<?php
require_once('./helpers/json_helper.php');
require_once('./helpers/common_helper.php');
require_once('./libraries/Generation.php');

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
				return_json('有必填项没填啊！', false);
			}
			$generation = new Generation($config);
			return_json($generation->get_database_tebles(), true);
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
				return_json('请至少选择一张表', false);
			}
			if (has_null($config)) {
				return_json('有必填项没填啊！', false);
			}
			
			$generation = new Generation($config);
			$tables_bean = $generation->create_tables_bean();

			// 将tables_bean输出到config
			if (!file_put_contents('./config.json', $tables_bean))
				return_json('创建配置文件失败', true);
			return_json($tables_bean, true);
			break;

		case '3_4':
			// 使用设置的配置和配置文件的配置生成mvc文件
			$config = array(
				'generation_config' => $_POST['generation_config'],
				//mvc相对于当前目录的路径
				'folder'=>array(
					'm' => $_POST['m_folder'],
					//给v_folder，c_folder添加上子路径
					'v' => $_POST['v_folder'] . ($_POST['v_child_folder'] ? '/'.$_POST['v_child_folder'] : ''),
					'c' => $_POST['c_folder'] . ($_POST['c_child_folder'] ? '/'.$_POST['c_child_folder'] : ''),
					//给v_child_folder，c_child_folder添加"/"为了在ci的URL函数中使用
					'v_child' => $_POST['v_child_folder'] ? $_POST['v_child_folder'].'/' : '',
					'c_child' => $_POST['v_child_folder'] ? $_POST['c_child_folder'].'/' : ''
					)
				);
					
			// 判断是否保存generation_config
			if (isset($_POST['is_save_config']) &&  $_POST['is_save_config'] == 'true') {
				file_put_contents('./config.json', $config['generation_config']);
			}
			$generation = new Generation($config);
			$info = $generation->output_mvc_file();
			return_json($info, true);
		default:
			break;
	}
}




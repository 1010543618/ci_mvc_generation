<?php
/**
 * 返回json
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    mul        $info     信息
 * @param    bool       $status   是否成功
 * @return   null
 */
function return_json($info, $status){
	$result = array();
	$result['info'] = $info;
	$result['status'] = $status;
	// header("Content-type: application/json");
	echo json_encode($result);
	die();
}

/**
 * 检查是否有null值
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    array        $array     要检查的数组
 * @return   bool                    是否有null值
 */
function has_null($array){
	$is_has_null = false;
	foreach ($array as $key => $value) {
		if ($value == null){
			$is_has_null = true;
			break;
		}
	}
	return $is_has_null;
}
<?php
/**
 * 添加json缩进
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    String     $json   json字符串
 * @return   String     		添加缩进后的json字符串
 */
function add_json_indent($json){
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
 * 去除json缩进
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    String     $json   json字符串
 * @return   String     		去除缩进后的json字符串
 */
function remove_json_indent($json){
	return preg_replace('/[\n\t\r]/', '', $json);
}

/**
 * 编码json不转义unicode
 * @Author   zjf
 * @DateTime 2017-03-10
 * @param    nul     $data   	要转义的数据
 * @return   String     		转义后的json字符串
 */
function json_unescaped_unicode_encode($data){
	if (version_compare(PHP_VERSION,'5.4.0','<'))
		return preg_replace_callback("#\\\u([0-9a-f]{4})#i", function($matchs){return iconv('UCS-2BE','UTF-8',pack('H4', $matchs[1]));}, $data);
	else
		return json_encode($data, JSON_UNESCAPED_UNICODE);
}

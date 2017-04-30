<?php echo "<?php".PHP_EOL ?>
// <?php echo $bean['tbl_comment'] ?>控制器
defined('BASEPATH') OR exit('No direct script access allowed');

class <?php echo $controller_name ?> extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->bean = array(
<?php if ($bean["extras"]['judge']['has_id']): ?>
            'ids' => array(<?php echo implode(', ', $bean["extras"]['ids']) ?>),
<?php endif ?>
			'form_fields' => array(<?php echo implode(', ', $bean["extras"]['form_fields']) ?>),
			'files' => array(<?php echo implode(', ', $bean["extras"]['files']) ?>),
			'multichoice' => array(<?php echo implode(', ', $bean["extras"]['multichoice']) ?>),
			'get_form_data' => array(<?php echo implode(', ', $bean["extras"]['get_form_data']) ?>),
            'join_manipulation_pri_col' => array(<?php echo implode(', ', $bean["extras"]['join_manipulation_pri_col']) ?>)
			);
<?php foreach ($bean["extras"]['jointable'] as $jointable): // 引入所有要操作的表模型?>
		$this->load->model('<?php echo $jointable.'_model'?>');
<?php endforeach ?>
	}

	public function index()
	{
		$this->loadViewhf('<?php echo "back/{$bean_name}.html" ?>');
	}
<?php /*----------col的type=file上传文件处理----------*/?>
<?php foreach ($bean['col'] as $key => $column): ?>
<?php   if ($column['type'] == 'file'): ?>
    public function upload_<?php echo $column['field']?>(){
        // header("Content-type: application/json");
    	$config['upload_path']      = './uploads/<?php echo $column['file_path'] ?>/'.date("Ym");
        $config['allowed_types']    = 'gif|jpg|png';
        $config['max_size']     = 100;
        $config['max_width']        = 1024;
        $config['max_height']       = 768;
        $config['encrypt_name']     = true;
        $this->load->library('upload', $config);
        if (!is_dir($config['upload_path'])) {
            if (!mkdir($config['upload_path'],0777,true)) {
                echo json_encode(array('error'=>array('创建文件夹失败')));die();
            }
        }
        if (!$this->upload->do_upload('<?php echo $column['field']?>-file'))
        {
			echo json_encode(array('error'=>array($this->upload->display_errors())));
            die();
        }else{
            $file_path = strstr($config['upload_path'],'uploads').'/'.$this->upload->data()['file_name'];
            echo json_encode(array('error'=>array(),'file_path'=>$file_path));
            die();
        }
	}
<?php   endif ?>
<?php endforeach ?>
<?php /*----------/col的type=file上传文件处理----------*/?>

<?php if (0): //废弃了不执行拉！！！?>
<?php /*----------为multichoice重写insert，update*/?>
<?php
	$multichoice = null;
	if(isset($bean['join'])){
		foreach ($bean['join'] as $join_table_name => $join_table) {
			if (isset($join_table['form_type']) && $join_table['form_type'] == 'multichoice') {
				$multichoice[] = $join_table['pri_field'];
			}
		}
	}
?>
<?php if ($multichoice): ?>
	public function insert(){
		// 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 处理multichoice
<?php foreach ($multichoice as $key => $value): ?>
		$form_data['<?php echo $value ?>'] = $form_data['<?php echo $value ?>'] ? implode(',',$form_data['<?php echo $value ?>']) : '';
<?php endforeach ?>
        if ($this->{$this->model_name}->insert($form_data)) {
            $result['status'] = true;
        }else{
            $result['status'] = false;
        }
        $this->returnResult($result);
	}

	public function update(){
		// 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 获取id
        $id = array($this->bean['id'] => $this->input->post($this->bean['id'], TRUE));
        // 处理multichoice
<?php foreach ($multichoice as $key => $value): ?>
		$form_data['<?php echo $value ?>'] = $form_data['<?php echo $value ?>'] ? implode(',',$form_data['<?php echo $value ?>']) : '';
<?php endforeach ?>
        if ($this->{$this->model_name}->update($form_data, $id)) {
            $result['status'] = true;
        }else{
            $result['status'] = false;
        }
        $this->returnResult($result);
	}
<?php endif ?>
<?php /*----------/为multichoice重写insert，update*/?>

	public function get_form_data(){
<?php 	foreach ($bean['join'] as $join_table_name => $join_table): ?>
<?php 		foreach ($join_table['col'] as $join_table_col): ?>
		$field = array('<?php echo $join_table_col['field'] ?>');
		$result['<?php echo $join_table_name ?>'] = $this-><?php echo $join_table_name.'_modle' ?>->getAll($field);
<?php 		endforeach ?>
<?php 	endforeach ?>
		$result['status'] = true;
		$this->returnResult($result);
	}
<?php endif //废弃了不执行拉！！！?>

}
<?php echo "<?php".PHP_EOL ?>
// 院系控制器
defined('BASEPATH') OR exit('No direct script access allowed');

class <?php echo $controller_name ?> extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->bean = array(
			'id' => '<?php echo $bean['id']['field'] ?>',
<?php //生成表单需要的字段
	$form_fields = "'{$bean['col'][0]['field']}'";
	foreach ($bean['col'] as $key => $column) {
		if ($key == 0) continue;
		$form_fields = $form_fields . ", '{$column['field']}'";
	}
	if (isset($bean['join'])) {
		foreach ($bean['join'] as $key => $join_table) {
			$form_fields = $form_fields . ", '{$join_table['pri_field']}'";
		}
	}
?>
			'form_fields' => array(<?php echo $form_fields ?>)
			);
<?php if (isset($bean['join'])): //引入join的表的模型?>
<?php	foreach ($bean['join'] as $join_table_name => $join_table): ?>
		$this->load->model('<?php echo $join_table_name.'_model' ?>');
<?php 	endforeach ?>
<?php endif ?>
	}

	public function index()
	{
		$this->loadViewhf('<?php echo "back/{$bean_name}.html" ?>');
	}

<?php if (isset($bean['join'])): //为add，edie表单查找外链接的表的数据?>
	public function get_form_data(){
<?php 	foreach ($bean['join'] as $join_table_name => $join_table): ?>
		$field = array('<?php echo $join_table['join_field'] ?>', '<?php echo $join_table['join_show_field'] ?>');
		$result['<?php echo $join_table_name ?>'] = $this-><?php echo $join_table_name.'_model' ?>->getAll($field);
<?php 	endforeach ?>
		$result['status'] = true;
		$this->returnResult($result);
	}
<?php endif ?>

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
}
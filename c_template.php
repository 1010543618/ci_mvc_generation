<?php echo "<?php".PHP_EOL ?>
// 院系控制器
defined('BASEPATH') OR exit('No direct script access allowed');

class <?php echo $controller_name ?> extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->bean = array(
			'id' => '<?php echo $bean['id']['field'] ?>',
<?php 
	$form_fields = "'{$bean['col'][0]['field']}'";
	foreach ($bean['col'] as $key => $column) {
		if ($key == 0) continue;
		$form_fields = $form_fields . ", '{$column['field']}'";
	} 
?>
			'form_fields' => array(<?php echo $form_fields ?>)
			);
	}

	public function index()
	{
		$this->loadViewhf('<?php echo "back/{$bean_name}.html" ?>');
	}

	
}
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->table_name = strtolower(get_class($this));
        $this->model_name = isset($this->model_name) ? $this->model_name : $this->table_name . '_model';
        $this->load->model($this->model_name);
    }

    public function selectPage() {

        $start = $this->input->get('start', true);
        $length = $this->input->get('length', true);
        $where_source = $this->input->get('custom_search', true) ? $this->input->get('custom_search', true) : array();
        $where = array();
        foreach ($where_source as $key => $value) {
            if (strpos($key, '-') === false)
                $where["{$this->table_name}.$key"] = $value;
            else
                $where[str_replace('-', '.', $key)] = $value;
        }
        // var_dump($where);die();
        $result['draw'] = $this->input->get('draw', true);
        $result['data'] = $this->{$this->model_name}->selectPage($start, $length, $where);
        // echo $this->db->last_query();die();
        $result['recordsTotal'] = $this->{$this->model_name}->countAll($where);
        $result['recordsFiltered'] = $result['recordsTotal'];

        $this->returnResult($result);
    }

    public function insert() {
        // 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 处理mutilchoice字段
        foreach ($this->bean['multichoice'] as $multichoice) {
            $form_data[$multichoice] = $form_data[$multichoice] ? implode(',', $form_data[$multichoice]) : '';
        }
        // 处理要操作的外链接表
        $join_data = array();
        foreach ($this->bean['join_manipulation_pri_col'] as $table_name => $fields) {
            $join_data[$table_name] = $form_data[$table_name];
            unset($form_data[$table_name]);
        }
        // 插入数据
        if (!$this->{$this->model_name}->insert($form_data)) {
            $result['status'] = false;
            $this->returnResult($result);
        }
        // 将操作的外链接表数据插入
        foreach ($this->bean['join_manipulation_pri_col'] as $table_name => $fields) {
            $insert_batch_data = array();
            foreach ($fields as $pri_field => $field) {
                $pri_cols[$field] = isset($form_data[$pri_field]) ? $form_data[$pri_field] : $this->db->insert_id();
            }
            foreach ($join_data[$table_name] as $col_name => $col_datas) {
                $data = $pri_cols;
                foreach ($col_datas as $col_data) {
                    $data[$col_name] = $col_data;
                    $insert_batch_data[] = $data;
                }
            }
            $model = "{$table_name}_model";
            if (!$this->$model->insert_batch($insert_batch_data)) {
                $result['status'] = false;
                $this->returnResult($result);
            }
        }
        $result['status'] = true;
        $this->returnResult($result);
    }

    public function update() {
        if (!isset($this->bean['ids'])) {
            $result['status'] = false;
            $result['msg'] = '没有id无法更新';
            $this->returnResult($result);
        }
        // 获取form_fields的字段
        foreach ($this->bean['form_fields'] as $form_field) {
            $form_data[$form_field] = $this->input->post($form_field, TRUE);
        }
        // 获取id
        $ids = $this->input->post('ids', TRUE);
        // 处理mutilchoice字段
        foreach ($this->bean['multichoice'] as $multichoice) {
            $form_data[$multichoice] = $form_data[$multichoice] ? implode(',', $form_data[$multichoice]) : '';
        }
        // 若有文件update前将文件位置保存
        if ($this->bean['files']) {
            $files = $this->{$this->model_name}->getFieldsById($ids, implode(',', $this->bean['files']));
        }
        // 处理要操作的外链接表
        $join_data = array();
        foreach ($this->bean['join_manipulation_pri_col'] as $table_name => $fields) {
            $join_data[$table_name] = $form_data[$table_name];
            unset($form_data[$table_name]);
        }
        // 更新数据
        if ($this->{$this->model_name}->update($form_data, $ids)) {
            $result['status'] = true;
        } else {
            $result['status'] = false;
        }
        // 若有文件进行了修改，update后删除删除原文件
        if ($this->bean['files']) {
            foreach ($files as $file_col_name => $file_name) {
                if ($file_name != $form_data[$file_col_name]) {
                    @unlink('./' . $file);
                }
            }
        }
        // 修改后删除要操作的外链接表的数据再重新插入
        foreach ($this->bean['join_manipulation_pri_col'] as $table_name => $fields) {
            $insert_batch_data = array();
            // var_dump($this->bean['join_manipulation_pri_col'],  $table_name, $fields);die();
            foreach ($fields as $pri_field => $field) {
                $pri_cols[$field] = $ids[$pri_field];
            }
            foreach ($join_data[$table_name] as $col_name => $col_datas) {
                $data = $pri_cols;
                foreach ($col_datas as $col_data) {
                    $data[$col_name] = $col_data;
                    $insert_batch_data[] = $data;
                }
            }
            
            $model = "{$table_name}_model";
            if (!$this->$model->delete($ids)) {
                $result['status'] = false;
                $this->returnResult($result);
            }
            if (!$this->$model->insert_batch($insert_batch_data)) {
                $result['status'] = false;
                $this->returnResult($result);
            }
        }
        $this->returnResult($result);
    }

    public function get_form_data() {
        foreach ($this->bean['get_form_data'] as $table_col) {
            $result[$table_col[0]] = $this->{$table_col[0] . '_model'}->getAll(array($table_col[1], $table_col[2]));
        }
        $result['status'] = true;
        $this->returnResult($result);
    }

    public function delete() {
        if (!isset($this->bean['ids'])) {
            $result['status'] = false;
            $result['msg'] = '没有id无法更新';
            $this->returnResult($result);
        }
        // 获取id
        $ids = $this->input->post('ids', TRUE);
        // 若有文件delete前将文件位置保存
        if ($this->bean['files']) {
            $files = $this->{$this->model_name}->getFieldsById($id, implode(',', $this->bean['files']));
        }
        // 若有要操作的外链接表，应该在删除数据前删除外链表中的数据
        foreach ($this->bean['join_manipulation_pri_col'] as $join_manipulation_pri_col) {
            $insert_batch_data = array();
            $model = "{$join_manipulation_pri_col[0]}_model";
            if (!$this->$model->delete($id)) {
                $result['status'] = false;
                $this->returnResult($result);
            }
        }
        // 删除数据
        if ($this->{$this->model_name}->delete($ids)) {
            $result['status'] = true;
        } else {
            $result['status'] = false;
        }
        // 若有文件delete后删除删除原文件
        if ($this->bean['files']) {
            foreach ($files as $file) {
                @unlink('./' . $file);
            }
        }
        $this->returnResult($result);
    }

    protected function loadViewhf($view, $response_data = array()) {
        $data = $response_data;
        if ($cj = $this->_getViewCssjs($view)) {
            $data['css'] = $cj['css'];
            $data['js'] = $cj['js'];
        } else {
            $data['css'] = array();
            $data['js'] = array();
        }
        $this->load->view('back/header.html', $data);
        $this->load->view($view);
        $this->load->view('back/footer.html');
    }

    protected function returnResult($result) {
        header("Content-type: application/json");
        echo json_encode($result);
        die();
    }

    private function _getViewCssjs($path) {
        // 获取全部cssjs
        ob_start();
        require('views/cssjs.json');
        $cssjs_str = ob_get_contents();
        @ob_end_clean();
        $cssjs = json_decode($cssjs_str, TRUE);

        // 获取找出当前cssjs
        while (true) {
            if (array_key_exists($path, $cssjs)) {
                $result['css'] = $cssjs[$path]['css'];
                $result['js'] = $cssjs[$path]['js'];
                return $result;
            }elseif (strrchr($path, '/') === false) {
                $result['css'] = $cssjs['/']['css'];
                $result['js'] = $cssjs['/']['js'];
                return $result;
            }
            $path = str_replace(strrchr($path, '/'), '', $path);
        }
        return false;
    }

}

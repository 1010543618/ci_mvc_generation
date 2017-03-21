<!-- page content -->
<div class="right_col" role="main">
  <div class="">
    <div class="page-title">
      <div class="title_left">
        <h3><?php echo $bean['tbl_comment']?>管理</h3>
      </div>

      <div class="title_right">

        <div class="col-md-5 col-sm-5 col-xs-12 pull-right text-right">
          <button class="btn btn-success" onClick="add_dialog()">添加<?php echo $bean['tbl_comment']?></button>
        </div>
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <!-- table -->
        <table id="responsived-atatable" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
          <thead>
            <tr>
              <th>序号</th>
<?php /*----------生成表格头----------*/?>
<?php foreach ($bean['col'] as $key => $column): //主表字段?>
              <th><?php echo $column['comment']?></th>
<?php endforeach //end主表字段?>
<?php if (isset($bean['join'])): //连接表字段?>
<?php   foreach ($bean['join'] as $join_table): ?>
<?php     if (isset($join_table['col'])): ?>
<?php       foreach ($join_table['col'] as $column): ?>
              <th><?php echo $column['comment']?></th>
<?php       endforeach ?>
<?php     endif ?>
<?php   endforeach ?>
<?php endif //end连接表字段?>
<?php /*----------/生成表格头----------*/?>
              <th>修改</th>
              <th>删除</th>
            </tr>
          </thead>

        </table>
        <!-- /table -->

        <!-- add -->
        <form id="js-add-form" data-parsley-validate style="display: none">
<?php /*----------生成添加表单----------*/?>
<?php foreach ($bean['col'] as $key => $column): //主表字段?>
          <label><?php echo $column['comment']?></label>
          <input name="<?php echo $column['field']?>" type="text" class="form-control"/>
<?php endforeach //end主表字段?>
<?php if (isset($bean['join'])): //连接表字段?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
          <label><?php echo $join_table['comment']?></label>
<?php     if ($join_table['form_type'] == 'muticheck'): //连接表字段类型是muticheck?>
          <div class="row js-checkbox-<?php echo $join_table_name?>"></div>
<?php     else: //连接表字段类型是其他?>
          <select name="<?php echo $join_table['pri_field']?>" class="js-select-<?php echo $join_table_name?> form-control"></select>
<?php     endif ?>
<?php   endforeach ?>
<?php endif //end连接表字段?>
<?php /*----------/生成添加表单----------*/?>
          <br/>
          <span class="btn btn-primary">添加<?php echo $bean['tbl_comment']?></span>
        </form>
        <!-- /add -->

        <!-- edit -->
        <form id="js-edit-form" data-parsley-validate style="display: none">
<?php /*----------生成修改表单----------*/?>
<?php foreach ($bean['col'] as $key => $column): //主表字段?>
          <label><?php echo $column['comment']?></label>
          <input name="<?php echo $column['field']?>" type="text" class="form-control"/>
<?php endforeach ?>
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): //连接表字段?>
          <label><?php echo $join_table['comment']?></label>
<?php     if ($join_table['form_type'] == 'muticheck'): //连接表字段类型是muticheck?>
          <div class="row js-checkbox-<?php echo $join_table_name?>"></div>
<?php     else: //连接表字段类型是其他?>
          <select name="<?php echo $join_table['pri_field']?>" class="js-select-<?php echo $join_table_name?> form-control"></select>
<?php     endif ?>
<?php   endforeach ?>
<?php endif //end连接表字段?>
<?php /*----------生成修改表单----------*/?>
          <input name="<?php echo $bean['id']['field']?>" type="text" style="display: none" />
          <br/>
          <span class="btn btn-primary">修改<?php echo $bean['tbl_comment']?></span>
        </form>
        <!-- /edit -->   
      </div>
    </div>
  </div>
</div>
<!-- /page content -->

<script>
  function init_table(){
    window.DEP_TABLE = $('#responsived-atatable').DataTable({
      "ordering": false,
      "searching": false,
      "serverSide": true,
      "ajax": "<?php echo "<?=site_url('back/{$bean_name}/selectPage')?>"?>",
        
        "columns": [
          {"data":"<?php echo $bean['id']['field']?>" },
<?php foreach ($bean['col'] as $key => $column): ?>
          {"data":"<?php echo $column['field']?>" },
<?php endforeach ?>
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table): ?>
<?php     if (isset($join_table['col'])): ?>
<?php       foreach ($join_table['col'] as $column): ?>
          {"data":"<?php echo $column['field']?>" },
<?php       endforeach ?>
<?php     endif ?>
<?php   endforeach ?>
<?php endif ?>
          { 
            "data": null,
            "render": function(data) {
              data = JSON.stringify(data);
              data = data.replace(/"/g, '&quot;');
              var editdiv = '<a class="edit green" onClick="edit_dialog(\''+data+'\')"><i class="fa fa-pencil bigger-130"></i>修改</a>';
              return '<div class="action-buttons">'+ editdiv +'</div>';
            }
          },
          { 
            "data": "<?php echo "{$bean['id']['field']}"?>",
            "render": function(data) {
              var deldiv = '<a class="del red" onClick="del_confirm('+data+')"><i class="fa fa-trash bigger-130"></i>删除</a>';
              return '<div class="action-buttons">'+ deldiv +'</div>';
            }
          }
        ],
      
      
        "language": {
            "processing": "处理中...",
            "lengthMenu": "显示 _MENU_ 项结果",
            "zeroRecords": "没有匹配结果",
            "info": "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
            "infoEmpty": "显示第 0 至 0 项结果，共 0 项",
            "infoFiltered": "(由 _MAX_ 项结果过滤)",
            "infoPostFix": "",
            "search": "搜索:",
            "searchPlaceholder": "搜索...",
            "url": "",
            "emptyTable": "表中数据为空",
            "loadingRecords": "载入中...",
            "infoThousands": ",",
            "paginate": {
                "first": "首页",
                "previous": "上页",
                "next": "下页",
                "last": "末页"
            },
            "aria": {
                "paginate": {
                    first: '首页',
                    previous: '上页',
                    next: '下页',
                    last: '末页'
                },
                "sortAscending": ": 以升序排列此列",
                "sortDescending": ": 以降序排列此列"
            },
            "decimal": "-",
            "thousands": "."
        },
    });
  }
  

  function edit_dialog(data){
    data = data.replace(/&quot;/g, '"');
    data = JSON.parse(data);
    console.log(data);
    var validate_form = function() {
      if (true === $('#edit-form').parsley().isValid()) {
        $('.bs-callout-info').removeClass('hidden');
        $('.bs-callout-warning').addClass('hidden');
      } else {
        $('.bs-callout-info').addClass('hidden');
        $('.bs-callout-warning').removeClass('hidden');
      }
    };

    var init_dialog = function($edit_form){
<?php foreach ($bean['col'] as $key => $column): //初始化主表默认值?>
      $edit_form.find(":input[name='<?php echo $column['field']?>']").val(data['<?php echo $column['field']?>']);
<?php endforeach ?>
<?php if (isset($bean['join'])): //初始化连接表默认值?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
      $edit_form.find(":input[name='<?php echo $join_table['pri_field']?>']").val(data['<?php echo $join_table['pri_field']?>']);
<?php   endforeach ?>
<?php endif ?>
      $edit_form.find(":input[name='<?php echo $bean['id']['field']?>']").val(data['<?php echo $bean['id']['field']?>']);
      $/*.listen*/('parsley:field:validate', function() {
        validate_form();
      });
      $('#edit-form .btn').on('click', function() {
        if ($('#edit-form').parsley().validate()) {
          var post_data = new Object();
<?php foreach ($bean['col'] as $key => $column): ?>
          post_data['<?php echo $column['field']?>'] = $("#edit-form :input[name='<?php echo $column['field']?>']").val();
<?php endforeach ?>
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
          post_data['<?php echo $join_table['pri_field']?>'] = $("#edit-form :input[name='<?php echo $join_table['pri_field']?>']").val();
<?php   endforeach ?>
<?php endif ?>
          post_data['<?php echo $bean['id']['field']?>'] = $("#edit-form :input[name='<?php echo $bean['id']['field']?>']").val();
          $.post("<?php echo "<?=site_url('back/{$bean_name}/update')?>"?>", post_data, function(data){
            if (data['status'] == true) {
              DEP_TABLE.ajax.reload( null, false );
              edit_dialog.setContent('修改成功');
            }else{
              edit_dialog.setContent(data['message']);
            }
          });
        }       
        validate_form();
      });
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
      $edit_form.find('.js-checkbox-<?php echo $join_table_name?> input').each(function(){
          var self = $(this),
              label = self.next(),
              label_text = label.text();
          label.remove();
          self.iCheck({
            checkboxClass: 'icheckbox_line-green',
            insert: '<div class="icheck_line-icon"></div>' + label_text
          });
      });
<?php   endforeach ?>
<?php endif ?> 
    }

    var edit_dialog = $.dialog({
        title: '修改<?php echo $bean['tbl_comment']?>',
        content: function(){
          return '<form id="edit-form" data-parsley-validate>' + $('#js-edit-form').html() + '</form>';
        },
        onContentReady: function(){
          init_dialog(edit_dialog.$content);
        }
    });
  }



  function add_dialog(data){
    var validate_form = function() {
      if (true === $('#add-form').parsley().isValid()) {
        $('.bs-callout-info').removeClass('hidden');
        $('.bs-callout-warning').addClass('hidden');
      } else {
        $('.bs-callout-info').addClass('hidden');
        $('.bs-callout-warning').removeClass('hidden');
      }
    };
    
    var init_dialog = function($add_form){
      $/*.listen*/('parsley:field:validate', function() {
        validate_form();
      });
      $add_form.find('.btn').on('click', function() {
        if ($('#add-form').parsley().validate()) {
          var post_data = new Object();
<?php foreach ($bean['col'] as $key => $column): ?>
          post_data['<?php echo $column['field']?>'] = $("#add-form :input[name='<?php echo $column['field']?>']").val();
<?php endforeach ?>
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
          post_data['<?php echo $join_table['pri_field']?>'] = $("#add-form :input[name='<?php echo $join_table['pri_field']?>']").val();
<?php   endforeach ?>
<?php endif ?>
          $.post("<?php echo "<?=site_url('back/{$bean_name}/insert')?>"?>", post_data, function(data){
            if (data['status'] == true) {
              DEP_TABLE.ajax.reload( null, false );
              add_dialog.setContent('添加成功');
            }else{
              add_dialog.setContent(data['message']);
            }
          })
        }
      });
<?php if (isset($bean['join'])): ?>
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
     $add_form.find('.js-checkbox-<?php echo $join_table_name?> input').each(function(){
          var self = $(this),
              label = self.next(),
              label_text = label.text();
          label.remove();
          self.iCheck({
            checkboxClass: 'icheckbox_line-green',
            insert: '<div class="icheck_line-icon"></div>' + label_text
          });
      });
<?php   endforeach ?>
<?php endif ?>
    }

    var add_dialog = $.dialog({
        title: '添加<?php echo $bean['tbl_comment']?>',
        content: function(){
          return '<form id="add-form" data-parsley-validate>' + $('#js-add-form').html() + '</form>';
        },
        onContentReady: function(){
          init_dialog(add_dialog.$content);
        }
    });
  }

  function del_confirm(data){
    var del_confirm = $.confirm({
        title: '删除',
        content: '是否删除该数据，序号'+data,
        buttons: {
          confirm: {
            text: '确认',
            btnClass : 'btn-danger',
            action : function(){
              $.post("<?php echo "<?=site_url('back/{$bean_name}/delete')?>"?>", {<?php echo $bean['id']['field']?> : data}, function(data,status){
                if (data['status'] == true) {
                  DEP_TABLE.ajax.reload( null, false );
                  $.dialog('删除成功');
                }
              });
            }
          },
          cancel: {
            text: '取消',
            btnClass : 'btn-info'
          }
        }
    });
  }

<?php /*----------初始化添加，修改表单（对在表单中选择用的外链表数据初始化）----------*/?>
<?php if (isset($bean['join'])): ?>
  function init_add_edit_form(){
    $.post("<?php echo "<?=site_url('back/{$bean_name}/get_form_data')?>"?>", {}, function(data,status){
      if (data['status'] == true) {
<?php   foreach ($bean['join'] as $join_table_name => $join_table): ?>
<?php     if ($join_table['form_type'] == 'muticheck'): ?>
          var $<?php echo $join_table_name?> = $('.js-checkbox-<?php echo $join_table_name?>');
          $(data.<?php echo $join_table_name?>).each(function(){
            var checkbox_str = '<div class="col-md-4"><input name="<?php echo $join_table['pri_field'] ?>[]" type="checkbox" value="'+this.<?php echo $join_table['join_field'] ?>+'" />'+'<label>'+this.<?php echo $join_table['join_show_field'] ?>+'</label></div>';
            $<?php echo $join_table_name?>.append(checkbox_str);
          });
<?php     else: ?>
          var $<?php echo $join_table_name?> = $('.js-select-<?php echo $join_table_name?>');
          $(data.<?php echo $join_table_name ?>).each(function(){
            var option_str = '<option value="'+this.<?php echo $join_table['join_field'] ?>+'">'+this.<?php echo $join_table['join_show_field'] ?>+'</option>'
            $<?php echo $join_table_name?>.append(option_str);
          });
<?php     endif ?>       
<?php   endforeach ?>
      }
    });
  }
<?php endif ?>
<?php /*----------/初始化添加，修改表单（对在表单中选择用的外链表数据初始化）----------*/?>

  window.onload = function(){
    init_table();
<?php if (isset($bean['join'])): ?>
    init_add_edit_form();
<?php endif ?>
  }
</script>



<?php
use yangzie\YZE_Form;
use app\user\User_Model;

$users = User_Model::find_all ();
?>
<button class="btn btn-primary" onclick="add_user_ajax()">Add from ajax</button>
<button class="btn btn-primary" onclick="add_user_iframe()">Add from iframe</button>
<table class="table">
<?php foreach ($users as $user){?>
  <tr>
        <td><?php echo $user->name?></td>
        <td><?php echo $user->nickname?></td>
    </tr>
  <?php }?>
</table>
<div id="dialog"></div>
<div id="iframe"></div>
<script>
function add_user_ajax(){
    var front = new yze_ajax_front_controller();
    front.get("/user/add",{},function(form){
        $("#dialog").html(form);
        $("#add_user").modal("show");
    },function(data){
        if(data.result == true){
            $("#add_user").modal("hide");
        }
    });
}

function add_user_iframe(){
    var front = new yze_ajax_front_controller();
    front.load("/user/add2",
        function(form){
            $("#iframe").html(form);
            $("#add_user").modal("show");
        }, 
        function(){
            $("#add_user").modal("hide");
        }, 
        function(data){
            if(data.result == true){
                $("#add_user").modal("hide");
        }
    });
}
</script>
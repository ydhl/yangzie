<?php
use yangzie\YZE_Form;

$form = new YZE_Form ( $this, "test" );
$form->begin_form ();
?>
<div class="" id="add_user">
    <div class="">
        <div class="">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">add user</h4>
            </div>
            <div class="modal-body">
                <?php echo\yangzie\yze_controller_error ();?>
                <div class="form-group">
                    <label for="exampleInputEmail1">Name</label> <input
                        type="text" class="form-control" name="name"
                        value="<?php echo \yangzie\yze_get_default_value(null, "name", $this->controller)?>"
                        id="exampleInputEmail1"
                        placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label for="exampleInputEmail1">Email address</label>
                    <input type="text" class="form-control" name="email"
                        value="<?php echo \yangzie\yze_get_default_value(null, "email", $this->controller)?>"
                        id="exampleInputEmail1"
                        placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label for="exampleInputPassword1">Password</label>
                    <input type="text" class="form-control"
                        name="password"
                        value="<?php echo \yangzie\yze_get_default_value(null, "password", $this->controller)?>"
                        id="exampleInputPassword1"
                        placeholder="Password">
                </div>
                <div class="form-group">
                    <label for="exampleInputPassword1">Nick Name</label>
                    <input type="text" class="form-control"
                        name="nickname"
                        value="<?php echo \yangzie\yze_get_default_value(null, "nickname", $this->controller)?>"
                        id="exampleInputPassword1"
                        placeholder="Password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<?php $form->end_form()?>

        
<?php

class TestOrderEntity extends Entity{
	public function getTable(){
		return "orders";
	}
	public function getKeyName(){
		return array("order_id");
	}
	public function getColumns(){
		return array(
		'order_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'order_time'=>array(
			'type'=>'date',
			'null'=>false),
		'order_status'=>array(
			'type'=>'enum',
			'null'=>false),
		'last_modified'=>array(
			'type'=>'date',
			'null'=>false),
		'date_added'=>array(
			'type'=>'date',
			'null'=>false));
	}
}

class TestCustomerEntity extends Entity{
	public function getTable(){
		return "customers";
	}
	public function getKeyName(){
		return array("customer_id");
	}
	public function getColumns(){
		return array(
		'customer_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'first_name'=>array(
			'type'=>'string',
			'null'=>false),
		'email'=>array(
			'type'=>'string',
			'null'=>false),
		'last_modified'=>array(
			'type'=>'date',
			'null'=>false),
		'date_added'=>array(
			'type'=>'date',
			'null'=>false));
	}
}

class TestLineItemEntity extends Entity{
	public function getTable(){
		return "quote_order_items";
	}
	public function getKeyName(){
		return array("qo_item_id");
	}
	public function getColumns(){
				return array(
		'order_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'quote_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'part_no'=>array(
			'type'=>'string',
			'null'=>false));
	}
}

class TestSourceEntity extends Entity{
	
	public function getTable(){
		return "assemble_part_source";
	}
	public function getKeyName(){
		return array('qo_item_id','part_item_id');
	}
	public function getColumns(){
		return array(
		'qo_item_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'part_item_id'=>array(
			'type'=>'integer',
			'null'=>false),
		'comment'=>array(
			'type'=>'string',
			'null'=>false),
		'last_modified'=>array(
			'type'=>'date',
			'null'=>false),
		'date_added'=>array(
			'type'=>'date',
			'null'=>false),
		'user_added'=>array(
			'type'=>'integer',
			'null'=>false),
		'user_id_change'=>array(
			'type'=>'integer',
			'null'=>false),
		'changed_column'=>array(
			'type'=>'string',
			'null'=>false),);
	}
	
	
}
?>

<?php
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
create table arena_shippings_mapping(id int not null auto_increment, arena_id varchar(255),arena_name varchar(255), shipping_code varchar(255), primary key(id));
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
	 
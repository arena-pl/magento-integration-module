<?php
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
create table arena_queue(id int not null auto_increment, task varchar(255),params text, primary key(id));
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
	 
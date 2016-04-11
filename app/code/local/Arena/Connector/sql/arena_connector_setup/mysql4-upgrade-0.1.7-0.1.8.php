<?php
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
ALTER TABLE `sales_flat_order`
ADD COLUMN `arena_placed_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN `arena_updated_at` DATETIME NULL DEFAULT NULL;
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
	 
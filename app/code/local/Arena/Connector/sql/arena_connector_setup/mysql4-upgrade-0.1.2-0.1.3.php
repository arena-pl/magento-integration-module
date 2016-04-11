<?php
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
ALTER TABLE `arena_shippings_mapping`
ADD COLUMN `store_id` INT NOT NULL COMMENT '' AFTER `shipping_code`,
ADD INDEX `store` (`store_id` ASC)  COMMENT '';
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
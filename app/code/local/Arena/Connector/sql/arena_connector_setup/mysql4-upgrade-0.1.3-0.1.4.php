<?php
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
ALTER TABLE `arena_shippings_mapping`
ADD COLUMN `cash_on_delivery` INT NOT NULL COMMENT '' AFTER `shipping_code`;
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
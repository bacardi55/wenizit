<?php

/**
 * @author bacardi55
 */

$schema = new \Doctrine\DBAL\Schema\Schema();

$cd = $schema->createTable('coutdowns');
$cd->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$cd->addColumn('title', 'string', array('length' => 55));
$cd->addColumn('date', 'datetime');
$cd->addColumn('password', 'string', array('length' => 254));
$cd->setPrimaryKey(array('id'));

return $schema;

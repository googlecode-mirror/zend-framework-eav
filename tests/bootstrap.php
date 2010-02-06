<?php

define('TESTS_PATH', realpath(dirname(__FILE__)));
define('EAV_ROOT', TESTS_PATH . '/../');

set_include_path(
    TESTS_PATH . '/../lib'  . PATH_SEPARATOR
  . TESTS_PATH . '/../app/code' . PATH_SEPARATOR
  . TESTS_PATH . PATH_SEPARATOR
  . get_include_path()
);

require_once 'Zend/Loader/Autoloader.php';
require_once 'PHPUnit/Framework.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

require_once TESTS_PATH . '/config.php';
$config = new Zend_Config($config);
Zend_Registry::set('config', $config);

$db = Zend_Db::factory('Pdo_Mysql', array(
    'host'      => $config->db->host,
    'username'  => $config->db->username,
    'password'  => $config->db->password,
    'dbname'    => $config->db->dbname,
    'driver_options'=> array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
    )
));

Zend_Db_Table_Abstract::setDefaultAdapter($db);
Zend_Registry::set('db', $db);
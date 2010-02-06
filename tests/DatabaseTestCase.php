<?php

require_once 'PHPUnit/Extensions/Database/TestCase.php';

abstract class DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    protected function getConnection()
    {
        $config = Zend_Registry::get('config');
        $host = $config->db->host;
        $user = $config->db->username;
        $password = $config->db->password;
        $dbname = $config->db->dbname;

        $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $user, $password);
        return $this->createDefaultDBConnection($pdo, $dbname);
    }
}
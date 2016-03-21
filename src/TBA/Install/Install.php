<?php
$db = array(
    'scheme' => 'mysql',
    'user' => 'root',
    'pass' => 'root123',
    'host' => 'localhost',
    'db' => 'tba',
    'table' => 'tba_user',
);

$conn = new \PDO(
    "{$db['scheme']}:host={$db['host']};dbname={$db['db']}",
    $db['user'],
    $db['pass']
);

$sql_mysql = "
CREATE TABLE {$db['table']} (
	id integer not null auto_increment primary key,
	username varchar(40) not null,
	passwd varchar(128) not null,
	token varchar(128),
	tokenval datetime,
	last_login datetime
);
";

$sql_pgsql = "
CREATE TABLE {$db['table']} (
	id serial primary key,
	username varchar(40) not null,
	passwd varchar(128) not null,
	token varchar(128),
	tokenval datetime,
	last_login datetime
);
";

switch ($db['scheme']) {
    case 'pgsql':
        $install_table = $sql_pgsql;
        break;

    case 'mysql':
    default:
        $install_table = $sql_mysql;
        break;
}

$conn->exec($install_table);

define("APP_ROOT", dirname(__DIR__));

require APP_ROOT . "/TokenBasedAuth.php";
require APP_ROOT . "/Header.php";

$app = array();
$app['tba.table_name'] = 'tba_user';
$app['tba.user_field'] = 'username';
$app['tba.pass_field'] = 'passwd';
$app['tba.token_timeout'] = '60';
$app['tba.salt'] = 'M3T45_901X';

$tba = new TBA\TokenBasedAuth($app);
$tba->setConnection($conn);

$sql_user = "INSERT INTO tba_user (username,passwd) values (:user,:pwd);";
$user = 'evaldobarbosa@gmail.com';
$passwd = 'evaldo123';

$pwdHash = md5($app['tba.salt'] . "{$passwd}123X");

$rs = $conn->prepare($sql_user);
$rs->bindParam("user", $user);
$rs->bindParam("pwd", $pwdHash);

$rs->execute();

<?php
$db = array(
    'scheme' => 'mysql',
    'user' => 'root',
    'pass' => 'root123',
    'host' => 'localhost',
    'db' => 'charon',
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
	passwd varchar(40) not null,
	token varchar(40),
	tokenval datetime,
	last_login datetime
);
";

$sql_pgsql = "
CREATE TABLE {$db['table']} (
	id serial primary key,
	username varchar(40) not null,
	passwd varchar(40) not null,
	token varchar(40),
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

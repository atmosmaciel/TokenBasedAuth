# TokenBasedAuth
Biblioteca para autenticação baseada em tokens

##Como usar

`php
$auth = new \TBA\TokenBasedAuth($this->config);

$conn = new \PDO(
	"mysql:dbname=example;host=127.0.0.1;port=3306",
	DBUSER,
	DBPASS,
	[ \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ]
);

$auth->setConnection( $conn );

$auth->check( TOKEN_STR );
`php

###Veja um exemplo
Abra o diretório src/Boilerplate/src/Routable. O exemplo é baseado no microframework respect/rest.
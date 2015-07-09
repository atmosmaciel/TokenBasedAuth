[Travis](https://travis-ci.org/evaldobarbosa/TokenBasedAuth.svg?branch=master)

# TokenBasedAuth
Biblioteca para autenticação baseada em tokens

##Como usar

####Adicione o repositório ao composer.json

```json
"repositories": [
	{
            "type": "vcs",
            "url": "https://github.com/evaldobarbosa/TokenBasedAuth.git"
        }
],
"require": {
	"evaldobarbosa/tokenbasedauth": "dev-master"
}
```

####Utilize o bloco de código a seguir
```php
$auth = new \TBA\TokenBasedAuth($this->config);

$conn = new \PDO(
	"mysql:dbname=example;host=127.0.0.1;port=3306",
	DBUSER,
	DBPASS,
	[ \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ]
);

$auth->setConnection( $conn );

$auth->check( TOKEN_STR );
```

###Veja um exemplo
Abra o diretório src/Boilerplate/src/Routable. O exemplo é baseado no microframework respect/rest.

###Artigos
http://www.evaldobarbosa.com.br

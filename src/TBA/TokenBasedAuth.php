<?php
namespace TBA;

use \TBA\Generators\TokenGenerator,
	\TBA\Generators\Md5TokenGenerator,
	\TBA\Header;

class TokenBasedAuth {
	static private $staticConfig;

	private $user;

	private $generator;
	private $conn;
	private $header;

	private $config;
	private $authEvents = [];

	static function setConfig($config) {
		self::$staticConfig = $config;
	}

	function __construct($config=null) {
		$this->config = ( is_null($config) )
			? self::$staticConfig
			: $config;
	}

	function getUserByToken() {
		$qry = $this->conn->prepare(
			'SELECT id, nome, login, token, tokenval FROM pessoa WHERE token = :token'
		);

		$qry->bindParam('token',$token);
		$qry->execute();

		$this->user = $qry->fetchObject();
	}

	function getNewToken($value=null) {
		return $this->getGenerator()->generate($value);
	}

	function login($user,$password) {
		$sql = sprintf(
				'SELECT * FROM %s WHERE %s = :user AND %s = :pass;',
				filter_var( $this->config['table_name'], FILTER_SANITIZE_STRING ),
				filter_var( $this->config['user_field'], FILTER_SANITIZE_STRING ),
				filter_var( $this->config['pass_field'], FILTER_SANITIZE_STRING )
			);
		$qry = $this->conn->prepare( $sql );

		$qry->bindParam('user',$user);
		$qry->bindParam('pass',$password);
		$qry->execute();
		
		if ( $qry->rowCount() == 0 ) {
			throw new \TBA\Exceptions\InvalidLoginException("Combinação de login e senha inválida");
		}
			
		$this->user = $qry->fetchObject();
		unset( $this->user->{$this->config["pass_field"]} );

		$this->changeToken();
	}

	function changeToken() {
		if ( is_null($this->user) ) {
			$this->getUserByToken();
		}

		$this->getUser()->token = $this->getNewToken();

		$time = sprintf(
			'now +%d minutes',
			$this->config['token_timeout']
		);
		$this->getUser()->tokenval = new \Datetime($time);

		$qry = $this->conn->prepare(
			'UPDATE pessoa SET token = :token, tokenval = :tokenval WHERE id = :id'
		);

		$val = $this->getUser()->tokenval->format("Y-m-d H:i:s");
		$qry->bindParam('token',$this->getUser()->token);
		$qry->bindParam('tokenval',$val);
		$qry->bindParam('id',$this->getUser()->id);
		$qry->execute();
	}

	function getToken($token) {
		$qry = $this->conn->prepare(
			'SELECT token, tokenval FROM pessoa WHERE token = :token'
		);

		$qry->bindParam('token',$token);
		$qry->execute();

		return $qry->fetchObject();
	}

	function check($token) {
		$tokenFromDb = $this->getToken($token);

		if ( isset($tokenFromDb->token) > 0 ) {
			$tval = new \Datetime($tokenFromDb->tokenval);
			$diff = ( new \Datetime )->diff( $tval );

			if ( $diff->i == 1 || $diff->i == 2 ) {
				$this->changeToken();
			} else

			if ( $tval < new \Datetime ) {
				throw new \TBA\Exceptions\InvalidTokenException("Token expirado",401);
			} 

			return true;
		} else {
			throw new \TBA\Exceptions\InvalidTokenException("Credencial errada");
		}

		return false;
	}

	function setConnection(\PDO $conn) {
		$this->conn = $conn;

		return $this;
	}

	function setHeader(Header $header) {
		$this->header = $header;

		return $this;
	}

	function setGenerator(TokenGenerator $generator) {
		$this->generator = $generator;

		return $this;
	}

	function getUser() {
		return $this->user;
	}

	function getGenerator() {
		if ( is_null($this->generator) ) {
			$this->generator = new Md5TokenGenerator($this->config['salt']);
		}

		return $this->generator;
	}

	function getHeader() {
		return $this->header;
	}
}
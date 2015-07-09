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
		$token = $this->getHeader()->getClientToken();
		
		$sql = sprintf(
				'SELECT id, %s, token, tokenval FROM %s WHERE token = :token',
				filter_var( $this->config['user_field'], FILTER_SANITIZE_STRING ),
				filter_var( $this->config['table_name'], FILTER_SANITIZE_STRING )
			);
		$qry = $this->conn->prepare( $sql );
		$qry->bindParam('token',$token);
		$qry->execute();

		$this->user = $qry->fetchObject();
	}

	function getNewToken($value=null) {
		return $this->getGenerator()->generate($value);
	}

	function login($user,$password) {
		$sql = sprintf(
				'SELECT * FROM %s WHERE %s = :my_user AND %s = :my_pass;',
				filter_var( $this->config['table_name'], FILTER_SANITIZE_STRING ),
				filter_var( $this->config['user_field'], FILTER_SANITIZE_STRING ),
				filter_var( $this->config['pass_field'], FILTER_SANITIZE_STRING )
			);
		$qry = $this->conn->prepare( $sql );

		$qry->bindParam('my_user',$user);
		$qry->bindParam('my_pass',$password);
		$qry->execute();

		$this->user = $qry->fetchObject();
		
		if ( !isset($this->user->id) || (int)$this->user->id == 0 ) {
			throw new \TBA\Exceptions\InvalidLoginException("Combinação de login e senha inválida");
		}
			
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

		$sql = sprintf(
				'UPDATE %s SET token = :token, tokenval = :tokenval WHERE id = :id',
				filter_var( $this->config['table_name'], FILTER_SANITIZE_STRING )
			);
		$qry = $this->conn->prepare( $sql );

		$val = $this->getUser()->tokenval->format("Y-m-d H:i:s");
		$qry->bindParam('token',$this->getUser()->token);
		$qry->bindParam('tokenval',$val);
		$qry->bindParam('id',$this->getUser()->id);
		$qry->execute();
	}

	function getToken($token) {
		$sql = sprintf(
			'SELECT token, tokenval FROM %s WHERE token = :token',
			filter_var( $this->config['table_name'], FILTER_SANITIZE_STRING )
		);

		$qry = $this->conn->prepare( $sql );

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
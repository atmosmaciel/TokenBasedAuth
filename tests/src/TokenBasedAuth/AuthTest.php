<?php
namespace TBA;

/**
 * @group Lib
 */
class AuthTest extends \PHPUnit_Framework_TestCase {
	private $auth;
	private $conn;
	private $config;

	function setup() {
		$this->config = [
			"table_name" => "pessoa",
			"user_field" => "usuario",
			"pass_field" => "senha",
			"salt"		 => "ABCDEFGH1092",
			"token_timeout" => 10
		];
		$this->auth = new \TBA\TokenBasedAuth($this->config);

		$this->conn = new \PDO(
			"mysql:dbname=yourdbname;host=127.0.0.1;port=3306",
			DBUSER,
			DBPASS,
			[ \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ]
		);

		$this->auth->setConnection( $this->conn );
	}

	function getMockedTBAObject( $methods ) {
		if ( !is_array($methods) ) {
			throw new \Exception("Forneça um array com os nomes dos métodos", 1);
		}

		return $this->getMockBuilder('TBA\TokenBasedAuth')
				->setConstructorArgs([$this->config])
				->setMethods( $methods )->getMock();
	}

	function testTipoCorretoObjeto() {
		$this->assertEquals( 'TBA\TokenBasedAuth', get_class($this->auth) );
	}

	function testPegarNovoToken() {
		$token1 = $this->auth->getNewToken();
		$token2 = $this->auth->getNewToken('abc');

		$this->assertTrue( !empty($token1), "{$token1} ok" );
		$this->assertTrue( !empty($token2), "{$token2} ok" );
		$this->assertTrue( $token1 !== $token2 );
	}

	function testLoginFuncionando() {
		$this->auth->login('evaldo','evaldo123');

		$this->assertTrue( !empty($this->auth->getUser()) );
	}

	/**
	 * @expectedException TBA\Exceptions\InvalidLoginException
	 * @expectedExceptionMessage Combinação de login e senha inválida
	 */
	function testLoginInvalido() {
		$this->auth->login('evaldo','teste123');

		$this->assertTrue( !empty($this->auth->getUser()) );
	}

	function testAtribuiTipoGeracaoToken() {
		$gen = new \TBA\Generators\Sha1TokenGenerator($this->config['salt']);
		$this->auth->setGenerator($gen);

		$this->assertEquals( 'TBA\Generators\Sha1TokenGenerator', get_class( $this->auth->getGenerator() ) );
	}

	function testValidaHeaders() {
		$h = $this->getMock('TBA\Header');
		$h->expects($this->any())
			->method('getAllHeaders')
			->will(
				$this->returnValue([
					'AppToken' => 'ABC12DE45X',
					'ClientToken' => 'XI8KSIEJD',
					'Content-type' => 'text/html'
				])
			);

		$h->expects($this->any())
			->method('getAppToken')
			->will(
				$this->returnValue(APP_TOKEN)
			);
		$h->expects($this->any())
			->method('getClientToken')
			->will(
				$this->returnValue('XI8KSIEJD')
			);

		$this->auth->setHeader($h);

		$this->assertEquals( APP_TOKEN, $this->auth->getHeader()->getAppToken() );
		$this->assertEquals( 'XI8KSIEJD', $this->auth->getHeader()->getClientToken() );
	}

	/**
	 * @expectedException TBA\Exceptions\InvalidTokenException
	 * @expectedExceptionMessage Token expirado
	 */
	function testChecarTokenExpirado() {
		$token = (object)[
			'token' => 'AAAAAAA',
			'tokenval' => '2010-01-01 10:10:10'
		];

		$auth = $this->getMockedTBAObject(['getToken']);

		$auth->expects( $this->any() )
			->method('getToken')
			->willReturn( $token );

		$auth->check('AAAAAAA');
	}

	/**
	 * @expectedException TBA\Exceptions\InvalidTokenException
	 * @expectedExceptionMessage Credencial errada
	 */
	function testChecarTokenNaoExistente() {
		$user = (object)[
			'id' => 1,
			'nome' => 'Evaldo Barbosa',
			'usuario' => 'evaldo',
			'token' => 'AAAAAAA',
			'tokenval' => '2010-01-01 10:10:10'
		];

		$auth = $this->getMockedTBAObject(['getUser']);

		$auth->expects( $this->any() )
			->method('getUser')
			->willReturn( $user );

		$auth->setConnection($this->conn)->check('AAAAAAA');
	}

	function testTrocaDeToken() {
		$dt = ( new \Datetime );
		$user = (object)[
			'id' => 1,
			'nome' => 'Evaldo Barbosa',
			'usuario' => 'evaldo',
			'token' => 'AAAAAAA',
			'tokenval' => $dt->format("Y-m-d H:i:s")
		];

		$token = (object)[
			'token' => $user->token,
			'tokenval' => $dt->modify( '-2 minutes' )->format("Y-m-d H:i:s")
		];

		$auth = $this->getMockedTBAObject(['getUser','getToken']);

		$auth->expects( $this->any() )
			->method('getUser')
			->willReturn( $user );

		$auth->expects( $this->any() )
			->method('getToken')
			->willReturn( $token );

		$tokenChanged = $auth
							->setConnection($this->conn)
							->check( $user->token );

		$this->assertTrue( $tokenChanged );
	}
}
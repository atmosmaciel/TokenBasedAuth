<?php
namespace TBA;

use PDO;

/**
 * @group Lib
 */
class AuthTest extends \PHPUnit_Framework_TestCase
{
    private $auth;
    private $conn;
    private $config;

    private static $pdo = null;

    public function setup()
    {
        $this->config = [
            "table_name" => "user",
            "user_field" => "username",
            "pass_field" => "password",
            "salt" => "ABCDEFGH1092",
            "token_timeout" => 10,
        ];
        $this->auth = new \TBA\TokenBasedAuth($this->config);

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $pdo->exec(file_get_contents(__DIR__ . '/table.sql'));

            $this->conn = $pdo;

            $this->auth->setConnection($this->conn);
        } catch (\Exception $e) {
            error_log("erro na conexão com o sqlite: {$e->getMessage()}");
        }
    }

    public function getMockedTBAObject($methods)
    {
        if (!is_array($methods)) {
            throw new \Exception("Forneça um array com os nomes dos métodos", 1);
        }

        $h = $this->getMockBuilder('TBA\Header')
            ->setMethods(['getClientToken', 'getAppToken'])
            ->getMock();
        $h->expects($this->any())
            ->method('getClientToken')
            ->willReturn('9I3JJSYEH');
        $h->expects($this->any())
            ->method('getAppToken')
            ->willReturn('ABC12DE45X');

        $methods[] = 'getHeader';

        $auth = $this->getMockBuilder('TBA\TokenBasedAuth')
            ->setConstructorArgs([$this->config])
            ->setMethods($methods)->getMock();

        $auth->expects($this->any())
            ->method('getHeader')
            ->willReturn($h);

        return $auth;
    }

    public function testTipoCorretoObjeto()
    {
        $this->assertEquals('TBA\TokenBasedAuth', get_class($this->auth));
    }

    public function testPegarNovoToken()
    {
        $token1 = $this->auth->getNewToken();
        $token2 = $this->auth->getNewToken('abc');

        $this->assertTrue(!empty($token1), "{$token1} ok");
        $this->assertTrue(!empty($token2), "{$token2} ok");
        $this->assertTrue($token1 !== $token2);
    }

    public function testLoginFuncionando()
    {
        $this->auth->login('evaldo', 'evaldo123');

        $this->assertTrue(!empty($this->auth->getUser()));
    }

    /**
     * @expectedException TBA\Exceptions\InvalidLoginException
     * @expectedExceptionMessage Combinação de login e senha inválida
     */
    public function testLoginInvalido()
    {
        $this->auth->login('evaldo', 'teste123');

        $this->assertTrue(!empty($this->auth->getUser()));
    }

    public function testAtribuiTipoGeracaoToken()
    {
        $gen = new \TBA\Generators\Sha1TokenGenerator($this->config['salt']);
        $this->auth->setGenerator($gen);

        $this->assertEquals('TBA\Generators\Sha1TokenGenerator', get_class($this->auth->getGenerator()));
    }

    public function testValidaHeaders()
    {
        $h = $this->getMock('TBA\Header');
        $h->expects($this->any())
            ->method('getAllHeaders')
            ->will(
                $this->returnValue([
                    'AppToken' => 'ABC12DE45X',
                    'ClientToken' => 'XI8KSIEJD',
                    'Content-type' => 'text/html',
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

        $this->assertEquals(APP_TOKEN, $this->auth->getHeader()->getAppToken());
        $this->assertEquals('XI8KSIEJD', $this->auth->getHeader()->getClientToken());
    }

    /**
     * @expectedException TBA\Exceptions\InvalidTokenException
     * @expectedExceptionMessage Token expirado
     */
    public function testChecarTokenExpirado()
    {
        $token = (object) [
            'token' => 'AAAAAAA',
            'tokenval' => '2010-01-01 10:10:10',
        ];

        $auth = $this->getMockedTBAObject(['getToken']);

        $auth->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $auth->check('AAAAAAA');
    }

    /**
     * @expectedException TBA\Exceptions\InvalidTokenException
     * @expectedExceptionMessage Credencial errada
     */
    public function testChecarTokenNaoExistente()
    {
        $user = (object) [
            'id' => 1,
            'name' => 'Evaldo Barbosa',
            'username' => 'evaldo',
            'token' => 'AAAAAAA',
            'tokenval' => '2010-01-01 10:10:10',
        ];

        $auth = $this->getMockedTBAObject(['getUser']);

        $auth->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $auth->setConnection($this->conn)->check('AAAAAAA');
    }

    public function testTrocaDeToken()
    {
        $dt = (new \Datetime);
        $user = (object) [
            'id' => 1,
            'name' => 'Evaldo Barbosa',
            'username' => 'evaldo',
            'token' => 'AAAAAAA',
            'tokenval' => $dt->format("Y-m-d H:i:s"),
        ];

        $token = (object) [
            'token' => $user->token,
            'tokenval' => $dt->modify('-2 minutes')->format("Y-m-d H:i:s"),
        ];

        $auth = $this->getMockedTBAObject(['getUser', 'getToken', 'getUserByToken']);

        $auth->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $auth->expects($this->any())
            ->method('getUserByToken')
            ->willReturn($user);

        $auth->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $tokenChanged = $auth
            ->setConnection($this->conn)
            ->check($user->token);

        $this->assertTrue($tokenChanged);
    }
}

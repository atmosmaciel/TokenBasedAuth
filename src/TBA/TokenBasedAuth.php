<?php
namespace TBA;

use \TBA\Exceptions\InvalidTokenException;
use \TBA\Generators\Md5TokenGenerator;
use \TBA\Generators\TokenGenerator;
use \TBA\Header;

class TokenBasedAuth
{
    private static $staticConfig;

    private $user;

    private $generator;
    private $conn;
    private $header;

    private $config;

    public function __construct($config = null)
    {
        $this->config = (is_null($config))
        ? self::$staticConfig
        : $config;
    }

    public function getUserByToken()
    {
        $token = $this->getHeader()->getClientToken();

        $sql = sprintf(
            'SELECT * FROM %s tba WHERE token = :token',
            filter_var($this->config['table_name'], FILTER_SANITIZE_STRING)
        );
        $qry = $this->conn->prepare($sql);
        $qry->bindParam('token', $token);
        $qry->execute();

        if ($qry->rowCount() == 0) {
            throw new InvalidTokenException("Token inválido");
        }

        $this->user = $qry->fetchObject();

        unset($this->user->{$this->config['pass_field']});

        return $this->user;
    }

    public function getNewToken($value = null)
    {
        return $this->getGenerator()->generate($value);
    }

    public function login($user, $password)
    {
        $sql = sprintf(
            'SELECT * FROM %s tba WHERE %s = :my_user AND %s = :my_pass;',
            filter_var($this->config['table_name'], FILTER_SANITIZE_STRING),
            filter_var($this->config['user_field'], FILTER_SANITIZE_STRING),
            filter_var($this->config['pass_field'], FILTER_SANITIZE_STRING)
        );
        $qry = $this->conn->prepare($sql);

        $qry->bindParam('my_user', $user);
        $qry->bindParam('my_pass', $password);
        $qry->execute();

        $this->user = $qry->fetchObject();

        if (!isset($this->user->id) || (int) $this->user->id == 0) {
            throw new \TBA\Exceptions\InvalidLoginException("Combinação de login e senha inválida");
        }

        unset($this->user->{$this->config["pass_field"]});

        $sql = sprintf(
            'UPDATE %s SET last_login = :login_date WHERE :user_id;',
            filter_var($this->config['table_name'], FILTER_SANITIZE_STRING)
        );
        $qry = $this->conn->prepare($sql);

        $data_hora = (new \Datetime)->format("Y-m-d H:i:s");
        $qry->bindParam(':login_date', $data_hora);
        $qry->bindParam('user_id', $this->user->id);
        $qry->execute();

        $this->changeToken();
    }

    public function changeToken()
    {
        if (is_null($this->user)) {
            $this->getUserByToken();
        }

        $this->getUser()->token = $this->getNewToken();

        //error_log("Novo token: {$this->user->token}");

        $time = sprintf(
            'now +%d minutes',
            $this->config['token_timeout']
        );
        $this->getUser()->tokenval = new \Datetime($time);

        $sql = sprintf(
            'UPDATE %s SET token = :token, tokenval = :tokenval WHERE id = :id',
            filter_var($this->config['table_name'], FILTER_SANITIZE_STRING)
        );
        $qry = $this->conn->prepare($sql);

        $val = $this->getUser()->tokenval->format("Y-m-d H:i:s");
        $qry->bindParam('token', $this->getUser()->token);
        $qry->bindParam('tokenval', $val);
        $qry->bindParam('id', $this->getUser()->id);
        $qry->execute();
    }

    public function getToken($token)
    {
        $sql = sprintf(
            'SELECT token, tokenval FROM %s tba WHERE token = :token',
            filter_var($this->config['table_name'], FILTER_SANITIZE_STRING)
        );

        $qry = $this->conn->prepare($sql);

        $qry->bindParam('token', $token);
        $qry->execute();

        return $qry->fetchObject();
    }

    public function check($token)
    {
        $tokenFromDb = $this->getToken($token);

        if (isset($tokenFromDb->token)) {
            $tval = new \Datetime($tokenFromDb->tokenval);
            $diff = (new \Datetime)->diff($tval);

            if ($diff->i == 1 || $diff->i == 2) {
                echo "token\n";
                $this->changeToken();
            } else

            if ($tval < new \Datetime) {
                throw new \TBA\Exceptions\InvalidTokenException("Token expirado", 401);
            }

            return true;
        } else {
            throw new \TBA\Exceptions\InvalidTokenException("Credencial errada");
        }

        return false;
    }

    public function setConnection(\PDO $conn)
    {
        $this->conn = $conn;

        return $this;
    }

    public function setHeader(Header $header)
    {
        $this->header = $header;

        return $this;
    }

    public function setGenerator(TokenGenerator $generator)
    {
        $this->generator = $generator;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getGenerator()
    {
        if (is_null($this->generator)) {
            $this->generator = new Md5TokenGenerator($this->config['salt']);
        }

        return $this->generator;
    }

    public function getHeader()
    {
        if (is_null($this->header)) {
            $this->header = new \TBA\Header;
        }
        return $this->header;
    }

    public function getConfig()
    {
        return $this->config;
    }
}

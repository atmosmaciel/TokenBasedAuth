<?php
namespace TBA\Boilerplate\Silex\Skeleton\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TBA\Exceptions\UnauthorizedException;
use \TBA\Exceptions\InvalidLoginException;
use \TBA\TokenBasedAuth;

class TBAProvider implements ServiceProviderInterface
{
    const ERRO_1 = "Combinação de login e senha inválidos";

    private $config = array();

    public function register(Application $app)
    {
        $app['tba.obj'] = $app->share(function () use ($app) {
            if (isset($app['config'])) {
                $this->config['table_name'] = $app['config']->tba->table_name;
                $this->config['user_field'] = $app['config']->tba->user_field;
                $this->config['pass_field'] = $app['config']->tba->pass_field;
                $this->config['token_timeout'] = $app['config']->tba->token_timeout;
                $this->config['salt'] = $app['config']->tba->salt;
            } else {
                $this->config['table_name'] = $app['tba.table_name'];
                $this->config['user_field'] = $app['tba.user_field'];
                $this->config['pass_field'] = $app['tba.pass_field'];
                $this->config['token_timeout'] = $app['tba.token_timeout'];
                $this->config['salt'] = $app['tba.salt'];
            }

            $tba = new TokenBasedAuth($this->config);
            $tba->setHeader(new \TBA\Header);

            if (isset($app['db'])) {
                $tba->setConnection($app['db']);
            }

            return $tba;
        });

        $app->get('/logout', function () {
            if (isset($_SESSION["m1_53ck_m4"])) {
                unset($_SESSION["m1_53ck_m4"]);
            }

            $dados = ["loggedOut" => "ok"];

            error_log("/logout");
            return new JsonResponse($dados, 200);
        });

        $app->options('/login', function () {
            return new JsonResponse(["result" => "ok"], 200);
        });

        $app->post('/login', function (Request $request) use ($app) {
            try {
                $dados = array();
                $form = $request->request->all();

                if (!isset($form['user']) || !isset($form['passwd'])) {
                    error_log("1 - email ou senha não enviados");
                    throw new UnauthorizedException(self::ERRO_1, 120001);
                }

                $pwdHash = md5($this->config['salt'] . "{$form['passwd']}123X");
                if (!$app['tba.obj']->login($form['user'], $pwdHash)) {
                    error_log("senha informada não confere: {$form['user']} - {$pwdHash}");
                    throw new UnauthorizedException("erro no login", 120004);
                }

                $dados["msg"] = "Login com sucesso";

                if (isset($this->config->token_as_field) && $this->config->token_as_field == true) {
                    $dados['token'] = $app['tba.obj']->getUser()->token;
                }

                unset($form);

                $response = new JsonResponse($dados, 200);
                $response->headers->set("ClientToken", $app['tba.obj']->getUser()->token);

                return $response;
            } catch (UnauthorizedException $e) {
                error_log(">> 1 - 401 - erro verificado: {$e->getMessage()}");

                return new JsonResponse(["msg" => $e->getMessage()], 401);
            } catch (InvalidLoginException $e) {
                error_log(">> 1 - 403 - erro verificado: {$e->getMessage()}");

                return new JsonResponse(["msg" => $e->getMessage()], 403);
            } catch (\Exception $e) {
                error_log(">> 2 - 500 - erro verificado: {$e->getMessage()}");

                return new JsonResponse(["msg" => $e->getMessage()], 500);
            }
        });

        $app->before(function (Request $request) use ($app) {
            if (is_null($request->headers->get('AppToken'))) {
                error_log("token não informado");
                throw new UnauthorizedException("Aplicação não reconhecida", 120006);
            }

            if ($request->headers->get('AppToken') !== APP_TOKEN) {
                error_log("token não confere");
                throw new UnauthorizedException("Aplicação não reconhecida", 120005);
            }
        }, 1);

        $app->before(function (Request $request) use ($app) {
            if (!in_array($request->getRequestUri(), $app['login.openroutes'])) {
                $dados = array();

                if (is_null($request->headers->get('ClientToken'))) {
                    throw new UnauthorizedException("Cliente não reconhecido");
                }
            }
        }, 3);

        $app->after(function (Request $request, Response $response) use ($app) {
            $response->headers->set("AppToken", APP_TOKEN);

            try {
                $user = $app['tba.obj']->getUserByToken();
                error_log(print_r($user, true));

                $response->headers->set("ClientToken", $user->token);
            } catch (\Exception $e) {
                error_log("client token: {$e->getMessage()}");
            }

            return $response;
        }, 1);
    }

    public function boot(Application $app)
    {
    }
}

/*
OK Checa se existe o AppToken
OK Checa se o AppToken confere
OK Deixa passar a rota liberada
OK Checa se existe o token de cliente
Checa se o token é válido
Coloca o token de cliente em todas as respostas
 */

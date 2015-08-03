<?php
namespace TBA\Boilerplate\Silex\Skeleton\Controller;

use Skel\Controller\Controller;

abstract class PrivateController extends Controller {
  public function isAdmin() {
    return true; //roadmap
  }

  public function isOwner() {
    return true; //roadmap
  }

  public function checkAppAndClientToken() {
    return ( $this->app['tba']->checkAppToken() && $this->app['tba']->checkClientToken() );
  }

  public function checkAppToken() {
    $token = \TBA\Header::me()->getAppToken();
      //error_log("TOKEN API: {$token}");
    return ( $token == APP_TOKEN );
  }

  public function checkClientToken() {
    $token = \TBA\Header::me()->getClientToken();
      //error_log("TOKEN: {$token}");

    $this->app['tba']->setConnection( $this->app['db'] );
      
    try {
      return $this->app['tba']->check($token);
    } catch (\Exception $e) {
      if ( $e->getCode() == 401 ) {
        throw new \UnauthorizedException("Error Processing Request", 1);
      }
    }
  }

  /**
  * Verifica se o usuário está devidamente logado,
  * se o token de cliente é válido,
  * e se o token da aplicação está correto
  */
  public function validate() {
    return true;
  }
}
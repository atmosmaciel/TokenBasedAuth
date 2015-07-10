<?php
namespace Acoes;

use \Respect\Rest\Routable;
use \Auth\TokenBasedAuth;
use \Auth\Header;

abstract class PrivateRoutable implements Routable {
  public function isAdmin() {
    return true;
  }

  public function isOwner() {
    return true;
  }

  public function checkAppAndClientToken() {
    return ( $this->checkAppToken() && $this->checkClientToken() );
  }

  public function checkAppToken() {
    $token = \Auth\Header::me()->getAppToken();
      //error_log("TOKEN API: {$token}");
    return ( $token == APP_TOKEN );
  }

  public function checkClientToken() {
    $token = \Auth\Header::me()->getClientToken();
      //error_log("TOKEN: {$token}");

    $a = new \Auth\TokenBasedAuth;
      $a->setConnection( \Charon\Connection::me()->get() );
      
    try {
      return $a->check($token);
    } catch (\Exception $e) {
      if ( $e->getCode() == 401 ) {
        $this->naoAutorizado( $e->getMessage() );
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

  public function naoAutorizado($msg="Não autorizado") {
    http_response_code(401);
    return ["msg"=>$msg];
  }
}

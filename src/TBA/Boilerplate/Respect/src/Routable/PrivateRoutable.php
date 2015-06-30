<?php
namespace Acoes;

use \Respect\Rest\Routable;
use \Auth\TokenBasedAuth,
    \Auth\Header;

abstract class PrivateRoutable implements Routable {
  function isAdmin() {
    return true;
  }

  function isOwner() {
    return true;
  }

  function checkAppAndClientToken() {
    return ( $this->checkAppToken() && $this->checkClientToken() );
  }

  function checkAppToken() {
    $token = \Auth\Header::me()->getAppToken();
      //error_log("TOKEN API: {$token}");
    return ( $token == APP_TOKEN );
  }

  function checkClientToken() {
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
  function validate() {
    return true;
  }

  function naoAutorizado($msg="Não autorizado") {
    http_response_code(401);
    return ["msg"=>$msg];
  }
}

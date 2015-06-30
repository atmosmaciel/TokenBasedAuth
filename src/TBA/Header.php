<?php
namespace TBA;

class Header {
  private $headers;

  private static $instance;

  static function me() {
    if ( is_null(self::$instance ) ) {
      self::$instance = new Header;
    }

    return self::$instance;
  }

  function getAllHeaders() {
    return getallheaders();
  }

  function getAll() {
    if ( empty($this->headers) ) {
      $h = $this->getAllHeaders();
      foreach( $h as $key=>$value ) {
        $this->headers[ $key ] = $value;
        //echo "{$key} = {$value}\n";
      }
    }
    
    return $this->headers;
  }

  function getClientToken() {
    $this->getAll();

    return $this->headers['ClientToken'];
  }

  function getAppToken() {
    $this->getAll();

    return $this->headers['AppToken'];
  }
}

<?php
namespace TBA;

class Header {
  private $headers;

  private static $instance;

  public static function me() {
    if (is_null(self::$instance )) {
      self::$instance = new Header;
    }

    return self::$instance;
  }

  public function getAllHeaders() {
    return getallheaders();
  }

  public function getAll() {
    if (empty($this->headers)) {
      $h = $this->getAllHeaders();
      foreach($h as $key=>$value) {
        $this->headers[ $key ] = $value;
      }
    }
    
    return $this->headers;
  }

  public function getClientToken() {
    $this->getAll();

    return $this->headers['ClientToken'];
  }

  public function getAppToken() {
    $this->getAll();

    return $this->headers['AppToken'];
  }
}

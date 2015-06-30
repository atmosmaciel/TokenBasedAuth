<?php
namespace TBA\Generators;

abstract class TokenGenerator {
	protected $salt;

	function __construct($salt) {
		$this->salt = $salt;
	}

	abstract function generate($value=null);
}
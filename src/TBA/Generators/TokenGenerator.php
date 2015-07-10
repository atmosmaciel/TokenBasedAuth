<?php
namespace TBA\Generators;

abstract class TokenGenerator {
	protected $salt;

	public function __construct($salt) {
		$this->salt = $salt;
	}

	abstract public function generate($value=null);
}
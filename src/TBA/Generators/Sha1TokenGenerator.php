<?php
namespace TBA\Generators;

class Sha1TokenGenerator extends TokenGenerator {
	function generate($value=null) {
		$value = ( is_null($value) )
			? "bta-" . ( new \Datetime )->format("Y-m-d H:i:s")
			: $value . ( new \Datetime )->format("Y-m-d H:i:s");

		return sha1("{$this->salt}-{$value}");
	}
}
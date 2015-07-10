<?php
namespace TBA\Generators;

class Sha1TokenGenerator extends TokenGenerator {
	public function generate($value=null) {
		$value = ( is_null($value) )
			? "sha1-bta-" . ( new \Datetime )->format("Y-m-d H:i:s")
			: "sha1-bta-" . $value . ( new \Datetime )->format("Y-m-d H:i:s");

		return sha1("{$this->salt}-{$value}");
	}
}
<?php
namespace TBA\Generators;

class Sha1TokenGenerator extends TokenGenerator {
	public function generate($value=null) {
		$value = "sha1-bta-{$value}" . ( new \Datetime )->format("Y-m-d H:i:s");

		$value .= (new \Datetime)->format("Y-m-d H:i:s");

		return sha1("{$this->salt}-{$value}");
	}
}
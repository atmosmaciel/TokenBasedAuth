<?php
namespace TBA\Generators;

class Md5TokenGenerator extends TokenGenerator {
	function generate($value=null) {
		$value = ( is_null($value) )
			? "bta-" . ( new \Datetime )->format("Y-m-d H:i:s")
			: $value . ( new \Datetime )->format("Y-m-d H:i:s");

		return md5("{$this->salt}-{$value}");
	}
}
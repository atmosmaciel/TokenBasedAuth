<?php
namespace TBA\Generators;

class Md5TokenGenerator extends TokenGenerator {
	public function generate($value=null) {
		$value = "md5-bta-{$value}" . ( new \Datetime )->format("Y-m-d H:i:s");

		$value .= ( new \Datetime )->format("Y-m-d H:i:s");

		return md5("{$this->salt}-{$value}");
	}
}
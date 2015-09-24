<?php

namespace TBA\Generators;

class Sha2TokenGenerator extends TokenGenerator
{
    public function generate($value=null)
    {
        $value = "sha256-bta-{$value}" . ( new \Datetime )->format("Y-m-d H:i:s");

        return hash("sha256", "{$this->salt}-{$value}");
    }
}

<?php
namespace TBA\Generators;

class MCryptGenerator extends Generator
{
    public function genetate($value = null)
    {
        $value = "sha1-bta-{$value}" . (new \Datetime)->format("Y-m-d H:i:s");

        $value .= (new \Datetime)->format("Y-m-d H:i:s");

        return base64_encode($value);
    }
}

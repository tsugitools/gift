<?php

require_once "util.php";

class UtilTest extends PHPUnit_Framework_TestCase
{
    public function testSomething() {
        $ent = htmlent_utf8('<>');
        $this->assertEquals($ent, "&lt;&gt;");
    }
}


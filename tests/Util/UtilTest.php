<?php

require_once "util.php";

class UtilTest extends PHPUnit_Framework_TestCase
{
    public function testSomething() {
        $ent = htmlentities('<>');
        $this->assertEquals($ent, "&lt;&gt;");
    }
}


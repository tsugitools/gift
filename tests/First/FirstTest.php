<?php

class FirstTest extends PHPUnit_Framework_TestCase
{
    public function testSomething() {
        $stack = [];
        $this->assertEmpty($stack);
        $this->assertEquals("hello", "hello");
        $this->assertEquals(array("hello"), array("hello"));
        $this->assertTrue(2 == 2);
        // echo("HELLO WORLD\n"); // Only for debugging...
    }
}


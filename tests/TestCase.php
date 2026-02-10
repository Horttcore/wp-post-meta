<?php

namespace RalfHortt\Meta\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        clear_wordpress_globals();
    }

    protected function tearDown(): void
    {
        clear_wordpress_globals();
        parent::tearDown();
    }
}

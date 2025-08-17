<?php

namespace Abigah\BotCop\Tests;

use Abigah\BotCop\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}

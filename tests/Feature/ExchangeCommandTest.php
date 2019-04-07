<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExchangeCommandTest extends TestCase
{
    /**
     * Test response from Exchange.
     *
     * @return void
     */
    public function testExchangeCommand()
    {
        $this->artisan('exchange Halberd')
             ->expectsOutput('Halberd [1]')
             ->assertExitCode(0);
    }
}

<?php

namespace Tests\Feature;

use \Tests\TestCase;

class ActivityLogTest extends TestCase
{
    // WIP
    public function test_it_logs_an_activity()
    {
        $activity = activity();
        $this->assertInstanceOf( \Activity::class, $activity);
    }

}
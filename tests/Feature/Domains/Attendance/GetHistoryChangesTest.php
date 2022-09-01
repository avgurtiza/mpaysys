<?php


namespace Tests\Feature\Domains\Attendance;

use Domains\Attendance\Actions\GetHistoryChanges;
use Tests\TestCase;

class GetHistoryChangesTest extends TestCase
{
    /**
     * @var GetHistoryChanges
     */
    private $action;

    public function __setup() {
        parent::setup();

        $this->action = new GetHistoryChanges();
    }

    /** @test */
    public function test_history_is_fetched() {
        $this->markTestIncomplete('Someday');
    }
}
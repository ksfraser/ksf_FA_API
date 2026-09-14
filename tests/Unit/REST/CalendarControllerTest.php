<?php
/**
 * CalendarControllerTest
 *
 * Unit tests for Ksfraser\FA\API\REST\CalendarController.
 *
 * FA infrastructure (hook_invoke_first, db_query, etc.) is stubbed in
 * tests/bootstrap.php.  Tests control hook return values via
 * $GLOBALS['_test_hook_results'].
 *
 * A testable subclass (CalendarControllerTestable) overrides _parse_body()
 * so create/update tests can inject a request body without php://input.
 *
 * PHP 7.4+ compatible.
 *
 * @package Ksfraser\Tests\Unit\REST
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\REST;

use Ksfraser\REST\CalendarController;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass — overrides _parse_body() so tests can inject bodies
 * without php://input.
 *
 * @since 1.0.0
 */
class CalendarControllerTestable extends CalendarController
{
    /** @var array|null Body to return from _parse_body() */
    public $injectedBody = array();

    /**
     * @return array|null
     */
    protected function _parse_body()
    {
        return $this->injectedBody;
    }
}

/**
 * @covers \Ksfraser\FA\API\REST\CalendarController
 */
class CalendarControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset hook stubs before each test
        $GLOBALS['_test_hook_results'] = array();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Capture JSON output from a controller action.
     *
     * @param callable $fn
     * @return array Decoded JSON
     */
    private function capture(callable $fn): array
    {
        ob_start();
        $fn();
        $raw = ob_get_clean();
        $decoded = json_decode($raw, true);
        $this->assertNotNull($decoded, "Output was not valid JSON: " . $raw);
        return $decoded;
    }

    // -------------------------------------------------------------------------
    // list()
    // -------------------------------------------------------------------------

    public function testListReturnsEmptyArrayWhenNoHookHandler(): void
    {
        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->list(array());
        });

        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);
        $this->assertEmpty($json['data']);
        $this->assertEquals(0, $json['total']);
    }

    public function testListReturnsEntriesFromHook(): void
    {
        $entries = array(
            array('id' => 1, 'title' => 'Board Meeting', 'start' => '2026-05-17T10:00:00'),
            array('id' => 2, 'title' => 'Follow-up Call', 'start' => '2026-05-18T14:00:00'),
        );

        $GLOBALS['_test_hook_results']['calendar_entries_query'] = function (
            string $method,
            array &$data
        ) use ($entries) {
            $data['entries'] = $entries;
            return $entries;
        };

        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->list(array());
        });

        $this->assertTrue($json['success']);
        $this->assertCount(2, $json['data']);
        $this->assertEquals(2, $json['total']);
        $this->assertEquals('Board Meeting', $json['data'][0]['title']);
    }

    public function testListPassesQueryStringFiltersToHook(): void
    {
        $_GET['source'] = 'crm';
        $_GET['start']  = '2026-05-01';
        $_GET['end']    = '2026-05-31';

        $capturedData = null;

        $GLOBALS['_test_hook_results']['calendar_entries_query'] = function (
            string $method,
            array &$data
        ) use (&$capturedData) {
            $capturedData = $data;
            return array();
        };

        $controller = new CalendarController();
        $this->capture(function () use ($controller) {
            $controller->list(array());
        });

        // Clean up superglobal
        unset($_GET['source'], $_GET['start'], $_GET['end']);

        $this->assertNotNull($capturedData);
        $this->assertEquals('2026-05-01', $capturedData['start']);
        $this->assertEquals('2026-05-31', $capturedData['end']);
        $this->assertEquals('crm', $capturedData['filters']['source']);
    }

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsSingleEntryByIdFilter(): void
    {
        $entry = array('id' => 5, 'title' => 'Client Date');

        $GLOBALS['_test_hook_results']['calendar_entries_query'] = function (
            string $method,
            array &$data
        ) use ($entry) {
            $data['entries'] = array($entry);
            return array($entry);
        };

        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->get(array('id' => 5));
        });

        $this->assertTrue($json['success']);
        $this->assertEquals(5, $json['data']['id']);
        $this->assertEquals('Client Date', $json['data']['title']);
    }

    public function testGetReturns404WhenEntryNotFound(): void
    {
        $GLOBALS['_test_hook_results']['calendar_entries_query'] = function (
            string $method,
            array &$data
        ) {
            $data['entries'] = array();
            return array();
        };

        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->get(array('id' => 99));
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('not found', strtolower($json['error']));
    }

    public function testGetReturns400ForInvalidId(): void
    {
        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->get(array('id' => 0));
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('invalid', strtolower($json['error']));
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function testCreateReturns201WithNewId(): void
    {
        $GLOBALS['_test_hook_results']['calendar_entry_create'] = 42;

        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array(
            'title'       => 'Team Standup',
            'start_date'  => '2026-05-20',
            'source_type' => 'meeting',
        );

        $json = $this->capture(function () use ($controller) {
            $controller->create(array());
        });

        $this->assertTrue($json['success']);
        $this->assertEquals(42, $json['data']['id']);
    }

    public function testCreateDefaultsSourceToUser(): void
    {
        $capturedData = null;

        $GLOBALS['_test_hook_results']['calendar_entry_create'] = function (
            string $method,
            array &$data
        ) use (&$capturedData) {
            $capturedData = $data;
            return 1;
        };

        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array('title' => 'My Event', 'start_date' => '2026-06-01');

        $this->capture(function () use ($controller) {
            $controller->create(array());
        });

        $this->assertNotNull($capturedData);
        $this->assertEquals('user', $capturedData['source']);
    }

    public function testCreateReturns503WhenNoHookHandler(): void
    {
        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array('title' => 'Orphan Event', 'start_date' => '2026-06-01');

        $json = $this->capture(function () use ($controller) {
            $controller->create(array());
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('not available', strtolower($json['error']));
    }

    public function testCreateReturns400ForInvalidJson(): void
    {
        $controller = new CalendarControllerTestable();
        $controller->injectedBody = null; // _parse_body returns null = bad JSON

        $json = $this->capture(function () use ($controller) {
            $controller->create(array());
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('invalid json', strtolower($json['error']));
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function testUpdateReturnsSuccessWhenHookHandled(): void
    {
        $GLOBALS['_test_hook_results']['calendar_entry_update'] = true;

        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array('title' => 'Updated Title');

        $json = $this->capture(function () use ($controller) {
            $controller->update(array('id' => 7));
        });

        $this->assertTrue($json['success']);
        $this->assertEquals(7, $json['data']['id']);
    }

    public function testUpdateMergesPathIdIntoHookData(): void
    {
        $capturedData = null;

        $GLOBALS['_test_hook_results']['calendar_entry_update'] = function (
            string $method,
            array &$data
        ) use (&$capturedData) {
            $capturedData = $data;
            return true;
        };

        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array('title' => 'Renamed');

        $this->capture(function () use ($controller) {
            $controller->update(array('id' => 13));
        });

        $this->assertNotNull($capturedData);
        $this->assertEquals(13, $capturedData['id']);
        $this->assertEquals('Renamed', $capturedData['title']);
    }

    public function testUpdateReturns400ForInvalidId(): void
    {
        $controller = new CalendarControllerTestable();

        $json = $this->capture(function () use ($controller) {
            $controller->update(array('id' => 0));
        });

        $this->assertFalse($json['success']);
    }

    public function testUpdateReturns503WhenNoHookHandler(): void
    {
        $controller = new CalendarControllerTestable();
        $controller->injectedBody = array('title' => 'X');

        $json = $this->capture(function () use ($controller) {
            $controller->update(array('id' => 5));
        });

        $this->assertFalse($json['success']);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function testDeleteReturnsSuccessWhenHookHandled(): void
    {
        $GLOBALS['_test_hook_results']['calendar_entry_delete'] = true;

        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->delete(array('id' => 3));
        });

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('message', $json);
    }

    public function testDeletePassesCorrectIdToHook(): void
    {
        $capturedData = null;

        $GLOBALS['_test_hook_results']['calendar_entry_delete'] = function (
            string $method,
            array &$data
        ) use (&$capturedData) {
            $capturedData = $data;
            return true;
        };

        $controller = new CalendarController();
        $this->capture(function () use ($controller) {
            $controller->delete(array('id' => 17));
        });

        $this->assertNotNull($capturedData);
        $this->assertEquals(17, $capturedData['id']);
    }

    public function testDeleteReturns400ForInvalidId(): void
    {
        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->delete(array('id' => 0));
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('invalid', strtolower($json['error']));
    }

    public function testDeleteReturns503WhenNoHookHandler(): void
    {
        $controller = new CalendarController();
        $json = $this->capture(function () use ($controller) {
            $controller->delete(array('id' => 99));
        });

        $this->assertFalse($json['success']);
        $this->assertStringContainsString('not available', strtolower($json['error']));
    }
}

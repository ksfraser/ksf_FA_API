<?php
/**
 * CalendarController
 *
 * REST controller for calendar entries.  Routes HTTP requests to calendar
 * operations via hook_invoke_first() so the ksf_FA_API module has no hard
 * dependency on ksf_FA_Calendar — the hook glues them together at runtime.
 *
 * Called from api.php (FA-bootstrapped front controller).  $Hooks[] is already
 * populated by add_access_extensions() before this class is used.
 *
 * PHP 7.4 compatible — no PHP 8+ syntax.
 *
 * Endpoints (path relative to api.php):
 *   GET    /api/v1/calendar/entries          list entries (start/end/filters via query string)
 *   GET    /api/v1/calendar/entries/{id}     get single entry
 *   POST   /api/v1/calendar/entries          create entry
 *   PUT    /api/v1/calendar/entries/{id}     update entry
 *   DELETE /api/v1/calendar/entries/{id}     delete entry
 *
 * @package Ksfraser\FA\API\REST
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\REST;

/**
 * REST controller for /api/v1/calendar/entries.
 *
 * All public action methods follow the signature:
 *   public function <action>(array $pathArgs): void
 *
 * They write JSON to stdout and call http_response_code() as needed.
 * api.php calls them directly (no Slim / PSR-7 layer).
 *
 * @since 1.0.0
 */
class CalendarController
{
    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/calendar/entries
     * Query calendar entries for a date range.
     *
     * Query params:
     *   start   (Y-m-d) range start; default first day of current month
     *   end     (Y-m-d) range end;   default last day of current month
     *   source  (string) filter by source (pm, crm, hrm, user, …)
     *   assigned_to (string) filter by FA user
     *
     * @param array $pathArgs Unused for list action
     * @return void
     *
     * @since 1.0.0
     */
    public function list(array $pathArgs)
    {
        $data = array(
            'start'   => isset($_GET['start'])       ? (string) $_GET['start']       : null,
            'end'     => isset($_GET['end'])         ? (string) $_GET['end']         : null,
            'filters' => array(),
        );

        if (isset($_GET['source'])) {
            $data['filters']['source'] = (string) $_GET['source'];
        }
        if (isset($_GET['assigned_to'])) {
            $data['filters']['assigned_to'] = (string) $_GET['assigned_to'];
        }

        $entries = hook_invoke_first('calendar_entries_query', $data);

        if ($entries === null) {
            // No handler installed — calendar module not active
            $this->_json(array('success' => true, 'data' => array(), 'total' => 0));
            return;
        }

        $this->_json(array(
            'success' => true,
            'data'    => $entries,
            'total'   => count($entries),
        ));
    }

    /**
     * GET /api/v1/calendar/entries/{id}
     * Return a single calendar entry.
     *
     * @param array $pathArgs ['id' => int]
     * @return void
     *
     * @since 1.0.0
     */
    public function get(array $pathArgs)
    {
        $id = isset($pathArgs['id']) ? (int) $pathArgs['id'] : 0;
        if ($id <= 0) {
            $this->_error(400, 'Invalid entry ID');
            return;
        }

        // Query a single entry by ID via the entries hook (filters by id)
        $data = array(
            'filters' => array('id' => $id),
        );

        $entries = hook_invoke_first('calendar_entries_query', $data);

        if ($entries === null || empty($entries)) {
            $this->_error(404, 'Entry not found');
            return;
        }

        $this->_json(array(
            'success' => true,
            'data'    => $entries[0],
        ));
    }

    /**
     * POST /api/v1/calendar/entries
     * Create a calendar entry.
     *
     * Request body: JSON object with entry fields
     *   (title, start_date, end_date, source, source_type, …)
     *
     * @param array $pathArgs Unused
     * @return void
     *
     * @since 1.0.0
     */
    public function create(array $pathArgs)
    {
        $body = $this->_parse_body();
        if ($body === null) {
            $this->_error(400, 'Invalid JSON body');
            return;
        }

        // Provide safe defaults
        if (empty($body['source'])) {
            $body['source'] = 'user';
        }

        $data = $body;

        $id = hook_invoke_first('calendar_entry_create', $data);

        if ($id === null) {
            $this->_error(503, 'Calendar module not available');
            return;
        }

        http_response_code(201);
        $this->_json(array(
            'success' => true,
            'data'    => array('id' => (int) $id),
        ));
    }

    /**
     * PUT /api/v1/calendar/entries/{id}
     * Update an existing calendar entry.
     *
     * @param array $pathArgs ['id' => int]
     * @return void
     *
     * @since 1.0.0
     */
    public function update(array $pathArgs)
    {
        $id = isset($pathArgs['id']) ? (int) $pathArgs['id'] : 0;
        if ($id <= 0) {
            $this->_error(400, 'Invalid entry ID');
            return;
        }

        $body = $this->_parse_body();
        if ($body === null) {
            $this->_error(400, 'Invalid JSON body');
            return;
        }

        $data       = $body;
        $data['id'] = $id;

        $result = hook_invoke_first('calendar_entry_update', $data);

        if ($result === null) {
            $this->_error(503, 'Calendar module not available or entry not found');
            return;
        }

        $this->_json(array(
            'success' => true,
            'data'    => array('id' => $id),
        ));
    }

    /**
     * DELETE /api/v1/calendar/entries/{id}
     * Soft-delete a calendar entry.
     *
     * @param array $pathArgs ['id' => int]
     * @return void
     *
     * @since 1.0.0
     */
    public function delete(array $pathArgs)
    {
        $id = isset($pathArgs['id']) ? (int) $pathArgs['id'] : 0;
        if ($id <= 0) {
            $this->_error(400, 'Invalid entry ID');
            return;
        }

        $data = array('id' => $id);

        $result = hook_invoke_first('calendar_entry_delete', $data);

        if ($result === null) {
            $this->_error(503, 'Calendar module not available or entry not found');
            return;
        }

        $this->_json(array(
            'success' => true,
            'message' => 'Entry deleted',
        ));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse JSON request body.
     *
     * Protected (not private) so test subclasses can inject a body without
     * relying on php://input stream.
     *
     * @return array|null Decoded array, or null on parse failure
     *
     * @since 1.0.0
     */
    protected function _parse_body()
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return array();
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Output a JSON response (Content-Type header already set by api.php).
     *
     * @param array $payload
     * @return void
     *
     * @since 1.0.0
     */
    private function _json(array $payload)
    {
        echo json_encode($payload);
    }

    /**
     * Output a JSON error response with the given HTTP status code.
     *
     * @param int    $status HTTP status code
     * @param string $message Error message
     * @return void
     *
     * @since 1.0.0
     */
    private function _error(int $status, string $message)
    {
        http_response_code($status);
        echo json_encode(array('success' => false, 'error' => $message));
    }
}

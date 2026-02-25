<?php
/**
 * CSP Violation Reporter
 * 
 * Handles Content Security Policy violation reports and logging
 */
class Blue_Flamingo_CSP_Reporter {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize hooks
        add_action('rest_api_init', array($this, 'register_rest_route'));
    }

    public function register_rest_route() {
        register_rest_route('blueflamingo/v1', '/csp-report', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_csp_report'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_csp_report(WP_REST_Request $request) {
        if ($request->get_method() !== 'POST') {
            return new WP_REST_Response(['error' => 'Method not allowed'], 405);
        }
        $site_url = rtrim(get_site_url(), '/');
        $home_url = rtrim(home_url(), '/');

        $allowed_domains = [$site_url, $home_url];

        // Optionally expand with "www" or staging domains
        $parsed = wp_parse_url($site_url);
        if (!empty($parsed['host'])) {
            $allowed_domains[] = 'https://www.' . $parsed['host'];
        }

        // Merge admin-configured domains if available
        $extra_domains = get_option('blueflamingo_csp_allowed_domains', '');
        if ($extra_domains) {
            $allowed_domains = array_merge($allowed_domains, array_map('trim', explode(',', $extra_domains)));
        }
        $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');

        $is_allowed = false;
        foreach ($allowed_domains as $domain) {
            if (stripos($origin, $domain) === 0) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return new WP_REST_Response(['error' => 'Unauthorized origin'], 403);
        }
        
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';
        $ip = explode(',', $ip)[0];
        $limit_key = 'bf_csp_limit_' . md5($ip);
        if (get_transient($limit_key)) {
            return new WP_REST_Response(['error' => 'Too many requests'], 429);
        }
        set_transient($limit_key, true, 5); // allow one request per 5 seconds per IP

        $report = file_get_contents('php://input');
        $violations = json_decode($report, true);

        if (!$violations) {
            return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);
        }

        // Some browsers send a single object, some send an array
        if (isset($violations[0])) {
            // Multiple reports
            foreach ($violations as $violation) {
                $this->process_single_csp_violation($violation);
            }
        } else {
            // Single report
            $this->process_single_csp_violation($violations);
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }

    private function process_single_csp_violation($violation) {
        if (isset($violation['csp-report'])) {
            $report_data = $violation['csp-report'];
        } elseif (isset($violation['body'])) {
            $report_data = $violation['body'];
        } else {
            $report_data = $violation;
        }

        if (!empty($violation['user_agent'])) {
            $report_data['user_agent'] = $violation['user_agent'];
        }

        if (empty($report_data)) {
            blueflamingo_debug_log("Skipping empty CSP report");
            return;
        }

        $this->log_violation($report_data);
    }

    private function log_violation($report_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bf_csp_violations';

        // Normalize CSP report keys (handles multiple browser formats)
        $blocked_uri_full = $report_data['blocked-uri']
            ?? $report_data['blockedURL']
            ?? $report_data['blockedUrl']
            ?? $report_data['sourceFile']
            ?? $report_data['source-file']
            ?? '';

        $blocked_uri = '';

        if (!empty($blocked_uri_full)) {
            $blocked_uri_full = trim($blocked_uri_full);

            // Handle data:, blob:, inline, eval
            if (stripos($blocked_uri_full, 'data:') === 0) {
                $blocked_uri = 'data:';
            } elseif (stripos($blocked_uri_full, 'blob:') === 0) {
                $blocked_uri = 'blob:';
            } elseif (in_array(strtolower($blocked_uri_full), ['inline', "'inline'", 'eval', "'eval'"])) {
                $blocked_uri = strtolower(trim($blocked_uri_full, "'"));
            } else {
                // Try to parse as URL
                $parsed = parse_url($blocked_uri_full);
                if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
                    $blocked_uri = $parsed['scheme'] . '://' . $parsed['host'];
                    if (!empty($parsed['path'])) {
                        $blocked_uri .= $parsed['path'];
                    }
                } else {
                    // Fallback: remove query/fragments manually if any
                    $blocked_uri = strtok($blocked_uri_full, '?#');
                }
            }
        }
        $document_uri = $report_data['document-uri']
            ?? $report_data['documentURL']
            ?? $report_data['documentUrl']
            ?? '';

        $violated_directive = $report_data['violated-directive']
            ?? $report_data['violatedDirective']
            ?? '';

        $effective_directive = $report_data['effective-directive']
            ?? $report_data['effectiveDirective']
            ?? '';

        $original_policy = $report_data['original-policy']
            ?? $report_data['originalPolicy']
            ?? '';

        $referrer = $report_data['referrer'] ?? '';
        $user_agent = $report_data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';

        $status_code = null;
        if (isset($report_data['status-code'])) {
            $status_code = intval($report_data['status-code']);
        } elseif (isset($report_data['statusCode'])) {
            $status_code = intval($report_data['statusCode']);
        } elseif (isset($report_data['status'])) {
            $status_code = intval($report_data['status']);
        }

        // Skip empty/invalid reports
        if (empty($blocked_uri) && empty($document_uri) && (empty($effective_directive) || empty($violated_directive))) {
            blueflamingo_debug_log('Skipping empty CSP violation');
            return;
        }

        // Check for existing violation
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, count FROM $table_name
            WHERE blocked_uri = %s
            AND document_uri = %s
            AND (violated_directive = %s OR effective_directive = %s)
            LIMIT 1",
            $blocked_uri,
            $document_uri,
            $violated_directive,
            $effective_directive
        ));

        if ($existing) {
            $wpdb->update(
                $table_name,
                [
                    'count' => $existing->count + 1,
                    'last_occurrence' => current_time('mysql'),
                ],
                ['id' => $existing->id],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'blocked_uri' => substr($blocked_uri, 0, 1024),
                    'blocked_uri_full' => $blocked_uri_full,
                    'document_uri' => $document_uri,
                    'violated_directive' => $violated_directive,
                    'effective_directive' => $effective_directive,
                    'original_policy' => $original_policy,
                    'referrer' => $referrer,
                    'status_code' => $status_code,
                    'user_agent' => $user_agent,
                    'first_occurrence' => current_time('mysql'),
                    'last_occurrence' => current_time('mysql'),
                ],
                ['%s','%s','%s','%s','%s','%s','%d','%s','%s','%s']
            );

            if ($wpdb->last_error) {
                blueflamingo_debug_log("CSP DB insert error: " . $wpdb->last_error);
            }
        }
    }


}

// Initialize the CSP Reporter
Blue_Flamingo_CSP_Reporter::get_instance();
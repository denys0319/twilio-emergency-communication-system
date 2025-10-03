<?php
/**
 * Service layer class for Emergency Communication System
 * Handles WordPress database operations and Twilio API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Service {
    
    private $client;
    private $account_sid;
    private $auth_token;
    private $phone_number;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->account_sid = get_option('ecs_twilio_account_sid');
        $this->auth_token = get_option('ecs_twilio_auth_token');
        $this->phone_number = get_option('ecs_twilio_phone_number');
        
        if ($this->account_sid && $this->auth_token) {
            $this->init_twilio_client();
        }
    }
    
    private function init_twilio_client() {
        try {
            $this->client = new Twilio\Rest\Client($this->account_sid, $this->auth_token);
        } catch (Exception $e) {
            error_log('Twilio client initialization failed: ' . $e->getMessage());
            $this->client = null;
        }
    }
    
    /**
     * Create contact in WordPress database
     */
    public function create_contact($phone, $name = '', $group_id = null) {
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        
        // Check if table exists, create if missing
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_contacts'") != $table_contacts) {
            $ecs_database = new ECS_Database();
            $ecs_database->create_missing_tables();
            
            // Check again after creation attempt
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_contacts'") != $table_contacts) {
                return array('success' => false, 'error' => 'Database table does not exist and could not be created. Please check database permissions.');
            }
        }
        
        $data = array(
            'phone' => sanitize_text_field($phone),
            'name' => sanitize_text_field($name),
            'group_id' => $group_id ? intval($group_id) : null
        );
        
        $result = $this->wpdb->insert($table_contacts, $data);
        
        if ($result === false) {
            $error_message = $this->wpdb->last_error ? $this->wpdb->last_error : 'Unknown database error';
            return array('success' => false, 'error' => 'Failed to create contact: ' . $error_message);
        }
        
        return array(
            'success' => true,
            'contact_id' => $this->wpdb->insert_id,
            'phone' => $data['phone'],
            'name' => $data['name'],
            'group_id' => $data['group_id']
        );
    }
    
    /**
     * Update contact in WordPress database
     */
    public function update_contact($contact_id, $phone, $name = '', $group_id = null) {
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        
        // Check if table exists, create if missing
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_contacts'") != $table_contacts) {
            $ecs_database = new ECS_Database();
            $ecs_database->create_missing_tables();
            
            // Check again after creation attempt
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_contacts'") != $table_contacts) {
                return array('success' => false, 'error' => 'Database table does not exist and could not be created. Please check database permissions.');
            }
        }
        
        $data = array(
            'phone' => sanitize_text_field($phone),
            'name' => sanitize_text_field($name),
            'group_id' => $group_id ? intval($group_id) : null
        );
        
        $where = array('id' => intval($contact_id));
        
        $result = $this->wpdb->update($table_contacts, $data, $where);
        
        if ($result === false) {
            $error_message = $this->wpdb->last_error ? $this->wpdb->last_error : 'Unknown database error';
            return array('success' => false, 'error' => 'Failed to update contact: ' . $error_message);
        }
        
        return array(
            'success' => true,
            'contact_id' => $contact_id,
            'phone' => $data['phone'],
            'name' => $data['name'],
            'group_id' => $data['group_id']
        );
    }
    
    /**
     * Get contacts from WordPress database
     */
    public function get_contacts($group_id = null, $limit = 50, $offset = 0) {
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        
        $where_clause = '';
        if ($group_id === 'no-group') {
            // Filter for contacts without a group
            $where_clause = "WHERE c.group_id IS NULL";
        } elseif ($group_id && is_numeric($group_id)) {
            // Filter for contacts in a specific group
            $where_clause = $this->wpdb->prepare("WHERE c.group_id = %d", $group_id);
        }
        
        $sql = "SELECT c.*, g.name as group_name 
                FROM $table_contacts c 
                LEFT JOIN $table_groups g ON c.group_id = g.id 
                $where_clause 
                ORDER BY c.created_at DESC 
                LIMIT %d OFFSET %d";
        
        $contacts = $this->wpdb->get_results($this->wpdb->prepare($sql, $limit, $offset), ARRAY_A);
        
        return array('success' => true, 'contacts' => $contacts);
    }
    
    /**
     * Create contact group in WordPress database
     */
    public function create_contact_group($name, $description = '') {
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        
        // Check if table exists, create if missing
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_groups'") != $table_groups) {
            $ecs_database = new ECS_Database();
            $ecs_database->create_missing_tables();
            
            // Check again after creation attempt
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_groups'") != $table_groups) {
                return array('success' => false, 'error' => 'Database table does not exist and could not be created. Please check database permissions.');
            }
        }
        
        $data = array(
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description)
        );
        
        $result = $this->wpdb->insert($table_groups, $data);
        
        if ($result === false) {
            $error_message = $this->wpdb->last_error ? $this->wpdb->last_error : 'Unknown database error';
            return array('success' => false, 'error' => 'Failed to create contact group: ' . $error_message);
        }
        
        return array(
            'success' => true,
            'group_id' => $this->wpdb->insert_id,
            'name' => $data['name'],
            'description' => $data['description']
        );
    }
    
    /**
     * Get contact groups from WordPress database
     */
    public function get_contact_groups() {
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        
        $sql = "SELECT g.*, COUNT(c.id) as contact_count 
                FROM $table_groups g 
                LEFT JOIN {$this->wpdb->prefix}ecs_contacts c ON g.id = c.group_id 
                GROUP BY g.id 
                ORDER BY g.created_at DESC";
        
        $groups = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array('success' => true, 'groups' => $groups);
    }
    
    /**
     * Update contact group
     */
    public function update_contact_group($group_id, $name, $description = '') {
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        
        $data = array(
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description)
        );
        
        $result = $this->wpdb->update($table_groups, $data, array('id' => $group_id));
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to update contact group');
        }
        
        return array('success' => true, 'message' => 'Contact group updated successfully');
    }
    
    /**
     * Delete contact group
     */
    public function delete_contact_group($group_id) {
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        
        // Remove group_id from contacts in this group
        $this->wpdb->update($table_contacts, array('group_id' => null), array('group_id' => $group_id));
        
        // Delete the group
        $result = $this->wpdb->delete($table_groups, array('id' => $group_id));
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to delete contact group');
        }
        
        return array('success' => true, 'message' => 'Contact group deleted successfully');
    }
    
    /**
     * Delete contact
     */
    public function delete_contact($contact_id) {
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        
        $result = $this->wpdb->delete($table_contacts, array('id' => $contact_id));
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to delete contact');
        }
        
        return array('success' => true, 'message' => 'Contact deleted successfully');
    }
    
    /**
     * Get single contact by ID
     */
    public function get_contact($contact_id) {
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        
        $contact = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_contacts WHERE id = %d",
            $contact_id
        ), ARRAY_A);
        
        if (!$contact) {
            return array('success' => false, 'error' => 'Contact not found');
        }
        
        return array('success' => true, 'contact' => $contact);
    }
    
    /**
     * Send message using Twilio
     */
    public function send_message($to, $message, $from = null, $recipient_name = '') {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        $from = $from ?: $this->phone_number;
        
        try {
            $message_obj = $this->client->messages->create(
                $to,
                array(
                    'from' => $from,
                    'body' => $message
                )
            );
            
            // Store message in database with recipient name
            $this->store_message($message_obj, $to, $message, $from, $recipient_name);
            
            return array(
                'success' => true,
                'message_sid' => $message_obj->sid,
                'status' => $message_obj->status,
                'to' => $message_obj->to,
                'from' => $message_obj->from,
                'body' => $message_obj->body,
                'date_created' => $message_obj->dateCreated->format('Y-m-d H:i:s')
            );
        } catch (Exception $e) {
            error_log('Twilio SMS Error: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send bulk messages using Twilio
     */
    public function send_bulk_message($recipients, $message, $from = null) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        $from = $from ?: $this->phone_number;
        $results = array();
        
        foreach ($recipients as $recipient) {
            // Pass recipient name to send_message
            $recipient_name = $recipient['name'] ?? '';
            $result = $this->send_message($recipient['phone'], $message, $from, $recipient_name);
            $result['recipient_name'] = $recipient_name;
            $result['recipient_id'] = $recipient['id'] ?? '';
            $results[] = $result;
        }
        
        return array('success' => true, 'results' => $results);
    }
    
    /**
     * Store message in database
     */
    private function store_message($message_obj, $to, $message, $from, $recipient_name = '') {
        $table_messages = $this->wpdb->prefix . 'ecs_messages';
        
        // Store in WordPress timezone
        $sent_at = null;
        if ($message_obj->dateSent) {
            // Convert Twilio UTC time to WordPress timezone
            $gmt_offset = get_option('gmt_offset', 0);
            $sent_at = date('Y-m-d H:i:s', $message_obj->dateSent->getTimestamp() + ($gmt_offset * 3600));
        }
        
        // Get current WordPress time
        $created_at = current_time('mysql');
        
        $data = array(
            'twilio_sid' => $message_obj->sid,
            'recipient_phone' => $to,
            'recipient_name' => $recipient_name, // Store recipient name
            'message' => $message,
            'from_number' => $from,
            'status' => $message_obj->status,
            'sent_at' => $sent_at,
            'created_at' => $created_at,
            'error_code' => $message_obj->errorCode,
            'error_message' => $message_obj->errorMessage
        );
        
        error_log("Storing message - Recipient: $recipient_name ($to), created_at: $created_at, sent_at: $sent_at");
        
        $result = $this->wpdb->insert($table_messages, $data);
        
        if ($result === false) {
            error_log("Failed to insert message: " . $this->wpdb->last_error);
        }
    }
    
    /**
     * Get message history from database
     */
    public function get_message_history($limit = 50, $offset = 0, $status = null) {
        $table_messages = $this->wpdb->prefix . 'ecs_messages';
        
        $where_clause = '';
        if ($status) {
            $where_clause = $this->wpdb->prepare("WHERE status = %s", $status);
        }
        
        $sql = "SELECT * FROM $table_messages $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        $messages = $this->wpdb->get_results($this->wpdb->prepare($sql, $limit, $offset), ARRAY_A);
        
        return array('success' => true, 'messages' => $messages);
    }
    
    /**
     * Get message status from Twilio and update database
     */
    public function get_message_status($message_sid) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            // Fetch message status from Twilio
            $message = $this->client->messages($message_sid)->fetch();
            
            // Prepare update data
            $update_data = array(
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage
            );
            
            // If message is delivered, update delivered_at timestamp
            // Use dateUpdated which reflects when status last changed (including to 'delivered')
            if ($message->status === 'delivered' && !empty($message->dateUpdated)) {
                // Store in WordPress timezone
                $update_data['delivered_at'] = date('Y-m-d H:i:s', $message->dateUpdated->getTimestamp() + (get_option('gmt_offset', 0) * 3600));
            }
            
            // Update status in database
            $table_messages = $this->wpdb->prefix . 'ecs_messages';
            $this->wpdb->update(
                $table_messages,
                $update_data,
                array('twilio_sid' => $message_sid),
                array_fill(0, count($update_data), '%s'),
                array('%s')
            );
            
            return array(
                'success' => true,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'date_sent' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Schedule message
     */
    public function schedule_message($recipients, $message, $send_time, $from = null) {
        $table_scheduled = $this->wpdb->prefix . 'ecs_scheduled_messages';
        
        // Convert datetime-local format (2025-10-01T15:00) to MySQL format
        // The input is in LOCAL WordPress timezone
        $send_time_formatted = str_replace('T', ' ', $send_time) . ':00'; // Convert to Y-m-d H:i:s
        
        // Get WordPress timezone
        $wp_timezone = wp_timezone_string();
        $timezone = new DateTimeZone($wp_timezone);
        
        // Create DateTime object in WordPress timezone
        $send_datetime = new DateTime($send_time_formatted, $timezone);
        
        // Get timestamp for cron scheduling (WordPress uses local timestamp)
        $send_timestamp = $send_datetime->getTimestamp();
        
        error_log("ECS Schedule Debug:");
        error_log("  Input: $send_time");
        error_log("  Formatted: $send_time_formatted");
        error_log("  WP Timezone: $wp_timezone");
        error_log("  DateTime: " . $send_datetime->format('Y-m-d H:i:s T'));
        error_log("  Timestamp: $send_timestamp");
        error_log("  Current timestamp: " . current_time('timestamp'));
        
        // Store the send time in database (in WordPress timezone)
        $data = array(
            'recipients' => json_encode($recipients),
            'message' => $message,
            'from_number' => $from ?: $this->phone_number,
            'send_time' => $send_time_formatted,
            'status' => 'pending'
        );
        
        $result = $this->wpdb->insert($table_scheduled, $data);
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to schedule message: ' . $this->wpdb->last_error);
        }
        
        $message_id = $this->wpdb->insert_id;
        
        // Schedule WordPress cron job
        $cron_scheduled = wp_schedule_single_event($send_timestamp, 'ecs_send_scheduled_message', array($message_id));
        
        if ($cron_scheduled === false) {
            // Cron scheduling failed, clean up the database entry
            $this->wpdb->delete($table_scheduled, array('id' => $message_id));
            return array('success' => false, 'error' => 'Failed to schedule cron job');
        }
        
        error_log("ECS: Message scheduled - ID: $message_id, Time: $send_time_formatted, Timestamp: $send_timestamp");
        
        return array(
            'success' => true,
            'message_id' => $this->wpdb->insert_id,
            'scheduled_time' => $send_time
        );
    }
    
    /**
     * Get scheduled messages
     */
    public function get_scheduled_messages($limit = 50, $offset = 0, $status = null) {
        $table_scheduled = $this->wpdb->prefix . 'ecs_scheduled_messages';
        
        $where_clause = '';
        if ($status) {
            $where_clause = $this->wpdb->prepare("WHERE status = %s", $status);
        }
        
        $sql = "SELECT * FROM $table_scheduled $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $messages = $this->wpdb->get_results($this->wpdb->prepare($sql, $limit, $offset), ARRAY_A);
        
        return array('success' => true, 'messages' => $messages);
    }
    
    /**
     * Delete scheduled message
     */
    public function delete_scheduled_message($message_id) {
        $table_scheduled = $this->wpdb->prefix . 'ecs_scheduled_messages';
        
        $result = $this->wpdb->delete($table_scheduled, array('id' => $message_id));
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to delete scheduled message');
        }
        
        // Cancel WordPress cron job
        wp_clear_scheduled_hook('ecs_send_scheduled_message', array($message_id));
        
        return array('success' => true, 'message' => 'Scheduled message deleted successfully');
    }
    
    /**
     * Cancel scheduled message (mark as cancelled instead of deleting)
     */
    public function cancel_scheduled_message($message_id) {
        $table_scheduled = $this->wpdb->prefix . 'ecs_scheduled_messages';
        
        // Update status to cancelled
        $result = $this->wpdb->update(
            $table_scheduled,
            array('status' => 'cancelled'),
            array('id' => $message_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to cancel scheduled message');
        }
        
        // Cancel WordPress cron job
        wp_clear_scheduled_hook('ecs_send_scheduled_message', array($message_id));
        
        return array('success' => true, 'message' => 'Scheduled message cancelled successfully');
    }
    
    /**
     * Get message templates
     */
    public function get_message_templates() {
        $table_templates = $this->wpdb->prefix . 'ecs_message_templates';
        
        $sql = "SELECT * FROM $table_templates WHERE is_active = 1 ORDER BY created_at DESC";
        $templates = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array('success' => true, 'templates' => $templates);
    }
    
    /**
     * Create message template
     */
    public function create_message_template($name, $subject, $message) {
        $table_templates = $this->wpdb->prefix . 'ecs_message_templates';
        
        $data = array(
            'name' => sanitize_text_field($name),
            'subject' => sanitize_text_field($subject),
            'message' => sanitize_textarea_field($message),
            'is_active' => 1
        );
        
        $result = $this->wpdb->insert($table_templates, $data);
        
        if ($result === false) {
            return array('success' => false, 'error' => 'Failed to create message template');
        }
        
        return array('success' => true, 'template_id' => $this->wpdb->insert_id);
    }
    
    /**
     * Get Twilio client (public accessor)
     */
    public function get_client() {
        return $this->client;
    }
    
    /**
     * Normalize phone number to E.164 format
     */
    public function normalize_phone_number($phone) {
        if (empty($phone)) {
            return null;
        }
        
        $phone = trim($phone);
        
        // Skip if it's just "ext" or extension numbers
        if (preg_match('/^(ext|extension)/i', $phone)) {
            return null;
        }
        
        // Remove extensions (x123, ext 123, extension 123, etc.)
        $phone = preg_replace('/\s*(x|ext|extension)\s*\d+.*$/i', '', $phone);
        
        // Remove all non-digit characters except + at the beginning
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with +, assume it's already in E.164 format
        if (strpos($cleaned, '+') === 0) {
            return $cleaned;
        }
        
        // Remove any remaining + signs
        $cleaned = str_replace('+', '', $cleaned);
        
        // Handle different US phone number formats
        if (strlen($cleaned) == 10) {
            // 10-digit US number: 8164478859 -> +18164478859
            return '+1' . $cleaned;
        } elseif (strlen($cleaned) == 11 && substr($cleaned, 0, 1) == '1') {
            // 11-digit US number starting with 1: 18164478859 -> +18164478859
            return '+' . $cleaned;
        } elseif (strlen($cleaned) == 7) {
            // 7-digit local number: assume US area code (this is risky, but common)
            // We'll skip these as they're ambiguous
            return null;
        }
        
        // For other formats, try to clean and add + if it looks like a valid number
        if (strlen($cleaned) >= 10 && strlen($cleaned) <= 15) {
            return '+' . $cleaned;
        }
        
        // If we can't normalize it, return null
        return null;
    }
    
    /**
     * Test Twilio connection
     */
    public function test_connection() {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            $account = $this->client->api->accounts($this->account_sid)->fetch();
            
            return array(
                'success' => true,
                'account_name' => $account->friendlyName,
                'account_status' => $account->status,
                'phone_number' => $this->phone_number
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
}
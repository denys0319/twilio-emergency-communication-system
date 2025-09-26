<?php
/**
 * AJAX handlers class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Ajax {
    
    public function __construct() {
        // Constructor
    }
    
    public function init() {
        // AJAX actions for logged in users
        add_action('wp_ajax_ecs_save_contact', array($this, 'save_contact'));
        add_action('wp_ajax_ecs_save_group', array($this, 'save_group'));
        add_action('wp_ajax_ecs_get_contact', array($this, 'get_contact'));
        add_action('wp_ajax_ecs_get_group', array($this, 'get_group'));
        add_action('wp_ajax_ecs_upload_contacts', array($this, 'upload_contacts'));
        add_action('wp_ajax_ecs_delete_contact', array($this, 'delete_contact'));
        add_action('wp_ajax_ecs_delete_group', array($this, 'delete_group'));
        add_action('wp_ajax_ecs_send_message', array($this, 'send_message'));
        add_action('wp_ajax_ecs_schedule_message', array($this, 'schedule_message'));
        add_action('wp_ajax_ecs_get_message_status', array($this, 'get_message_status'));
        add_action('wp_ajax_ecs_get_contacts', array($this, 'get_contacts'));
        add_action('wp_ajax_ecs_get_groups', array($this, 'get_groups'));
        add_action('wp_ajax_ecs_cancel_scheduled', array($this, 'delete_scheduled_message'));
        add_action('wp_ajax_ecs_bulk_action_contacts', array($this, 'bulk_action_contacts'));
    }
    
    public function save_contact() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $phone = sanitize_text_field($_POST['ecs_contact_phone']);
        $name = sanitize_text_field($_POST['ecs_contact_name']);
        $group_id = sanitize_text_field($_POST['ecs_contact_group']);
        
        // Debug: Log the contact data
        error_log('ECS AJAX: Saving contact - Phone: ' . $phone . ', Name: ' . $name . ', Group ID: ' . $group_id);
        
        $ecs_service = new ECS_Service();
        
        // Check if Twilio client is initialized (not required for database operations)
        // if (!$ecs_service->get_client()) {
        //     wp_send_json_error(array('message' => 'Twilio client not initialized. Please check your Twilio settings.'));
        // }
        
        if (isset($_POST['ecs_contact_id']) && $_POST['ecs_contact_id']) {
            // Update existing contact
            $contact_id = intval($_POST['ecs_contact_id']);
            $result = $ecs_service->update_contact($contact_id, $phone, $name, $group_id);
            $message = $result['success'] ? 'Contact updated successfully!' : 'Failed to update contact: ' . $result['error'];
        } else {
            // Create new contact
            $result = $ecs_service->create_contact($phone, $name, $group_id);
            $message = $result['success'] ? 'Contact created successfully!' : 'Failed to create contact: ' . $result['error'];
        }
        
        // Debug: Log the result
        error_log('ECS AJAX: Contact save result: ' . print_r($result, true));
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => $message));
        }
    }
    
    public function save_group() {
        check_ajax_referer('ecs_group', 'ecs_group_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $name = sanitize_text_field($_POST['ecs_group_name']);
        
        // Debug: Log the group name
        error_log('ECS AJAX: Creating group with name: ' . $name);
        
        $ecs_service = new ECS_Service();
        
        // Check if Twilio client is initialized (not required for database operations)
        // if (!$ecs_service->get_client()) {
        //     wp_send_json_error(array('message' => 'Twilio client not initialized. Please check your Twilio settings.'));
        // }
        
        if (isset($_POST['ecs_group_id']) && $_POST['ecs_group_id']) {
            // Update existing group (delete old, create new)
            $result = $ecs_service->create_contact_group($name);
            $message = $result['success'] ? 'Contact group updated successfully!' : 'Failed to update contact group: ' . $result['error'];
        } else {
            // Create new group
            $result = $ecs_service->create_contact_group($name);
            $message = $result['success'] ? 'Contact group created successfully!' : 'Failed to create contact group: ' . $result['error'];
        }
        
        // Debug: Log the result
        error_log('ECS AJAX: Group creation result: ' . print_r($result, true));
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => $message));
        }
    }
    
    public function get_contact() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id']);
        
        // Get contact from WordPress database
        global $wpdb;
        $table_contacts = $wpdb->prefix . 'ecs_contacts';
        
        $contact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_contacts WHERE id = %d",
            $contact_id
        ), ARRAY_A);
        
        if ($contact) {
            wp_send_json_success($contact);
        } else {
            wp_send_json_error(array('message' => 'Contact not found'));
        }
    }
    
    public function get_group() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = sanitize_text_field($_POST['group_id']);
        $ecs_service = new ECS_Service();
        
        // Get group from WordPress database
        global $wpdb;
        $table_groups = $wpdb->prefix . 'ecs_contact_groups';
        
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_groups WHERE id = %d",
            $group_id
        ), ARRAY_A);
        
        if ($group) {
            wp_send_json_success($group);
        } else {
            wp_send_json_error(array('message' => 'Group not found'));
        }
    }
    
    public function upload_contacts() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['ecs_contact_file']) || $_FILES['ecs_contact_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'File upload failed. Please try again.'));
        }
        
        $file = $_FILES['ecs_contact_file'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['ext'], array('csv', 'xlsx', 'xls'))) {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload CSV or Excel files only.'));
        }
        
        $ecs_service = new ECS_Service();
        $contacts = $this->parse_uploaded_file($file['tmp_name'], $file_type['ext']);
        
        if ($contacts === false) {
            wp_send_json_error(array('message' => 'Invalid CSV format. Please ensure the file has "Display name" column and either "Phone number" or "Mobile Phone" column.'));
        } elseif (empty($contacts)) {
            wp_send_json_error(array('message' => 'No valid contacts found in the file. Please check that contacts have both name and valid phone numbers.'));
        } else {
            $success_count = 0;
            $duplicate_count = 0;
            $error_count = 0;
            
            foreach ($contacts as $contact) {
                $result = $ecs_service->create_contact($contact['phone'], $contact['name'], $contact['group_id']);
                if ($result['success']) {
                    $success_count++;
                } elseif (strpos($result['error'], 'Duplicate entry') !== false) {
                    $duplicate_count++;
                } else {
                    $error_count++;
                }
            }
            
            $message = "Import completed! ";
            $message .= "Successfully imported: {$success_count} contacts. ";
            if ($duplicate_count > 0) {
                $message .= "Skipped duplicates: {$duplicate_count}. ";
            }
            if ($error_count > 0) {
                $message .= "Errors: {$error_count}. ";
            }
            
            wp_send_json_success(array('message' => $message));
        }
    }
    
    private function parse_uploaded_file($file_path, $extension) {
        $contacts = array();
        
        if ($extension === 'csv') {
            $contacts = $this->parse_csv($file_path);
        } elseif (in_array($extension, array('xlsx', 'xls'))) {
            $contacts = $this->parse_excel($file_path);
        }
        
        return $contacts;
    }
    
    private function parse_csv($file_path) {
        $contacts = array();
        $handle = fopen($file_path, 'r');
        
        if ($handle !== false) {
            $header = fgetcsv($handle);
            
            // Debug: Log the headers to see what we're working with
            $this->debug_csv_headers($header);
            
            // Find the column indices for Display name and Phone number
            $name_index = $this->find_column_index($header, array('Display name', 'display name', 'name', 'Name', 'display_name', 'Display_Name'));
            $phone_index = $this->find_column_index($header, array('Phone number', 'phone number', 'phone', 'Phone', 'phone_number', 'Phone_Number'));
            $mobile_index = $this->find_column_index($header, array('Mobile Phone', 'mobile phone', 'mobile', 'Mobile', 'mobile_phone', 'Mobile_Phone'));
            
            if ($name_index === false || ($phone_index === false && $mobile_index === false)) {
                fclose($handle);
                return false; // Invalid CSV format
            }
            
            $ecs_service = new ECS_Service();
            
            while (($data = fgetcsv($handle)) !== false) {
                $max_index = max($name_index, $phone_index !== false ? $phone_index : 0, $mobile_index !== false ? $mobile_index : 0);
                
                if (count($data) > $max_index) {
                    $raw_name = isset($data[$name_index]) ? trim($data[$name_index]) : '';
                    
                    // Get phone number from either Phone number or Mobile Phone column
                    $raw_phone = '';
                    if ($phone_index !== false && isset($data[$phone_index])) {
                        $raw_phone = trim($data[$phone_index]);
                    }
                    if (empty($raw_phone) && $mobile_index !== false && isset($data[$mobile_index])) {
                        $raw_phone = trim($data[$mobile_index]);
                    }
                    
                    // Skip empty rows
                    if (empty($raw_name) && empty($raw_phone)) {
                        continue;
                    }
                    
                    // Normalize phone number
                    $normalized_phone = $ecs_service->normalize_phone_number($raw_phone);
                    
                    // Only add contact if we have both name and valid phone
                    if (!empty($raw_name) && $normalized_phone) {
                        $contacts[] = array(
                            'phone' => $normalized_phone,
                            'name' => sanitize_text_field($raw_name),
                            'group_id' => null // No group assignment from CSV
                        );
                    }
                }
            }
            
            fclose($handle);
        }
        
        return $contacts;
    }
    
    private function find_column_index($header, $possible_names) {
        foreach ($header as $index => $column_name) {
            // Clean the column name more thoroughly
            $column_name = trim($column_name);
            $column_name = preg_replace('/[\x00-\x1F\x7F]/', '', $column_name); // Remove control characters
            $column_name = str_replace("\xEF\xBB\xBF", '', $column_name); // Remove BOM
            
            foreach ($possible_names as $possible_name) {
                if (strcasecmp($column_name, $possible_name) === 0) {
                    return $index;
                }
            }
        }
        
        // Fallback: try partial matching for common variations
        foreach ($header as $index => $column_name) {
            $column_name = trim($column_name);
            $column_name = preg_replace('/[\x00-\x1F\x7F]/', '', $column_name);
            $column_name = str_replace("\xEF\xBB\xBF", '', $column_name);
            
            // Check for partial matches
            if (stripos($column_name, 'display') !== false && stripos($column_name, 'name') !== false) {
                return $index;
            }
            if (stripos($column_name, 'phone') !== false && stripos($column_name, 'number') !== false) {
                return $index;
            }
            if (stripos($column_name, 'mobile') !== false && stripos($column_name, 'phone') !== false) {
                return $index;
            }
        }
        
        return false;
    }
    
    private function debug_csv_headers($header) {
        error_log('ECS CSV Debug - Headers found: ' . print_r($header, true));
        
        $name_index = $this->find_column_index($header, array('Display name', 'display name', 'name', 'Name'));
        $phone_index = $this->find_column_index($header, array('Phone number', 'phone number', 'phone', 'Phone'));
        $mobile_index = $this->find_column_index($header, array('Mobile Phone', 'mobile phone', 'mobile', 'Mobile'));
        
        error_log('ECS CSV Debug - Name index: ' . ($name_index !== false ? $name_index : 'NOT FOUND'));
        error_log('ECS CSV Debug - Phone index: ' . ($phone_index !== false ? $phone_index : 'NOT FOUND'));
        error_log('ECS CSV Debug - Mobile index: ' . ($mobile_index !== false ? $mobile_index : 'NOT FOUND'));
    }
    
    private function parse_excel($file_path) {
        // For Excel files, we'll need to use a library like PhpSpreadsheet
        // For now, return empty array - this would need to be implemented
        return array();
    }
    
    private function get_group_id_by_name($group_name) {
        $ecs_service = new ECS_Service();
        $result = $ecs_service->get_contact_groups();
        
        if ($result['success']) {
            foreach ($result['groups'] as $group) {
                if ($group['name'] === $group_name) {
                    return $group['id'];
                }
            }
        }
        
        return null;
    }
    
    public function delete_contact() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $contact_id = sanitize_text_field($_POST['contact_id']);
        $ecs_service = new ECS_Service();
        
        $result = $ecs_service->delete_contact($contact_id);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => 'Contact deleted successfully'));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    public function delete_group() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = sanitize_text_field($_POST['group_id']);
        $ecs_service = new ECS_Service();
        
        $result = $ecs_service->delete_contact_group($group_id);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => 'Contact group deleted successfully'));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    public function send_message() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $recipients = json_decode(stripslashes($_POST['recipients']), true);
        $message = sanitize_textarea_field($_POST['message']);
        $from_number = sanitize_text_field($_POST['from_number']);
        
        if (empty($recipients) || empty($message)) {
            wp_send_json_error(array('message' => 'Recipients and message are required'));
        }
        
        $ecs_service = new ECS_Service();
        
        $results = $ecs_service->send_bulk_message($recipients, $message, $from_number);
        
        // Message history is automatically stored in Twilio
        
        wp_send_json_success(array(
            'message' => 'Message sent successfully',
            'results' => $results
        ));
    }
    
    public function schedule_message() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $recipients = json_decode(stripslashes($_POST['recipients']), true);
        $message = sanitize_textarea_field($_POST['message']);
        $from_number = sanitize_text_field($_POST['from_number']);
        $send_time = sanitize_text_field($_POST['send_time']);
        
        if (empty($recipients) || empty($message) || empty($send_time)) {
            wp_send_json_error(array('message' => 'Recipients, message, and send time are required'));
        }
        
        $ecs_service = new ECS_Service();
        $result = $ecs_service->schedule_message($recipients, $message, $send_time, $from_number);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Message scheduled successfully',
                'message_id' => $result['message_id']
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    public function get_message_status() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $message_sid = sanitize_text_field($_POST['message_sid']);
        
        $ecs_service = new ECS_Service();
        $result = $ecs_service->get_message_status($message_sid);
        
        if ($result['success']) {
            // Status is automatically updated in Twilio
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function get_contacts() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        $ecs_service = new ECS_Service();
        $result = $ecs_service->get_contacts($group_id, $limit, $offset);
        $contacts = $result['success'] ? $result['contacts'] : array();
        
        wp_send_json_success($contacts);
    }
    
    public function get_groups() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $ecs_service = new ECS_Service();
        $result = $ecs_service->get_contact_groups();
        $groups = $result['success'] ? $result['groups'] : array();
        
        wp_send_json_success($groups);
    }
    
    public function delete_scheduled_message() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $message_id = sanitize_text_field($_POST['message_id']);
        $ecs_service = new ECS_Service();
        
        $result = $ecs_service->delete_scheduled_message($message_id);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => 'Scheduled message deleted successfully'));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    public function bulk_action_contacts() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $contact_ids = array_map('intval', $_POST['contact_ids']);
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;
        
        // Debug logging
        error_log('ECS Bulk Action: Action=' . $bulk_action . ', Contact IDs=' . print_r($contact_ids, true) . ', Group ID=' . $group_id);
        
        if (empty($contact_ids)) {
            wp_send_json_error(array('message' => 'No contacts selected.'));
        }
        
        $ecs_service = new ECS_Service();
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($contact_ids as $contact_id) {
            try {
                if ($bulk_action === 'delete') {
                    $result = $ecs_service->delete_contact($contact_id);
                } elseif ($bulk_action === 'move-to-group') {
                    // Get contact details first
                    $contact_result = $ecs_service->get_contact($contact_id);
                    if ($contact_result['success']) {
                        $contact = $contact_result['contact'];
                        $result = $ecs_service->update_contact($contact_id, $contact['phone'], $contact['name'], $group_id);
                    } else {
                        $result = array('success' => false, 'error' => 'Contact not found');
                    }
                } else {
                    $result = array('success' => false, 'error' => 'Invalid bulk action');
                }
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Contact ID {$contact_id}: " . $result['error'];
                }
            } catch (Exception $e) {
                $error_count++;
                $errors[] = "Contact ID {$contact_id}: " . $e->getMessage();
                error_log('ECS Bulk Action Error: ' . $e->getMessage());
            }
        }
        
        if ($bulk_action === 'delete') {
            $message = "Bulk delete completed! Successfully deleted: {$success_count} contacts.";
            if ($error_count > 0) {
                $message .= " Errors: {$error_count}. " . implode('; ', $errors);
            }
        } elseif ($bulk_action === 'move-to-group') {
            $message = "Bulk move completed! Successfully moved: {$success_count} contacts to group.";
            if ($error_count > 0) {
                $message .= " Errors: {$error_count}. " . implode('; ', $errors);
            }
        } else {
            $message = "Bulk action completed! Success: {$success_count}, Errors: {$error_count}.";
            if ($error_count > 0) {
                $message .= " " . implode('; ', $errors);
            }
        }
        
        wp_send_json_success(array('message' => $message));
    }
}

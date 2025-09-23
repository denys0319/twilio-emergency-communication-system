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
    }
    
    public function save_contact() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $phone = sanitize_text_field($_POST['ecs_contact_phone']);
        $name = sanitize_text_field($_POST['ecs_contact_name']);
        $group_id = sanitize_text_field($_POST['ecs_contact_group']);
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Check if Twilio client is initialized
        if (!$ecs_twilio_storage->get_client()) {
            wp_send_json_error(array('message' => 'Twilio client not initialized. Please check your Twilio settings.'));
        }
        
        if (isset($_POST['ecs_contact_id']) && $_POST['ecs_contact_id']) {
            // Update existing contact (delete old, create new)
            $result = $ecs_twilio_storage->create_contact($phone, $name, $group_id);
            $message = $result['success'] ? 'Contact updated successfully!' : 'Failed to update contact.';
        } else {
            // Create new contact
            $result = $ecs_twilio_storage->create_contact($phone, $name, $group_id);
            $message = $result['success'] ? 'Contact created successfully!' : 'Failed to create contact.';
        }
        
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Check if Twilio client is initialized
        if (!$ecs_twilio_storage->get_client()) {
            wp_send_json_error(array('message' => 'Twilio client not initialized. Please check your Twilio settings.'));
        }
        
        if (isset($_POST['ecs_group_id']) && $_POST['ecs_group_id']) {
            // Update existing group (delete old, create new)
            $result = $ecs_twilio_storage->create_contact_group($name);
            $message = $result['success'] ? 'Contact group updated successfully!' : 'Failed to update contact group.';
        } else {
            // Create new group
            $result = $ecs_twilio_storage->create_contact_group($name);
            $message = $result['success'] ? 'Contact group created successfully!' : 'Failed to create contact group.';
        }
        
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
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Get contact from Twilio Messaging Service
        try {
            $client = $ecs_twilio_storage->get_client();
            if ($client) {
                $service = $client->messaging->v1->services($contact_id)->fetch();
                $phone_numbers = $client->messaging->v1->services($contact_id)->phoneNumbers->read();
                
                if (count($phone_numbers) > 0) {
                    $phone_number = $phone_numbers[0];
                    $contact = array(
                        'id' => $phone_number->sid,
                        'phone' => $phone_number->phoneNumber,
                        'name' => str_replace('ECS_Contact_', '', $service->friendlyName),
                        'group_id' => $ecs_twilio_storage->extract_group_id_from_service($service)
                    );
                    wp_send_json_success($contact);
                } else {
                    wp_send_json_error(array('message' => 'Contact not found'));
                }
            } else {
                wp_send_json_error(array('message' => 'Twilio client not initialized'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to get contact: ' . $e->getMessage()));
        }
    }
    
    public function get_group() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = sanitize_text_field($_POST['group_id']);
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Get group from Twilio Messaging Service
        try {
            $client = $ecs_twilio_storage->get_client();
            if ($client) {
                $service = $client->messaging->v1->services($group_id)->fetch();
                $group = array(
                    'id' => $service->sid,
                    'name' => $service->friendlyName,  // Use actual service name without prefix removal
                    'service_sid' => $service->sid,
                    'created_at' => $service->dateCreated->format('Y-m-d H:i:s')
                );
                wp_send_json_success($group);
            } else {
                wp_send_json_error(array('message' => 'Twilio client not initialized'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to get group: ' . $e->getMessage()));
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $contacts = $this->parse_uploaded_file($file['tmp_name'], $file_type['ext']);
        
        if ($contacts) {
            $success_count = 0;
            
            foreach ($contacts as $contact) {
                $result = $ecs_twilio_storage->create_contact($contact['phone'], $contact['name'], $contact['group_id']);
                if ($result['success']) {
                    $success_count++;
                }
            }
            
            $message = $success_count > 0 ? "Successfully imported {$success_count} contacts!" : 'Failed to import contacts.';
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => 'Failed to parse uploaded file.'));
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
            
            // Expected format: phone, name, group (optional)
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 2) {
                    $contacts[] = array(
                        'phone' => sanitize_text_field($data[0]),
                        'name' => sanitize_text_field($data[1]),
                        'group_id' => isset($data[2]) ? $this->get_group_id_by_name($data[2]) : null
                    );
                }
            }
            
            fclose($handle);
        }
        
        return $contacts;
    }
    
    private function parse_excel($file_path) {
        // For Excel files, we'll need to use a library like PhpSpreadsheet
        // For now, return empty array - this would need to be implemented
        return array();
    }
    
    private function get_group_id_by_name($group_name) {
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $result = $ecs_twilio_storage->get_contact_groups();
        
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
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Delete contact by removing the messaging service
        try {
            $client = $ecs_twilio_storage->get_client();
            if ($client) {
                $client->messaging->v1->services($contact_id)->delete();
                wp_send_json_success(array('message' => 'Contact deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Twilio client not initialized'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to delete contact: ' . $e->getMessage()));
        }
    }
    
    public function delete_group() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = sanitize_text_field($_POST['group_id']);
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Delete group by removing the messaging service
        try {
            $client = $ecs_twilio_storage->get_client();
            if ($client) {
                $client->messaging->v1->services($group_id)->delete();
                wp_send_json_success(array('message' => 'Contact group deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Twilio client not initialized'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to delete contact group: ' . $e->getMessage()));
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        $results = $ecs_twilio_storage->send_bulk_message($recipients, $message, $from_number);
        
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $result = $ecs_twilio_storage->schedule_message($recipients, $message, $send_time, $from_number);
        
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $result = $ecs_twilio_storage->get_message_status($message_sid);
        
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
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $result = $ecs_twilio_storage->get_contacts($group_id, $limit, $offset);
        $contacts = $result['success'] ? $result['contacts'] : array();
        
        wp_send_json_success($contacts);
    }
    
    public function get_groups() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        $result = $ecs_twilio_storage->get_contact_groups();
        $groups = $result['success'] ? $result['groups'] : array();
        
        wp_send_json_success($groups);
    }
    
    public function delete_scheduled_message() {
        check_ajax_referer('ecs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $message_id = sanitize_text_field($_POST['message_id']);
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Delete scheduled message by removing the messaging service
        try {
            $client = $ecs_twilio_storage->get_client();
            if ($client) {
                $client->messaging->v1->services($message_id)->delete();
                
                // Also cancel the WordPress cron job if it exists
                wp_clear_scheduled_hook('ecs_send_scheduled_message', array($message_id));
                
                wp_send_json_success(array('message' => 'Scheduled message deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Twilio client not initialized'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to delete scheduled message: ' . $e->getMessage()));
        }
    }
}

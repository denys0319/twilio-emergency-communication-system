<?php
/**
 * Cron job handler for scheduled messages
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Cron {
    
    public function __construct() {
        // Constructor
    }
    
    public function init() {
        // Add cron job handler
        add_action('ecs_send_scheduled_message', array($this, 'send_scheduled_message'), 10, 1);
        
        // Add custom cron schedule if needed
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
        
        // Add cleanup cron jobs
        add_action('ecs_cleanup_old_messages', array($this, 'cleanup_old_messages'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('ecs_cleanup_old_messages')) {
            wp_schedule_event(time(), 'daily', 'ecs_cleanup_old_messages');
        }
    }
    
    /**
     * Send scheduled message
     */
    public function send_scheduled_message($message_id) {
        $ecs_service = new ECS_Service();
        
        try {
            // Get scheduled message from database
            global $wpdb;
            $table_scheduled = $wpdb->prefix . 'ecs_scheduled_messages';
            
            $scheduled_message = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_scheduled WHERE id = %d AND status = 'pending'",
                $message_id
            ), ARRAY_A);
            
            if (!$scheduled_message) {
                error_log('ECS: Scheduled message not found or already processed: ' . $message_id);
                return;
            }
            
            // Check if message should be sent now
            $scheduled_time = strtotime($scheduled_message['send_time']);
            $current_time = current_time('timestamp');
            
            if ($current_time < $scheduled_time) {
                // Reschedule for later
                wp_schedule_single_event($scheduled_time, 'ecs_send_scheduled_message', array($message_id));
                return;
            }
            
            // Decode recipients
            $recipients = json_decode($scheduled_message['recipients'], true);
            
            if (empty($recipients)) {
                error_log('ECS: No recipients found for scheduled message: ' . $message_id);
                return;
            }
            
            // Send the message
            $results = $ecs_service->send_bulk_message($recipients, $scheduled_message['message'], $scheduled_message['from_number']);
            
            // Update status to sent
            $wpdb->update($table_scheduled, array('status' => 'sent'), array('id' => $message_id));
            
            error_log('ECS: Scheduled message sent successfully: ' . $message_id);
            
        } catch (Exception $e) {
            error_log('ECS: Failed to send scheduled message: ' . $e->getMessage());
        }
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['ecs_minute'] = array(
            'interval' => 60,
            'display' => __('Every Minute (ECS)', 'emergency-communication-system')
        );
        
        $schedules['ecs_five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 Minutes (ECS)', 'emergency-communication-system')
        );
        
        return $schedules;
    }
    
    /**
     * Clean up old messages from database
     */
    public function cleanup_old_messages() {
        global $wpdb;
        
        try {
            // Clean up old sent scheduled messages (older than 30 days)
            $table_scheduled = $wpdb->prefix . 'ecs_scheduled_messages';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_scheduled WHERE status = 'sent' AND created_at < %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            ));
            
            // Clean up old message history (older than 90 days)
            $table_messages = $wpdb->prefix . 'ecs_messages';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_messages WHERE created_at < %s",
                date('Y-m-d H:i:s', strtotime('-90 days'))
            ));
            
            error_log('ECS: Old messages cleaned up successfully');
            
        } catch (Exception $e) {
            error_log('ECS: Failed to cleanup old messages: ' . $e->getMessage());
        }
    }
}
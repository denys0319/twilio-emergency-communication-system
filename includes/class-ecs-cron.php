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
        error_log("ECS Cron: send_scheduled_message called with message_id: $message_id");
        
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
                error_log('ECS Cron: Scheduled message not found or already processed: ' . $message_id);
                return;
            }
            
            // Check if message should be sent now
            $scheduled_time = strtotime($scheduled_message['send_time']);
            $current_time = current_time('timestamp');
            
            error_log("ECS Cron: Scheduled time: " . date('Y-m-d H:i:s', $scheduled_time) . " (timestamp: $scheduled_time)");
            error_log("ECS Cron: Current time: " . date('Y-m-d H:i:s', $current_time) . " (timestamp: $current_time)");
            
            if ($current_time < $scheduled_time) {
                // Too early, reschedule for later
                error_log("ECS Cron: Too early to send. Rescheduling for later.");
                wp_schedule_single_event($scheduled_time, 'ecs_send_scheduled_message', array($message_id));
                return;
            }
            
            error_log("ECS Cron: Sending message now...");
            
            // Decode recipients
            $recipients = json_decode($scheduled_message['recipients'], true);
            
            if (empty($recipients)) {
                error_log('ECS Cron: No recipients found for scheduled message: ' . $message_id);
                return;
            }
            
            error_log("ECS Cron: Found " . count($recipients) . " recipients");
            
            // Send the message
            $results = $ecs_service->send_bulk_message($recipients, $scheduled_message['message'], $scheduled_message['from_number']);
            
            // Update status to sent with current timestamp
            $update_result = $wpdb->update(
                $table_scheduled,
                array(
                    'status' => 'sent',
                    'sent_at' => current_time('mysql')
                ),
                array('id' => $message_id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($update_result === false) {
                error_log('ECS Cron: Failed to update status for message: ' . $message_id . ' Error: ' . $wpdb->last_error);
            } else {
                error_log('ECS Cron: Scheduled message sent successfully: ' . $message_id . ' (status updated)');
            }
            
        } catch (Exception $e) {
            error_log('ECS Cron: Failed to send scheduled message: ' . $e->getMessage());
            
            // Update status to failed
            $wpdb->update(
                $table_scheduled,
                array(
                    'status' => 'failed'
                ),
                array('id' => $message_id),
                array('%s'),
                array('%d')
            );
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
            // Clean up old scheduled messages (sent, cancelled, failed - older than 30 days)
            $table_scheduled = $wpdb->prefix . 'ecs_scheduled_messages';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_scheduled WHERE status IN ('sent', 'cancelled', 'failed') AND created_at < %s",
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
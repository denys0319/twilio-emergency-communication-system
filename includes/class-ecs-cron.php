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
    }
    
    /**
     * Send scheduled message
     */
    public function send_scheduled_message($service_sid) {
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        try {
            // Get messaging service details from Twilio
            $service = $ecs_twilio_storage->client->messaging->v1->services($service_sid)->fetch();
            
            // Extract message details from service metadata
            $webhook_url = $service->inboundRequestUrl;
            parse_str(parse_url($webhook_url, PHP_URL_QUERY), $params);
            
            if (!isset($params['scheduled']) || !$params['scheduled']) {
                error_log('ECS: Service is not a scheduled message: ' . $service_sid);
                return;
            }
            
            $message = $params['message'] ?? '';
            $send_time = $params['send_time'] ?? '';
            
            // Check if message should be sent now
            $scheduled_time = strtotime($send_time);
            $current_time = current_time('timestamp');
            
            if ($current_time < $scheduled_time) {
                // Reschedule for later
                wp_schedule_single_event($scheduled_time, 'ecs_send_scheduled_message', array($service_sid));
                return;
            }
            
            // Get phone numbers from the messaging service
            $phone_numbers = $ecs_twilio_storage->client->messaging->v1->services($service_sid)
                ->phoneNumbers->read();
            
            $recipients = array();
            foreach ($phone_numbers as $phone_number) {
                $recipients[] = array(
                    'phone' => $phone_number->phoneNumber,
                    'name' => $service->friendlyName
                );
            }
            
            // Send the message
            $results = $ecs_twilio_storage->send_bulk_message($recipients, $message);
            
            // Message history is automatically stored in Twilio
            error_log('ECS: Scheduled message sent: ' . $service_sid);
            
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
     * Clean up old scheduled messages (Twilio-based)
     */
    public function cleanup_old_scheduled_messages() {
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        try {
            // Get all messaging services
            $services = $ecs_twilio_storage->client->messaging->v1->services->read();
            
            foreach ($services as $service) {
                if (strpos($service->friendlyName, 'ECS_Scheduled_') === 0) {
                    // Check if this is an old scheduled message (older than 30 days)
                    $service_date = str_replace('ECS_Scheduled_', '', $service->friendlyName);
                    $service_timestamp = strtotime(str_replace('_', ' ', $service_date));
                    
                    if ($service_timestamp && (time() - $service_timestamp) > (30 * 24 * 60 * 60)) {
                        // Delete old scheduled messaging service
                        $ecs_twilio_storage->client->messaging->v1->services($service->sid)->delete();
                    }
                }
            }
        } catch (Exception $e) {
            error_log('ECS: Failed to cleanup old scheduled messages: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up old message history (Twilio-based)
     * Note: Twilio automatically manages message history retention
     */
    public function cleanup_old_message_history() {
        // Twilio automatically manages message history retention
        // No cleanup needed as Twilio handles this
        error_log('ECS: Message history cleanup not needed - Twilio manages retention');
    }
}

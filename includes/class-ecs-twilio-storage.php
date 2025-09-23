<?php
/**
 * Twilio-only data storage class
 * Stores all data in Twilio services instead of WordPress database
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Twilio_Storage {
    
    private $client;
    private $account_sid;
    private $auth_token;
    private $phone_number;
    
    public function __construct() {
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
     * Store contacts using Twilio Messaging Services
     * We'll use Twilio's messaging services to manage phone number lists
     */
    public function create_contact($phone, $name = '', $group = '') {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            // Create a messaging service for this contact if it doesn't exist
            $service_name = 'ECS_Contact_' . sanitize_title($name ?: $phone);
            $service = $this->get_or_create_messaging_service($service_name);
            
            // Add phone number to the messaging service
            $phone_number = $this->client->messaging->v1->services($service->sid)
                ->phoneNumbers->create($phone);
            
            // Store contact metadata in Twilio's messaging service webhook URL or use Twilio's data storage
            // For now, we'll use the messaging service's friendly name to store contact info
            $this->client->messaging->v1->services($service->sid)->update(array(
                'friendlyName' => $name ?: $phone,
                'inboundRequestUrl' => $this->get_webhook_url() . '?contact_name=' . urlencode($name) . '&group=' . urlencode($group)
            ));
            
            return array(
                'success' => true,
                'contact_id' => $phone_number->sid,
                'service_sid' => $service->sid,
                'phone' => $phone,
                'name' => $name,
                'group' => $group
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get contacts from Twilio Messaging Services
     */
    public function get_contacts($group = null, $limit = 50, $offset = 0) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            $contacts = array();
            $services = $this->client->messaging->v1->services->read();
            
            foreach ($services as $service) {
                if (strpos($service->friendlyName, 'ECS_Contact_') === 0) {
                    // Get phone numbers for this service
                    $phone_numbers = $this->client->messaging->v1->services($service->sid)
                        ->phoneNumbers->read();
                    
                    foreach ($phone_numbers as $phone_number) {
                        $contacts[] = array(
                            'id' => $phone_number->sid,
                            'phone' => $phone_number->phoneNumber,
                            'name' => $service->friendlyName,
                            'service_sid' => $service->sid,
                            'group' => $this->extract_group_from_service($service),
                            'created_at' => $service->dateCreated->format('Y-m-d H:i:s')
                        );
                    }
                }
            }
            
            // Apply pagination manually since Twilio doesn't support offset/limit
            if ($offset > 0 || $limit < count($contacts)) {
                $contacts = array_slice($contacts, $offset, $limit);
            }
            
            return array('success' => true, 'contacts' => $contacts);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Create contact group using Twilio Messaging Services
     */
    public function create_contact_group($name, $description = '') {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            $service = $this->client->messaging->v1->services->create($name);
            
            return array(
                'success' => true,
                'group_id' => $service->sid,
                'name' => $name
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get contact groups from Twilio
     */
    public function get_contact_groups() {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            $groups = array();
            $services = $this->client->messaging->v1->services->read();
            
            foreach ($services as $service) {
                // Skip services that are contacts or scheduled messages
                if (strpos($service->friendlyName, 'ECS_Contact_') === 0 || 
                    strpos($service->friendlyName, 'ECS_Scheduled_') === 0) {
                    continue;
                }
                
                $groups[] = array(
                    'id' => $service->sid,
                    'name' => $service->friendlyName,  // Use actual service name
                    'service_sid' => $service->sid,
                    'created_at' => $service->dateCreated->format('Y-m-d H:i:s')
                );
            }
            
            return array('success' => true, 'groups' => $groups);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send message using Twilio
     */
    public function send_message($to, $message, $from = null) {
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
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send bulk messages using Twilio Messaging Services
     */
    public function send_bulk_message($recipients, $message, $from = null) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        $from = $from ?: $this->phone_number;
        $results = array();
        
        foreach ($recipients as $recipient) {
            $result = $this->send_message($recipient['phone'], $message, $from);
            $result['recipient_name'] = $recipient['name'] ?? '';
            $result['recipient_id'] = $recipient['id'] ?? '';
            $results[] = $result;
        }
        
        return array('success' => true, 'results' => $results);
    }
    
    /**
     * Get message status from Twilio
     */
    public function get_message_status($message_sid) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            $message = $this->client->messages($message_sid)->fetch();
            
            return array(
                'success' => true,
                'sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'body' => $message->body,
                'date_created' => $message->dateCreated->format('Y-m-d H:i:s'),
                'date_sent' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                'date_updated' => $message->dateUpdated->format('Y-m-d H:i:s'),
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'price' => $message->price,
                'price_unit' => $message->priceUnit
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get message history from Twilio
     */
    public function get_message_history($limit = 50, $offset = 0, $status = null) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            // Twilio messages API supports status filter but not pagination in the same way
            $messages = $this->client->messages->read();
            $result = array();
            
            foreach ($messages as $message) {
                // Apply status filter if specified
                if ($status && $message->status !== $status) {
                    continue;
                }
                
                $result[] = array(
                    'id' => $message->sid,
                    'twilio_sid' => $message->sid,
                    'recipient_phone' => $message->to,
                    'recipient_name' => $this->get_contact_name_by_phone($message->to),
                    'message' => $message->body,
                    'from_number' => $message->from,
                    'status' => $message->status,
                    'sent_at' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                    'delivered_at' => $message->status === 'delivered' ? $message->dateUpdated->format('Y-m-d H:i:s') : null,
                    'error_code' => $message->errorCode,
                    'error_message' => $message->errorMessage,
                    'created_at' => $message->dateCreated->format('Y-m-d H:i:s')
                );
            }
            
            // Apply pagination manually
            if ($offset > 0 || $limit < count($result)) {
                $result = array_slice($result, $offset, $limit);
            }
            
            return array('success' => true, 'messages' => $result);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Schedule message using Twilio (we'll use WordPress cron but store schedule in Twilio)
     */
    public function schedule_message($recipients, $message, $send_time, $from = null) {
        if (!$this->client) {
            return array('success' => false, 'error' => 'Twilio client not initialized');
        }
        
        try {
            // Create a messaging service for scheduled messages
            $service_name = 'ECS_Scheduled_' . date('Y-m-d_H-i-s', strtotime($send_time));
            $webhook_url = $this->get_webhook_url() . '?scheduled=true&send_time=' . urlencode($send_time) . '&message=' . urlencode($message);
            
            $service = $this->client->messaging->v1->services->create($service_name);
            
            // Update the service with webhook URL
            $this->client->messaging->v1->services($service->sid)->update(array(
                'inboundRequestUrl' => $webhook_url
            ));
            
            // Add all recipient phone numbers to the service
            foreach ($recipients as $recipient) {
                try {
                    $this->client->messaging->v1->services($service->sid)
                        ->phoneNumbers->create($recipient['phone']);
                } catch (Exception $e) {
                    // Phone number might already exist, continue
                }
            }
            
            // Schedule WordPress cron job
            wp_schedule_single_event(strtotime($send_time), 'ecs_send_scheduled_message', array($service->sid));
            
            return array(
                'success' => true,
                'message_id' => $service->sid,
                'scheduled_time' => $send_time
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Helper methods
     */
    private function get_or_create_messaging_service($name) {
        try {
            // Get all services and find by name
            $services = $this->client->messaging->v1->services->read();
            
            foreach ($services as $service) {
                if ($service->friendlyName === $name) {
                    return $service;
                }
            }
            
            // Create new service if not found
            return $this->client->messaging->v1->services->create($name);
        } catch (Exception $e) {
            throw new Exception('Failed to create messaging service: ' . $e->getMessage());
        }
    }
    
    private function get_webhook_url() {
        return home_url('/wp-json/ecs/v1/webhook');
    }
    
    private function extract_group_name_from_service($service) {
        return str_replace('ECS_Group_', '', $service->friendlyName);
    }
    
    private function get_contact_name_by_phone($phone) {
        // Try to find contact name from messaging services
        try {
            $services = $this->client->messaging->v1->services->read();
            foreach ($services as $service) {
                if (strpos($service->friendlyName, 'ECS_Contact_') === 0) {
                    $phone_numbers = $this->client->messaging->v1->services($service->sid)
                        ->phoneNumbers->read();
                    foreach ($phone_numbers as $phone_number) {
                        if ($phone_number->phoneNumber === $phone) {
                            return str_replace('ECS_Contact_', '', $service->friendlyName);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore errors
        }
        return 'Unknown';
    }
    
    /**
     * Get Twilio client (public accessor)
     */
    public function get_client() {
        return $this->client;
    }
    
    /**
     * Extract group ID from service metadata
     */
    public function extract_group_id_from_service($service) {
        // Extract group ID from service metadata or webhook URL
        if (isset($service->inboundRequestUrl) && $service->inboundRequestUrl) {
            parse_str(parse_url($service->inboundRequestUrl, PHP_URL_QUERY), $params);
            return isset($params['group_id']) ? $params['group_id'] : null;
        }
        return null;
    }
    
    /**
     * Extract group ID from service metadata
     */
    public function extract_group_from_service($service) {
        // Extract group information from service metadata or webhook URL
        if (isset($service->inboundRequestUrl) && $service->inboundRequestUrl) {
            parse_str(parse_url($service->inboundRequestUrl, PHP_URL_QUERY), $params);
            return isset($params['group']) ? $params['group'] : '';
        }
        return '';
    }
    
    /**
     * Extract description from service metadata
     */
    public function extract_description_from_service($service) {
        // Extract description from service metadata or webhook URL
        if (isset($service->inboundRequestUrl) && $service->inboundRequestUrl) {
            parse_str(parse_url($service->inboundRequestUrl, PHP_URL_QUERY), $params);
            return isset($params['description']) ? $params['description'] : '';
        }
        return '';
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

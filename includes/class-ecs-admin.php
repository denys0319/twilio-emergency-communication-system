<?php
/**
 * Admin functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Admin {
    
    public function __construct() {
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    public function init() {
        // Admin initialization - all functionality is handled in constructor
        // Form submissions are now handled via AJAX in class-ecs-ajax.php
    }
    
    public function handle_admin_actions() {
        // Handle settings form submission
        if (isset($_POST['ecs_settings_submit']) && wp_verify_nonce($_POST['ecs_settings_nonce'], 'ecs_settings')) {
            $this->save_settings();
        }
        
        // Form submissions are now handled via AJAX in class-ecs-ajax.php
    }
    
    private function save_settings() {
        $account_sid = sanitize_text_field($_POST['ecs_twilio_account_sid']);
        $auth_token = sanitize_text_field($_POST['ecs_twilio_auth_token']);
        $phone_number = sanitize_text_field($_POST['ecs_twilio_phone_number']);
        
        update_option('ecs_twilio_account_sid', $account_sid);
        update_option('ecs_twilio_auth_token', $auth_token);
        update_option('ecs_twilio_phone_number', $phone_number);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        });
    }
    
    public function show_admin_notices() {
        // Admin notices are handled in individual methods
    }
}
<?php
/**
 * Uninstall script for Emergency Communication System
 * This file is automatically called when the plugin is deleted
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('ecs_twilio_account_sid');
delete_option('ecs_twilio_auth_token');
delete_option('ecs_twilio_phone_number');

// No database tables to remove - all data stored in Twilio
// Note: Twilio data will remain in Twilio services unless manually cleaned up

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('ecs_send_scheduled_message');

// Remove any transients
delete_transient('ecs_twilio_connection_test');
delete_transient('ecs_message_status_cache');

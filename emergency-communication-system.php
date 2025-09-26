<?php
/**
 * Plugin Name: Emergency Communication System
 * Description: A comprehensive emergency communication system using Twilio for sending alerts, managing contacts, and coordinating emergency communications.
 * Version: 1.0.0
 * Author: Steven Cameron, Denys Motuliak
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ECS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ECS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECS_PLUGIN_VERSION', '1.0.0');

// Load Composer autoloader
if (file_exists(ECS_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once ECS_PLUGIN_PATH . 'vendor/autoload.php';
}

// Include required files
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-database.php';
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-core.php';
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-service.php';
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-admin.php';
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-ajax.php';
require_once ECS_PLUGIN_PATH . 'includes/class-ecs-cron.php';

// Initialize the plugin
function ecs_init() {
    $ecs_core = new ECS_Core();
    $ecs_core->init();
    
    // Initialize cron handler
    $ecs_cron = new ECS_Cron();
    $ecs_cron->init();
}
add_action('plugins_loaded', 'ecs_init');

// Activation hook
register_activation_hook(__FILE__, 'ecs_activate');
function ecs_activate() {
    // Create database tables
    $ecs_database = new ECS_Database();
    $ecs_database->create_tables();
    
    // Set default options
    add_option('ecs_twilio_account_sid', '');
    add_option('ecs_twilio_auth_token', '');
    add_option('ecs_twilio_phone_number', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ecs_deactivate');
function ecs_deactivate() {
    // Clean up if needed
}

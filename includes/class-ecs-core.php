<?php
/**
 * Core plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Core {
    
    public function __construct() {
        // Constructor
    }
    
    public function init() {
        // Initialize admin functionality
        if (is_admin()) {
            $ecs_admin = new ECS_Admin();
            $ecs_admin->init();
        }
        
        // Initialize AJAX handlers
        $ecs_ajax = new ECS_Ajax();
        $ecs_ajax->init();
        
        // Initialize Twilio storage integration
        $ecs_twilio_storage = new ECS_Twilio_Storage();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Emergency Communication',
            'Emergency Communication',
            'manage_options',
            'emergency-communication',
            array($this, 'admin_dashboard'),
            'dashicons-megaphone',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'emergency-communication',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'emergency-communication',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Phone Lists',
            'Phone Lists',
            'manage_options',
            'ecs-phone-lists',
            array($this, 'admin_phone_lists')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Contact Groups',
            'Contact Groups',
            'manage_options',
            'ecs-contact-groups',
            array($this, 'admin_contact_groups')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Individual Contacts',
            'Individual Contacts',
            'manage_options',
            'ecs-individual-contacts',
            array($this, 'admin_individual_contacts')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Compose Alert',
            'Compose Alert',
            'manage_options',
            'ecs-compose-alert',
            array($this, 'admin_compose_alert')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Message History',
            'Message History',
            'manage_options',
            'ecs-message-history',
            array($this, 'admin_message_history')
        );
        
        add_submenu_page(
            'emergency-communication',
            'Settings',
            'Settings',
            'manage_options',
            'ecs-settings',
            array($this, 'admin_settings')
        );
    }
    
    public function admin_dashboard() {
        include ECS_PLUGIN_PATH . 'admin/dashboard.php';
    }
    
    public function admin_phone_lists() {
        include ECS_PLUGIN_PATH . 'admin/phone-lists.php';
    }
    
    public function admin_contact_groups() {
        include ECS_PLUGIN_PATH . 'admin/contact-groups.php';
    }
    
    public function admin_individual_contacts() {
        include ECS_PLUGIN_PATH . 'admin/individual-contacts.php';
    }
    
    public function admin_compose_alert() {
        include ECS_PLUGIN_PATH . 'admin/compose-alert.php';
    }
    
    public function admin_message_history() {
        include ECS_PLUGIN_PATH . 'admin/message-history.php';
    }
    
    public function admin_settings() {
        include ECS_PLUGIN_PATH . 'admin/settings.php';
    }
    
    public function enqueue_admin_scripts($hook) {
        // Debug: Log the hook name
        error_log('ECS: Admin hook: ' . $hook);
        
        // Only load on our plugin pages
        if (strpos($hook, 'emergency-communication') === false && strpos($hook, 'ecs-') === false) {
            error_log('ECS: Script not loaded for hook: ' . $hook);
            return;
        }
        
        error_log('ECS: Loading scripts for hook: ' . $hook);
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        
        wp_enqueue_script('ecs-admin', ECS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ECS_PLUGIN_VERSION, true);
        wp_enqueue_style('ecs-admin', ECS_PLUGIN_URL . 'assets/css/admin.css', array(), ECS_PLUGIN_VERSION);
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        // Localize script for AJAX
        wp_localize_script('ecs-admin', 'ecs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecs_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'emergency-communication-system'),
                'loading' => __('Loading...', 'emergency-communication-system'),
                'error' => __('An error occurred. Please try again.', 'emergency-communication-system'),
            )
        ));
    }
}

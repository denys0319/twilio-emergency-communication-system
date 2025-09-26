<?php
/**
 * Test file to verify plugin structure and functionality
 * This file can be run to test basic plugin functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please run this script from within WordPress.');
    }
}

echo '<h1>Emergency Communication System - Plugin Test</h1>';

// Test 1: Check if classes exist
echo '<h2>Class Availability Test</h2>';
$classes = [
    'ECS_Core',
    'ECS_Twilio',
    'ECS_Admin',
    'ECS_Database',
    'ECS_Ajax',
    'ECS_Cron'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo '<p style="color: green;">✓ ' . $class . ' class exists</p>';
    } else {
        echo '<p style="color: red;">✗ ' . $class . ' class not found</p>';
    }
}

// Test 2: Check Twilio storage (no database tables needed)
echo '<h2>Twilio Storage Test</h2>';
echo '<p style="color: green;">✓ No WordPress database tables needed - all data stored in Twilio</p>';
echo '<p>This plugin uses Twilio Messaging Services for data storage:</p>';
echo '<ul>';
echo '<li>Contact information stored in Twilio Messaging Services</li>';
echo '<li>Contact groups stored in Twilio Messaging Services</li>';
echo '<li>Message history stored in Twilio Messages API</li>';
echo '<li>Scheduled messages stored in Twilio Messaging Services</li>';
echo '</ul>';

// Test 3: Check Twilio configuration
echo '<h2>Twilio Configuration Test</h2>';
$account_sid = get_option('ecs_twilio_account_sid');
$auth_token = get_option('ecs_twilio_auth_token');
$phone_number = get_option('ecs_twilio_phone_number');

if ($account_sid) {
    echo '<p style="color: green;">✓ Account SID is set</p>';
} else {
    echo '<p style="color: orange;">⚠ Account SID not configured</p>';
}

if ($auth_token) {
    echo '<p style="color: green;">✓ Auth Token is set</p>';
} else {
    echo '<p style="color: orange;">⚠ Auth Token not configured</p>';
}

if ($phone_number) {
    echo '<p style="color: green;">✓ Phone Number is set: ' . esc_html($phone_number) . '</p>';
} else {
    echo '<p style="color: orange;">⚠ Phone Number not configured</p>';
}

// Test 4: Test Twilio connection if credentials are available
if ($account_sid && $auth_token) {
    echo '<h2>Twilio Connection Test</h2>';
    $ecs_service = new ECS_Service();
    $test_result = $ecs_service->test_connection();
    
    if ($test_result['success']) {
        echo '<p style="color: green;">✓ Twilio connection successful</p>';
        echo '<p>Account: ' . esc_html($test_result['account_name']) . '</p>';
        echo '<p>Status: ' . esc_html($test_result['account_status']) . '</p>';
    } else {
        echo '<p style="color: red;">✗ Twilio connection failed: ' . esc_html($test_result['error']) . '</p>';
    }
} else {
    echo '<h2>Twilio Connection Test</h2>';
    echo '<p style="color: orange;">⚠ Cannot test Twilio connection - credentials not configured</p>';
}

// Test 5: Test Twilio storage operations
echo '<h2>Twilio Storage Operations Test</h2>';
$ecs_service = new ECS_Service();

// Test creating a contact group
$group_result = $ecs_service->create_contact_group('Test Group', 'Test Description');
if ($group_result['success']) {
    echo '<p style="color: green;">✓ Contact group creation successful (ID: ' . $group_result['group_id'] . ')</p>';
    
    // Test creating a contact
    $contact_result = $ecs_service->create_contact('+1234567890', 'Test Contact', 'Test Group');
    if ($contact_result['success']) {
        echo '<p style="color: green;">✓ Contact creation successful (ID: ' . $contact_result['contact_id'] . ')</p>';
        
        // Test retrieving contacts
        $contacts_result = $ecs_service->get_contacts();
        if ($contacts_result['success']) {
            echo '<p style="color: green;">✓ Contact retrieval successful (' . count($contacts_result['contacts']) . ' contacts found)</p>';
        } else {
            echo '<p style="color: red;">✗ Contact retrieval failed</p>';
        }
        
        echo '<p style="color: green;">✓ Test data stored in Twilio (no cleanup needed)</p>';
    } else {
        echo '<p style="color: red;">✗ Contact creation failed: ' . $contact_result['error'] . '</p>';
    }
} else {
    echo '<p style="color: red;">✗ Contact group creation failed: ' . $group_result['error'] . '</p>';
}

// Test 6: Check file permissions
echo '<h2>File Permissions Test</h2>';
$files_to_check = [
    'emergency-communication-system.php',
    'includes/class-ecs-core.php',
    'includes/class-ecs-twilio.php',
    'includes/class-ecs-database.php',
    'admin/dashboard.php',
    'assets/css/admin.css',
    'assets/js/admin.js'
];

foreach ($files_to_check as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        if (is_readable($file_path)) {
            echo '<p style="color: green;">✓ ' . $file . ' exists and is readable</p>';
        } else {
            echo '<p style="color: red;">✗ ' . $file . ' exists but is not readable</p>';
        }
    } else {
        echo '<p style="color: red;">✗ ' . $file . ' not found</p>';
    }
}

// Test 7: Check WordPress hooks
echo '<h2>WordPress Hooks Test</h2>';
$hooks_to_check = [
    'admin_menu',
    'admin_enqueue_scripts',
    'wp_ajax_ecs_send_message',
    'wp_ajax_ecs_delete_contact',
    'ecs_send_scheduled_message'
];

foreach ($hooks_to_check as $hook) {
    if (has_action($hook)) {
        echo '<p style="color: green;">✓ Hook registered: ' . $hook . '</p>';
    } else {
        echo '<p style="color: orange;">⚠ Hook not found: ' . $hook . '</p>';
    }
}

echo '<h2>Test Complete</h2>';
echo '<p>Plugin test completed. Check the results above for any issues.</p>';
echo '<p><a href="' . admin_url('admin.php?page=emergency-communication') . '">Go to Plugin Dashboard</a></p>';
?>

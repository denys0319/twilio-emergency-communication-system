<?php
/**
 * Installation script for Emergency Communication System
 * Run this file once after uploading the plugin to install dependencies
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress context, try to find WordPress
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
        die('WordPress not found. Please run this script from within WordPress or ensure wp-load.php is accessible.');
    }
}

// Check if user has admin capabilities
if (!current_user_can('manage_options')) {
    die('You do not have permission to run this installation script.');
}

echo '<h1>Emergency Communication System - Installation</h1>';

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('<p style="color: red;">Error: PHP 7.4 or higher is required. Current version: ' . PHP_VERSION . '</p>');
}

echo '<p>✓ PHP version check passed: ' . PHP_VERSION . '</p>';

// Check if Composer is available
$composer_path = __DIR__ . '/composer.phar';
$composer_available = file_exists($composer_path) || (function_exists('shell_exec') && shell_exec('which composer'));

if (!$composer_available) {
    echo '<p style="color: orange;">Warning: Composer not found. Please install Composer to manage dependencies.</p>';
    echo '<p>You can download Composer from: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></p>';
} else {
    echo '<p>✓ Composer is available</p>';
}

// Check if vendor directory exists
$vendor_path = __DIR__ . '/vendor';
if (!is_dir($vendor_path)) {
    echo '<p style="color: orange;">Warning: Vendor directory not found. Dependencies need to be installed.</p>';
    if ($composer_available) {
        echo '<p>Running: composer install</p>';
        $output = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && composer install 2>&1');
        echo '<pre>' . htmlspecialchars($output) . '</pre>';
        
        if (is_dir($vendor_path)) {
            echo '<p style="color: green;">✓ Dependencies installed successfully</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to install dependencies</p>';
        }
    }
} else {
    echo '<p>✓ Dependencies are installed</p>';
}

// Check Twilio SDK
if (file_exists($vendor_path . '/autoload.php')) {
    require_once $vendor_path . '/autoload.php';
    
    if (class_exists('Twilio\Rest\Client')) {
        echo '<p>✓ Twilio SDK is available</p>';
    } else {
        echo '<p style="color: red;">✗ Twilio SDK not found</p>';
    }
} else {
    echo '<p style="color: red;">✗ Autoloader not found</p>';
}

// Check Twilio storage (no database tables needed)
echo '<h2>Twilio Storage Check</h2>';
echo '<p style="color: green;">✓ No WordPress database tables needed - all data stored in Twilio</p>';
echo '<p>This plugin uses Twilio Messaging Services to store:</p>';
echo '<ul>';
echo '<li>Contact information</li>';
echo '<li>Contact groups</li>';
echo '<li>Message history</li>';
echo '<li>Scheduled messages</li>';
echo '</ul>';

// Check plugin activation
if (is_plugin_active('emergency-communication-system/emergency-communication-system.php')) {
    echo '<p>✓ Plugin is active</p>';
} else {
    echo '<p style="color: orange;">⚠ Plugin is not active. Please activate it from the WordPress admin panel.</p>';
}

// Check permissions
$upload_dir = wp_upload_dir();
if (is_writable($upload_dir['basedir'])) {
    echo '<p>✓ Upload directory is writable</p>';
} else {
    echo '<p style="color: orange;">⚠ Upload directory is not writable</p>';
}

echo '<h2>Next Steps</h2>';
echo '<ol>';
echo '<li>Activate the plugin if not already active</li>';
echo '<li>Go to <strong>Emergency Communication > Settings</strong> in WordPress admin</li>';
echo '<li>Enter your Twilio credentials</li>';
echo '<li>Test the connection</li>';
echo '<li>Start adding contacts and creating groups</li>';
echo '</ol>';

echo '<h2>Configuration</h2>';
echo '<p>To configure Twilio:</p>';
echo '<ol>';
echo '<li>Sign up for a Twilio account at <a href="https://www.twilio.com/try-twilio" target="_blank">twilio.com</a></li>';
echo '<li>Get your Account SID and Auth Token from the Twilio Console</li>';
echo '<li>Purchase a phone number from Twilio Console > Phone Numbers > Manage > Buy a number</li>';
echo '<li>Enter these credentials in the plugin settings</li>';
echo '</ol>';

echo '<p style="color: green; font-weight: bold;">Installation check complete!</p>';
echo '<p><a href="' . admin_url('admin.php?page=ecs-settings') . '">Go to Plugin Settings</a></p>';
?>

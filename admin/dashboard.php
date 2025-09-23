<?php
/**
 * Dashboard page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_twilio_storage = new ECS_Twilio_Storage();

// Get statistics from Twilio
$contacts_result = $ecs_twilio_storage->get_contacts(null, 1000, 0);
$total_contacts = $contacts_result['success'] ? $contacts_result['contacts'] : array();

$groups_result = $ecs_twilio_storage->get_contact_groups();
$total_groups = $groups_result['success'] ? $groups_result['groups'] : array();

$messages_result = $ecs_twilio_storage->get_message_history(10, 0);
$recent_messages = $messages_result['success'] ? $messages_result['messages'] : array();

// Get scheduled messages from Twilio (using messaging services with ECS_Scheduled_ prefix)
$scheduled_messages = array();
try {
    $client = $ecs_twilio_storage->get_client();
    if ($client) {
        $services = $client->messaging->v1->services->read();
        foreach ($services as $service) {
            if (strpos($service->friendlyName, 'ECS_Scheduled_') === 0) {
                $scheduled_messages[] = $service;
            }
        }
    }
} catch (Exception $e) {
    $scheduled_messages = array();
}

// Test Twilio connection
$connection_test = $ecs_twilio_storage->test_connection();
?>

<div class="wrap">
    <h1><?php _e('Emergency Communication System Dashboard', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-dashboard">
        <div class="ecs-stats-grid">
            <div class="ecs-stat-card">
                <h3><?php _e('Total Contacts', 'emergency-communication-system'); ?></h3>
                <div class="ecs-stat-number"><?php echo count($total_contacts); ?></div>
            </div>
            
            <div class="ecs-stat-card">
                <h3><?php _e('Contact Groups', 'emergency-communication-system'); ?></h3>
                <div class="ecs-stat-number"><?php echo count($total_groups); ?></div>
            </div>
            
            <div class="ecs-stat-card">
                <h3><?php _e('Messages Sent Today', 'emergency-communication-system'); ?></h3>
                <div class="ecs-stat-number">
                    <?php 
                    $today_messages = array_filter($recent_messages, function($msg) {
                        return date('Y-m-d', strtotime($msg['created_at'])) === date('Y-m-d');
                    });
                    echo count($today_messages);
                    ?>
                </div>
            </div>
            
            <div class="ecs-stat-card">
                <h3><?php _e('Scheduled Messages', 'emergency-communication-system'); ?></h3>
                <div class="ecs-stat-number"><?php echo count($scheduled_messages); ?></div>
            </div>
        </div>
        
        <div class="ecs-dashboard-content">
            <div class="ecs-dashboard-left">
                <div class="ecs-card">
                    <h2><?php _e('Twilio Connection Status', 'emergency-communication-system'); ?></h2>
                    <div class="ecs-connection-status">
                        <?php if ($connection_test['success']): ?>
                            <div class="ecs-status-success">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Connected', 'emergency-communication-system'); ?>
                                <p><?php printf(__('Account: %s', 'emergency-communication-system'), esc_html($connection_test['account_name'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="ecs-status-error">
                                <span class="dashicons dashicons-no"></span>
                                <?php _e('Connection Failed', 'emergency-communication-system'); ?>
                                <p><?php echo esc_html($connection_test['error']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ecs-card">
                    <h2><?php _e('Quick Actions', 'emergency-communication-system'); ?></h2>
                    <div class="ecs-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=ecs-compose-alert'); ?>" class="button button-primary">
                            <?php _e('Compose Alert', 'emergency-communication-system'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ecs-phone-lists'); ?>" class="button">
                            <?php _e('Manage Contacts', 'emergency-communication-system'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ecs-contact-groups'); ?>" class="button">
                            <?php _e('Manage Groups', 'emergency-communication-system'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ecs-settings'); ?>" class="button">
                            <?php _e('Settings', 'emergency-communication-system'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="ecs-dashboard-right">
                <div class="ecs-card">
                    <h2><?php _e('Recent Messages', 'emergency-communication-system'); ?></h2>
                    <div class="ecs-recent-messages">
                        <?php if (empty($recent_messages)): ?>
                            <p><?php _e('No messages sent yet.', 'emergency-communication-system'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Recipient', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Message', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Status', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Sent', 'emergency-communication-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_messages as $message): ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html($message['recipient_name'] ?: $message['recipient_phone']); ?>
                                                <br><small><?php echo esc_html($message['recipient_phone']); ?></small>
                                            </td>
                                            <td><?php echo esc_html(wp_trim_words($message['message'], 10)); ?></td>
                                            <td>
                                                <span class="ecs-status-badge ecs-status-<?php echo esc_attr($message['status']); ?>">
                                                    <?php echo esc_html(ucfirst($message['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($message['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ecs-card">
                    <h2><?php _e('Scheduled Messages', 'emergency-communication-system'); ?></h2>
                    <div class="ecs-scheduled-messages">
                        <?php if (empty($scheduled_messages)): ?>
                            <p><?php _e('No scheduled messages.', 'emergency-communication-system'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Message', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Recipients', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Scheduled For', 'emergency-communication-system'); ?></th>
                                        <th><?php _e('Status', 'emergency-communication-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scheduled_messages as $scheduled): ?>
                                        <?php
                                        // Extract message details from service metadata
                                        $webhook_url = $scheduled->inboundRequestUrl ?? '';
                                        parse_str(parse_url($webhook_url, PHP_URL_QUERY), $params);
                                        $message = $params['message'] ?? 'No message details';
                                        $send_time = $params['send_time'] ?? '';
                                        
                                        // Get recipient count from phone numbers in the service
                                        $recipient_count = 0;
                                        try {
                                            $client = $ecs_twilio_storage->get_client();
                                            if ($client) {
                                                $phone_numbers = $client->messaging->v1->services($scheduled->sid)
                                                    ->phoneNumbers->read();
                                                $recipient_count = count($phone_numbers);
                                            }
                                        } catch (Exception $e) {
                                            $recipient_count = 0;
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html(wp_trim_words($message, 10)); ?></td>
                                            <td><?php echo $recipient_count; ?> recipients</td>
                                            <td><?php echo esc_html($send_time ? date('M j, Y g:i A', strtotime($send_time)) : 'Unknown'); ?></td>
                                            <td>
                                                <span class="ecs-status-badge ecs-status-pending">
                                                    <?php _e('Pending', 'emergency-communication-system'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

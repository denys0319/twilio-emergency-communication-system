<?php
/**
 * Dashboard page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_service = new ECS_Service();

// Auto-update recent message statuses from Twilio (for messages less than 24 hours old)
$recent_messages_for_update = $ecs_service->get_message_history(50, 0, null);
if ($recent_messages_for_update['success']) {
    foreach ($recent_messages_for_update['messages'] as $message) {
        // Only update if message is recent and has a Twilio SID
        if (!empty($message['twilio_sid']) && 
            strtotime($message['created_at']) > strtotime('-24 hours')) {
            // Update status in background (non-blocking)
            $ecs_service->get_message_status($message['twilio_sid']);
        }
    }
}

// Get statistics from WordPress database
$contacts_result = $ecs_service->get_contacts(null, 1000, 0);
$total_contacts = $contacts_result['success'] ? $contacts_result['contacts'] : array();

$groups_result = $ecs_service->get_contact_groups();
$total_groups = $groups_result['success'] ? $groups_result['groups'] : array();

$messages_result = $ecs_service->get_message_history(5, 0);
$recent_messages = $messages_result['success'] ? $messages_result['messages'] : array();

// Get scheduled messages from database (limit to 5)
$scheduled_result = $ecs_service->get_scheduled_messages(5, 0);
$scheduled_messages = $scheduled_result['success'] ? $scheduled_result['messages'] : array();

// Test Twilio connection
$connection_test = $ecs_service->test_connection();
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
                <div class="ecs-stat-number">
                    <?php 
                    // Count only pending scheduled messages for the stat
                    $pending_count = count(array_filter($scheduled_messages, function($msg) {
                        return $msg['status'] === 'pending';
                    }));
                    echo $pending_count;
                    ?>
                </div>
                <small><?php _e('Pending', 'emergency-communication-system'); ?></small>
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
                                            <td><?php echo esc_html(date_i18n('M j, Y g:i A', strtotime($message['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p style="text-align: right; margin-top: 10px;">
                                <a href="<?php echo admin_url('admin.php?page=ecs-message-history'); ?>" class="button">
                                    <?php _e('View All Messages →', 'emergency-communication-system'); ?>
                                </a>
                            </p>
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
                                        <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scheduled_messages as $scheduled): ?>
                                        <?php
                                        $recipients = json_decode($scheduled['recipients'], true);
                                        $recipient_count = is_array($recipients) ? count($recipients) : 0;
                                        ?>
                                        <tr data-scheduled-id="<?php echo esc_attr($scheduled['id']); ?>">
                                            <td><?php echo esc_html(wp_trim_words($scheduled['message'], 10)); ?></td>
                                            <td><?php echo $recipient_count; ?> recipients</td>
                                            <td><?php echo esc_html(date_i18n('M j, Y g:i A', strtotime($scheduled['send_time']))); ?></td>
                                            <td>
                                                <span class="ecs-status-badge ecs-status-<?php echo esc_attr($scheduled['status']); ?>">
                                                    <?php echo esc_html(ucfirst($scheduled['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($scheduled['status'] === 'pending'): ?>
                                                    <button type="button" class="button button-small ecs-cancel-scheduled" 
                                                            data-scheduled-id="<?php echo esc_attr($scheduled['id']); ?>">
                                                        <?php _e('Cancel', 'emergency-communication-system'); ?>
                                                    </button>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p style="text-align: right; margin-top: 10px;">
                                <a href="<?php echo admin_url('admin.php?page=ecs-message-history'); ?>" class="button">
                                    <?php _e('View All Scheduled Messages →', 'emergency-communication-system'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

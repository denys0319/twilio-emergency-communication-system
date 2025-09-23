<?php
/**
 * Message history page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_twilio_storage = new ECS_Twilio_Storage();

// Handle pagination
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
$messages_result = $ecs_twilio_storage->get_message_history($per_page, $offset, $status_filter);
$messages = $messages_result['success'] ? $messages_result['messages'] : array();

$total_messages_result = $ecs_twilio_storage->get_message_history(1000, 0, $status_filter);
$total_messages = $total_messages_result['success'] ? count($total_messages_result['messages']) : 0;
$total_pages = ceil($total_messages / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Message History', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-message-history">
        <div class="ecs-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="ecs-message-history" />
                
                <label for="status-filter"><?php _e('Filter by Status:', 'emergency-communication-system'); ?></label>
                <select id="status-filter" name="status">
                    <option value=""><?php _e('All Statuses', 'emergency-communication-system'); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'emergency-communication-system'); ?></option>
                    <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php _e('Sent', 'emergency-communication-system'); ?></option>
                    <option value="delivered" <?php selected($status_filter, 'delivered'); ?>><?php _e('Delivered', 'emergency-communication-system'); ?></option>
                    <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'emergency-communication-system'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'emergency-communication-system'); ?>" />
            </form>
        </div>
        
        <div class="ecs-messages-list">
            <h2><?php _e('Message History', 'emergency-communication-system'); ?></h2>
            
            <?php if (empty($messages)): ?>
                <p><?php _e('No messages found.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <div class="ecs-messages-controls">
                    <button type="button" class="button" id="ecs-refresh-status">
                        <?php _e('Refresh Status', 'emergency-communication-system'); ?>
                    </button>
                    <button type="button" class="button" id="ecs-export-messages">
                        <?php _e('Export Messages', 'emergency-communication-system'); ?>
                    </button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Recipient', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Message', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Status', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Sent', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Delivered', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Error', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr data-message-sid="<?php echo esc_attr($message['twilio_sid']); ?>">
                                <td>
                                    <strong><?php echo esc_html($message['recipient_name'] ?: 'Unknown'); ?></strong>
                                    <br><small><?php echo esc_html($message['recipient_phone']); ?></small>
                                </td>
                                <td>
                                    <div class="ecs-message-preview">
                                        <?php echo esc_html(wp_trim_words($message['message'], 15)); ?>
                                    </div>
                                    <?php if (strlen($message['message']) > 50): ?>
                                        <button type="button" class="button button-small ecs-view-full-message" 
                                                data-message="<?php echo esc_attr($message['message']); ?>">
                                            <?php _e('View Full', 'emergency-communication-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ecs-status-badge ecs-status-<?php echo esc_attr($message['status']); ?>">
                                        <?php echo esc_html(ucfirst($message['status'])); ?>
                                    </span>
                                    <?php if ($message['twilio_sid']): ?>
                                        <br><small><?php _e('SID:', 'emergency-communication-system'); ?> <?php echo esc_html(substr($message['twilio_sid'], 0, 10) . '...'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['sent_at']): ?>
                                        <?php echo esc_html(date('M j, Y g:i A', strtotime($message['sent_at']))); ?>
                                    <?php else: ?>
                                        <?php echo esc_html(date('M j, Y g:i A', strtotime($message['created_at']))); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['delivered_at']): ?>
                                        <?php echo esc_html(date('M j, Y g:i A', strtotime($message['delivered_at']))); ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['error_code'] || $message['error_message']): ?>
                                        <div class="ecs-error-info">
                                            <?php if ($message['error_code']): ?>
                                                <strong><?php echo esc_html($message['error_code']); ?></strong>
                                            <?php endif; ?>
                                            <?php if ($message['error_message']): ?>
                                                <br><small><?php echo esc_html($message['error_message']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['twilio_sid']): ?>
                                        <button type="button" class="button button-small ecs-check-status" 
                                                data-message-sid="<?php echo esc_attr($message['twilio_sid']); ?>">
                                            <?php _e('Check Status', 'emergency-communication-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="ecs-pagination">
                        <?php
                        $args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous'),
                            'next_text' => __('Next &raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        );
                        
                        if ($status_filter) {
                            $args['add_args'] = array('status' => $status_filter);
                        }
                        
                        echo paginate_links($args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Scheduled Messages Section -->
        <div class="ecs-scheduled-messages-section">
            <h2><?php _e('Scheduled Messages', 'emergency-communication-system'); ?></h2>
            
            <?php
            // Get scheduled messages from Twilio Messaging Services
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
            ?>
            
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
                            <th><?php _e('Created', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
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
                            <tr data-scheduled-id="<?php echo esc_attr($scheduled->sid); ?>">
                                <td>
                                    <div class="ecs-message-preview">
                                        <?php echo esc_html(wp_trim_words($message, 15)); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $recipient_count; ?> <?php _e('recipients', 'emergency-communication-system'); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($send_time ? date('M j, Y g:i A', strtotime($send_time)) : 'Unknown'); ?>
                                </td>
                                <td>
                                    <span class="ecs-status-badge ecs-status-pending">
                                        <?php _e('Pending', 'emergency-communication-system'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(date('M j, Y g:i A', strtotime($scheduled->dateCreated))); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small ecs-cancel-scheduled" 
                                            data-scheduled-id="<?php echo esc_attr($scheduled->sid); ?>">
                                        <?php _e('Cancel', 'emergency-communication-system'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Full Message Modal -->
<div id="ecs-view-message-modal" class="ecs-modal" style="display: none;">
    <div class="ecs-modal-content">
        <div class="ecs-modal-header">
            <h2><?php _e('Full Message', 'emergency-communication-system'); ?></h2>
            <span class="ecs-modal-close">&times;</span>
        </div>
        <div class="ecs-modal-body">
            <div id="ecs-full-message-content"></div>
        </div>
        <div class="ecs-modal-footer">
            <button type="button" class="button ecs-modal-cancel">
                <?php _e('Close', 'emergency-communication-system'); ?>
            </button>
        </div>
    </div>
</div>

<?php
/**
 * Message history page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_service = new ECS_Service();

// Auto-update message statuses from Twilio (for messages less than 24 hours old)
$recent_messages_result = $ecs_service->get_message_history(100, 0, null);
if ($recent_messages_result['success']) {
    foreach ($recent_messages_result['messages'] as $message) {
        // Only update if message is recent and has a Twilio SID
        if (!empty($message['twilio_sid']) && 
            strtotime($message['created_at']) > strtotime('-24 hours')) {
            // Update status in background (non-blocking)
            $ecs_service->get_message_status($message['twilio_sid']);
        }
    }
}

// Handle pagination
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
$messages_result = $ecs_service->get_message_history($per_page, $offset, $status_filter);
$messages = $messages_result['success'] ? $messages_result['messages'] : array();

$total_messages_result = $ecs_service->get_message_history(1000, 0, $status_filter);
$total_messages = $total_messages_result['success'] ? count($total_messages_result['messages']) : 0;
$total_pages = ceil($total_messages / $per_page);

// Scheduled messages filter
$scheduled_status_filter = isset($_GET['scheduled_status']) ? sanitize_text_field($_GET['scheduled_status']) : null;
?>

<div class="wrap">
    <h1><?php _e('Message History', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-message-history">
        <div class="ecs-messages-list">
            <h2><?php _e('Message History', 'emergency-communication-system'); ?></h2>
            
            <div class="ecs-messages-controls">
                <?php if (!empty($messages)): ?>
                    <div class="ecs-bulk-actions">
                        <input type="checkbox" id="ecs-select-all-messages" />
                        <label for="ecs-select-all-messages"><?php _e('Select All', 'emergency-communication-system'); ?></label>
                        
                        <button type="button" class="button" id="ecs-delete-selected-messages">
                            <?php _e('Delete Selected', 'emergency-communication-system'); ?>
                        </button>
                        
                        <span id="ecs-selected-messages-count" style="margin-left: 10px;"></span>
                    </div>
                <?php endif; ?>
                
                <div class="ecs-filters">
                    <form method="get" action="" style="display: inline-flex; align-items: center; gap: 8px;">
                        <input type="hidden" name="page" value="ecs-message-history" />
                        
                        <label for="status-filter"><?php _e('Filter:', 'emergency-communication-system'); ?></label>
                        <select id="status-filter" name="status">
                            <option value=""><?php _e('All Statuses', 'emergency-communication-system'); ?></option>
                            <option value="queued" <?php selected($status_filter, 'queued'); ?>><?php _e('Queued', 'emergency-communication-system'); ?></option>
                            <option value="sending" <?php selected($status_filter, 'sending'); ?>><?php _e('Sending', 'emergency-communication-system'); ?></option>
                            <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php _e('Sent', 'emergency-communication-system'); ?></option>
                            <option value="delivered" <?php selected($status_filter, 'delivered'); ?>><?php _e('Delivered', 'emergency-communication-system'); ?></option>
                            <option value="undelivered" <?php selected($status_filter, 'undelivered'); ?>><?php _e('Undelivered', 'emergency-communication-system'); ?></option>
                            <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'emergency-communication-system'); ?></option>
                        </select>
                        
                        <input type="submit" class="button" value="<?php _e('Filter', 'emergency-communication-system'); ?>" />
                        
                        <button type="button" class="button" id="ecs-refresh-status">
                            <?php _e('Refresh Status', 'emergency-communication-system'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if (empty($messages)): ?>
                <p><?php _e('No messages found.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="ecs-select-all-messages-header" /></th>
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
                            <tr data-message-id="<?php echo esc_attr($message['id']); ?>" data-message-sid="<?php echo esc_attr($message['twilio_sid']); ?>">
                                <td>
                                    <input type="checkbox" class="ecs-message-checkbox" value="<?php echo esc_attr($message['id']); ?>" />
                                </td>
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
                                        <?php 
                                        // Display in WordPress timezone using WordPress date function
                                        echo esc_html(date_i18n('M j, Y g:i A', strtotime($message['sent_at'])));
                                        ?>
                                    <?php else: ?>
                                        <?php 
                                        echo esc_html(date_i18n('M j, Y g:i A', strtotime($message['created_at'])));
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['delivered_at']): ?>
                                        <?php 
                                        // Display in WordPress timezone using WordPress date function
                                        echo esc_html(date_i18n('M j, Y g:i A', strtotime($message['delivered_at'])));
                                        ?>
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
                                    <button type="button" class="button button-small ecs-delete-message" 
                                            data-message-id="<?php echo esc_attr($message['id']); ?>">
                                        <?php _e('Delete', 'emergency-communication-system'); ?>
                                    </button>
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
            // Handle pagination for scheduled messages
            $scheduled_page = isset($_GET['scheduled_paged']) ? intval($_GET['scheduled_paged']) : 1;
            $scheduled_per_page = 10;
            $scheduled_offset = ($scheduled_page - 1) * $scheduled_per_page;
            
            // Get scheduled messages from WordPress database
            $scheduled_result = $ecs_service->get_scheduled_messages($scheduled_per_page, $scheduled_offset, $scheduled_status_filter);
            $scheduled_messages = $scheduled_result['success'] ? $scheduled_result['messages'] : array();
            
            // Get total count for pagination
            $total_scheduled_result = $ecs_service->get_scheduled_messages(10000, 0, $scheduled_status_filter);
            $total_scheduled = $total_scheduled_result['success'] ? count($total_scheduled_result['messages']) : 0;
            $total_scheduled_pages = ceil($total_scheduled / $scheduled_per_page);
            ?>
            
            <div class="ecs-messages-controls">
                <?php if (!empty($scheduled_messages)): ?>
                    <div class="ecs-bulk-actions">
                        <input type="checkbox" id="ecs-select-all-scheduled" />
                        <label for="ecs-select-all-scheduled"><?php _e('Select All', 'emergency-communication-system'); ?></label>
                        
                        <button type="button" class="button" id="ecs-delete-selected-scheduled">
                            <?php _e('Delete Selected', 'emergency-communication-system'); ?>
                        </button>
                        
                        <span id="ecs-selected-scheduled-count" style="margin-left: 10px;"></span>
                    </div>
                <?php endif; ?>
                
                <div class="ecs-filters">
                    <form method="get" action="" style="display: inline-flex; align-items: center; gap: 8px;">
                        <input type="hidden" name="page" value="ecs-message-history" />
                        <?php if ($page > 1): ?>
                            <input type="hidden" name="paged" value="<?php echo $page; ?>" />
                        <?php endif; ?>
                        <?php if ($status_filter): ?>
                            <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>" />
                        <?php endif; ?>
                        
                        <label for="scheduled-status-filter"><?php _e('Filter:', 'emergency-communication-system'); ?></label>
                        <select id="scheduled-status-filter" name="scheduled_status">
                            <option value=""><?php _e('All Statuses', 'emergency-communication-system'); ?></option>
                            <option value="pending" <?php selected($scheduled_status_filter, 'pending'); ?>><?php _e('Pending', 'emergency-communication-system'); ?></option>
                            <option value="sent" <?php selected($scheduled_status_filter, 'sent'); ?>><?php _e('Sent', 'emergency-communication-system'); ?></option>
                            <option value="cancelled" <?php selected($scheduled_status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'emergency-communication-system'); ?></option>
                            <option value="failed" <?php selected($scheduled_status_filter, 'failed'); ?>><?php _e('Failed', 'emergency-communication-system'); ?></option>
                        </select>
                        
                        <input type="submit" class="button" value="<?php _e('Filter', 'emergency-communication-system'); ?>" />
                    </form>
                </div>
            </div>
            
            <?php if (empty($scheduled_messages)): ?>
                <p><?php _e('No scheduled messages.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="ecs-select-all-scheduled-header" /></th>
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
                            // Decode recipients to get count
                            $recipients = json_decode($scheduled['recipients'], true);
                            $recipient_count = is_array($recipients) ? count($recipients) : 0;
                            ?>
                            <tr data-scheduled-id="<?php echo esc_attr($scheduled['id']); ?>">
                                <td>
                                    <input type="checkbox" class="ecs-scheduled-checkbox" value="<?php echo esc_attr($scheduled['id']); ?>" />
                                </td>
                                <td>
                                    <div class="ecs-message-preview">
                                        <?php echo esc_html(wp_trim_words($scheduled['message'], 15)); ?>
                                    </div>
                                    <?php if (strlen($scheduled['message']) > 50): ?>
                                        <button type="button" class="button button-small ecs-view-full-message" 
                                                data-message="<?php echo esc_attr($scheduled['message']); ?>">
                                            <?php _e('View Full', 'emergency-communication-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $recipient_count; ?> <?php _e('recipients', 'emergency-communication-system'); ?>
                                </td>
                                <td>
                                    <?php 
                                    // Display in WordPress timezone
                                    echo esc_html(date_i18n('M j, Y g:i A', strtotime($scheduled['send_time'])));
                                    ?>
                                </td>
                                <td>
                                    <span class="ecs-status-badge ecs-status-<?php echo esc_attr($scheduled['status']); ?>">
                                        <?php echo esc_html(ucfirst($scheduled['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Display in WordPress timezone
                                    echo esc_html(date_i18n('M j, Y g:i A', strtotime($scheduled['created_at'])));
                                    ?>
                                </td>
                                <td>
                                    <?php if ($scheduled['status'] === 'pending'): ?>
                                        <button type="button" class="button button-small ecs-cancel-scheduled" 
                                                data-scheduled-id="<?php echo esc_attr($scheduled['id']); ?>">
                                            <?php _e('Cancel', 'emergency-communication-system'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small ecs-delete-scheduled-single" 
                                            data-scheduled-id="<?php echo esc_attr($scheduled['id']); ?>">
                                        <?php _e('Delete', 'emergency-communication-system'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_scheduled_pages > 1): ?>
                    <div class="ecs-pagination">
                        <?php
                        $scheduled_args = array(
                            'base' => add_query_arg('scheduled_paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous'),
                            'next_text' => __('Next &raquo;'),
                            'total' => $total_scheduled_pages,
                            'current' => $scheduled_page
                        );
                        
                        if ($page > 1) {
                            $scheduled_args['add_args'] = array('paged' => $page);
                        }
                        if ($status_filter) {
                            if (!isset($scheduled_args['add_args'])) {
                                $scheduled_args['add_args'] = array();
                            }
                            $scheduled_args['add_args']['status'] = $status_filter;
                        }
                        if ($scheduled_status_filter) {
                            if (!isset($scheduled_args['add_args'])) {
                                $scheduled_args['add_args'] = array();
                            }
                            $scheduled_args['add_args']['scheduled_status'] = $scheduled_status_filter;
                        }
                        
                        echo paginate_links($scheduled_args);
                        ?>
                    </div>
                <?php endif; ?>
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

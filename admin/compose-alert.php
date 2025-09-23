<?php
/**
 * Compose alert page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_twilio_storage = new ECS_Twilio_Storage();
$groups_result = $ecs_twilio_storage->get_contact_groups();
$groups = $groups_result['success'] ? $groups_result['groups'] : array();
$phone_number = get_option('ecs_twilio_phone_number', '');
?>

<div class="wrap">
    <h1><?php _e('Compose Alert Message', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-compose-alert">
        <form id="ecs-compose-form">
            <?php wp_nonce_field('ecs_nonce', 'ecs_nonce'); ?>
            
            <div class="ecs-compose-section">
                <h2><?php _e('Message Details', 'emergency-communication-system'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ecs_message_text"><?php _e('Message Text', 'emergency-communication-system'); ?></label>
                        </th>
                        <td>
                            <textarea id="ecs_message_text" name="message" rows="6" class="large-text" 
                                      placeholder="<?php _e('Enter your emergency alert message here...', 'emergency-communication-system'); ?>" 
                                      maxlength="1600" required></textarea>
                            <p class="description">
                                <span id="ecs-char-count">0</span> / 1600 <?php _e('characters', 'emergency-communication-system'); ?>
                                <br><?php _e('SMS messages are limited to 160 characters per message. Longer messages will be split.', 'emergency-communication-system'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ecs_from_number"><?php _e('From Number', 'emergency-communication-system'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ecs_from_number" name="from_number" 
                                   value="<?php echo esc_attr($phone_number); ?>" class="regular-text" 
                                   placeholder="+1234567890" required />
                            <p class="description">
                                <?php _e('Your Twilio phone number in E.164 format', 'emergency-communication-system'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="ecs-compose-section">
                <h2><?php _e('Recipients', 'emergency-communication-system'); ?></h2>
                
                <div class="ecs-recipients-selection">
                    <div class="ecs-recipients-tabs">
                        <button type="button" class="ecs-tab-button active" data-tab="groups">
                            <?php _e('Send to Groups', 'emergency-communication-system'); ?>
                        </button>
                        <button type="button" class="ecs-tab-button" data-tab="individuals">
                            <?php _e('Send to Individuals', 'emergency-communication-system'); ?>
                        </button>
                        <button type="button" class="ecs-tab-button" data-tab="custom">
                            <?php _e('Custom Numbers', 'emergency-communication-system'); ?>
                        </button>
                    </div>
                    
                    <div class="ecs-tab-content active" id="ecs-groups-tab">
                        <h3><?php _e('Select Groups', 'emergency-communication-system'); ?></h3>
                        <?php if (empty($groups)): ?>
                            <p><?php _e('No contact groups available. Create groups first.', 'emergency-communication-system'); ?></p>
                        <?php else: ?>
                            <div class="ecs-groups-list">
                                <?php foreach ($groups as $group): ?>
                                    <?php
                                    $contacts_in_group_result = $ecs_twilio_storage->get_contacts($group['id'], 1000, 0);
                                    $contacts_count = $contacts_in_group_result['success'] ? count($contacts_in_group_result['contacts']) : 0;
                                    ?>
                                    <label class="ecs-group-item">
                                        <input type="checkbox" class="ecs-group-checkbox" 
                                               value="<?php echo esc_attr($group['id']); ?>" 
                                               data-group-name="<?php echo esc_attr($group['name']); ?>" />
                                        <div class="ecs-group-info">
                                            <strong><?php echo esc_html($group['name']); ?></strong>
                                            <span class="ecs-group-count"><?php echo $contacts_count; ?> <?php _e('contacts', 'emergency-communication-system'); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ecs-tab-content" id="ecs-individuals-tab">
                        <h3><?php _e('Select Individual Contacts', 'emergency-communication-system'); ?></h3>
                        <div class="ecs-individuals-search">
                            <input type="text" id="ecs-individuals-search" placeholder="<?php _e('Search contacts...', 'emergency-communication-system'); ?>" />
                        </div>
                        <div id="ecs-individuals-list" class="ecs-individuals-list">
                            <!-- Individual contacts will be loaded here via AJAX -->
                        </div>
                    </div>
                    
                    <div class="ecs-tab-content" id="ecs-custom-tab">
                        <h3><?php _e('Enter Custom Phone Numbers', 'emergency-communication-system'); ?></h3>
                        <textarea id="ecs-custom-numbers" rows="5" class="large-text" 
                                  placeholder="<?php _e('Enter phone numbers, one per line (e.g., +1234567890)', 'emergency-communication-system'); ?>"></textarea>
                        <p class="description">
                            <?php _e('Enter phone numbers in E.164 format, one per line', 'emergency-communication-system'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="ecs-selected-recipients">
                    <h3><?php _e('Selected Recipients', 'emergency-communication-system'); ?></h3>
                    <div id="ecs-recipients-summary">
                        <p><?php _e('No recipients selected', 'emergency-communication-system'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="ecs-compose-section">
                <h2><?php _e('Send Options', 'emergency-communication-system'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ecs-send-type"><?php _e('Send Type', 'emergency-communication-system'); ?></label>
                        </th>
                        <td>
                            <select id="ecs-send-type" name="send_type">
                                <option value="immediate"><?php _e('Send Immediately', 'emergency-communication-system'); ?></option>
                                <option value="scheduled"><?php _e('Schedule for Later', 'emergency-communication-system'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr id="ecs-schedule-row" style="display: none;">
                        <th scope="row">
                            <label for="ecs-schedule-time"><?php _e('Schedule Time', 'emergency-communication-system'); ?></label>
                        </th>
                        <td>
                            <input type="datetime-local" id="ecs-schedule-time" name="schedule_time" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Select date and time', 'emergency-communication-system'); ?>" />
                            <p class="description">
                                <?php _e('Select when to send this message', 'emergency-communication-system'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="ecs-compose-actions">
                <button type="button" id="ecs-preview-message" class="button">
                    <?php _e('Preview Message', 'emergency-communication-system'); ?>
                </button>
                <button type="submit" id="ecs-send-message" class="button button-primary">
                    <?php _e('Send Message', 'emergency-communication-system'); ?>
                </button>
                <button type="button" id="ecs-save-draft" class="button">
                    <?php _e('Save as Draft', 'emergency-communication-system'); ?>
                </button>
            </div>
        </form>
        
        <!-- Preview Modal -->
        <div id="ecs-preview-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2><?php _e('Message Preview', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <div class="ecs-preview-content">
                    <h3><?php _e('Message Text', 'emergency-communication-system'); ?></h3>
                    <div id="ecs-preview-text" class="ecs-preview-message"></div>
                    
                    <h3><?php _e('Recipients', 'emergency-communication-system'); ?></h3>
                    <div id="ecs-preview-recipients" class="ecs-preview-recipients"></div>
                    
                    <h3><?php _e('Send Details', 'emergency-communication-system'); ?></h3>
                    <div id="ecs-preview-details" class="ecs-preview-details"></div>
                </div>
                <div class="ecs-modal-footer">
                    <button type="button" class="button ecs-modal-cancel">
                        <?php _e('Close', 'emergency-communication-system'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Individual contacts management page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_twilio_storage = new ECS_Twilio_Storage();

// Get groups from Twilio
$groups_result = $ecs_twilio_storage->get_contact_groups();
$groups = $groups_result['success'] ? $groups_result['groups'] : array();

// Handle pagination
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get contacts from Twilio
$contacts_result = $ecs_twilio_storage->get_contacts(null, $per_page, $offset);
$contacts = $contacts_result['success'] ? $contacts_result['contacts'] : array();

$total_contacts_result = $ecs_twilio_storage->get_contacts(null, 1000, 0);
$total_contacts = $total_contacts_result['success'] ? count($total_contacts_result['contacts']) : 0;
$total_pages = ceil($total_contacts / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Individual Contacts Management', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-individual-contacts">
        <div class="ecs-actions-bar">
            <button type="button" class="button button-primary" id="ecs-add-individual-contact-btn">
                <?php _e('Add Individual Contact', 'emergency-communication-system'); ?>
            </button>
            <button type="button" class="button" id="ecs-bulk-actions-btn">
                <?php _e('Bulk Actions', 'emergency-communication-system'); ?>
            </button>
        </div>
        
        <!-- Add/Edit Individual Contact Modal -->
        <div id="ecs-individual-contact-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2 id="ecs-individual-modal-title"><?php _e('Add Individual Contact', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <form id="ecs-individual-contact-form">
                    <?php wp_nonce_field('ecs_contact', 'ecs_contact_nonce'); ?>
                    <input type="hidden" id="ecs_individual_contact_id" name="ecs_contact_id" value="" />
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ecs_individual_contact_phone"><?php _e('Phone Number', 'emergency-communication-system'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="ecs_individual_contact_phone" name="ecs_contact_phone" 
                                       class="regular-text" placeholder="+1234567890" required />
                                <p class="description">
                                    <?php _e('Enter phone number in E.164 format (e.g., +1234567890)', 'emergency-communication-system'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ecs_individual_contact_name"><?php _e('Name', 'emergency-communication-system'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="ecs_individual_contact_name" name="ecs_contact_name" 
                                       class="regular-text" placeholder="<?php _e('Contact Name', 'emergency-communication-system'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ecs_individual_contact_group"><?php _e('Group', 'emergency-communication-system'); ?></label>
                            </th>
                            <td>
                                <select id="ecs_individual_contact_group" name="ecs_contact_group">
                                    <option value=""><?php _e('No Group', 'emergency-communication-system'); ?></option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo esc_attr($group['id']); ?>">
                                            <?php echo esc_html($group['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="ecs_contact_submit" class="button-primary" 
                               value="<?php _e('Save Contact', 'emergency-communication-system'); ?>" />
                        <button type="button" class="button ecs-modal-cancel">
                            <?php _e('Cancel', 'emergency-communication-system'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Bulk Actions Modal -->
        <div id="ecs-bulk-actions-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2><?php _e('Bulk Actions', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <div class="ecs-bulk-actions-content">
                    <h3><?php _e('Select Action', 'emergency-communication-system'); ?></h3>
                    <select id="ecs-bulk-action-select">
                        <option value=""><?php _e('Select Action', 'emergency-communication-system'); ?></option>
                        <option value="add_to_group"><?php _e('Add to Group', 'emergency-communication-system'); ?></option>
                        <option value="remove_from_group"><?php _e('Remove from Group', 'emergency-communication-system'); ?></option>
                        <option value="delete"><?php _e('Delete Selected', 'emergency-communication-system'); ?></option>
                    </select>
                    
                    <div id="ecs-group-selection" style="display: none;">
                        <h4><?php _e('Select Group', 'emergency-communication-system'); ?></h4>
                        <select id="ecs-bulk-group-select">
                            <option value=""><?php _e('Select Group', 'emergency-communication-system'); ?></option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group['id']); ?>">
                                    <?php echo esc_html($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <p class="submit">
                        <button type="button" class="button-primary" id="ecs-execute-bulk-action">
                            <?php _e('Execute Action', 'emergency-communication-system'); ?>
                        </button>
                        <button type="button" class="button ecs-modal-cancel">
                            <?php _e('Cancel', 'emergency-communication-system'); ?>
                        </button>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Individual Contacts List -->
        <div class="ecs-individual-contacts-list">
            <h2><?php _e('Individual Contacts', 'emergency-communication-system'); ?></h2>
            
            <?php if (empty($contacts)): ?>
                <p><?php _e('No individual contacts found. Add contacts to get started.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <div class="ecs-contacts-controls">
                    <label>
                        <input type="checkbox" id="ecs-select-all-contacts" />
                        <?php _e('Select All', 'emergency-communication-system'); ?>
                    </label>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column">
                                <input type="checkbox" id="ecs-select-all-contacts-header" />
                            </th>
                            <th><?php _e('Name', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Phone', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Group', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Created', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr data-contact-id="<?php echo esc_attr($contact['id']); ?>">
                                <th class="check-column">
                                    <input type="checkbox" class="ecs-contact-checkbox" 
                                           value="<?php echo esc_attr($contact['id']); ?>" />
                                </th>
                                <td><?php echo esc_html($contact['name'] ?: '—'); ?></td>
                                <td><?php echo esc_html($contact['phone']); ?></td>
                                <td><?php echo esc_html($contact['group_name'] ?: '—'); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($contact['created_at']))); ?></td>
                                <td>
                                    <button type="button" class="button button-small ecs-edit-individual-contact" 
                                            data-contact-id="<?php echo esc_attr($contact['id']); ?>">
                                        <?php _e('Edit', 'emergency-communication-system'); ?>
                                    </button>
                                    <button type="button" class="button button-small ecs-delete-individual-contact" 
                                            data-contact-id="<?php echo esc_attr($contact['id']); ?>">
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
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous'),
                            'next_text' => __('Next &raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

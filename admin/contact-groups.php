<?php
/**
 * Contact groups management page
 */

if (!defined('ABSPATH')) {
    exit;
}

$ecs_service = new ECS_Service();
$groups_result = $ecs_service->get_contact_groups();
$groups = $groups_result['success'] ? $groups_result['groups'] : array();
?>

<div class="wrap">
    <h1><?php _e('Contact Groups Management', 'emergency-communication-system'); ?></h1>
    
    <div class="ecs-contact-groups">
        <div class="ecs-actions-bar">
            <button type="button" class="button button-primary" id="ecs-add-group-btn">
                <?php _e('Add Group', 'emergency-communication-system'); ?>
            </button>
        </div>
        
        <!-- Add/Edit Group Modal -->
        <div id="ecs-group-modal" class="ecs-modal" style="display: none;">
            <div class="ecs-modal-content">
                <div class="ecs-modal-header">
                    <h2 id="ecs-group-modal-title"><?php _e('Add Contact Group', 'emergency-communication-system'); ?></h2>
                    <span class="ecs-modal-close">&times;</span>
                </div>
                <form id="ecs-group-form">
                    <?php wp_nonce_field('ecs_group', 'ecs_group_nonce'); ?>
                    <input type="hidden" id="ecs_group_id" name="ecs_group_id" value="" />
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ecs_group_name"><?php _e('Group Name', 'emergency-communication-system'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="ecs_group_name" name="ecs_group_name" 
                                       class="regular-text" placeholder="<?php _e('Group Name', 'emergency-communication-system'); ?>" required />
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="ecs_group_submit" class="button-primary" 
                               value="<?php _e('Save Group', 'emergency-communication-system'); ?>" />
                        <button type="button" class="button ecs-modal-cancel">
                            <?php _e('Cancel', 'emergency-communication-system'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Groups List -->
        <div class="ecs-groups-list">
            <h2><?php _e('Contact Groups', 'emergency-communication-system'); ?></h2>
            
            <?php if (empty($groups)): ?>
                <p><?php _e('No contact groups found. Create a group to organize your contacts.', 'emergency-communication-system'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Group Name', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Contacts Count', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Created', 'emergency-communication-system'); ?></th>
                            <th><?php _e('Actions', 'emergency-communication-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <?php
                            $contacts_in_group_result = $ecs_service->get_contacts($group['id'], 1000, 0);
                            $contacts_count = $contacts_in_group_result['success'] ? count($contacts_in_group_result['contacts']) : 0;
                            ?>
                            <tr data-group-id="<?php echo esc_attr($group['id']); ?>">
                                <td><strong><?php echo esc_html($group['name']); ?></strong></td>
                                <td><?php echo $contacts_count; ?> <?php _e('contacts', 'emergency-communication-system'); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($group['created_at']))); ?></td>
                                <td>
                                    <button type="button" class="button button-small ecs-edit-group" 
                                            data-group-id="<?php echo esc_attr($group['id']); ?>">
                                        <?php _e('Edit', 'emergency-communication-system'); ?>
                                    </button>
                                    <button type="button" class="button button-small ecs-delete-group" 
                                            data-group-id="<?php echo esc_attr($group['id']); ?>">
                                        <?php _e('Delete', 'emergency-communication-system'); ?>
                                    </button>
                                    <a href="<?php echo admin_url('admin.php?page=ecs-phone-lists&group=' . $group['id']); ?>" 
                                       class="button button-small">
                                        <?php _e('View Contacts', 'emergency-communication-system'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

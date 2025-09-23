<?php
/**
 * Settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

$account_sid = get_option('ecs_twilio_account_sid', '');
$auth_token = get_option('ecs_twilio_auth_token', '');
$phone_number = get_option('ecs_twilio_phone_number', '');
?>

<div class="wrap">
    <h1><?php _e('Emergency Communication System Settings', 'emergency-communication-system'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('ecs_settings', 'ecs_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ecs_twilio_account_sid"><?php _e('Twilio Account SID', 'emergency-communication-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="ecs_twilio_account_sid" name="ecs_twilio_account_sid" 
                           value="<?php echo esc_attr($account_sid); ?>" class="regular-text" required />
                    <p class="description">
                        <?php _e('Your Twilio Account SID. You can find this in your Twilio Console.', 'emergency-communication-system'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="ecs_twilio_auth_token"><?php _e('Twilio Auth Token', 'emergency-communication-system'); ?></label>
                </th>
                <td>
                    <input type="password" id="ecs_twilio_auth_token" name="ecs_twilio_auth_token" 
                           value="<?php echo esc_attr($auth_token); ?>" class="regular-text" required />
                    <p class="description">
                        <?php _e('Your Twilio Auth Token. Keep this secure and never share it publicly.', 'emergency-communication-system'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="ecs_twilio_phone_number"><?php _e('Twilio Phone Number', 'emergency-communication-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="ecs_twilio_phone_number" name="ecs_twilio_phone_number" 
                           value="<?php echo esc_attr($phone_number); ?>" class="regular-text" 
                           placeholder="+1234567890" required />
                    <p class="description">
                        <?php _e('Your Twilio phone number in E.164 format (e.g., +1234567890).', 'emergency-communication-system'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="ecs_settings_submit" class="button-primary" 
                   value="<?php _e('Save Settings', 'emergency-communication-system'); ?>" />
        </p>
    </form>
    
    <div class="ecs-settings-info">
        <h2><?php _e('Getting Started with Twilio', 'emergency-communication-system'); ?></h2>
        <ol>
            <li>
                <?php _e('Sign up for a Twilio account at', 'emergency-communication-system'); ?> 
                <a href="https://www.twilio.com/try-twilio" target="_blank">twilio.com</a>
            </li>
            <li>
                <?php _e('Get your Account SID and Auth Token from the Twilio Console Dashboard', 'emergency-communication-system'); ?>
            </li>
            <li>
                <?php _e('Purchase a phone number from Twilio Console > Phone Numbers > Manage > Buy a number', 'emergency-communication-system'); ?>
            </li>
            <li>
                <?php _e('Enter your credentials above and save the settings', 'emergency-communication-system'); ?>
            </li>
        </ol>
        
        <h3><?php _e('Important Notes', 'emergency-communication-system'); ?></h3>
        <ul>
            <li><?php _e('Keep your Auth Token secure and never share it publicly', 'emergency-communication-system'); ?></li>
            <li><?php _e('Phone numbers must be in E.164 format (e.g., +1234567890)', 'emergency-communication-system'); ?></li>
            <li><?php _e('Test your connection after saving settings', 'emergency-communication-system'); ?></li>
            <li><?php _e('SMS costs apply per message sent through Twilio', 'emergency-communication-system'); ?></li>
        </ul>
    </div>
</div>

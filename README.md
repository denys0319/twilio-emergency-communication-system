# Emergency Communication System WordPress Plugin

A comprehensive WordPress plugin for emergency communication using Twilio's SMS API. This plugin allows administrators to manage contacts, create groups, compose emergency alerts, and send messages to individuals or groups with delivery tracking.

## 📚 Documentation

**[Read the Complete User Guide →](USER_GUIDE.md)**

The detailed User Guide covers:
- System overview and architecture
- Contact and group management
- Sending immediate and scheduled messages
- Understanding message statuses (with detailed explanations)
- Message history and filtering
- Troubleshooting and best practices

## Features

- **Contact Management**: Upload contacts from CSV/Excel files or add individually
- **Group Management**: Create and manage contact groups for organized messaging
- **Message Composition**: Compose emergency alerts with character count and preview
- **Flexible Recipients**: Send to groups, individuals, or custom phone numbers
- **Message Scheduling**: Schedule messages for future delivery with WordPress cron
- **Delivery Tracking**: Monitor message status and real-time delivery confirmations from Twilio
- **Status Filtering**: Filter messages by delivery status (queued, sent, delivered, failed, etc.)
- **Bulk Operations**: Select and delete multiple messages or scheduled jobs
- **Twilio Integration**: Direct integration with Twilio's SMS API for reliable delivery

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Twilio account with SMS capabilities
- Composer (for dependency management)

## Installation

### 1. Download and Install Plugin

1. Download the plugin files to your WordPress `/wp-content/plugins/emergency-communication-system/` directory
2. Activate the plugin through the WordPress admin panel

### 2. Install Dependencies

Run the following command in the plugin directory to install Twilio SDK:

```bash
composer install
```

### 3. Configure Twilio Settings

1. Go to **Emergency Communication > Settings** in your WordPress admin
2. Enter your Twilio credentials:
   - **Account SID**: Your Twilio Account SID
   - **Auth Token**: Your Twilio Auth Token
   - **Phone Number**: Your Twilio phone number in E.164 format (e.g., +1234567890)
3. Click **Save Settings**

### 4. Get Twilio Credentials

If you don't have a Twilio account:

1. Sign up at [twilio.com](https://www.twilio.com/try-twilio)
2. Get your Account SID and Auth Token from the Twilio Console Dashboard
3. Purchase a phone number from Twilio Console > Phone Numbers > Manage > Buy a number

## Usage

### Managing Contacts

#### Adding Individual Contacts
1. Go to **Emergency Communication > Individual Contacts**
2. Click **Add Individual Contact**
3. Fill in the contact details (phone number, name, optional group)
4. Click **Save Contact**

#### Uploading Contacts from File
1. Go to **Emergency Communication > Phone Lists**
2. Click **Upload from File**
3. Select a CSV or Excel file with the format: `phone, name, group (optional)`
4. Click **Upload Contacts**

#### Creating Contact Groups
1. Go to **Emergency Communication > Contact Groups**
2. Click **Add Group**
3. Enter group name and description
4. Click **Save Group**

### Composing and Sending Messages

1. Go to **Emergency Communication > Compose Alert**
2. Enter your message text (character count is displayed)
3. Select recipients:
   - **Send to Groups**: Choose from existing contact groups
   - **Send to Individuals**: Search and select individual contacts
   - **Custom Numbers**: Enter phone numbers manually
4. Choose send type:
   - **Send Immediately**: Message sends right away
   - **Schedule for Later**: Set a specific date and time
5. Click **Send Message** or **Preview Message** first

### Monitoring Messages

1. Go to **Emergency Communication > Message History**
2. View all sent messages with real-time Twilio delivery status
3. **Filter by Status**: queued, sending, sent, delivered, undelivered, failed
4. **Refresh Status**: Automatically updates messages less than 24 hours old
5. **Scheduled Messages**: View and manage scheduled message jobs
   - Filter: pending, sent, cancelled, failed
   - Cancel pending messages or delete completed ones

**Understanding Statuses**:
- **Message History**: Individual SMS delivery status from Twilio (queued → sent → delivered)
- **Scheduled Messages**: Overall scheduled job status (pending → sent)

## File Structure

```
emergency-communication-system/
├── emergency-communication-system.php    # Main plugin file
├── composer.json                        # Composer dependencies
├── README.md                           # This file
├── includes/                           # Core classes
│   ├── class-ecs-core.php
│   ├── class-ecs-twilio.php
│   ├── class-ecs-admin.php
│   ├── class-ecs-database.php
│   └── class-ecs-ajax.php
├── admin/                              # Admin pages
│   ├── dashboard.php
│   ├── settings.php
│   ├── phone-lists.php
│   ├── contact-groups.php
│   ├── individual-contacts.php
│   ├── compose-alert.php
│   └── message-history.php
└── assets/                             # CSS and JavaScript
    ├── css/
    │   └── admin.css
    └── js/
        └── admin.js
```

## Data Storage

The plugin uses a hybrid storage approach:

- **WordPress Database**: Stores contacts, groups, message history, and scheduled messages
  - `wp_ecs_contact_groups` - Contact groups
  - `wp_ecs_contacts` - Individual contacts  
  - `wp_ecs_messages` - Message history with Twilio statuses
  - `wp_ecs_scheduled_messages` - Scheduled message jobs
  - `wp_ecs_message_templates` - Message templates

- **Twilio API**: Handles actual SMS sending and provides real-time delivery status
  - Messages are sent through Twilio's Messages API
  - Status updates are fetched from Twilio for delivery tracking
  - No contact data is stored in Twilio (sent per-message only)

## Security Features

- Nonce verification for all forms and AJAX requests
- Input sanitization and validation
- Capability checks for admin functions
- SQL injection prevention through prepared statements

## Troubleshooting

### Common Issues

1. **Twilio Connection Failed**
   - Verify your Account SID and Auth Token are correct
   - Ensure your phone number is in E.164 format
   - Check that your Twilio account has sufficient credits

2. **Messages Not Sending**
   - Verify Twilio credentials in Settings
   - Check that phone numbers are in correct format
   - Ensure your Twilio account is not suspended

3. **File Upload Issues**
   - Ensure file is CSV or Excel format
   - Check file size limits
   - Verify file format matches expected structure

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please contact the plugin developer or create an issue in the project repository.

## License

This plugin is licensed under the GPL v2 or later.

## Key Concepts

### Message Statuses

**Individual Messages (Message History)**:
- `queued` - Waiting in Twilio queue
- `sending` - Being sent by Twilio
- `sent` - Delivered to carrier
- `delivered` - Delivered to recipient's device ✅
- `undelivered` - Could not be delivered
- `failed` - Failed to send

**Scheduled Messages**:
- `pending` - Waiting for scheduled time
- `sent` - Job executed successfully
- `cancelled` - User cancelled the job
- `failed` - System error during execution

### Workflow

```
Create Scheduled Message
    ↓
Status: pending (waiting)
    ↓
Cron executes at scheduled time
    ↓
Status: sent (scheduled message)
    ↓
Creates individual messages in Message History
    ↓
Each message: queued → sending → sent → delivered
```

## Changelog

### Version 1.0.0
- Initial release
- Contact management (individual and bulk upload)
- Contact group management
- Message composition and sending
- Message scheduling with WordPress cron
- Real-time delivery status tracking from Twilio
- Status filtering for message history and scheduled messages
- Bulk delete operations
- Auto-refresh for recent message statuses
- Cancel/delete scheduled messages

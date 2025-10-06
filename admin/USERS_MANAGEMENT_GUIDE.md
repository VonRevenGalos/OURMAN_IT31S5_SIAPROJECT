# Users Management System - Complete Guide

## 🎯 Overview

The Users Management System provides comprehensive control over regular users and admin accounts with professional UI, real-time updates, and advanced security features.

## 📊 Features

### Statistics Dashboard
- **Total Users**: Count of regular users (role = NULL or empty)
- **Active Users**: Non-suspended users
- **Suspended Users**: Blocked users who cannot log in
- **Total Admins**: Count of admin users (role = 'admin')
- **Verified Users**: Users with verified email addresses
- **Unverified Users**: Users pending email verification

### Regular Users Management
- **View Users**: Display first name, last name, username, email, gender, phone
- **Edit Users**: Update user information through modal forms
- **Suspend Users**: Block user access (immediate logout)
- **Unsuspend Users**: Restore user access
- **Status Indicators**: Visual badges for active/suspended/verified status

### Admin Users Management
- **View Admins**: Display all admin users with role indicators
- **Add Admin**: Create new admin accounts with secure passwords
- **Edit Admin**: Update admin information and passwords
- **Delete Admin**: Remove admin accounts (protection against self-deletion)
- **Current Admin Highlighting**: Visual indication of your own account

## 🔒 Security Features

### Suspension Logic
The suspension system is fully integrated with the existing session management:

1. **Automatic Logout**: When a user is suspended, they are immediately logged out
2. **Login Prevention**: Suspended users cannot log back in
3. **Session Validation**: All session checks include suspension status
4. **Real-time Monitoring**: Client-side monitoring detects suspension changes

### Implementation Details

#### Server-side (Already Implemented)
```php
// In includes/session.php - validateSession() method
$stmt = $this->pdo->prepare("
    SELECT id, first_name, last_name, username, email, role, is_suspended, is_verified 
    FROM users 
    WHERE id = ? AND is_suspended = 0  // ← Suspension check
");
```

#### Client-side Monitoring
```php
// Include in user pages for real-time suspension detection
<?php include 'includes/suspension_check.php'; ?>
```

## 📱 Mobile Responsiveness

### Desktop View
- Fixed sidebar with full navigation
- Large statistics cards in 6-column layout
- Full-width tables with all columns visible
- Hover effects and detailed tooltips

### Mobile View
- Collapsible sidebar with burger menu
- Stacked statistics cards
- Responsive tables with horizontal scroll
- Touch-friendly buttons and controls

## 🎨 Design System

### Color Scheme
- **Black Statistics Cards**: Professional dark theme with white text
- **Status Badges**: 
  - Green (Success): Active users
  - Red (Danger): Suspended users
  - Blue (Info): Verified status
  - Yellow (Warning): Admin role
  - Gray (Secondary): Unverified status

### Icons
- **Users**: `fas fa-users` (Primary blue)
- **Active**: `fas fa-user-check` (Success green)
- **Suspended**: `fas fa-user-times` (Danger red)
- **Admins**: `fas fa-user-shield` (Warning yellow)
- **Verified**: `fas fa-user-check` (Info blue)
- **Unverified**: `fas fa-user-clock` (Secondary gray)

## 🚀 Usage Instructions

### Accessing the System
1. Log in to admin panel
2. Click "Users" in the sidebar
3. View statistics dashboard
4. Switch between "Regular Users" and "Admin Users" tabs

### Managing Regular Users

#### Suspending a User
1. Find the user in the Regular Users table
2. Click the red "Suspend" button (user-times icon)
3. Confirm the action
4. User is immediately logged out and cannot log back in

#### Unsuspending a User
1. Find the suspended user (red "Suspended" badge)
2. Click the green "Unsuspend" button (user-check icon)
3. Confirm the action
4. User can now log in again

#### Editing User Information
1. Click the blue "Edit" button (edit icon)
2. Update information in the modal form
3. Click "Update User"
4. Changes are saved and table refreshes

### Managing Admin Users

#### Adding New Admin
1. Switch to "Admin Users" tab
2. Click "Add Admin" button
3. Fill in the form (password minimum 6 characters)
4. Click "Create Admin"
5. New admin can immediately log in

#### Editing Admin
1. Click the blue "Edit" button for any admin
2. Update information (leave password blank to keep current)
3. Click "Update Admin"
4. Changes are saved immediately

#### Deleting Admin
1. Click the red "Delete" button (trash icon)
2. Confirm the dangerous action
3. Admin account is permanently removed
4. **Note**: Cannot delete your own account (safety feature)

## ⚡ Real-time Features

### Auto-refresh
- Statistics update every 30 seconds
- Manual refresh button available
- Live status indicators

### AJAX Operations
- All actions happen without page reloads
- Instant feedback with success/error alerts
- Smooth animations and transitions

### Suspension Monitoring
- Client-side monitoring checks every 30 seconds
- Immediate alert and logout when suspended
- Professional suspension modal with countdown

## 🔧 Technical Implementation

### Database Schema
```sql
-- Users table structure (already exists)
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','buyer') NOT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,  -- Suspension flag
  `is_verified` tinyint(1) DEFAULT 0,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  -- ... other fields
);
```

### File Structure
```
admin/
├── users.php                    # Main users management page
├── test_users_system.php        # System verification test
└── USERS_MANAGEMENT_GUIDE.md    # This documentation

includes/
├── session.php                  # Session management (suspension logic)
├── suspension_check.php         # Client-side suspension monitoring
└── check_suspension.php         # AJAX suspension status endpoint
```

## 🛡️ Security Considerations

### Protection Measures
1. **Admin Self-Protection**: Cannot delete own admin account
2. **Session Validation**: All operations validate admin permissions
3. **Input Sanitization**: All user inputs are properly escaped
4. **SQL Injection Prevention**: Prepared statements used throughout
5. **XSS Protection**: HTML escaping for all user-generated content

### Best Practices
1. **Regular Monitoring**: Check user activity regularly
2. **Suspension Documentation**: Keep records of why users were suspended
3. **Admin Account Management**: Limit number of admin accounts
4. **Password Security**: Enforce strong passwords for admin accounts

## 📞 Support

For technical issues or questions about the Users Management System:
1. Check the browser console for JavaScript errors
2. Verify database connectivity
3. Ensure proper file permissions
4. Review server error logs

The system is designed to be robust and handle errors gracefully, with comprehensive logging and user feedback.

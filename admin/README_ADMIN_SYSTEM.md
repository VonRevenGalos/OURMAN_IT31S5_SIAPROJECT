# Admin System Documentation

## Overview
The admin login system has been completely rewritten to provide secure, separate session management for administrators while keeping the client-side user functionality intact.

## Key Features

### 🔐 Secure Admin Authentication
- **Separate Sessions**: Admin sessions are completely separate from user sessions
- **Rate Limiting**: Protection against brute force attacks (5 attempts per 15 minutes)
- **Session Validation**: Real-time validation against database
- **Secure Cookies**: HTTPOnly, Secure, and SameSite cookie settings
- **Session Timeout**: 24-hour session expiration

### 🛡️ Security Improvements
- **Password Verification**: Proper bcrypt password verification
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: All output is properly escaped
- **CSRF Protection**: Session-based protection
- **Logging**: Comprehensive security event logging

### 📊 Admin Dashboard
- **Real-time Statistics**: Users, products, orders, revenue
- **Session Management**: Track login time and session duration
- **User Management**: View and manage admin accounts
- **Responsive Design**: Mobile-friendly admin interface

## Files Modified/Created

### Core Authentication Files
- `admin/adminlogin.php` - ✅ **REWRITTEN** - Secure admin login page
- `admin/admin_login_process.php` - ✅ **REWRITTEN** - Login processing with rate limiting
- `admin/admin_logout.php` - ✅ **REWRITTEN** - Secure logout with session cleanup
- `admin/includes/admin_auth.php` - ✅ **NEW** - Core authentication functions

### Dashboard Files
- `admin/dashboard.php` - ✅ **UPDATED** - Uses new authentication system
- `admin/includes/sidebar.php` - ✅ **UPDATED** - Updated to use new auth functions

### Testing Files
- `admin/test_admin_system.php` - ✅ **NEW** - Comprehensive system test page

## Available Admin Accounts

Based on the `users.sql` file, the following admin accounts are available:

| ID | Username | Email | Name | Status |
|----|----------|-------|------|--------|
| 1 | `admin` | vonrevenmewe@gmail.com | admin admin | Active |
| 28 | `sethadmin` | - | Seth Tumacay | Active |
| 29 | `sethtubs` | - | seth aw | Active |

## Session Variables

The new admin system uses the following session variables:

```php
$_SESSION['admin_logged_in'] = true;           // Login status flag
$_SESSION['admin_user_id'] = $user['id'];      // Admin user ID
$_SESSION['admin_role'] = 'admin';             // User role
$_SESSION['admin_email'] = $user['email'];     // Admin email
$_SESSION['admin_username'] = $user['username']; // Admin username
$_SESSION['admin_first_name'] = $user['first_name']; // First name
$_SESSION['admin_last_name'] = $user['last_name'];   // Last name
$_SESSION['admin_login_time'] = time();        // Login timestamp
$_SESSION['admin_last_activity'] = time();    // Last activity timestamp
```

## Key Functions

### Authentication Functions
```php
isAdminLoggedIn()           // Check if admin is logged in
requireAdminLogin()         // Require admin login (redirect if not)
getCurrentAdminUser()       // Get current admin user data
validateAdminSession()      // Validate session against database
adminLogout()              // Logout and clear admin session
```

### Utility Functions
```php
getAdminSessionInfo()       // Get session info for display
hasAdminPrivileges()        // Check admin privileges
getAdminDashboardStats()    // Get dashboard statistics
```

## Testing the System

1. **Access the test page**: `admin/test_admin_system.php`
2. **Login with admin credentials**:
   - Username: `admin` or Email: `vonrevenmewe@gmail.com`
   - Password: (use the original password for the admin account)
3. **Verify functionality**:
   - Login status
   - Session information
   - Dashboard statistics
   - User data retrieval

## Security Features

### Rate Limiting
- Maximum 5 failed login attempts per IP address
- 15-minute lockout period after exceeding limit
- Automatic reset after timeout

### Session Security
- Secure session cookie parameters
- HTTPOnly cookies (prevent XSS)
- Secure cookies (HTTPS only when available)
- SameSite strict cookies (CSRF protection)
- Session regeneration on login

### Database Security
- Prepared statements for all queries
- Input validation and sanitization
- Password hashing with bcrypt
- SQL injection protection

### Logging
- Failed login attempts
- Successful logins
- Session validation errors
- Database errors

## Client-Side Compatibility

✅ **IMPORTANT**: The admin system is completely separate from the client-side user system:

- **User sessions remain intact** - Regular users can still login/logout normally
- **No interference** - Admin sessions don't affect user functionality
- **Separate authentication** - Different session variables and cookies
- **Independent operation** - Admin and user systems work independently

## Usage Instructions

### For Administrators
1. Navigate to `admin/adminlogin.php`
2. Enter admin username/email and password
3. Access the admin dashboard
4. Use admin functions without affecting user sessions

### For Developers
1. Include `admin/includes/admin_auth.php` in admin pages
2. Use `requireAdminLogin()` to protect admin pages
3. Use `getCurrentAdminUser()` to get admin data
4. Use `getAdminDashboardStats()` for dashboard data

## Troubleshooting

### Common Issues
1. **"Session invalid" errors**: Clear browser cookies and login again
2. **Rate limiting**: Wait 15 minutes after failed attempts
3. **Database errors**: Check database connection and table structure
4. **Permission errors**: Ensure admin role is set correctly in database

### Debug Mode
Access `admin/test_admin_system.php` to:
- Check login status
- View session variables
- Test database connectivity
- Verify admin accounts

## Next Steps

The admin system is now fully functional and secure. You can:

1. **Test the login system** using the test page
2. **Access the admin dashboard** after logging in
3. **Extend functionality** by adding more admin features
4. **Monitor logs** for security events
5. **Add more admin users** as needed

The system maintains complete separation from the client-side functionality, ensuring users can continue to use the website normally while administrators have secure access to the admin panel.

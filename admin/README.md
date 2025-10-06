# ShoeStore Admin Panel

A comprehensive admin panel for managing the ShoeStore ecommerce website with advanced security, analytics, and management features.

## Features

### 🔐 Security
- **Role-based Access Control**: Only users with `role = 'admin'` can access admin pages
- **Session Management**: Secure session handling with automatic validation
- **Access Logging**: All admin activities are logged for security monitoring
- **Security Headers**: Implemented security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- **Automatic Redirects**: Non-admin users are automatically redirected to login

### 📊 Dashboard Analytics
- **Real-time Statistics**: Total users, products, orders, and revenue
- **Interactive Charts**: Monthly sales overview and order status distribution
- **Recent Orders**: Latest order activity with status tracking
- **Top Products**: Best-selling products with sales metrics

### 🎨 Professional UI
- **Modern Design**: Clean, professional interface with gradient themes
- **Responsive Layout**: Works perfectly on desktop, tablet, and mobile
- **Collapsible Sidebar**: Space-efficient navigation with smooth animations
- **Interactive Elements**: Hover effects, transitions, and visual feedback

### 🚀 Easy Access
- **Admin Icon**: Discrete admin access icon on the main login page
- **New Tab Opening**: Admin login opens in a new tab, preserving user session
- **Quick Navigation**: Intuitive sidebar with organized menu sections

## Installation & Setup

### 1. Admin Users
Ensure you have admin users in your database. Admin users must have:
- `role = 'admin'`
- `is_verified = 1`
- `is_suspended = 0`

### 2. File Structure
```
admin/
├── adminlogin.php          # Admin login page
├── admin_login_process.php # Login processing
├── admin_logout.php        # Logout functionality
├── dashboard.php           # Main dashboard
├── test_security.php       # Security verification page
├── assets/
│   └── css/
│       ├── admin.css       # Main admin styles
│       ├── admin-auth.css  # Login page styles
│       └── dashboard.css   # Dashboard-specific styles
└── includes/
    ├── admin_session.php   # Admin session management
    └── sidebar.php         # Reusable sidebar component
```

### 3. Database Requirements
The admin system uses the existing database tables:
- `users` - For admin authentication
- `products` - For product statistics
- `orders` - For order analytics
- `order_items` - For sales data

## Usage

### Accessing Admin Panel
1. Go to the main login page (`login.php`)
2. Look for the admin icon in the bottom-right corner
3. Click the icon to open admin login in a new tab
4. Login with admin credentials

### Admin Credentials
Use any user from the database with `role = 'admin'`:
- Email: `vonrevenmewe@gmail.com` (User ID: 1)
- Or create new admin users by setting `role = 'admin'` in the database

### Navigation
- **Dashboard**: Overview with statistics and charts
- **Products**: Product management (coming soon)
- **Orders**: Order management (coming soon)
- **Users**: User management (coming soon)
- **Analytics**: Detailed analytics (coming soon)
- **Reviews**: Review management (coming soon)
- **Vouchers**: Voucher management (coming soon)
- **Settings**: System settings (coming soon)

## Security Features

### Access Control
- Automatic admin role verification on every page
- Session validation against database
- Unauthorized access logging
- Secure logout with session cleanup

### Session Security
- Extends the main session system
- Admin-specific session validation
- Automatic session regeneration
- Remember me functionality for admins

### Logging
All admin activities are logged including:
- Login attempts (successful and failed)
- Page access
- Unauthorized access attempts
- Logout events

## Customization

### Adding New Admin Pages
1. Create new PHP file in `/admin/` directory
2. Include `require_once 'includes/admin_session.php';` at the top
3. Use the standard admin layout structure
4. Add navigation link to `includes/sidebar.php`

### Styling
- Main styles: `assets/css/admin.css`
- Page-specific styles: Create new CSS file and include
- Color scheme: Modify CSS variables in `:root` selector

### Adding Charts
The dashboard uses Chart.js for analytics. To add new charts:
1. Include Chart.js library
2. Create canvas element
3. Initialize chart with data from PHP

## Browser Compatibility
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Mobile Support
- Fully responsive design
- Touch-friendly navigation
- Optimized for tablets and phones
- Collapsible sidebar for mobile

## Performance
- Optimized CSS and JavaScript
- Efficient database queries
- Lazy loading for charts
- Minimal external dependencies

## Security Best Practices
- Regular session validation
- SQL injection prevention
- XSS protection
- CSRF protection (recommended to implement)
- Regular security audits

## Future Enhancements
- Product management interface
- Order management system
- User management tools
- Advanced analytics
- Email notifications
- Backup and restore
- Multi-language support
- API integration

## Support
For issues or questions about the admin panel, check:
1. Error logs for detailed error information
2. Browser console for JavaScript errors
3. Database connection and user permissions
4. File permissions for admin directory

## License
This admin panel is part of the ShoeStore ecommerce system.

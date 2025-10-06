# Database Schema Reference

This document contains the complete database schema for the e-commerce application.

## Tables Overview

### 1. cart
Stores user shopping cart items.

| Column | Type | Description |
|--------|------|-------------|
| cart_id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| product_id | INT | Foreign key to products table |
| quantity | INT | Number of items |
| date_added | TIMESTAMP | When item was added to cart |
| size | VARCHAR | Product size |

### 2. favorites
Stores user's favorite products.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| product_id | INT | Foreign key to products table |
| created_at | DATETIME | When favorite was added |
| size | VARCHAR | Product size |

### 3. notifications
Stores user notifications.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| type | VARCHAR | Notification type |
| message | TEXT | Notification message |
| is_read | BOOLEAN | Read status |
| created_at | DATETIME | When notification was created |

### 4. orders
Stores order information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| status | VARCHAR | Order status |
| total_price | DECIMAL | Total order amount |
| payment_method | VARCHAR | Payment method used |
| shipping_address_id | INT | Foreign key to user_addresses table |
| created_at | DATETIME | Order creation time |
| updated_at | DATETIME | Last update time |

### 5. order_items
Stores individual items within orders.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| order_id | INT | Foreign key to orders table |
| product_id | INT | Foreign key to products table |
| quantity | INT | Number of items |
| price | DECIMAL | Price per item |
| size | VARCHAR | Product size |

### 6. products
Stores product information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| title | VARCHAR | Product name |
| price | DECIMAL | Product price |
| stock | INT | Available quantity |
| image | VARCHAR | Main product image path |
| category | VARCHAR | Product category |
| description | TEXT | Product description |
| thumbnail1 | VARCHAR | First thumbnail image |
| thumbnail2 | VARCHAR | Second thumbnail image |
| thumbnail3 | VARCHAR | Third thumbnail image |
   color       enum
   height      enum
   width       enum
   brand       varchar
   collection   varchar
   date_added   timestamp

### 7. users
Stores user account information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| first_name | VARCHAR | User's first name |
| last_name | VARCHAR | User's last name |
| username | VARCHAR | Unique username |
| email | VARCHAR | User's email address |
| password | VARCHAR | Hashed password |
| role | VARCHAR | User role (admin/user) |
| is_suspended | BOOLEAN | Account suspension status |
| otp_code | VARCHAR | One-time password code |
| otp_expires_at | DATETIME | OTP expiration time |
| is_verified | BOOLEAN | Email verification status |
| gender | VARCHAR | User's gender |
| date_of_birth | DATE | User's birth date |
| phone | VARCHAR | Phone number |
| remember_selector | VARCHAR | Remember me selector |
| remember_validator_hash | VARCHAR | Remember me validator hash |
| remember_expiry | DATETIME | Remember me expiration |

### 8. user_addresses
Stores user shipping/billing addresses.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| user_id | INT | Foreign key to users table |
| type | VARCHAR | Address type (shipping/billing) |
| full_name | VARCHAR | Full name for address |
| address_line1 | VARCHAR | Primary address line |
| address_line2 | VARCHAR | Secondary address line |
| city | VARCHAR | City |
| state | VARCHAR | State/Province |
| postal_code | VARCHAR | ZIP/Postal code |
| country | VARCHAR | Country |
| phone | VARCHAR | Phone number |
| is_default | BOOLEAN | Default address flag |
| created_at | DATETIME | Creation time |
| updated_at | DATETIME | Last update time |

### 9. vouchers
Stores discount vouchers/coupons.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| code | VARCHAR | Unique voucher code |
| discount | DECIMAL | Discount amount/percentage |
| valid_until | DATETIME | Expiration date |

## Relationships

- **users** → **cart** (1:many)
- **users** → **favorites** (1:many)
- **users** → **notifications** (1:many)
- **users** → **orders** (1:many)
- **users** → **user_addresses** (1:many)
- **products** → **cart** (1:many)
- **products** → **favorites** (1:many)
- **products** → **order_items** (1:many)
- **orders** → **order_items** (1:many)
- **user_addresses** → **orders** (1:many)

## Indexes (Recommended)

- `users.email` (unique)
- `users.username` (unique)
- `vouchers.code` (unique)
- `cart.user_id`
- `cart.product_id`
- `favorites.user_id`
- `favorites.product_id`
- `orders.user_id`
- `order_items.order_id`
- `order_items.product_id`
- `user_addresses.user_id`

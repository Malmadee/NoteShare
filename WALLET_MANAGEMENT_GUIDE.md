# Wallet Management - Complete Guide

## Overview
Your NoteShare system now supports **200 coins as the default wallet balance** for all users. You have multiple ways to manage user wallets.

---

## Option 1: SQL Scripts (Direct Database Management)

### File Location
`WALLET_MANAGEMENT_SQL.sql` - Contains all SQL queries

### Quick Commands

#### 1. Set ALL existing users to 200 coins
```sql
UPDATE users SET wallet_balance = 200 WHERE wallet_balance IS NOT NULL;
```

#### 2. Set new default to 200 coins for future users
```sql
ALTER TABLE users MODIFY COLUMN wallet_balance INT DEFAULT 200;
```

#### 3. Adjust individual user balance
```sql
-- Set user ID 1 to 200 coins
UPDATE users SET wallet_balance = 200 WHERE id = 1;

-- Add 100 coins to user ID 1
UPDATE users SET wallet_balance = wallet_balance + 100 WHERE id = 1;

-- Deduct 50 coins from user ID 1
UPDATE users SET wallet_balance = wallet_balance - 50 WHERE id = 1 AND wallet_balance >= 50;
```

#### 4. Give bonus to all users
```sql
-- Add 50 bonus coins to all users
UPDATE users SET wallet_balance = wallet_balance + 50 WHERE wallet_balance IS NOT NULL;
```

#### 5. View all user balances
```sql
SELECT id, first_name, last_name, email, wallet_balance FROM users ORDER BY id;
```

#### 6. View low balance users (less than 100 coins)
```sql
SELECT id, CONCAT(first_name, ' ', last_name) as name, email, wallet_balance
FROM users WHERE wallet_balance < 100
ORDER BY wallet_balance ASC;
```

### How to Use in phpMyAdmin
1. Open phpMyAdmin
2. Select your `noteshare` database
3. Click the **SQL** tab
4. Copy and paste any query from above
5. Click **Go** to execute

---

## Option 2: Web Admin Panel (User Interface)

### File Location
`admin/wallet-admin.html`

### How to Access
```
http://localhost/NoteShare/admin/wallet-admin.html
```

### Features
✅ View all users and their current balances  
✅ Adjust individual user balance  
✅ Reset single user to 200 coins  
✅ Reset ALL users to 200 coins  
✅ See statistics (total users, total coins, average balance)  
✅ Color-coded balance display (green=rich, red=low)  

### How to Use
1. Navigate to the admin panel URL above
2. View user list with balances
3. Click **Adjust** button to change a user's balance
4. Choose action:
   - **Set to Amount**: Set exact balance
   - **Add Coins**: Add coins to current balance
   - **Deduct Coins**: Remove coins from current balance
5. Click **Save Changes**

### Bulk Operations
- **Reset All to 200**: Resets every user to 200 coins (requires 2 confirmations for safety)

---

## Option 3: Backend APIs (Programmatic Access)

### Available Endpoints

#### Get All Users' Wallets
```php
GET /admin/get-all-users-wallets.php

Response:
{
  "success": true,
  "users": [
    {
      "id": 1,
      "first_name": "Malmadee",
      "last_name": "Alahakoon",
      "email": "malmadee2005@gmail.com",
      "wallet_balance": 200,
      "created_at": "2025-11-20 10:30:00"
    }
  ],
  "count": 1
}
```

#### Update User Balance
```php
POST /admin/update-user-wallet.php

Parameters:
- user_id (int): The user ID
- new_balance (int): The new balance amount

Response:
{
  "success": true,
  "message": "Wallet updated successfully",
  "user_id": 1,
  "new_balance": 250
}
```

#### Reset All Wallets
```php
POST /admin/reset-all-wallets.php

Response:
{
  "success": true,
  "message": "All users reset to 200 coins",
  "count": 1
}
```

---

## Database Schema

### Users Table Structure
```sql
Column: wallet_balance
Type: INT
Default: 200
Nullable: No

-- Current state for user ID 1
wallet_balance = 200 (from last update)
```

### Coins System Info
- **Default balance**: 200 coins per user
- **Minimum balance**: 0 coins (cannot go negative)
- **Can be manually adjusted**: Yes, through any of the 3 options above
- **Used for**: Purchase materials on the platform

---

## Recommended Workflow

### For First-Time Setup
```sql
-- Run this in phpMyAdmin to set all existing users to 200 coins
ALTER TABLE users MODIFY COLUMN wallet_balance INT DEFAULT 200;
UPDATE users SET wallet_balance = 200 WHERE wallet_balance IS NOT NULL;
```

### For Daily Management
- Use **Web Admin Panel** for quick adjustments
- Use **SQL** for bulk operations or specific queries
- Use **APIs** if integrating with other systems

### For User Support
```sql
-- If user complains about low balance, check it:
SELECT wallet_balance FROM users WHERE id = YOUR_USER_ID;

-- Reset user to default:
UPDATE users SET wallet_balance = 200 WHERE id = YOUR_USER_ID;

-- Give bonus coins:
UPDATE users SET wallet_balance = wallet_balance + 50 WHERE id = YOUR_USER_ID;
```

---

## Security Notes

⚠️ **Important**
- The admin panel is currently accessible without authentication
- In production, add authentication checks
- Do not expose these tools publicly
- Audit all balance changes in a log table

---

## Files Created/Modified

### New Files
- `WALLET_MANAGEMENT_SQL.sql` - SQL reference guide
- `admin/wallet-admin.html` - Web admin interface
- `admin/get-all-users-wallets.php` - API for getting users
- `admin/update-user-wallet.php` - API for updating balance
- `admin/reset-all-wallets.php` - API for bulk reset

### Modified Files
- `api/get-wallet-balance.php` - Changed default from 1500 to 200
- `assets/js/wallet.js` - Updated default from 1500 to 200

---

## Troubleshooting

### Issue: Balance not updating
**Solution**: Clear browser cache and refresh the page

### Issue: Admin panel showing "Loading users..."
**Solution**: Check if database connection is working
```bash
php check-user-balances.php
```

### Issue: Need to find specific user
**Solution**: Use SQL to search
```sql
SELECT * FROM users WHERE email = 'user@example.com';
```

---

## Summary

You now have **3 ways** to manage user wallets:

| Method | Best For | Difficulty |
|--------|----------|-----------|
| **SQL (phpMyAdmin)** | Bulk operations, automation | Medium |
| **Web Admin Panel** | Quick manual adjustments | Easy |
| **APIs** | Integration, scripting | Hard |

**Next Steps:**
1. Choose your preferred management method
2. Reset existing users to 200 coins using Option 1 or 2
3. All new users will automatically get 200 coins


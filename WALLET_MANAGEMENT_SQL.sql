-- =============================================================
-- SQL SCRIPT FOR WALLET BALANCE MANAGEMENT
-- Run these queries in phpMyAdmin to manage user wallet balances
-- =============================================================

-- ============================================================
-- 1. SET ALL EXISTING USERS TO 200 COINS DEFAULT
-- ============================================================
-- Uncomment and run this to reset all users to 200 coins
-- UPDATE users SET wallet_balance = 200 WHERE wallet_balance IS NOT NULL;


-- ============================================================
-- 2. SET NEW USERS TO 200 COINS BY MODIFYING THE DEFAULT
-- ============================================================
-- This ensures all NEW users created going forward get 200 coins
ALTER TABLE users MODIFY COLUMN wallet_balance INT DEFAULT 200;


-- ============================================================
-- 3. VIEW ALL USERS AND THEIR CURRENT BALANCES
-- ============================================================
-- Run this to see all user balances
SELECT 
    id,
    first_name,
    last_name,
    email,
    wallet_balance,
    created_at
FROM users
ORDER BY id DESC;


-- ============================================================
-- 4. ADJUST INDIVIDUAL USER BALANCE
-- ============================================================
-- Example: Set user ID 1 to 200 coins
-- UPDATE users SET wallet_balance = 200 WHERE id = 1;

-- Example: Add 100 coins to user ID 1
-- UPDATE users SET wallet_balance = wallet_balance + 100 WHERE id = 1;

-- Example: Deduct 50 coins from user ID 1
-- UPDATE users SET wallet_balance = wallet_balance - 50 WHERE id = 1 AND wallet_balance >= 50;


-- ============================================================
-- 5. GIVE BONUS COINS TO ALL USERS
-- ============================================================
-- Example: Add 50 bonus coins to all users
-- UPDATE users SET wallet_balance = wallet_balance + 50 WHERE wallet_balance IS NOT NULL;


-- ============================================================
-- 6. RESET SPECIFIC USER BALANCE TO 200
-- ============================================================
-- Replace USER_ID with the actual user ID
-- UPDATE users SET wallet_balance = 200 WHERE id = USER_ID;


-- ============================================================
-- 7. CHECK USERS WITH LOW BALANCE (Less than 100 coins)
-- ============================================================
SELECT 
    id,
    CONCAT(first_name, ' ', last_name) as name,
    email,
    wallet_balance
FROM users
WHERE wallet_balance < 100
ORDER BY wallet_balance ASC;


-- ============================================================
-- 8. VIEW PURCHASE HISTORY FOR A USER
-- ============================================================
-- Replace USER_ID with the actual user ID
-- SELECT 
--     p.id as purchase_id,
--     p.total_amount,
--     p.created_at,
--     COUNT(pi.id) as items_count
-- FROM purchases p
-- LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
-- WHERE p.user_id = USER_ID
-- GROUP BY p.id
-- ORDER BY p.created_at DESC;

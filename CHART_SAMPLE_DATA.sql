-- ============================================================
-- SAMPLE DATA FOR CHARTS (Optional - for testing charts)
-- ============================================================
-- Copy and paste this into phpMyAdmin SQL tab to create sample chart data
-- This creates test purchases across different categories and months

-- First, verify you have users and materials:
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as material_count FROM materials;

-- If you have users and materials, run the sample data below:

-- Create sample purchases for different months (2025)
INSERT INTO purchases (user_id, total_amount, created_at) VALUES
  (1, 100, '2025-01-15 10:30:00'),
  (1, 150, '2025-02-20 14:22:00'),
  (2, 200, '2025-03-10 09:15:00'),
  (1, 80, '2025-04-05 11:45:00'),
  (2, 120, '2025-05-12 16:30:00'),
  (1, 200, '2025-06-08 13:20:00'),
  (2, 90, '2025-07-22 10:00:00'),
  (1, 110, '2025-08-15 14:15:00'),
  (2, 150, '2025-09-03 09:45:00'),
  (1, 180, '2025-10-18 11:30:00'),
  (2, 140, '2025-11-25 15:00:00'),
  (1, 220, '2025-12-09 12:30:00');

-- Create sample purchase items linked to those purchases
-- Note: Adjust material_id values (1, 2, 3, 4) to match actual material IDs in your database
INSERT INTO purchase_items (purchase_id, material_id, price, qty) VALUES
  (1, 1, 50, 1),   -- User 1's material
  (1, 2, 50, 1),   -- User 2's material (to User 1)
  (2, 1, 75, 1),   -- User 1's material
  (2, 2, 75, 1),   -- User 2's material (to User 1)
  (3, 1, 100, 1),  -- User 1's material (to User 2)
  (3, 2, 100, 1),  -- User 2's material
  (4, 1, 40, 1),   -- User 1's material
  (4, 2, 40, 1),   -- User 2's material (to User 1)
  (5, 1, 60, 1),   -- User 1's material (to User 2)
  (5, 2, 60, 1),   -- User 2's material
  (6, 1, 100, 1),  -- User 1's material
  (6, 2, 100, 1),  -- User 2's material (to User 1)
  (7, 1, 45, 1),   -- User 1's material (to User 2)
  (7, 2, 45, 1),   -- User 2's material
  (8, 1, 55, 1),   -- User 1's material
  (8, 2, 55, 1),   -- User 2's material (to User 1)
  (9, 1, 75, 1),   -- User 1's material (to User 2)
  (9, 2, 75, 1),   -- User 2's material
  (10, 1, 90, 1),  -- User 1's material
  (10, 2, 90, 1),  -- User 2's material (to User 1)
  (11, 1, 70, 1),  -- User 1's material (to User 2)
  (11, 2, 70, 1),  -- User 2's material
  (12, 1, 110, 1), -- User 1's material
  (12, 2, 110, 1); -- User 2's material (to User 1)

-- ============================================================
-- VERIFY THE DATA
-- ============================================================
SELECT COUNT(*) as purchase_count FROM purchases;
SELECT COUNT(*) as purchase_items_count FROM purchase_items;

-- Check sales by category (what the chart will display)
SELECT 
    m.category,
    COUNT(pi.id) as sales_count
FROM purchase_items pi
JOIN materials m ON pi.material_id = m.id
GROUP BY m.category
ORDER BY m.category ASC;

-- Check monthly earnings for user 1
SELECT 
    MONTH(p.created_at) as month,
    COALESCE(SUM(pi.price), 0) as earnings
FROM purchase_items pi
JOIN materials m ON pi.material_id = m.id
JOIN purchases p ON pi.purchase_id = p.id
WHERE m.user_id = 1
    AND YEAR(p.created_at) = 2025
GROUP BY MONTH(p.created_at)
ORDER BY MONTH(p.created_at) ASC;

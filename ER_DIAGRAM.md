# NoteShare Database ER Diagram

```mermaid
erDiagram
    USERS ||--o{ MATERIALS : uploads
    USERS ||--o{ PURCHASES : makes
    USERS ||--o{ CART : adds_to
    USERS ||--o{ SUPPORT_MESSAGES : sends
    
    MATERIALS ||--o{ PURCHASE_ITEMS : contains
    MATERIALS ||--o{ CART : added_to
    MATERIALS }o--|| UPLOAD_CATEGORIES : categorized_by
    
    PURCHASES ||--o{ PURCHASE_ITEMS : has
    PURCHASE_ITEMS }o--|| MATERIALS : purchased
    
    SUBSCRIBERS ||--|| "Email System" : newsletter
    EMAIL_LOGS ||--|| "Email System" : audit_trail
    SUPPORT_MESSAGES ||--|| "Email System" : notifications

    USERS {
        int id PK "Primary Key"
        string first_name
        string last_name
        string email UK "Unique"
        string password
        int wallet_balance "Coin balance"
        timestamp created_at
        timestamp updated_at
    }

    MATERIALS {
        int id PK
        int user_id FK "References users"
        string title
        text description
        string category
        decimal price
        string file_path
        string file_type "e.g., PDF, DOC"
        int file_size "Bytes"
        timestamp upload_timestamp
        string thumbnail_path
        int pages_count "For PDFs"
        int purchases_count
        int category_id FK "References upload_categories"
        timestamp created_at
        timestamp updated_at
        int views "View count"
    }

    UPLOAD_CATEGORIES {
        int id PK
        string name UK "Unique category name"
    }

    PURCHASES {
        int id PK
        int user_id FK "References users"
        decimal total_amount "Total purchase value in coins"
        timestamp created_at
    }

    PURCHASE_ITEMS {
        int id PK
        int purchase_id FK "References purchases"
        int material_id FK "References materials"
        decimal price "Unit price at time of purchase"
        int qty "Quantity (usually 1)"
    }

    CART {
        int id PK
        int user_id FK "References users"
        int material_id FK "References materials"
        timestamp added_at
    }

    SUBSCRIBERS {
        int id PK
        string email UK "Newsletter subscriber email"
        timestamp subscribed_at
    }

    SUPPORT_MESSAGES {
        int id PK
        string name "Sender name"
        string email "Sender email"
        text message "Support message content"
        enum status "new / open / closed"
        timestamp created_at
    }

    EMAIL_LOGS {
        int id PK
        string to_email "Recipient email"
        string from_email "Sender email"
        string subject "Email subject"
        mediumtext body "Email body (HTML + text)"
        string status "sent / failed"
        text error "Error message if failed"
        timestamp sent_at
    }
```

## Database Summary

**Core Tables:**
- **users** - User accounts with wallet balance (coin system)
- **materials** - Study materials (PDFs, documents) uploaded by users
- **upload_categories** - Categories for organizing materials
- **purchases** - Purchase transactions (user buys materials)
- **purchase_items** - Line items in each purchase
- **cart** - Shopping cart for users

**Support & Communication:**
- **support_messages** - Support ticket submissions from users
- **subscribers** - Newsletter subscription emails
- **email_logs** - Audit trail of all sent/failed emails (notifications, newsletters, support)

## Key Relationships

1. **User → Materials** (One-to-Many): Users upload multiple study materials
2. **User → Purchases** (One-to-Many): Users make multiple purchases
3. **User → Cart** (One-to-Many): Users add multiple items to cart
4. **Purchase → Purchase_Items** (One-to-Many): Each purchase has multiple line items
5. **Materials → Purchase_Items** (One-to-Many): Each material can be in multiple purchases
6. **Materials → Upload_Categories** (Many-to-One): Materials belong to a category
7. **User → Support_Messages** (One-to-Many): Users send support tickets

## Wallet System

Users have a `wallet_balance` (integer representing coins):
- Users earn coins by uploading and selling materials
- Users spend coins to purchase materials from other users
- Coin balance is tracked in real-time in the `users` table

## Email System

All emails (newsletters, support notifications, etc.) are:
- Logged in `email_logs` for audit trail
- Tracked with status (sent/failed) and error messages
- Includes full email content (subject, body)

-- Add email field if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);

-- Add last_login field to track user activity
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME;

-- Add status field to manage user account status
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';

-- Add created_at field
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add updated_at field
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create an index on email for faster lookups
CREATE INDEX IF NOT EXISTS idx_email ON users(email);

-- Create an index on status for faster filtering
CREATE INDEX IF NOT EXISTS idx_status ON users(status); 
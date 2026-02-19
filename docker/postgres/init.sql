-- Initial database setup for Biblioteca
-- Tables will be created by Doctrine migrations

-- Create extension for UUID if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE biblioteca TO biblioteca;

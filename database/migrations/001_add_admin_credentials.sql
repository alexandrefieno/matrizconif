-- Execute somente se database/schema.sql já tiver sido importado antes desta atualização.
ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER name;
ALTER TABLE users ADD COLUMN must_change_password BOOLEAN NOT NULL DEFAULT TRUE AFTER active;
UPDATE users SET username = CONCAT('usuario_', id) WHERE username IS NULL;
ALTER TABLE users MODIFY username VARCHAR(60) NOT NULL;
ALTER TABLE users ADD CONSTRAINT uq_users_username UNIQUE (username);

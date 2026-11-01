SET NAMES utf8mb4;

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS google_sub VARCHAR(64) NULL;
ALTER TABLE `user` ADD UNIQUE INDEX IF NOT EXISTS uq_user_google_sub (google_sub);

USE biblioccaz;

ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS reset_token_hash CHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS reset_expire     DATETIME NULL;

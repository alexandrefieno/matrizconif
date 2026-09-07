-- Etapa Administrativa 3: validacao e incorporacao controlada dos lotes.
-- Execute uma unica vez em uma instalacao criada antes desta etapa.
USE matrizconif;

ALTER TABLE import_batches
  ADD COLUMN mapping JSON NULL AFTER status,
  ADD COLUMN rejection_reason TEXT NULL AFTER validation_report,
  ADD COLUMN validated_by BIGINT UNSIGNED NULL AFTER uploaded_by,
  ADD COLUMN promoted_by BIGINT UNSIGNED NULL AFTER validated_by,
  ADD COLUMN validated_at DATETIME NULL AFTER promoted_by,
  ADD COLUMN promoted_at DATETIME NULL AFTER validated_at,
  ADD CONSTRAINT fk_import_validator FOREIGN KEY (validated_by) REFERENCES users(id),
  ADD CONSTRAINT fk_import_promoter FOREIGN KEY (promoted_by) REFERENCES users(id);

-- Lotes da versao anterior eram marcados como validados sem conferencia de campos.
-- Eles retornam para conferencia; lotes ja incorporados permanecem inalterados.
UPDATE import_batches
   SET status = 'uploaded', validated_by = NULL, validated_at = NULL
 WHERE status = 'validated' AND mapping IS NULL;

UPDATE import_rows ir
JOIN import_batches ib ON ib.id = ir.import_batch_id
   SET ir.validation_status = 'pending', ir.validation_errors = NULL
 WHERE ib.status = 'uploaded';

ALTER TABLE pnp_income_bands
  ADD COLUMN source_row INT UNSIGNED NULL AFTER student_count;

CREATE TABLE IF NOT EXISTS institution_indicators (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  indicator_key VARCHAR(160) NOT NULL,
  numeric_value DECIMAL(24,10) NULL,
  text_value TEXT NULL,
  source_row INT UNSIGNED NULL,
  UNIQUE KEY uq_institution_indicator (base_period_id,unit_id,indicator_key),
  CONSTRAINT fk_indicator_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_indicator_import FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_indicator_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campus_parameter_values (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  parameter_key VARCHAR(160) NOT NULL,
  numeric_value DECIMAL(24,10) NULL,
  text_value TEXT NULL,
  source_row INT UNSIGNED NULL,
  UNIQUE KEY uq_campus_parameter (base_period_id,unit_id,parameter_key),
  CONSTRAINT fk_campus_parameter_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_campus_parameter_import FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_campus_parameter_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS budget_envelopes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  component VARCHAR(160) NOT NULL,
  action_code VARCHAR(40) NULL,
  amount DECIMAL(18,2) NOT NULL,
  source_row INT UNSIGNED NULL,
  UNIQUE KEY uq_budget_envelope (base_period_id,unit_id,component,action_code),
  CONSTRAINT fk_envelope_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_envelope_import FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_envelope_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO units (code, name, unit_type) VALUES
  ('REITORIA', 'REITORIA', 'rectory'),
  ('CARMO_DE_MINAS', 'CARMO DE MINAS', 'campus'),
  ('INCONFIDENTES', 'INCONFIDENTES', 'campus'),
  ('MACHADO', 'MACHADO', 'campus'),
  ('MUZAMBINHO', 'MUZAMBINHO', 'campus'),
  ('PASSOS', 'PASSOS', 'campus'),
  ('POUSO_ALEGRE', 'POUSO ALEGRE', 'campus'),
  ('POCOS_DE_CALDAS', 'POCOS DE CALDAS', 'campus'),
  ('TRES_CORACOES', 'TRES CORACOES', 'campus');

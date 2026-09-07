-- Compatível com MySQL 8.0+ e MariaDB 10.4+ (ambiente de referência: MariaDB 10.4.32)
CREATE DATABASE IF NOT EXISTS matrizconif
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE matrizconif;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','reviewer') NOT NULL DEFAULT 'reviewer',
  active BOOLEAN NOT NULL DEFAULT TRUE,
  must_change_password BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE base_periods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_year SMALLINT UNSIGNED NOT NULL,
  budget_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  status ENUM('draft','validated','published','archived') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  validated_by BIGINT UNSIGNED NULL,
  validated_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_period (base_year,budget_year),
  CONSTRAINT fk_period_creator FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_period_validator FOREIGN KEY (validated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  unit_type ENUM('campus','rectory') NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

INSERT INTO units (code, name, unit_type) VALUES
  ('REITORIA', 'REITORIA', 'rectory'),
  ('CARMO_DE_MINAS', 'CARMO DE MINAS', 'campus'),
  ('INCONFIDENTES', 'INCONFIDENTES', 'campus'),
  ('MACHADO', 'MACHADO', 'campus'),
  ('MUZAMBINHO', 'MUZAMBINHO', 'campus'),
  ('PASSOS', 'PASSOS', 'campus'),
  ('POUSO_ALEGRE', 'POUSO ALEGRE', 'campus'),
  ('POCOS_DE_CALDAS', 'POCOS DE CALDAS', 'campus'),
  ('TRES_CORACOES', 'TRES CORACOES', 'campus');

CREATE TABLE import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_type ENUM('pnp_cycles','pnp_income','institution_indicators','campus_parameters','budget_envelopes') NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  sha256 CHAR(64) NOT NULL,
  source_name VARCHAR(255) NOT NULL,
  source_url VARCHAR(1000) NULL,
  reference_date DATE NULL,
  row_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('uploaded','checking','rejected','validated','promoted') NOT NULL DEFAULT 'uploaded',
  mapping JSON NULL,
  validation_report JSON NULL,
  rejection_reason TEXT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  validated_by BIGINT UNSIGNED NULL,
  promoted_by BIGINT UNSIGNED NULL,
  validated_at DATETIME NULL,
  promoted_at DATETIME NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_import_hash (base_period_id,import_type,sha256),
  CONSTRAINT fk_import_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_import_user FOREIGN KEY (uploaded_by) REFERENCES users(id),
  CONSTRAINT fk_import_validator FOREIGN KEY (validated_by) REFERENCES users(id),
  CONSTRAINT fk_import_promoter FOREIGN KEY (promoted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  source_row INT UNSIGNED NOT NULL,
  payload JSON NOT NULL,
  validation_status ENUM('pending','valid','invalid') NOT NULL DEFAULT 'pending',
  validation_errors JSON NULL,
  UNIQUE KEY uq_batch_row (import_batch_id,source_row),
  CONSTRAINT fk_row_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pnp_cycles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  cycle_code VARCHAR(120) NOT NULL,
  course_name VARCHAR(255) NOT NULL,
  course_type VARCHAR(120) NOT NULL,
  modality VARCHAR(120) NOT NULL,
  funding_type VARCHAR(160) NULL,
  knowledge_axis VARCHAR(190) NULL,
  offer_type VARCHAR(120) NULL,
  agricultural BOOLEAN NOT NULL DEFAULT FALSE,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  cycle_hours DECIMAL(12,4) NULL,
  catalog_hours DECIMAL(12,4) NULL,
  enrollment_count DECIMAL(16,4) NOT NULL,
  source_row INT UNSIGNED NULL,
  UNIQUE KEY uq_cycle (base_period_id,unit_id,cycle_code),
  CONSTRAINT fk_cycle_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_cycle_import FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_cycle_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE pnp_income_bands (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  band_code VARCHAR(40) NOT NULL,
  student_count DECIMAL(16,4) NOT NULL,
  source_row INT UNSIGNED NULL,
  UNIQUE KEY uq_income_band (base_period_id,unit_id,band_code),
  CONSTRAINT fk_income_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_income_import FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
  CONSTRAINT fk_income_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE institution_indicators (
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

CREATE TABLE campus_parameter_values (
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

CREATE TABLE budget_envelopes (
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

CREATE TABLE matrix_parameters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  parameter_key VARCHAR(120) NOT NULL,
  numeric_value DECIMAL(24,10) NULL,
  text_value TEXT NULL,
  nature ENUM('normative','source_data','hypothesis','administrative') NOT NULL,
  source_reference VARCHAR(1000) NOT NULL,
  justification TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_period_parameter (base_period_id,parameter_key),
  CONSTRAINT fk_parameter_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_parameter_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE simulations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  base_period_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  scenario_type ENUM('administrative','public_ephemeral') NOT NULL DEFAULT 'administrative',
  engine_version VARCHAR(80) NOT NULL,
  status ENUM('draft','calculated','validated','published','archived') NOT NULL DEFAULT 'draft',
  assumptions JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  validated_by BIGINT UNSIGNED NULL,
  validated_at DATETIME NULL,
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sim_period FOREIGN KEY (base_period_id) REFERENCES base_periods(id),
  CONSTRAINT fk_sim_creator FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_sim_validator FOREIGN KEY (validated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE simulation_parameters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  simulation_id BIGINT UNSIGNED NOT NULL,
  parameter_key VARCHAR(120) NOT NULL,
  numeric_value DECIMAL(24,10) NULL,
  text_value TEXT NULL,
  source_type ENUM('period_default','administrator','public_user') NOT NULL,
  UNIQUE KEY uq_sim_parameter (simulation_id,parameter_key),
  CONSTRAINT fk_sim_parameter FOREIGN KEY (simulation_id) REFERENCES simulations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE simulation_unit_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  simulation_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  phase_results JSON NOT NULL,
  functioning_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  assistance_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  rip_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_sim_unit (simulation_id,unit_id),
  CONSTRAINT fk_result_sim FOREIGN KEY (simulation_id) REFERENCES simulations(id) ON DELETE CASCADE,
  CONSTRAINT fk_result_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(120) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  before_data JSON NULL,
  after_data JSON NULL,
  ip_address VARBINARY(16) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_audit_entity (entity_type,entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

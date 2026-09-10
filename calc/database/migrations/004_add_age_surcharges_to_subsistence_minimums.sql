ALTER TABLE subsistence_minimums ADD COLUMN IF NOT EXISTS age_70_surcharge NUMERIC(10, 2) DEFAULT 300.00;
ALTER TABLE subsistence_minimums ADD COLUMN IF NOT EXISTS age_75_surcharge NUMERIC(10, 2) DEFAULT 456.00;
ALTER TABLE subsistence_minimums ADD COLUMN IF NOT EXISTS age_80_surcharge NUMERIC(10, 2) DEFAULT 570.00;

UPDATE subsistence_minimums SET age_70_surcharge = 300.00, age_75_surcharge = 456.00, age_80_surcharge = 570.00 WHERE age_70_surcharge IS NULL OR age_70_surcharge = 0;

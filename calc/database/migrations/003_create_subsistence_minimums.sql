
CREATE TABLE IF NOT EXISTS subsistence_minimums (
    id SERIAL PRIMARY KEY,
    year INT UNIQUE NOT NULL,
    for_disabled_persons NUMERIC(10, 2) NULL,
    general_minimum NUMERIC(10, 2) NULL,
    age_surcharge_cap NUMERIC(10, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed historical & official values
INSERT INTO subsistence_minimums (year, for_disabled_persons, general_minimum, age_surcharge_cap)
VALUES
    (2023, 2093.00, 2589.00, 10340.35),
    (2024, 2361.00, 2920.00, 10340.35),
    (2025, 2595.00, 3200.00, 10340.35),
    (2026, 2750.00, 3400.00, 10340.35)
ON CONFLICT (year) DO UPDATE SET
    for_disabled_persons = EXCLUDED.for_disabled_persons,
    general_minimum = EXCLUDED.general_minimum,
    age_surcharge_cap = EXCLUDED.age_surcharge_cap,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO pension_coefficients (year, month, coefficient, description) VALUES
(2025, 1, 1.0520, 'Січень 2025 - Базовий індекс'),
(2025, 2, 1.0535, 'Лютий 2025 - Коригування інфляції'),
(2026, 1, 1.0610, 'Січень 2026 - Прогнозний показник')
ON CONFLICT (year, month) DO NOTHING;
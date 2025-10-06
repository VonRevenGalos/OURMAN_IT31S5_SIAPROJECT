CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text NOT NULL,
  `setting_type` enum('boolean','string','json') NOT NULL DEFAULT 'string',
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default payment method settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('payment_cod_enabled', '1', 'boolean', 'Enable Cash on Delivery payment method'),
('payment_bank_enabled', '1', 'boolean', 'Enable Bank Transfer payment method'),
('payment_card_enabled', '1', 'boolean', 'Enable Credit/Debit Card payment method'),
('payment_gcash_enabled', '1', 'boolean', 'Enable GCash payment method'),
('currency_display', 'PHP', 'string', 'Primary currency display'),
('currency_rates', '{"USD": 0.018, "GBP": 0.014, "CAD": 0.024}', 'json', 'Currency conversion rates from PHP'),
('site_name', 'ShoeARizz', 'string', 'Website name'),
('site_description', 'Premium Shoe Store', 'string', 'Website description'),
('shipping_fee', '150', 'string', 'Default shipping fee'),
('tax_rate', '0.12', 'string', 'Tax rate (VAT)');

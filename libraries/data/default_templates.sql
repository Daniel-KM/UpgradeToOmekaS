-- Exported from OmekaS v1.0.1

SET foreign_key_checks = 0;

TRUNCATE `resource_template`;
INSERT INTO `resource_template` (`id`, `owner_id`, `resource_class_id`, `label`) VALUES
(1,	NULL,	NULL,	'Base Resource');

TRUNCATE `resource_template_property`;
INSERT INTO `resource_template_property` (`id`, `resource_template_id`, `property_id`, `alternate_label`, `alternate_comment`, `position`, `data_type`, `is_required`) VALUES
(1,	1,	1,	NULL,	NULL,	1,	NULL,	0),
(2,	1,	15,	NULL,	NULL,	2,	NULL,	0),
(3,	1,	8,	NULL,	NULL,	3,	NULL,	0),
(4,	1,	2,	NULL,	NULL,	4,	NULL,	0),
(5,	1,	7,	NULL,	NULL,	5,	NULL,	0),
(6,	1,	4,	NULL,	NULL,	6,	NULL,	0),
(7,	1,	9,	NULL,	NULL,	7,	NULL,	0),
(8,	1,	12,	NULL,	NULL,	8,	NULL,	0),
(9,	1,	40,	'Place',	NULL,	9,	NULL,	0),
(10,	1,	5,	NULL,	NULL,	10,	NULL,	0),
(11,	1,	17,	NULL,	NULL,	11,	NULL,	0),
(12,	1,	6,	NULL,	NULL,	12,	NULL,	0),
(13,	1,	25,	NULL,	NULL,	13,	NULL,	0),
(14,	1,	10,	NULL,	NULL,	14,	NULL,	0),
(15,	1,	13,	NULL,	NULL,	15,	NULL,	0),
(16,	1,	29,	NULL,	NULL,	16,	NULL,	0),
(17,	1,	30,	NULL,	NULL,	17,	NULL,	0),
(18,	1,	50,	NULL,	NULL,	18,	NULL,	0),
(19,	1,	3,	NULL,	NULL,	19,	NULL,	0),
(20,	1,	41,	NULL,	NULL,	20,	NULL,	0);

SET foreign_key_checks = 1;

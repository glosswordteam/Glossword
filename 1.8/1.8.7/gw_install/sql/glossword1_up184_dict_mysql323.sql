ALTER TABLE `%s` ADD KEY `is_active` (`is_active`,`date_created`);
ALTER TABLE `%s` ADD KEY `recent` (`is_active`,`date_modified`,`date_created`);
ALTER TABLE `%s` ADD KEY `t123` (`is_active`,`term_1`,`term_2`,`term_3`,`term`,`date_created`);

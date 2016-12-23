-- <MailChimp connector>
-- Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
-- Copyright (C) 2016 Pierre-Henry Favre phf@atm-consulting.fr
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE IF NOT EXISTS llx_sendinblue_category_contact (
	rowid 					integer 		NOT NULL auto_increment PRIMARY KEY,
	entity 					integer 		NOT NULL DEFAULT 1,
	sendinblue_listid 		varchar(200),
	sendinblue_segmentid 	varchar(200),
	fk_category				integer			NOT NULL,
	tms 		timestamp
)ENGINE=InnoDB;

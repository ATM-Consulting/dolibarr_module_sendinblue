-- <MailChimp connector>
-- Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.

ALTER TABLE llx_mailchimp_activites ADD INDEX idx_fk_mailing_email (fk_mailing,email);
ALTER TABLE llx_mailchimp_activites ADD INDEX idx_mailchimp_activites_fk_mailing (fk_mailing);
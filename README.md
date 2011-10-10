Cerb5 Plugins - cerb5blog.convert_auditlog
===========================================
Copyright (C) 2011 Robert Middleswarth
[http://www.cerb5blog.com/](http://www.cerb5blog.com/)  

What's this?
------------
Converts Cerberus Audit log entires to Activity Log entires.

* Not yet tested 

Installation using Git
------------
* Change directory to **/cerb5/storage/plugins/**
* `git clone git://github.com/rmiddle/cerb5blog.convert_auditlog.git`
* `cd cerb5blog.convert_auditlog`
* `git checkout --track -b 5.6 origin/5.6`
* In your helpdesk, enable the plugin from **Setup->Features & Plugins**.

Installation using zip / tar.gz
------------
* Goto `https://github.com/rmiddle/cerb5blog.convert_auditlog`
* Select the correct branch that matches your version of Cerberus
* click Download button.
* Unzip in to **/cerb5/storage/plugins/cerb5blog.convert_auditlog**
* In your helpdesk, enable the plugin from **Setup->Features & Plugins**.

Note: Make sure you branch number matches your Cerberus Version Number.

Using the plugin
-----------
* Make sure you backup you database especially your ticket_audit_log table.
* Setup->Settings->scheduler
* Select "[Cerb5Blog.com] Convert Audit Log Cron Task"
* Make sure the setting are what you want and enable.

Notes
-----------
Ever scheduler/cron run will migrate 1000 (default) records from the ticket_audit_log table to context_activity_log.
If you use the setting "Convert only records that are currently tracked under Activity Logs and dump the rest?" some records will be destoryed.  You have been warned.

Credits
-------
This plugin was developed by [Robert Middleswarth](http://www.cerb5blog.com/).

License
-------

[http://opensource.org/licenses/gpl-2.0.php](http://opensource.org/licenses/gpl-2.0.php)  

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

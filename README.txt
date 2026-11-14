NETTRACK — NETWORK EQUIPMENT INVENTORY SYSTEM
================================================

This is a PHP + SQLite web application for a school project and local demonstration.
It does NOT require MySQL or phpMyAdmin. This is useful if the MySQL service on your
computer is not working.

FEATURES
--------
- Login-protected dashboard
- Equipment register: create, view, search, filter, edit and delete
- Equipment details: asset tag, category, manufacturer, model, serial number,
  IP address, MAC address, location, department, assigned user, purchase/warranty
  dates, status, condition and notes
- Maintenance log: service date, type, technician, cost, details and next due date
- Reports: category and status summaries, warranty register and CSV export
- Sample equipment records so the dashboard is populated immediately
- SQLite database stored outside the web root

REQUIREMENTS
------------
- Windows computer
- UwAmp with Apache and PHP
- PHP extensions pdo_sqlite and sqlite3 enabled
- MySQL is NOT required

INSTALLATION ON UWAMP
---------------------
1. Download and extract network_equipment_inventory.zip.
2. Copy the complete folder named "network_equipment_inventory" into UwAmp's
   www folder. The final path should be:
   UwAmp\\www\\network_equipment_inventory\\index.php
3. Keep the SQLite database outside the web root, at:
   UwAmp\\private\\neis\\inventory.sqlite
   When upgrading, back up the existing database and move/copy that same database
   file to this location. Do not replace an existing database with a blank file.
   For a new installation, run these PowerShell commands from the application folder
   before setting the administrator password:
   New-Item -ItemType Directory -Force ..\\..\\private\\neis
   New-Item -ItemType File ..\\..\\private\\neis\\inventory.sqlite
4. Start Apache in UwAmp and open:
   http://localhost/network_equipment_inventory/
5. Set the administrator password locally from PowerShell in the application folder:
   powershell -ExecutionPolicy Bypass -File .\\set_admin_password.ps1
   The command securely prompts for a new password. No default administrator
   password is provided or displayed by the application.
6. Sign in with the administrator username and the password you just set. Click
   Equipment register to review sample records. Click Add equipment to add
   your own equipment. Use Maintenance and Reports for the other features.

IF SQLITE IS NOT ENABLED
------------------------
1. Open the php.ini file used by UwAmp's active PHP version.
2. Search for these lines:
      ;extension=pdo_sqlite
      ;extension=sqlite3
3. If the lines have a leading semicolon, remove only that semicolon:
      extension=pdo_sqlite
      extension=sqlite3
   If already enabled, do not change them.
4. Save php.ini and restart Apache in UwAmp.
5. Refresh the application. If the extension names or configuration differ in your
   UwAmp installation, check the PHP error log or UwAmp documentation.

DATABASE AND BACKUP
-------------------
The live database is stored outside the web root at:
  UwAmp\\private\\neis\\inventory.sqlite

Back up this file regularly using SQLite's backup facility and verify the backup
before restoring or moving it. The data folder inside the application is web
protected and is not the live database location.

IMPORTANT SECURITY NOTES
------------------------
- Administrator passwords are created or reset using the local CLI helper; never
  publish a password in the login page or setup instructions.
- SQLite has no separate database username or password. Protect the database with
  filesystem permissions and keep it outside the public web root.
- This school-project edition is intended for a local demonstration.
- Before sharing it on a network or deploying it in a real organization, arrange
  proper access controls, HTTPS, regular backups and security review.
- The current edition has one administrator login and does not include an in-app
  password change screen or detailed audit trail.
- The five initial records are sample/demo data. Edit or delete them as appropriate.

PROJECT DESCRIPTION
------------------
The Network Equipment Inventory System is designed to centralize network asset
information for an organization. It helps ICT staff identify equipment, know where
it is installed, record its network identifiers, monitor status and physical
condition, document maintenance and export inventory reports. It can reduce reliance
on paper records and scattered spreadsheets.

This project is supplied as an educational starter implementation. Test it and
understand the modules before presenting or deploying it.

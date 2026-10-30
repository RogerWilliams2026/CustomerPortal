# Customer Portal 

## Created 26/10/2026 By Roger Williams

**Purpose**

Allow fictional customer to login to the web site and see summary data such as:
- Outstanding AR
- Open order value
- Open invoice value
- Credit limit

Also has reportssuch as:
- Reprint invoice
- Reprint credit memo


**Architecture**

Designed as a client-server structure:

Client: HTML, CSS, JavaScript
Server: MySQL, PHP


**Folder Structure:**

API - PHP code files
Config - PHP for accessing the database
CSS - CSS for the web site
HTML - web pages etc
JavaScript - client side JavaScript

**Note:** PHP purposely put into the 'Public' folder for testing purposes

**Developer Features**

Records PHP errors in a custom PHP log, at the moment written in the Public folder
for testing


### To Do

- If 'live' system obviously move the PHP files outside of the client folder (public)!
- Reports
- Change the JavaScript to use PROMISES as the AJAX works, but promises look neater
- Various aesthetic changes, colours, fonts etc
- See if scope for expanding the PHP error_log usage to something more interesting...

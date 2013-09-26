=== CAS/LDAP with Redirect ===
Contributors: Michael Seiler http://www.michaelseiler.net
Donate link: 
Tags: CAS authentication, LDAP integration and data mapping, post-login redirection
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.0

This plugin routes authentication through CAS, pulls user data from LDAP, and allows you to set redirection for users and admins following authentication.

== Description ==

This plugin allows you to implement a Single Sign On (SSO) authentication path for any sites in your organization that are built on WordPress.

The plugin hooks into the WP login and logout functions, and routes users to your Central Authentication Service (CAS) server for authentication.  Once authenticated, the system pulls the user's information from the Lightweight Directory Access Protocol (LDAP) server and populates the WP instance with the username, first name, last name, and email address if a user has never been to your site before.  A dummy password is inserted into the blog; it will never be used, but is necessary to lock the user account.  If the user has been there before, they are merely authenticated and redirected to the page you define in the options.

Data Mapping:
In our organization, our LDAP server used non-standard naming conventions, so we needed a way to map our user details to various fields in the LDAP.  

== Installation ==
Prerequisite:
None; Jasig CAS (1.3.2 at the moment) is included in this package.

Test Setup:
Line #185 is set *not* to validate the CAS Server when testing the package.  In production, after you've set up the firewalls and secure certificates and tested, you'll want to comment out that line.

== Frequently Asked Questions ==

= CAS and LDAP server credentials are correct, but no success. =

The most common problem is Firewall and Secure Certificate problems between your WP instance server and the CAS/LDAP servers.  You can test firewall settings by trying to "telnet <your_ldap_server> 389" (or 636 if you are using ldaps); if you get no response, then the issue is most likely a firewall setting on the LDAP server.  If you do get a response, but then get an error of "unable to connect to ldap server" while using ldaps, then the issue is most likely a certificate error.  Contact a system administrator to work out this issue.

== Screenshots ==
None at this time

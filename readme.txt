=== Simple Security ===
Name: Simple Security
Contributors: MyWebsiteAdvisor, ChrisHurst
Tags: Admin, Security
Requires at least: 3.3
Tested up to: 4.1.1
Stable tag: 1.1.6
Donate link: http://MyWebsiteAdvisor.com/donations/


Access Log to track Logins and Failed Login Attempts

== Description ==

Simple Security Plugin for WordPress is an Access Log to track Logins and Failed Login Attempts for the admin area of your WordPress Website

You can add a widget to the admin dashboard for logins and failed login attempts.



<a href="http://mywebsiteadvisor.com/plugins/simple-security/">**Upgrade to Simple Security Ultra**</a> for advanced features including:

* Configurable email alert notifications when selected conditions are met
* Receive an optional email alert when new IP addresses are added to Blacklist
* Receive an optional email alert after a failed login attempt
* Receive an optional email alert after a successful login
* Lifetime Priority Support and Update License



Check out the [Simple Security Plugin for WordPress Video Tutorial](http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1):

http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1



Developer Website: http://MyWebsiteAdvisor.com/

Plugin Support: http://MyWebsiteAdvisor.com/support/

Plugin Page: http://MyWebsiteAdvisor.com/plugins/simple-security/

Tutorial: http://mywebsiteadvisor.com/learning/video-tutorials/simple-security-tutorial/



Requirements:

* PHP v5.0+
* WordPress v3.3+


To-do:




== Installation ==

1. Upload `simple-security/` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Simple Security Plugin settings and enable Simple Security Plugin.



Check out the [Simple Security Plugin for WordPress Video Tutorial](http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1):

http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1






== Frequently Asked Questions ==

= Plugin doesn't work ... =

Please specify as much information as you can to help us debug the problem. 
Check in your error.log if you can. 
Please send screenshots as well as a detailed description of the problem.



<a href="http://mywebsiteadvisor.com/plugins/simple-security/">**Upgrade to Simple Security Ultra**</a> for advanced features including:

* Configurable email alert notifications when selected conditions are met
* Receive an optional email alert when new IP addresses are added to Blacklist
* Receive an optional email alert after a failed login attempt
* Receive an optional email alert after a successful login
* Lifetime Priority Support and Update License



Check out the [Simple Security Plugin for WordPress Video Tutorial](http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1):

http://www.youtube.com/watch?v=pMZ5oCUuX7k&hd=1



Developer Website: http://MyWebsiteAdvisor.com/

Plugin Support: http://MyWebsiteAdvisor.com/support/

Plugin Page: http://MyWebsiteAdvisor.com/plugins/simple-security/

Tutorial: http://mywebsiteadvisor.com/learning/video-tutorials/simple-security-tutorial/




== Screenshots ==

1. Access Log
2. Admin Dashboard Widget
3. Admin Settings Page
4. WordPress User Manager with Additional Last Login Column
5. IP Address Blacklist





== Changelog ==


= 1.1.6 =
* Fixed Two Low Risk XSS Vulnerabilities (HTB23244)
* Tested for compatibility with WP v4.1.1
* Updated links in readme and plugin for support, updates, etc


= 1.1.5 =
* fixed issue with clearing access log and warning about session start/headers already sent.
* tested for compatibility with WP v3.5.2


= 1.1.4 =
* updated contextual help, removed depricated filter and updated to preferred method
* added plugin upgrades info tab on plugin settings page
* added uninstall and deactivation funtions to clear plugin settings
* updated readme file


= 1.1.3 =
* added button to clear access log feature, which will purge all records from the Access Log DB table.
* added button to download access log as a CSV file.
* updated tutorial videos, links, embeds.
* updated readme file, plugin requires WordPress v3.3 for the help menu.


= 1.1.2 =
* added tab for tutorial video on plugin settings tabs
* added a link for plugin tutorial page on our website on the plugin row meta links on plugins page



= 1.1.1 =
* added seperate page for IP Address Blacklist
* added tabbed navigation to admin UI for settings, access log, and blacklist
* updated screenshots
* added tutorial video
* added link to upgrade to ultra version


= 1.1 =
* updated settings page to use WP settings API
* changed the hook for removing access log DB table from plugin deactivation to plugin uninstall hook.
* updated plugin settings page with tabs, rather than scrolling down the page
* reorgainzed entire plugin file layout


= 1.0.3 =
* removed leftover error_reporing(E_ALL) in plugin_loader.php file
* resolved issues with undefined index notifications
* added rate this plugin links
* fixed ip blacklist custom message which was not displaying properly
* fixed auto blacklist system to use the proper number of failed attempts as specified
* fixed access log to display proper number of records as specified in the screen options tab

= 1.0.2 =
* Removed extra error logging dev functions

= 1.0.1 =
* Resolved issues with auto blocking IP addresses


= 1.0 =
* Initial release





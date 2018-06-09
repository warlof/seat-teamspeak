# SeAT TeamSpeak
This plugin write for [SeAT](https://github.com/eveseat/seat) is providing to your instance a way to manage your Teamspeak with SeAT using both query and a grant permission system.

[![Latest Unstable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/unstable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Latest Stable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/stable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Build Status](https://img.shields.io/travis/warlof/seat-teamspeak.svg?style=flat-square)](https://travis-ci.org/warlof/seat-teamspeak)
[![Code Climate](https://img.shields.io/codeclimate/github/warlof/seat-teamspeak.svg?style=flat-square)](https://codeclimate.com/github/warlof/seat-teamspeak)
[![Coverage Status](https://img.shields.io/coveralls/warlof/seat-teamspeak.svg?style=flat-square)](https://coveralls.io/github/warlof/seat-teamspeak?branch=master)
[![License](https://poser.pugx.org/warlof/seat-teamspeak/license)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

## Quick Installation:

In your seat directory (By default:  /var/www/seat), type the following:

```
php artisan down
composer require warlof/seat-teamspeak
```

After a successful installation, you can include the actual plugin by editing **config/app.php** and adding the following after:

```
        /*
         * Package Service Providers...
         */
```
add
```
        Seat\Warlof\Teamspeak\TeamSpeakServiceProvider::class,

```
and save the file.  Now you're ready to tell SeAT how to use the plugin:

```
php artisan vendor:publish --force
php artisan migrate
php artisan up
```

And now, when you log into 'Seat', you should see a 'Teamspeak' link on the left.

Access your Teamspeak server and find the query_ip_whitelist.txt file. Add the IP address of your Seat install server to the list to avoid ServerQuery flood bans when running jobs.
Don't forget to add an empty line at the end of the query_ip_whitelist.txt.

Click on 'Teamspeak' and then click on 'Settings'.

Change the Configuration to meet your Teamspeak server's settings.  The Query port is 10011 by default.  Setting the ServerQuery username/password is beyond the scope of this and can be found at (https://www.teamspeak3.com/support/teamspeak-3-add-server-query-user.php).

Click 'Update', then click 'Update Teamspeak server groups' to load in all of your currently defined groups.

Access is granted through the 'Access Management' section.


Click on 'Settings' and then click on 'Schedule'. 
Add 'teamspeak:users:invite' and 'teamspeak:users:kick' (recommended 5 minutes).

Good luck, and Happy Hunting!!  o7

# SeAT TeamSpeak
This plugin write for [SeAT](https://github.com/eveseat/seat) is providing to your instance a way to manage your Teamspeak with SeAT using both query and a grant permission system.

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
```

And now, when you log into 'Seat', you should see a 'Teamspeak' link on the left.  

Click on 'Teamspeak' and then click on 'Settings'.

Change the Configuration to meet your Teamspeak server's settings.  The Query port is 10011 by default.  Setting the ServerQuery username/password is beyond the scope of this and can be found at (https://www.teamspeak3.com/support/teamspeak-3-add-server-query-user.php).

Click 'Update', then click 'Update Teamspeak server groups' to load in all of your currently defined groups.

Access is granted through the 'Access Management' section.

Good luck, and Happy Hunting!!  o7

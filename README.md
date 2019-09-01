# SeAT TeamSpeak
This plugin write for [SeAT](https://github.com/eveseat/seat) is providing to your instance a way to manage your Teamspeak with SeAT using both query and a grant permission system.

[![Latest Unstable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/unstable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Latest Stable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/stable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Maintainability](https://api.codeclimate.com/v1/badges/b7d8d113d57ba075b975/maintainability)](https://codeclimate.com/github/warlof/seat-teamspeak/maintainability)
[![License](https://poser.pugx.org/warlof/seat-teamspeak/license)](https://packagist.org/packages/warlof/seat-teamspeak)

## Quick Installation:

In your seat directory (by default:  `/var/www/seat`), type the following:

```
php artisan down
composer require warlof/seat-teamspeak

php artisan vendor:publish --force --all
php artisan migrate
php artisan up
```

And now, when you log into `SeAT`, you should see a `Connector` category in the sidebar.

Access your Teamspeak server and find the `query_ip_whitelist.txt` file.
Add the IP address of your Seat install server to the list to avoid ServerQuery flood bans when running jobs.
Don't forget to add an empty line at the end of the `query_ip_whitelist.txt`.

Click on `Connector` and then click on `Settings`.

Change the Configuration to meet your Teamspeak server's settings into `Teamspeak` block.
The Query port is `10011` by default.

Setting the ServerQuery username/password is beyond the scope of this documentation and can be found on
[official teamspeak website](https://www.teamspeak3.com/support/teamspeak-3-add-server-query-user.php).

**ATTENTION**
> In case you're not using `serveradmin` as Query User, ensure used Query User
> is not tied to any of your own Identities.
> 
> Created Query User **MUST** have permissions listed bellow :
>  - `i_group_member_add_power` : 75
>  - `i_group_member_remove_power` : 75
>  - `b_virtualserver_servergroup_client_list` : checked
>  - `i_client_serverquery_view_power` : 75

In the driver dropdown list, select `Teamspeak` and click on `Update Sets` button which will queue a job to pull all of your currently defined server groups.

Access is granted through the `Access Management` section.

Click on `Settings` and then click on `Schedule`. 
 - add `seat-connector:sync:sets` (recommended once an hour)
 - add `seat-connector:apply:policies` (recommended every 30 minutes)

In order to grant access to `Identities` section, you must add permission `seat-connector.view` to a role you're assigning to your users.

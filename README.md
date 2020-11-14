# SeAT TeamSpeak
This plugin write for [SeAT](https://github.com/eveseat/seat) is providing to your instance a way to manage your Teamspeak with SeAT using both query and a grant permission system.

[![Latest Unstable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/unstable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Latest Stable Version](https://poser.pugx.org/warlof/seat-teamspeak/v/stable)](https://packagist.org/packages/warlof/seat-teamspeak)
[![Maintainability](https://api.codeclimate.com/v1/badges/b7d8d113d57ba075b975/maintainability)](https://codeclimate.com/github/warlof/seat-teamspeak/maintainability)
[![License](https://poser.pugx.org/warlof/seat-teamspeak/license)](https://packagist.org/packages/warlof/seat-teamspeak)

## Installation

### Package deployment

#### Bare metal installation

In your seat directory (by default:  `/var/www/seat`), type the following:

```shell script
php artisan down
composer require warlof/seat-teamspeak

php artisan vendor:publish --force --all
php artisan migrate
php artisan up
```

Now, when you log into `SeAT`, you should see a `Connector` category in the sidebar.

#### Docker installation

In the directory where reside your `docker-compose.yml` file, edit the `.env` configuration file (by default: `/opt/seat-docker/.env`)

Find the line `SEAT_PLUGINS` and append `warlof/seat-teamspeak:^5.0` at the end.
 - In case the line is starting by a sharp `#`, replace the line by `SEAT_PLUGINS=warlof/seat-teamspeak:^5.0`
 - In case you already have other plugins defined, append a comma at the end of existing value `SEAT_PLUGINS=author/package,warlof/seat-teamspeak:^5.0`.

Once done, you can restart your stack using `docker-compose up -d`.

### Teamspeak Server Configuration

Access your Teamspeak server and find the `query_ip_whitelist.txt` file.
Add the IP address of your Seat install server to the list to avoid flood bans when running jobs.
Don't forget to add an empty line at the end of the `query_ip_whitelist.txt`.

If it's not already the case, you will have to enable either `http` or `https` protocol to allow server queries.
This can be done by appending one or the other to `TS3SERVER_QUERY_PROTOCOLS` environment variable (if using [Docker](https://hub.docker.com/_/teamspeak)) or `query_protocols` as startup parameter (if using a [blade installation](https://www.teamspeak.com/en/downloads/#server)).

**CAUTION**

> The used IP address must be the one SeAT will use to contact your Teamspeak server.
> In case you have a private network linking both servers, and you plan to ask SeAT to contact the teamspeak instance on this network, you must add the SeAT **private IP** from that network instead its **public IP** address.

Once this configuration has been done, we will generate an API Key which will be used by SeAT to send his commands to Teamspeak.
To do so, authenticate on the teamspeak server using `serveradmin` account with a tool of your choice (ie: [putty](https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html) or [YaTQA](https://yat.qa))

1. Authenticate against server using `login serveradmin ${serveradminpassword}`
2. Generate new API Key for SeAT using `apikeyadd scope=manage_scope lifetime=0`

The server should answer with something similar
```
TS3
Welcome to the TeamSpeak 3 ServerQuery interface, type "help" for a list of commands and "help <command>" for information on a specific command.
error id=0 msg=ok
apikey=BAByFoiEXZfnSJyE6dbXFiW_nn_SdwkclpKNz9j id=4 sid=0 cldbid=1 scope=manage time_left=unlimited created_at=1582102492 expires_at=1582102492
error id=0 msg=ok
```

In the upper example, the generated API Key is `BAByFoiEXZfnSJyE6dbXFiW_nn_SdwkclpKNz9j`.
This is the piece you will need to set up the connector into SeAT.

### Connector Setup

Authenticate on your SeAT instance with an admin account.
You can use the built-in administrator user using `php artisan seat:admin:login` which will provide you proper permissions.

On the sidebar, click on `Connector` and then click on `Settings`.

Change the Configuration to meet your Teamspeak server's settings into `Teamspeak` block.

 - Server Address: is the address your user will use to connect to your Teamspeak instance (it can be either an IP or a domain)
 - Server Port: is the port your user will use to connect to your Teamspeak instance (`9987` by default)
 - Api Base Uri: is the url that SeAT will use to contact your Teamspeak Server (by default `http://teamspeak_address:10080` or `https://teamspeak_address:10443`)
 - Api Key: this is the key you generated in previous steps

### Initializing

Authenticate on your SeAT instance with an admin account.
You can use the built-in administrator user using `php artisan seat:admin:login` which will provide you proper permissions.

On the sidebar, click on `Connector` and then click on `Settings`.

In the driver dropdown list, select `Teamspeak` and click on `Update Sets` button which will queue a job to pull all of your currently defined server groups.

Access are grant through the `Access Management` section.

### Scheduler

Authenticate on your SeAT instance with an admin account.
You can use the built-in administrator user using `php artisan seat:admin:login` which will provide you proper permissions.

On the sidebar, click on `Settings` and then click on `Schedule`. 
- add `seat-connector:apply:policies` (recommended every 30 minutes)

In order to grant access to `Identities` section, you must add permission `seat-connector.view` to a role you're assigning to your users.

## Query Permissions

You'll find below a list of required permissions and used query patterns

| Query                                | Server Permission                         | Api Scope      |
| ------------------------------------ | ----------------------------------------- | -------------- |
| `/serverlist`                        | `b_serverinstance_virtualserver_list`     | `manage_scope` |
| `/{instance}/clientdbfind`           | `b_virtualserver_client_dbsearch`         | `manage_scope` |
| `/{instance}/clientdbinfo`           | `b_virtualserver_client_dbinfo`           | `manage_scope` |
| `/{instance}/clientdblist`           | `b_virtualserver_client_dblist`           | `manage_scope` |
| `/{instance}/servergroupaddclient`   | `i_group_member_add_power`                | `manage_scope` |
| `/{instance}/servergroupsbyclientid` |                                           | `manage_scope` |
| `/{instance}/servergroupclientlist`  | `b_virtualserver_servergroup_client_list` | `manage_scope` |
| `/{instance}/servergroupdelclient`   | `i_group_member_remove_power`             | `manage_scope` |
| `/{instance}/servergrouplist`        | `b_virtualserver_servergroup_list`        | `manage_scope` |
| `/{instance}/serverinfo`             | `b_serverinstance_help_view`              | `manage_scope` |

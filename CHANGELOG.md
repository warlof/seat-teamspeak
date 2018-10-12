# 3.0.4
- Address an issue which was generating an empty address on the `join the server` button
- Address an issue which was thrown when an user doesn't have either a main character or a corporation set

# 3.0.3
- Address issues related to nickname already in use triggered by the query user
- Address an issue which was preventing user with revoked token to be kick
- Address an issue which was preventing user without `teamspeak.view` permission to see the menu entry
- Refactor jobs
- Refactor connector link

Be certain you're putting your SeAT server address into your Teamspeak query whitelist in order to avoid any issues.

> **/ ! \ Warning**
>
> Due to modification applied on settings level, you may need to setup your teamspeak again.
> It should be handled by migration, so take a look into settings.

> **/ ! \ Warning**
>
> Commands has been updated.
> - `teamspeak:groups:update` is now `teamspeak:group:sync`
> - `teamspeak:users:invite` is now `teamspeak:user:policy`
> - `teamspeak:users:kick` has been removed

# 3.0.1
Fix service registration process.

# 3.0.0
Initial release of teamspeak connector for SeAT 3.x

# 2.0.0-RC1
Release candidate is available. Thanks to [@denngarr](https://github.com/dysath)

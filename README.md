# Nextcloud Cospend 💰

![CI](https://github.com/julien-nc/cospend-nc/workflows/CI/badge.svg?branch=master&event=push)
[![coverage report](https://github.com/julien-nc/cospend-nc/raw/gh-pages/coverage.svg)](https://julien-nc.github.io/cospend-nc/)
[![Crowdin](https://d322cqt584bo4o.cloudfront.net/moneybuster/localized.svg)](https://crowdin.com/project/moneybuster)

Nextcloud Cospend is a group/shared budget manager.
It was inspired by the great [IHateMoney](https://github.com/spiral-project/ihatemoney/).

You can use it when you share a house, when you go on vacation with friends, whenever you share expenses with a group of people.

It lets you create projects with members and bills. Each member has a balance computed from the project bills.
Balances are not an absolute amount of money at members disposal but rather a relative information 
showing if a member has spent more for the group than the group has spent for her/him, independently of exactly who spent money for whom.
This way you can see who owes the group and who the group owes.
Ultimately you can ask for a settlement plan telling you which payments to make to reset members balances.

Project members are independent from Nextcloud users.
Projects can be shared with other Nextcloud users or via public links.

[MoneyBuster](https://gitlab.com/eneiluj/moneybuster) Android client is [available in F-Droid](https://f-droid.org/packages/net.eneiluj.moneybuster/) and on the [Play store](https://play.google.com/store/apps/details?id=net.eneiluj.moneybuster).

[PayForMe](https://github.com/mayflower/PayForMe) iOS client is currently under developpement!

The private and public APIs are documented using [the Nextcloud OpenAPI extractor](https://github.com/nextcloud/openapi-extractor/).
This documentation can be accessed directly in Nextcloud.
All you need is to install Cospend (>= v1.6.0) and use the
[the OCS API Viewer app](https://apps.nextcloud.com/apps/ocs_api_viewer) to browse the OpenAPI documentation.

## Features

* ✎ Create/edit/delete projects, members, bills, bill categories, currencies
* ⚖ Check member balances
* 🌐 View cumulative balances across all your projects
* 🤝 Create cross-project settlements to settle debts spanning multiple projects
* 🗠 Display project statistics
* ♻ Display settlement plan
* Move bills from one project to another
* Move bills to trash before actually deleting them
* Archive old projects before deleting them
* 🎇 Automatically create reimbursement bills from settlement plan
* 🗓 Create recurring bills (day/week/month/year)
* 📊 Optionally provide custom amount for each member in new bills
* 🔗 Link personal files to bills (picture of physical receipt for example)
* 👩 Public links for people outside Nextcloud (can be password protected)
* 👫 Share projects with Nextcloud users/groups/circles
* 🖫 Import/export projects as csv (compatible with csv files from IHateMoney and SplitWise)
* 🔗 Generate link/QRCode to easily add projects in MoneyBuster
* 🗲 Implement Nextcloud notifications and activity stream

This app usually support the 2 or 3 last major versions of Nextcloud.

This app is under development.

🌍 Help us to translate this app on [Nextcloud-Cospend/MoneyBuster Crowdin project](https://crowdin.com/project/moneybuster).

⚒ Check out other ways to help in the [contribution guidelines](https://github.com/julien-nc/cospend-nc/blob/master/CONTRIBUTING.md).

Link to Nextcloud application website : https://apps.nextcloud.com/apps/cospend

## Donation

I develop this app during my free time.

* [Paypal: <img src="https://raw.githubusercontent.com/stefan-niedermann/paypal-donate-button/master/paypal-donate-button.png" width="100"/>](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66PALMY8SF5JE) (you don't need a paypal account)
* [Liberapay : ![Donate using Liberapay](https://liberapay.com/assets/widgets/donate.svg)](https://liberapay.com/eneiluj/donate)

## Documentation

* [User documentation](https://github.com/julien-nc/cospend-nc/blob/master/docs/user.md)
* [Admin documentation](https://github.com/julien-nc/cospend-nc/blob/master/docs/admin.md)
* [Developer documentation](https://github.com/julien-nc/cospend-nc/blob/master/docs/dev.md)
* [CHANGELOG](https://github.com/julien-nc/cospend-nc/blob/master/CHANGELOG.md#change-log)
* [AUTHORS](https://github.com/julien-nc/cospend-nc/blob/master/AUTHORS.md#authors)

## Known issues

* ...

Any feedback will be appreciated.

## Screenshots

![1](https://github.com/julien-nc/cospend-nc/raw/main/img/screenshots/cospend1.jpg)
![2](https://github.com/julien-nc/cospend-nc/raw/main/img/screenshots/cospend2.jpg)
![3](https://github.com/julien-nc/cospend-nc/raw/main/img/screenshots/cospend3.jpg)
![4](https://github.com/julien-nc/cospend-nc/raw/main/img/screenshots/cospend4.jpg)

## Nightly

:warning: Make sure to only use nightly builds on test Nextcloud instances.

To install the latest nightly, you can either
* download it from https://apps.nextcloud.com/apps/cospend and replace
the app directory manually in `nextcloud/apps`
(make sure you give cospend directory's ownership to your webserver user)
* use this occ command:
```
occ app:update --allow-unstable cospend
```
When you want to get back to stable releases,
just disable and remove the app from app settings and reinstall it. You won't loose any data.

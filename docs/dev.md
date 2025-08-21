# Development environment

Clone this repository and build:

``` bash
cd /var/www/.../nextcloud/apps
git clone https://github.com/julien-nc/cospend-nc cospend
cd cospend
npm ci
npm run watch
```

Or if you want to use HMR (hot module replacement),
install the [Nextcloud HMR Enabler app](https://github.com/nextcloud/hmr_enabler)
and run this in cospend directory:
``` bash
npm run serve
```

# Public API

Plan was to make Cospend public API strictly identical to [IHateMoney API](https://ihatemoney.readthedocs.io/en/latest/api.html) but there is a restriction i couldn't bypass : the authentication system. IHateMoney uses the basic HTTP authentication, just like Nextcloud user authentication. So, to get a guest access to a Cospend project, this type of authentication was first rejected by Nextcloud user auth system and then accepted by Cospend with a huge latency.

So the only differences between IHateMoney API and Cospend API are :

* The password has to be included in the URL path, just after the project ID, like that : `https://mynextcloud.org/index.php/apps/cospend/api/myproject/projectPassword/bills`
* The parameter `payed_for` cannot be given multiple times like in IHateMoney. It has to be given once with coma separated values.

That's it.

## New API Endpoints

Recent additions to the Cospend API include cross-project functionality:

### Cross-Project Balances
* **GET** `/api/v1/cross-project-balances` - Retrieve cumulative balances across all user's projects
  * Returns aggregated balance data by person and currency
  * Excludes archived projects from calculations
  * Supports both Nextcloud users (aggregated by userid) and guest users (aggregated by name)

### Cross-Project Settlement
* **POST** `/api/v1/cross-project-settlement` - Create settlements spanning multiple projects
  * Supports both full and partial settlement modes
  * Handles automatic distribution of settlement amounts across projects
  * Includes validation and conflict resolution for overpayments

Detailed API description will come later.

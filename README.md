# Another Cospend fork 💰

A **heavily vibe-coded** fork of [julien-nc/cospend-nc](https://github.com/julien-nc/cospend-nc), the group/shared budget manager for Nextcloud.

**All the credit for Cospend belongs upstream.** This fork exists to develop features I want for my own use case *(because Splitwise decided to spectacularly enshitify itself, presumably thanks to whatever craptastic VC is behind it)*, most of which I've proposed as Feature Requests back upstream.

> **If you just want Cospend, install it from the [Nextcloud app store](https://apps.nextcloud.com/apps/cospend).** You only want this fork if one of the features below is worth the risk described immediately underneath.

---

## Potential 🚩: Please read this before you install anything

**Essentially all of the code in this fork has been written by some or the other LLM.** 

Claude Code (Opus 5 and Fable 5) did a majority of the work, on top of an initial scaffold from DeepSeek V4 Flash Free via OpenCode. I've tested it as thoroughly as I know how to, and I run it on my own production instance.

I know just enough programming to know how much I absolutely DO NOT know. I cannot review code that is THIS complex. I cannot run security tests or foresee how problematic it is going to be to maintain in the future.

Every release passes upstream's full CI (PHPUnit on MySQL, PostgreSQL and SQLite, Psalm, php-cs-fixer, ESLint, Stylelint, REUSE) and I test each feature by hand before it goes anywhere near my own data.

So far I haven't managed to break anything.

### ⚠️ That said: YMMV, and if you intend to deploy this, please please please have backups — because you never know what will go wrong when, where, how, and why.

Database migrations here **add columns and tables to your Cospend data**. They are written to be idempotent and none of them drop or rewrite anything, but "written to be" is not "proven to be" on *your* instance, with *your* data, on *your* database engine.

**There is no support, no warranty, and no promise that any of this will be maintained.** If it eats your expense history, you get to keep both pieces. Take a backup first. Take a backup first. Take a backup first. Take a back...

---

## What's different from upstream

| <sub><sup>Obligatory Emoji</sub></sup> | Feature | Status |
|---|---|---|
| 🧮 | **Cross-project balances & settlement**: aggregate balances across several projects & get one settlement plan covering all of them, with multi-currency support | [PR #396](https://github.com/julien-nc/cospend-nc/pull/396)|
| 🙈 | **Hide my own balance**: an option to keep your own balance out of the balance & settlement views | [PR #400](https://github.com/julien-nc/cospend-nc/pull/400) |
| 🏷️ | **Auto-categorise bills by title**: per-project `title → category` mappings, applied when a bill is created or edited without a category. Mappings build themselves up as you work, & can be applied retroactively or copied between projects | [PR #406](https://github.com/julien-nc/cospend-nc/pull/406) |
| 💳 | **Default payment mode per category**: each category can define a payment mode that new bills inherit. Chains with the above: a bill categorised from its title also gets that category's payment mode | fork only, for now |
| 🩹 | **Fixes not yet upstream**: qualified SQL columns in bill queries; the legacy `payment_mode` column derived from the correct project | fork only, for now |

Everything else is upstream's, tracked closely. See [CHANGELOG.md](CHANGELOG.md) for what landed when.

### A note on the open PRs

Three of the features above are proposed upstream and are waiting on review. If they merge, they leave this fork and become part of Cospend proper (which is the goal). Until then, this fork is the only place to get them, and it will keep following upstream's `main`.

---

## Installing

There is no app store release. Grab a
[release archive](https://github.com/AkshayRao27/cospend-nc/releases), extract it into your Nextcloud `apps/` (or `custom_apps/`) directory as `cospend`, and enable it:

```bash
occ app:enable cospend
occ upgrade          # runs the database migrations
```

Requires the Nextcloud versions upstream supports (currently 33–36). If you already run upstream Cospend, this replaces it in place and keeps your data (but see the disclaimer about backups, which applies with particular force to that sentence).

To go back to upstream Cospend, install it over this one. The extra columns this fork adds should theoretically be ignored by upstream's code, but I have not tested this.

## Versioning

Fork releases bump **past** the upstream version they're based on, so `4.1.4` here is upstream `4.1.2` plus everything in the table above. That keeps Nextcloud's upgrade path sane and makes it obvious which upstream release a build corresponds to.

## Branches

| Branch | |
|---|---|
| `personal-prod` | What I actually run. Upstream + every feature above + fork-only fixes |
| `upstream` | A clean mirror of upstream's `main` |
| one branch per feature | Each tracks an open PR and stays rebased on upstream |

## Contributing

Please send Cospend bug reports and feature requests
[upstream](https://github.com/julien-nc/cospend-nc/issues) - they belong there, not here. Issues specific to something in the table above are welcome in this repo, but I'm not sure when and if I will get around to fixing them if they don't directly affect my workflow.

## Licence

AGPL-3.0-or-later, same as upstream. See [COPYING](COPYING).

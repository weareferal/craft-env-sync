# Craft Env Sync plugin for Craft CMS 3.x

Backup and restore your database and volume assets across environments from the comfort of the Craft Control Panel.

![Craft Sync Logo](resources/img/plugin-logo.png)

## Requirements

- Craft CMS 3 or later on Linux or MacOS (untested on Windows as of yet)
- A private AWS S3 bucket for backups

## 💾 Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

   ```sh
   $ cd /path/to/project
   ```

2. Then tell Composer to load the plugin:

   ```sh
   $ composer require weareferal/env-sync
   ```

3. In the Control Panel, go to Settings → Plugins and click the *Install* button for Craft Env Sync.

## 🏔 Overview

Craft Env Sync is a plugin that makes it possible to sync your database and volume assets across numerous environments from the comfort of the Craft Control Panel. This makes it much easier to move your site from local development to staging and onto production.

![Craft Env Sync Overview](resources/img/overview.png)

This plugin uses AWS S3 (more providers to come soon) as the "single source of truth" for all site DB/volume asset backups. It provides an easy-to-configure settings page as well as an interface in Craft's "utilties" section to:

- Back up DB/volume assets locally
- Push backups to S3
- Pull backups from S3
- Restore backups

For more information read our blog post on ["Syncing your DB and assets across environments in Craft 3"](https://weareferal.com/tldr/syncing-your-db-and-assets-across-environments-in-craft-3/) or get in touch at [timmy@weareferal.com](mailto:timmy@weareferal.com)

This plugin is inspired by [Andrew Welsch's `craft-scripts` library](https://github.com/nystudio107/craft-scripts) who also [has a great blog post on syncing you DB and assets in Craft](https://nystudio107.com/blog/database-asset-syncing-between-environments-in-craft-cms).

## 🔧 Configuration

Configuration is done through the dedicated "Sync" settings panel. 

### Provider

![Craft Env Sync Setting Screenshot](resources/img/settings-screenshot.png)

The details entered here correspond to your AWS S3 account and bucket that you want to use for backups. It's recommended to set up a new IAM user that has programmatic access (meaning via a acces/secret key) to a private S3 bucket.

Once you have set this bucket up, you can either enter your AWS S3 details directly into the setting page, or you can use environment variables via your `.env` file (this is the recommended approach as seen in the screenshot above). This latter approach is more portable and secure as it prevents any private access/secret key values being included in files that you might commit to Github. Furthermore is means these variables can be reused in other plugins etc.

Here is an example portion of a  `.env` file:

```sh
...

AWS_ACCESS_KEY = 
AWS_SECRET_KEY = 
AWS_REGION = "us-west-2"
AWS_BUCKET_NAME = "feral-backups"
AWS_BUCKET_PREFIX = "craft-backups/my-site"
```

## 💻 Usage

### Control Panel

![Craft Env Sync Utilities Screenshot](resources/img/utilities-screenshot.png)

Once you have entered your settings variables you should be able to use the *Sync* tab on the *Utilities* section of the control panel.

There are two broad sections: one for the database and one for volume assets. Each section has four options to create a local backup, push that local backup to S3, pull all remote backups _from_ S3 and finally to restore a particular backup.

### Command Line

There are also console commands available for creating, pushing and pulling backups:

```sh
- env-sync/database                         Sync database backups
    env-sync/database/create                Create a local database backup
    env-sync/database/prune                 Prune database backups
    env-sync/database/pull                  Pull remote database backups from cloud
    env-sync/database/push                  Push local database backups to cloud

- env-sync/volume                          Sync volumes backup
    env-sync/volume/create                 Create a local volumes backup
    env-sync/volume/prune                  Prune volume backups
    env-sync/volume/pull                   Pull remote volume backups from cloud
    env-sync/volume/push                   Push local volume backups to cloud
```

For example:

```sh
./craft env-sync/database/create
```

These commands can be used alongside cron or your deployment scripts to automatically/periodically create backups.

## 📝 Functionality

![Image of backups](resources/img/backup-screenshot.png)

All local backups are stored in the existing `storage/backups` folder that Craft uses for its own database backup script.

- Database backups are created in a similar manner to the native Craft backup utility. In fact, the plugin uses this script behind-the-scenes, it just uses a slightly different naming scheme.
- For volume assets backups, we simply create a versioned zip file containing the handles of all volume assets currently saved in the system.

All backups have the following filename structure:

```sh
my_site_dev_200202_200020_yjrnz62yj4_v3.3.20.1.sql
```

Which includes:

- Your site name
- Your current environment
- Date & time of backup
- Random 10 character string
- Craft version

It's important not to manually rename these files as the plugin relies on this structure.

To create new backups and push/pull backups to the cloud you can use the "Sync" utility

### Queue

You can choose to use the Craft queue to perform create, push and pull operations from the CP utilities section. To enable use of the queue, toggle the "Use Queue" setting.

#### Control Panel errors

When not using the queue, if there is an issue pulling/pushing a backup you will get feedback (an alert box). You won't get the same feedback when using the queue. Instead it will look like the operation has been successful. To see if the operation was actually successul you'll need to check the queue manually.

#### CLI commands and the queue

The CLI commands ignore the queue setting. In other words, they will always run synchrously. This is by design as it's likely you will want to see the results of these operations if they are part of your crontab or deployment script.

### Deleting/Pruning old backups

![Pruning settings](resources/img/pruning-screenshot.png)

Env Sync supports pruning/deleting of old backups. To enable this feature toggle the "Prune Backup" setting. When you toggle this setting you will see a number of inputs for controlling the number of backups to be retained for a number of backup periods: daily, weekly, monthly, yearly. By default Env Sync will keep:

- The 14 most recent daily backups
- The earliest backups of the 4 most recent weeks
- The earliest backups of the 6 most recent months
- The earliest backups of the 3 most recent years

When enabled, backups will be pruned whenever a new backup is created via the Control Panel. Backups will be pruned independently. In other words, if you create a database backup, only the old database backups will be deleted, not the volume backups. You can also prune database or volume backups independently on the command line:

```sh
./craft env-sync/database/prune
./craft env-sync/volume/prune
```

Bear in mind these commands _will_ respect the settings. In other words, you won't be able to prune backups via the command line if the setting in the control panel is disabled.

Also note that the `./craft env-sync/[database|volume]/create` command does not automatically run the `prune` command (unlike the control panel).

### Automating backups

There is no built-in way to automate backups (periodic queue jobs are't something supported by the Craft queue). That said, it's very easy to automate backups either via cron or your own deployment script (if using Forge for example).

#### Cron

Here is an example daily cron entry to backup and prune daily at 01:00:

```cron
00 01 * * * /path/to/project/craft env-sync/database/prune
05 01 * * * /path/to/project/craft env-sync/database/create
10 01 * * * /path/to/project/craft env-sync/volume/prune
15 01 * * * /path/to/project/craft env-sync/volume/create
```

### Providers

The plugin has been built with the ability to add new providers relatively easily using a backend system. Currently the only provider available is AWS S3.

If you require another provider, please leave an issue on Github.

## 🚨 Troubleshooting

If you are getting errors while pushing/pulling/creating/restoring or pruning, the first thing to check is the Craft logs at `storage/logs/web.log`.

### Credentials

For pushing and pulling, the most likely issue is with your credentials, so double check that those are OK.

### Memory limit creating volumes

When you create a new volume backup, it's possible that your PHP memory limit will cause the process to crash. Make sure your memory limit is > than the volume folder you are trying to backup.

## 📞 Credits and support

Brought to you by [Feral](https://weareferal.com). Any problems email [timmy@weareferal.com](mailto:timmy@weareferal.com?subject=Craft%20Env%20Sync%20Question) or leave an issue on Github.

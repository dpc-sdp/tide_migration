# Tide Migration

Central repository to hold all migrations. 

## CONTENTS OF THIS FILE
* Introduction
* Requirements
* Usage

## INTRODUCTION
The Tide Migration module provides the functionality to import content from various sources to SDP sites.

## REQUIREMENTS
* [Migrate Source UI](https://drupal.org/project/migrate_source_ui)

## USAGE
* Run migration via UI

  - Navigate to: admin/config/development/configuration/single/import

  - Select:
    - Configuration type= "Simple configuration"

  - Enter
    - Configuration name = "migrate_plus.migration.{{migration_id}}" (File name without extension)

  - Paste
    - Paste your configuration here: content from file migrate_plus.migration.{{migration_id}}.yml

  - Click the "Import" button.

* Run migration via Drush command:

```
drush migrate-import { MIGRATION ID } --execute-dependencies
```

* Reset the status of all migrations:

```
drush migrate-reset-status { MIGRATION ID }
```

* Rollback content of all migrations:

```
drush migrate-rollback { MIGRATION ID }
```

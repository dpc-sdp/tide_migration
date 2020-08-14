# Tide Migration

Central repository to hold all migrations. 

## CONTENTS OF THIS FILE
* Introduction
* Requirements
* Usage

## INTRODUCTION
The Tide Migration module provides the functionality to import content from content.vic.gov.au to any other Drupal 8 Website.

## REQUIREMENTS
* [Migrate Plus](https://drupal.org/project/migrate_plus)
* [Migrate File](https://drupal.org/project/migrate_file)
* [Migrate Tools](https://drupal.org/project/migrate_tools)

## USAGE
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

### Settings
```config/tide_migration.settings.yml``` holds variables that can be configured per consumer project. 
Simply copy the file to config/sync/ folder in the project and define variables to be overridden.

#### Reserved Variable
Reserved variable allows you to define variable in the setting file and use them in filters.

#### Define Reserved Variable

Below are the steps involved to define a new reserved variable

- In ```config/tide_migration.settings.yml``` create a new variable with a value ```test: 123```
- In ```src/Enum/ReservedConfigNameEnum``` add a new constant i.e. ```const TEST = 'test'``` and add it to ```getReservedNames()```
- Go into a migration file you have created and use it in your filters i.e. ```value: '@site'```
- The functionality can be extended to be used in more locations by following an example below:
```
if (strpos($filter, '@') !== FALSE) {
  if ($this->reservedConfigNameEnum->validate(ltrim($filter, '@')) === TRUE) {
    $value = $this->configFetch->fetchValue(ltrim($filter, '@'));
  }
}
```
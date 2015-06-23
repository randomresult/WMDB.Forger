TYPO3 CMS FORGER

## Setup Notes

### Install

Use `composer require wmdb/forger` and `composer update` to install the package.

### Supply the necessary routes

Project Routes.yaml
<pre>
-
  name: 'Forger'
  uriPattern: '<ForgerRoutes>'
  defaults:
    '@format': 'html'
  subRoutes:
    ForgerRoutes:
      package: WMDB.Forger
##
# Flow subroutes
#

-
  name: 'Flow'
  uriPattern: '<FlowSubroutes>'
  defaults:
    '@format': 'html'
  subRoutes:
    FlowSubroutes:
      package: TYPO3.Flow
</pre>

### Application startup:

* Create an index in Elasticsearch called „forger“. This has to be done manually.
* Call setup:elasticmapping to create a approriate mapping and document types in Elasticsearch
* Call forgeimport:fullgerrit to import all reviews from gerrit (start with this, because it’s faster ;-))
* Call forgeimport:full to import all issues from forge (takes about 1.5 hrs)
* Add forgeimport:delta to CRON every 5 minutes
* Add forgeimport:gerritdelta to CRON every minute

## Functions

The Flow App has the following commandControllers:

<pre>
forgeimport:test            Test command - imports Forge Issue #63618
forgeimport:delta           Imports the last 50 issues from Redmine
forgeimport:gerritdelta     Imports the last 35 reviews from Gerrit
forgeimport:full            Runs a full import of all issues, both open and closed
forgeimport:fullgerrit      Runs a full import of all reviews

setup:elasticmapping        Sets up the mapping for Elasticsearch

gerrit:abandoned            Generates a list of abandoned reviews with open issues in Redmine Wiki Syntax
</pre>

## Developing

### Prerequisites

* Switch to `Packages/Application/WMDB.Forger/Libraries/`
* Call `npm install` to install grunt and bower - obviously, nodejs needs to be installed :)
* Call `bower update` to fetch all frontend related dependencies
* Call `grunt` once to make sure the taskrunner runs fine

### Settings

* Make sure to set the Flow Setting WMDB.Forger.Slack.Url to `Void`, otherwise you will flood the Slack channel with messages


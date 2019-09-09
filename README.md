![#risk_series](pics/logo.png)

Small footprint SQLite / PHP financial risk time series and validation database application

## What is is
- an SQLite database with PHP REST frontend for financial time series and other data
- a simple HTTP / JavaScript web frontend for configuration, data management and analysis
- a number of fully automatised financial validation scripts

## Features
- auditable storage of time series data
- seamless handling of typical requirements around financial time series (non-business days, data gaps, multiple support points/maturities)
- automatic import of time series data provided by third-party applications

## Application structure
_index.html_ : Configuration, data management and analysis frontend

_index.php_ : PHP REST interface (main entry point)

_app/*_ : PHP REST interface program files

_data/data.sqlite_ : SQLite database file


_test/*_ : Files for testing

## how to test

Just run `./serve.sh` and access API via browser under 127.0.0.1 for testing.

## how to deploy

Includes Apache .htaccess file that redirects all API calls to the main index.php file. 
## REST API
<table>
<tr><th>Endpoint</th><th> HTTP Method</th><th> Parameters </th><th>Description</th></tr>
<tr><td>/</td><td> GET</td><td> none </td><td>returns app version number and creates database file under /data/data.sqlite if it does not exist.</td></tr>
<tr><td>/def</td><td> GET</td><td> none </td><td>Query all stored time series definitions as CSV file</td></tr>
<tr><td>/def/NAME</td><td> POST</td><td> none </td><td>Create or update time series definition</td></tr>
<tr><td>/def/NAME</td><td> DELETE</td><td> none </td><td>Delete time series definition and data for name NAME</td></tr>
<tr><td>/data</td><td> GET</td><td> FROM (YYYY-MM-DD), TO (YYYY-MM-DD), ASOF (YYYY-MM-DD HH:MM:SS.SSS while SSS, SS.SSS or HH:MM:SS.SSS are optional) </td><td>Query all time series data of all time series between FROM and TO, exclude data updated after ASOF</td></tr>
<tr><td>/data/NAME</td><td> GET</td><td> FROM (YYYY-MM-DD), TO (YYYY-MM-DD), ASOF (YYYY-MM-DD HH:MM:SS.SSS while SSS, SS.SSS or HH:MM:SS.SSS are optional) </td><td>Query all time series data of time series NAME between FROM and TO, exclude data updated after ASOF</td></tr>
<tr><td>/data/NAME</td><td> POST</td><td> none </td><td>Post new time series data in csv format</td></tr>
</table>



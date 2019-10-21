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

## How to test

Just run `./serve.sh` and access API via browser under 127.0.0.1 for testing.

## How to deploy

Includes Apache .htaccess file that redirects all API calls to the main index.php file. 
## REST API
### Endpoints
<table>
<tr><th>Endpoint</th><th> HTTP Method</th><th> Parameters </th><th>Description</th></tr>
<tr><td>/</td><td> GET</td><td> none </td><td>returns app version number and creates database file under /data/data.sqlite if it does not exist.</td></tr>
<tr><td>/def</td><td> GET</td><td> FORMAT (json|csv) where json is the default </td><td>Query all stored time series definitions as JSON or CSV filet</td></tr>
<tr><td>/def/NAME</td><td> POST</td><td> FILE as multipart/form-data encoded attribute </td><td>Create or update time series definition with file</td></tr>
<tr><td>/def/NAME</td><td> DELETE</td><td> none </td><td>Delete time series definition and data for name NAME</td></tr>
<tr><td>/dates</td><td> GET</td><td> FROM (YYYY-MM-DD), TO (YYYY-MM-DD), ASOF (YYYY-MM-DDTHH:MM:SS while THH:MM:SS is optional) </td><td>Query all dates where data is available</td></tr>
<tr><td>/data</td><td> GET</td><td> FROM (YYYY-MM-DD), TO (YYYY-MM-DD), ASOF (YYYY-MM-DDTHH:MM:SS while THH:MM:SS is optional) </td><td>Query all time series data of all time series between FROM and TO, exclude data updated after ASOF</td></tr>
<tr><td>/data</td><td> POST</td><td> FILE as multipart/form-data encoded attribute </td><td>Post new time series data in csv format file</td></tr>
<tr><td>/data/NAME</td><td> GET</td><td> FROM (YYYY-MM-DD), TO (YYYY-MM-DD), ASOF (YYYY-MM-DDTHH:MM:SS.SSS while THH:MM:SS.SSS is optional) </td><td>Query all time series data of time series NAME between FROM and TO, exclude data updated after ASOF</td></tr>
<tr><td>/data/NAME</td><td> POST</td><td> FILE as multipart/form-data encoded attribute </td><td>Post new time series data in csv format</td></tr>
</table>

### Format specification
Time series data is uploaded via POST requests and downloaded via GET requests in a tabular csv format as specified below.
 - Record separator: `;`
 - Line separator: `\r\n` (i.e., Windows line ending)
 - Date format: YYYY-MM-DD
 - Decimal separator: `.` (dot)
 
Table structure:
<table>
  <tr><td>NAME</td><td>TAG1</td><td>TAG2</td><td>...</td></tr>
  <tr><td>DATE1</td><td>VALUE DATE1 TAG1</td><td>VALUE DATE1 TAG2</td><td>...</td></tr>
  <tr><td>DATE2</td><td>VALUE DATE2 TAG1</td><td>VALUE DATE2 TAG2</td><td>...</td></tr>
  <tr><td>...</td><td>...</td><td>...</td><td>...</td></tr>
</table>

Example:
<pre>
    YIELD_CURVE;1D;1W;1M;3M
    2019-09-03;0.01;0.015;0.016;0.017
    2019-09-04;0.011;0.015;0.017;0.018
    2019-09-05;0.012;0.016;0.016;0.018
</pre>
When uploading new data on the endpoint /data/NAME, the NAME info in the first csv field is overrided and, consequently, may be left empty.

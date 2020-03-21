var app = angular.module('seriesapp', []); 

app.controller('main_ctrl', [ '$scope', '$http', function($scope, $http) {

    // authorization token
    $scope.auth={token : "" };
    
    // bindings
    $scope.queried_time_series={
        name: '',
        from: '',
        to: '',
        asof: ''
    }

    $scope.upload_def = {
        fullname: '',
        file: ''
    }

    $scope.upload_data = {
        fullname: '',
        file: ''
    }

    $scope.is_loading = false;
    $scope.name_is_queried = false;
    $scope.response = false;

    $scope.data_csv = ''; // variable for saving queried csv data 

    $scope.file_name = ''; // name of csv file for download 
    
    $scope.alert_group = {
        alert : false,
        alert_text : '',
        alert_class : ''
    }

    // show and download 
    $scope.showData = function(){
        $scope.closeResponse();
        $scope.is_loading = true;
        return $scope.getQueriedTimeSeries($scope.queried_time_series);      
    }

    $scope.downloadData = function(){
        $scope.closeResponse();
        // do query passing only_csv = true
        return $scope.getQueriedTimeSeries($scope.queried_time_series, true);  
    }

    $scope.downloadCsv = function(filename) {
        var url = window.URL.createObjectURL($scope.data_csv);
        var a = document.getElementById('download-series');
        a.download = filename;
        a.href = url;
        a.click();
    }

    $scope.showDefinitions = function() {
        $scope.closeResponse();
        $scope.is_loading = true;
        return $scope.getQueriedTimeSeriesDefinitions();
    }

    $scope.downloadDefinitions = function() {
        $scope.closeResponse();
        $scope.is_loading = true;
        return $scope.getQueriedTimeSeriesDefinitions(true);
    }

    $scope.closeResponse = function() {
        $scope.response = false;
        $scope.time_series_def_data = null;
        $scope.time_series_data = null;
    }

    $scope.closeUploadResult = function() {
        $scope.alert_group.alert = false;
    }

    
    $scope.getQueriedTimeSeriesDefinitions = function(only_csv = false) {
        var _baseUrl = '';
        var full_url = _baseUrl + 'def';
        full_url += '?FORMAT=CSV';  // get always CSV format

        /* I prefer to have always a csv response, in order to get
        savy data for a possible download also in case they are just shown in the browser */ 

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'text/csv',
                    // 'Content-Type': 'application/json',
                }
            }
        ).then(function (res, data, headers, status, config) {
            $scope.is_loading = false;
            $scope.time_series_def_data = Papa.parse(res.data).data;
            $scope.time_series_def_data.pop();  /* to get rid of last array line, which is empty */
            $scope.data_csv = new Blob([res.data], {type: 'text/csv'});
            $scope.file_name = 'time-series-definitions.csv';
            
            if (only_csv) {
                $scope.response = false;
                return $scope.downloadCsv('time-series-definitions.csv');   
            } else {
                $scope.response = true;
            }
            
        }).catch(function(res) {
            $scope.is_loading = false;
            // console.log('return code: ' + res.status);

            // show error in the alert bar
            $scope.alert_group.alert = true;
            $scope.alert_group.alert_class = "alert-danger";
            $scope.alert_group.alert_text = "Error: Unable to download time series definitions. Return code: " + res.status;
        });   
    }

    $scope.getQueriedTimeSeries = function(query, only_csv = false) {
        var _baseUrl = '';
        var full_url = _baseUrl + 'data';
        $scope.name_is_queried = false;

        if (query.name) {
            $scope.name_is_queried = true;
            $scope.file_name = 'time-series.csv';
        } else {
            $scope.file_name = 'time-series-names.csv';
        }
        if (query.name || query.from || query.to || query.asof) { 
            full_url += '?';
            full_url += query.name? 'NAME=' + query.name + '&': '';
            full_url += query.from? 'FROM=' + query.from + '&': '';
            full_url += query.to? 'TO=' + query.to + '&': '';
            full_url += query.asof? 'ASOF=' + query.asof: '';
        } else {
            full_url += '?FORMAT=CSV';
        }

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    // 'Content-Type': 'application/json',
                    'Content-Type': 'text/csv',
                }
            }
        ).then(function (res, data, headers, status, config) {
            if (only_csv == false) {
                $scope.is_loading = false;
                $scope.time_series_data = Papa.parse(res.data).data;
                if ($scope.name_is_queried) $scope.time_series_data.pop(); /* to get rid of last array line, which is empty */
            }
             // save file for csv download
            $scope.data_csv = new Blob([res.data], {type: 'text/csv'});
            
            if (only_csv) {
                $scope.response = false;
                if ($scope.name_is_queried) {
                    return $scope.downloadCsv('time-series.csv');    
                } else {
                    return $scope.downloadCsv('time-series-names.csv');  
                }    
            } else {
                $scope.response = true;
            }

        }).catch(function(res) {
            $scope.is_loading = false;
            // console.log('return code: ' + res.status);
            
            // show error in the alert bar
            $scope.alert = true;
            $scope.alert_class = "alert-danger";
            $scope.alert_text = "Error: Unable to download time series data. Return code: " + res.status;     
        });        
    }

    // upload
    $scope.sendDef = function(url) {
        return $scope.sendDataForm(url, $scope.upload_def);
    }

    $scope.sendData = function(url) {
        return $scope.sendDataForm(url, $scope.upload_data);
    }

    $scope.sendDataForm = function(url, data) {
        var fd = new FormData();
        var form_data = data;
		for (var key in form_data) {
			fd.append(key, form_data[key]);
        }

        $http(
            {
                method: 'POST',
                url: url,
                data: fd,
                headers: {
                    // 'Content-Type': 'application/json',
                    'Authorization': $scope.auth.token
                }
            }
        ).then(function (res, data, headers, status, config) {
            alert("then");
            if(res.status === 200) {
                $scope.alert_group.alert = true;
                $scope.alert_group.alert_class = "alert-info";
                $scope.alert_group.alert_text = res.statusText;
                var result = res.data;

                if (result.success){
                    $scope.alert_group.alert = true;
                    $scope.alert_group.alert_class = "alert-info";
                    $scope.alert_group.alert_text = "Data upload was successful. " + result.num_success + " records updated.";
                    
                }else{
                    $scope.alert_group.alert = true;
                    $scope.alert_group.alert_class = "alert-danger";
                    if(result.num_invalid>0){
                        $scope.alert_group.alert_text = "Error: all " + result.num_failure + " records were invalid.";	
                    }else{
                        $scope.alert_group.alert_text = result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
                    }
    
                } 
            }  else {  /* I think this part is superfluous because any error is cought in the catch clause */
                $scope.alert_group.alert_dom = true;
                $scope.alert_group.alert_class = "alert-danger";
                $scope.alert_group.alert_text = "Error: an unexpected error occurred.";
            }
        }).catch(function(res) {
            if(res.status === 401) {
                $scope.alert_group.alert = true;
                $scope.alert_group.alert_class = "alert-danger";
                $scope.alert_group.alert_text = "Error: upload not authorized.";    
            } else if (res.status === 400) {
                var result = res.data;
                $scope.alert_group.alert = true;
                $scope.alert_group.alert_class = "alert-danger";			
                $scope.alert_group.alert_text = result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
            }else {
                $scope.alert_group.alert = true;	
                $scope.alert_group.alert_class = "alert-danger";
                $scope.alert_group.alert_text = "Could not send data to server. Error " + res.status;
            }
            
        }); 

    }

}]);



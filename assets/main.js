var app = angular.module('seriesapp', []); 

app.controller('main_ctrl', [ '$scope', '$http', function($scope, $http) {

    // authorization token
    $scope.auth={token : "" };
    
    // bindings
    $scope.time_series={
        available: [],
        name: '',
        from: '',
        to: '',
        asof: ''
    }

    $scope.res = {
        time_series_def_data : null,
        time_series_data : null,
        is_loading : false,
        alert_text : null,
        alert_error : false
    }

    // show and download 
    $scope.showData = function(){
        $scope.resetResults();
        $scope.is_loading = true;
        return $scope.getTimeSeries();      
    }

    $scope.showDefinitions = function() {
        $scope.resetResults();
        $scope.is_loading = true;
        return $scope.getTimeSeriesDefinitions();
    }

    $scope.resetResults = function() {
            $scope.res.time_series_def_data=null;
            $scope.res.time_series_data=null;
            $scope.res.alert_text=null;
            $scope.res.alert_error=false;
            $scope.res.is_loading=false;
    }

    $scope.getAvailableTimeSeries = function() {
        var _baseUrl = '';
        var full_url = _baseUrl + 'data';
        full_url += '?FORMAT=JSON';


        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'application/json',
                }
            }
        ).then(function (res, data, headers, status, config) {
            $scope.time_series.available = res.data;            
        }).catch(function(res) {
            // show error in the alert bar
            $scope.res.alert_error = true;
            $scope.res.alert_text = "Error: Unable to download time series names. Return code: " + res.status;
        });   
    }
    
    $scope.getTimeSeriesDefinitions = function() {
        var _baseUrl = '';
        var full_url = _baseUrl + 'def';
        full_url += '?FORMAT=JSON';  // get always JSON format

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'application/json',
                }
            }
        ).then(function (res, data, headers, status, config) {
            $scope.res.is_loading = false;
            $scope.res.time_series_def_data = res.data;
            
        }).catch(function(res) {
            $scope.is_loading = false;
            // show error in the alert bar
            $scope.res.alert_error = true;
            $scope.res.alert_text = "Error: Unable to download time series definitions. Return code: " + res.status;
        });   
    }

    $scope.getTimeSeries = function() {
        var query=$scope.time_series;        
        if (!query.name) return;
        var _baseUrl = '';
        var full_url = _baseUrl + 'data/' + query.name + '?FORMAT=CSV';

        if(query.from) full_url += '&FROM=' + query.from;
        if(query.to) full_url += '&TO=' + query.to;
        if(query.asof) full_url += '&ASOF=' + query.asof;

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'text/csv',
                }
            }
        ).then(function (res, data, headers, status, config) {
            
            $scope.res.is_loading = false;
            $scope.res.time_series_data = Papa.parse(res.data).data;

        }).catch(function(res) {
            $scope.res.is_loading = false;
            // console.log('return code: ' + res.status);
            
            // show error in the alert bar
            $scope.alert_error = true;
            $scope.alert_text = "Error: Unable to download time series data. Return code: " + res.status;     
        });        
    }

    $scope.sendData = function(kind) {
        var form=document.getElementById('form_' + kind);
        var fd = new FormData(form);


        $http(
            {
                method: 'POST',
                url: kind,
                data: fd,
                headers: {
                   'Authorization': $scope.auth.token,
                   'Content-Type': undefined
                 }
            }
        ).then(function (res, data, headers, status, config) {
            if(res.status === 200) {               
                var result = res.data;
                if (result.success){
                    $scope.res.alert_error =  false;
                    $scope.res.alert_text = "Data upload was successful. " + result.num_success + " records updated.";
                    
                }else{
                    
                    $scope.res.alert_error = true;
                    if(result.num_invalid>0){
                        $scope.res.alert_text = "Error: all " + result.num_failure + " records were invalid.";	
                    }else{
                        $scope.res.alert_text = result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
                    }
    
                } 
            }  else {
                $scope.res.alert_error = true;
                $scope.res.alert_text = "Error: an unexpected error occurred.";
            }
        }).catch(function(res) {
            if(res.status === 401) {
                
                $scope.res.alert_error = true;
                $scope.res.alert_text = "Error: upload not authorized.";    
            } else if (res.status === 400) {
                result=res.data;
                $scope.res.alert_error = true;			
                $scope.res.alert_text = result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
            }else {
                	
                $scope.res.alert_error = true;
                $scope.res.alert_text = "Could not send data to server. Error " + res.status;
            }
            
        }); 

    }

    $scope.getAvailableTimeSeries();

}]);



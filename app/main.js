var app = angular.module('seriesapp', []); 

app.controller('main_ctrl', [ '$scope', '$http', function($scope, $http) {

    // authorization token
    $scope.token = get_token();
    
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

    $scope.no_time_series_data = false;
    $scope.is_loading = false;
    $scope.name_is_queried = false;
    $scope.response = false;

    $scope.getQueriedTimeSeriesDefinitions = function() {
        var _baseUrl = '';
        var full_url = _baseUrl + 'def';

        // to show errors on the alert bar
        var alert=document.getElementById("upload-result");
        var alert_text=document.getElementById("upload-result-text");

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': $scope.token
                }
            }
        ).then(function (res, data, headers, status, config) {
            // 'records' assumes that server provides a json with key "records" and value a time series array
            $scope.is_loading = false;
            $scope.time_series_def_data = res.data.records;
            $scope.response = true;
            
            if (res.data.records.resp) {
                if (res.data.records.resp.failure) {
                    $scope.response = false;
                    alert.classList.add("alert-danger");
                    alert.classList.remove("alert-info");
                    alert_text.innerHTML="Error: Request not authorized";
                    alert.classList.add("show");
                    alert.classList.remove("collapse");	
                }    
            } 
        }).catch(function(res) {
            $scope.is_loading = false;
            console.log('return code: ' + res.status);

            // show error inb the alert bar
            alert.classList.add("alert-danger");
			alert.classList.remove("alert-info");
            alert_text.innerHTML="Error: Unable to download time series definitions. Return code: " + res.status;
            alert.classList.add("show");
            alert.classList.remove("collapse");	
        });   
    }

    $scope.getQueriedTimeSeries = function(query) {
        var _baseUrl = '';
        var full_url = _baseUrl + 'data';

        query.name? $scope.name_is_queried = true: $scope.name_is_queried = false;
        if (query.name || query.from || query.to || query.asof) full_url += '?';
        
        full_url += query.name? 'NAME=' + query.name + '&': '';
        full_url += query.from? 'FROM=' + query.from + '&': '';
        full_url += query.to? 'TO=' + query.to + '&': '';
        full_url += query.asof? 'ASOF=' + query.asof: '';

        // to show errors on the alert bar
        var alert=document.getElementById("upload-result");
	    var alert_text=document.getElementById("upload-result-text");

        $http(
            {
                method: 'GET',
                url: full_url,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': $scope.token
                }
            }
        ).then(function (res, data, headers, status, config) {
            // 'records' assumes that server provides a json with key "records" and value a time series array
            $scope.is_loading = false;
            $scope.time_series_data = res.data.records;
            $scope.response = true;

            if (res.data.records.resp) {
                if (res.data.records.resp.failure) {
                    $scope.response = false;
                    alert.classList.add("alert-danger");
                    alert.classList.remove("alert-info");
                    alert_text.innerHTML="Error: Request not authorized";
                    alert.classList.add("show");
                    alert.classList.remove("collapse");	
                }    
            } 
            console.log(res.data.records);  // just for testing
        }).catch(function(res) {
            $scope.is_loading = false;
            console.log('return code: ' + res.status);
            
            // show error inb the alert bar
            alert.classList.add("alert-danger");
            alert.classList.remove("alert-info");
            alert_text.innerHTML="Error: Unable to download time series data. Return code: " + res.status;
            alert.classList.add("show");
            alert.classList.remove("collapse");	        
        });        
    }

    $scope.downloadData = function(){
        $scope.no_time_series_data = true;
        $scope.is_loading = true;
        return $scope.getQueriedTimeSeries($scope.queried_time_series);      
    }

    $scope.downloadDefinitions = function() {
        $scope.no_time_series_data = true;
        $scope.is_loading = true;
        return $scope.getQueriedTimeSeriesDefinitions();
    }

    $scope.send_def = function(url) {
        return send_xml_data(url, $scope.upload_def, $scope.token);
    }

    $scope.send_data = function(url) {
        return send_xml_data(url, $scope.upload_data, $scope.token);
    }

    $scope.close_response = function() {
        $scope.response = false;
        $scope.time_series_def_data = null;
        $scope.time_series_data = null;
    }

    $scope.close_upload_result = function() {
        var alerts = document.getElementById("upload-result");
        alerts.classList.add("collapse");
        alerts.classList.remove("show");
    }

}]);



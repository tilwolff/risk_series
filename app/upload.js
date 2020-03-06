function send_xml_data(url, form_data, token){

    var req=new XMLHttpRequest();
    
    var fd = new FormData();
        for (var key in form_data) {
            fd.append(key, form_data[key]);
        }

	var alert=document.getElementById("upload-result");
	var alert_text=document.getElementById("upload-result-text");
	
	req.addEventListener('load', function(evt){
		if(req.status===200){
			var result=JSON.parse(req.responseText);
			if (result.success){
				alert.classList.add("alert-info");
				alert.classList.remove("alert-danger");
				alert_text.innerHTML="Data upload was successful. " + result.num_success + " records updated.";
			}else{
				alert.classList.add("alert-danger");
				alert.classList.remove("alert-info");
				if(result.num_invalid>0){
					alert_text.innerHTML="Error: all " + result.num_failure + " records were invalid.";	
				}else{
					alert_text.innerHTML=result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
				}

			}
		}else if(req.status===400){
			var result=JSON.parse(req.responseText);
			alert.classList.add("alert-danger");
			alert.classList.remove("alert-info");				
			alert_text.innerHTML=result.error_message || "Error: an error occurred, but the error message could not be retrieved.";
		}else if (req.status===401) {
            alert.classList.add("alert-danger");
            alert.classList.remove("alert-info");
            alert_text.innerHTML="Error: upload not authorized.";
        }else {
				alert.classList.add("alert-danger");
				alert.classList.remove("alert-info");
				alert_text.innerHTML="Error: an unexpected error ocurred.";
		}
		alert.classList.add("show");
		alert.classList.remove("collapse");
	});

	
	req.addEventListener('error', function(evt){
			alert.classList.add("alert-danger");
			alert.classList.remove("alert-info");
			alert_text.innerHTML="Error: could not send data to server.";
			alert.classList.add("show");
			alert.classList.remove("collapse");
	});

    
    req.open('POST', url);

    req.setRequestHeader("Authorization", token);
    
    req.send(fd);
    
    
}
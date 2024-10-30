function ytvalfunc(id){
	var vale = document.getElementById(id).value;
	var matches = vale.match(/watch\?v=([a-zA-Z0-9\-_]+)/);
		if(matches){
		return true;
	}else {
		alert('Not valid Youtube Watch URL');
		document.getElementById(id).value = "";
		return false;
	}
}	
function deleteRow(id){
	var y = confirm("Are you Sure you want to delete this??");
	if(y == true){ 
		var elem = document.getElementById("a"+id);
		elem.remove(); 
	}
	else{
		return false;
	}				
}

function deleteVideo(id){
	var y =confirm("Are you Sure you want to delete this video?");
	if(y == true){ 
	var elem = document.getElementById("ab"+id);
		elem.remove(); 
	}
	else{
		return false;
	}				
}

function appendRow()
{	
	var x=2;
	var d = document.getElementById('div');
	d.innerHTML += "<br/><div id='a"+x+"'><label>Youtube Url</label> <input type='text' id='tst"+x+"' name='up_video[]' size='45' value='' placeholder='Youtube video watch url' onchange='ytvalfunc(this.id)'><a href='javascript:void();' onclick='deleteRow("+x+");'>Delete</a></div>";
	x++;
}
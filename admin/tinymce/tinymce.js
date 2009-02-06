function init() {
	tinyMCEPopup.resizeToInnerSize();
}


function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function ProjectManagerInsertLink() {
	
	var tagtext;
	
	var project = document.getElementById('project_panel');
	var dataset = document.getElementById('dataset_panel');
	var search = document.getElementById('search_panel');
	
	// who is active ?
	if (project_.className.indexOf('current') != -1) {
		var projectId = document.getElementById('projects').value;
		var template = getCheckedValue(document.getElementsByName('project_display'));
		var cat = document.getElementById('list_cat_id').value;
	
		if (projectId != 0) {
			if (template == 'gallery') {
				tagtext = "[projects
			tagtext = "[dataset_list project_id=" + projectId + " output=" + showtype + " cat_id=" + cat + "]";
		} else {
			tinyMCEPopup.close();
		}
	}
	
	if (gallery.className.indexOf('current') != -1) {
		var projectId = document.getElementById('gallery_projects').value;
		var numCols = document.getElementById('num_cols').value;
		var cat = document.getElementById('gallery_cat_id').value;
		
		if (projectId != 0)
			tagtext = "[dataset_gallery project_id=" + projectId + " num_cols=" + numCols + " cat_id=" + cat + "]";
		else
			tinyMCEPopup.close();
	}
	
	if (dataset.className.indexOf('current') != -1) {
		var datasetId = document.getElementById('datasets').value;
		if (datasetId != 0)
			tagtext = "[dataset id=" + datasetId + "]";
		else
			tinyMCEPopup.close();
	}
	
	if (search.className.indexOf('current') != -1) {
		var projectId = document.getElementById('search_projects').value;
		var template = getCheckedValue(document.getElementsByName('search_display'));
		
		if (projectId != 0)
			tagtext = "[dataset_search project_id=" + projectId + " template=" + template + "]";
		else
			tinyMCEPopup.close();
	}
	
	if(window.tinyMCE) {
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		//Peforms a clean up of the current editor HTML. 
		//tinyMCEPopup.editor.execCommand('mceCleanup');
		//Repaints the editor. Sometimes the browser has graphic glitches. 
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}
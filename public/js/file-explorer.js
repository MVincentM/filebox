var currentFolder = {
	id: null,
	title: "Home"
}

function refresh(){
	build(currentFolder.id);
}

function build(id){
	jQuery.ajax({
		method: 'POST',
		url: "/get/templates/"+id,
		dataType: "JSON",
		success: function(data){
			var fs = data;

			if(fs.length > 0){
				var html = '';
				for(var i=0; i<fs.length; i++){
					var f = fs[i];

					html += '<tr class="'+f.type+'" id="'+f.id+'" title='+f.name+' path="'+f.path+f.name+'">';
					html += '<th scope="row">'+ (i+1) +'</th>';
					html += '<td>'+ f.name +'</td>';
					html += '<td>'+ f.lastUpdate +'</td>';
					html += '<td>'+ (f.version ? f.version : '') +'</td>';
					html += '<td>'+ f.lastUpdator +'</td>';
					html += '<td>'+ f.creator +'</td>';
					html += '</tr>';
				}

				jQuery('.empty-table').addClass('hide');
				jQuery('.file-explorer tbody').html(html);
			}
			else{
				jQuery('.empty-table').removeClass('hide');
				jQuery('.file-explorer tbody').html('');
			}

			jQuery('.file-explorer tr.folder').click(function(event){
				var target = event.currentTarget;
				currentFolder = {
				 	id: target.getAttribute('id'),
				 	title: target.getAttribute('title')
				}
				refresh();	
			});

			jQuery('.add-folder').click(function(){
				prompt('Add a folder to '+currentFolder.title, 'Enter the name of the new folder:', function(folderName){
					jQuery.ajax({
						method: 'POST',
						url: '/add/folder/'+folderName+'/in/'+currentFolder.id,
						success: refresh
					})
				});
			});

			jQuery('.add-file').click(function(){
				
			});
		}
	}); // requete ajax pour recupérer les folders/fichiers
	
}

function prompt(title, message, onOk){

	if(jQuery('.prompt-modal')[0] == null){
		html = '<div class="modal prompt-modal fade" role="dialog"><div class="modal-dialog">';
		html += '<div class="modal-content"><div class="modal-header">';
	    html += '<h4 class="modal-title"></h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>';
	    html += '<div class="modal-body">';

	    // Body
	    html += '<div class="modal-message"></div>'
	    html += '<input class="form-control modal-input">';

	    html += '</div><div class="modal-footer"><button type="button" class="btn btn-info btn-ok" data-dismiss="modal">Ok</button><button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button></div></div></div></div>';

	    jQuery('body').append(html);

	    jQuery('.prompt-modal .btn-ok').click(function(){
	    	var value = jQuery('.prompt-modal .modal-input').val();
	    	onOk(value);
	    });
	}

	jQuery('.prompt-modal .modal-title').text(title);
	jQuery('.prompt-modal .modal-message').text(message);
	jQuery('.prompt-modal .modal-input').val('');

    jQuery('.prompt-modal').modal('show');
}
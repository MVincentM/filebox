var currentFolder = {
	id: 0,
	title: "Home"
}

$('.goHome').click(function(){
	currentFolder = {
		id: 0,
		title: "Home"	
	}
	refresh();
	$('.breadcrumbs :not(.fixed)').remove();
});

function refresh(){
	clearTable();
	build(currentFolder.id);
}

function build(id){
	$.ajax({
		method: 'POST',
		url: "/get/templates/"+id,
		dataType: "JSON",
		success: function(data){
			var fs = data;

			if(fs.length > 0){
				var html = '';
				for(var i=0; i<fs.length; i++){
					var f = fs[i];
					var icon = '/icons/'+f.type+'.png';

					html += '<tr class="'+f.type+'" id="'+f.id+'" title="'+f.name+'" path="'+f.path+f.name+'">';
					html += '<th scope="row"><img src="'+icon+'"></th>';
					html += '<td>'+ f.name +'</td>';
					html += '<td>'+ f.lastUpdate +'</td>';
					html += '<td>'+ (f.version ? f.version : '') +'</td>';
					html += '<td>'+ f.lastUpdator +'</td>';
					html += '<td>'+ f.creator +'</td>';
					html += '</tr>';
				}

				$('.empty-table').addClass('hide');
				$('.file-explorer tbody').html(html);
			}
			else{
				$('.empty-table').removeClass('hide');
				clearTable();
			}

			$('.file-explorer tr.folder').click(function(event){
				var target = event.currentTarget;
				currentFolder = {
				 	id: target.getAttribute('id'),
				 	title: target.getAttribute('title')
				}
				appendToBreardcrumbs(currentFolder);
				refresh();	
			});

			$('.add-folder').click(function(){
				prompt('Add a folder to '+currentFolder.title, 'Enter the name of the new folder:', function(folderName, close){
					$.ajax({
						method: 'POST',
						url: '/add/folder/'+folderName+'/in/'+currentFolder.id,
						success: function(){
							close();
							refresh();
						}
					})
				});
			});

			$('.add-file').click(function(){
				askFiles('Add a file to '+currentFolder.title, null, function(files, close){
					var xhr = new XMLHttpRequest();
					var progress = document.getElementById('file-upload-progress');
					$(progress).removeClass('hide');

					xhr.open('POST', '/add/files/in/'+currentFolder.id);

					xhr.upload.addEventListener('progress', function(e) {
						progress.value = e.loaded;
						progress.max = e.total;
					});

					xhr.addEventListener('load', function() {
						close();
						refresh();
					});

					var form = new FormData();

					for(var i=0, c=files.length; i<c; i++)
						form.append('file', files[0]);

					xhr.send(form);
				});
			});
		}
	}); // requete ajax pour recupérer les folders/fichiers	
}

function appendToBreardcrumbs(f){
	if(f.id != 0){
		$('.breadcrumbs').append('<b>></b><span folder-id="'+f.id+'">'+f.title+'</span>');
		$('.breadcrumbs span[folder-id="'+f.id+'"]').click(function(event){
			var id = event.currentTarget.getAttribute('folder-id');
			if(id != currentFolder.id)
				build(id);
		})
	}
}

function clearTable(){
	$('.file-explorer tbody').html('');
}

function prompt(title, message, onOk){

	if($('.prompt-modal')[0] == null){
		html = '<div class="modal prompt-modal fade" role="dialog"><div class="modal-dialog">';
		html += '<div class="modal-content"><div class="modal-header">';
	    html += '<h4 class="modal-title"></h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>';
	    html += '<div class="modal-body">';

	    // Body
	    html += '<div class="modal-message"></div>'
	    html += '<input class="form-control modal-input">';

	    html += '</div><div class="modal-footer"><button type="button" class="btn btn-info btn-ok" data-dismiss="modal">Ok</button><button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button></div></div></div></div>';

	    $('body').append(html);

	    $('.prompt-modal .btn-ok').click(function(){
	    	var value = $('.prompt-modal .modal-input').val();
	    	onOk(value, function(){
	    		$('.prompt-modal').modal('hide');
	    	});
	    });
	}

	$('.prompt-modal .modal-title').text(title);
	$('.prompt-modal .modal-message').text(message);
	$('.prompt-modal .modal-input').val('');

    $('.prompt-modal').modal('show');
}

function askFiles(title, message, onOk){

	if($('.askFiles-modal')[0] == null){
		html = '<div class="modal askFiles-modal fade" role="dialog"><div class="modal-dialog">';
		html += '<div class="modal-content"><div class="modal-header">';
	    html += '<h4 class="modal-title"></h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>';
	    html += '<div class="modal-body">';

	    // Body
	    html += '<div class="modal-message"></div>'
	    html += '<input class="modal-input" type="file" multiple />';
	    html += '<progress id="file-upload-progress" class="hide"></progress>';

	    html += '</div><div class="modal-footer"><button type="button" class="btn btn-info btn-ok">Ok</button><button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button></div></div></div></div>';

	    $('body').append(html);

	    $('.askFiles-modal .btn-ok').click(function(){
	    	var input = $('.askFiles-modal .modal-input')[0];
	    	onOk(input.files, function(){
	    		$('.askFiles-modal').modal('hide');
	    	});
	    });
	}

	$('.askFiles-modal .modal-title').text(title);
	$('.askFiles-modal .modal-message').text(message);
	$('.askFiles-modal .modal-input').val('');

    $('.askFiles-modal').modal('show');
}
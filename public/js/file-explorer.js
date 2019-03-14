var HOME_ID;
var currentFolder = {}

function init(homeID){
	if(homeID !== undefined) HOME_ID = homeID;
	currentFolder = {
		id: HOME_ID,
		title: "Home"	
	}
	refresh();
}

$('.goHome').click(function(){
	init();
	$('.breadcrumbs :not(.fixed)').remove();
});

$('.add-folder').click(function(){
	prompt('Add a folder to '+currentFolder.title, 'Enter the name of the new folder:', function(folderName, close){
		$.ajax({
			method: 'POST',
			url: '/add/folder?nameFolder='+folderName+'&id='+currentFolder.id,
			success: function(){
				alert('success', 'Folder successfully added.');
				close();
				refresh();
			},
			error: function(){
				alert('danger', 'Fail to add folder.');
				close();
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

		xhr.onreadystatechange = function() {//Call a function when the state changes.
			if(xhr.readyState == 4){
				if(xhr.status == 200) {
					alert('success', 'File added successfully.');
				}
				else if(xhr.status == 404 || xhr.status == 500){
					alert('danger', 'Fail to add file.');
				}					        
			}
		}
		xhr.send(form);
	});
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
		error: function(){
			alert('danger', 'Fail to load the content of this folder.')
		},
		success: function(data){
			var fs = data;

			if(fs.length > 0){
				var html = '';
				for(var i=0; i<fs.length; i++){
					var f = fs[i];
					var icon = '/icons/'+f.type+'.png';

					html += '<tr class="'+f.type+'" id="'+f.id+'" name="'+f.name+'" path="'+f.path+f.name+'">';
					html += '<th scope="row"><img src="'+icon+'"></th>';
					html += '<td>'+ f.name +'</td>';
					html += '<td>'+ f.lastUpdate +'</td>';
					html += '<td>'+ (f.version ? f.version : '') +'</td>';
					html += '<td>'+ f.lastUpdator +'</td>';
					html += '<td>'+ f.creator +'</td>';
					html += '<td><img title="Download" class="download" src="/icons/download.png">'
								+'<img title="Rename" class="edit" src="/icons/edit.png">'
								+'<img title="Shared" class="user-access" src="/icons/users.png">'
								+'<img title="Delete" class="delete" src="/icons/delete.png"></td>';
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
				 	title: target.getAttribute('name')
				}
				appendToBreardcrumbs(currentFolder);
				refresh();	
			});

			$('img.download').click(function(event){
				event.stopPropagation();
				var tr = event.target.parentElement.parentElement;
				window.open('/download/'+tr.id, '_blank');
			});

			$('img.edit').click(function(event){
				event.stopPropagation();
				var target = event.currentTarget.parentElement.parentElement;
				var title = target.getAttribute('name');
				var id = target.getAttribute('id');
				prompt('File renaming','Rename file "'+title+'" to:', function(input, close){
					$.ajax({
						method: 'POST',
						url: '/rename/template?nameTemplate='+input+'&id='+id,
						success: function(){
							alert('success', '"'+title+'" has be renamed "'+input+'".');
							close();
							refresh();
						},
						error: function(){
							alert('danger', 'Fail to rename "'+title+'".');
							close();
						}
					})
				});
			});

			$('img.user-access').click(function(event){
				event.stopPropagation();
				var target = event.currentTarget.parentElement.parentElement;
				var name = target.getAttribute('name');
				var id = target.getAttribute('id');				
				askUserAccess(target.id, name, function(){
					console.log('ok');
				});
			})

			$('img.delete').click(function(event){
				event.stopPropagation();
				var target = event.target.parentElement.parentElement;
				var title = target.getAttribute('name');
				var id = target.getAttribute('id');
				$.ajax({
					method: 'POST',
					url: '/delete/template/'+id,
					success: function(){
						alert('success', '"'+title+'" has be deleted.');
						close();
						refresh();
					},
					error: function(){
						alert('danger', 'Fail to delete "'+title+'".');
						close();
					}
				})
			});
		}
	});
}

function appendToBreardcrumbs(f){
	if(f.id != 0){
		$('.breadcrumbs').append('<b>></b><span folder-id="'+f.id+'">'+f.title+'</span>');
		$('.breadcrumbs span[folder-id="'+f.id+'"]').click(function(event){
			var id = event.currentTarget.getAttribute('folder-id');
			if(id != currentFolder.id){
				while(true){
					var next = $(event.currentTarget).next();
					if(next.length) next.remove();
					else break;
				}
				build(id);
			}
		})
	}
}

function clearTable(){
	$('.file-explorer tbody').html('');
}

function prompt(title, message, onOk){

	$('.prompt-modal').remove();

	html = '<div class="modal prompt-modal fade" role="dialog"><div class="modal-dialog">';
	html += '<div class="modal-content"><div class="modal-header">';
	html += '<h4 class="modal-title"></h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>';
	html += '<div class="modal-body">';

	// Body
	html += '<div class="modal-message"></div>'
	html += '<input class="form-control modal-input">';

	html += '</div><div class="modal-footer"><button type="button" class="btn btn-info btn-ok">Ok</button><button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button></div></div></div></div>';

	$('body').append(html);

	$('.prompt-modal .btn-ok').click(function(){
		var value = $('.prompt-modal .modal-input').val();
		if(value != '')
			onOk(value, function(){
				$('.prompt-modal').modal('hide');
			});
	});

	$('.prompt-modal .modal-title').text(title);
	$('.prompt-modal .modal-message').text(message);
	$('.prompt-modal .modal-input').val('');

    $('.prompt-modal').modal('show');
}

function askFiles(title, message, onOk){

	$('.askFiles-modal').remove();

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
		if(input.files.length > 0)
			onOk(input.files, function(){
				$('.askFiles-modal').modal('hide');
			});
	});

	$('.askFiles-modal .modal-title').text(title);
	$('.askFiles-modal .modal-message').text(message);
	$('.askFiles-modal .modal-input').val('');

    $('.askFiles-modal').modal('show');
}

function alert(type, message){
	$('.alerts').prepend('<div class="alert alert-'+type+'">'+message+'<strong>X</strong></div>');

	var alerts = $('.alerts').children();
	$(alerts[0]).find('strong').click(function(event){event.currentTarget.parentElement.remove()});

	if(alerts.length > 5){
		alerts[alerts.length-1].remove();
	}
}

function askUserAccess(id, name, onOk){

	$('.useraccess-modal').remove();

	html = '<div class="modal useraccess-modal fade" role="dialog"><div class="modal-dialog">';
	html += '<div class="modal-content"><div class="modal-header">';
	html += '<h4 class="modal-title"></h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>';
	html += '<div class="modal-body">';

	// Body
	html += '<div class="modal-message"></div>'
	html += '<div class="autocomplete" style="width:100%;"><input type="text" placeholder="Search a user"></div>';
	html += '<div class="user-list">No other users has access</div>';

	html += '</div><div class="modal-footer"><button class="btn btn-default btn-close" data-dismiss="modal">Close</button></div></div></div></div>';

	$('body').append(html);

	$('.useraccess-modal .btn-ok').click(function(){
		var value = $('.useraccess-modal .modal-input').val();
		if(value != '')
			onOk(value, function(){
				$('.useraccess-modal').modal('hide');
			});
	});

	$('.useraccess-modal .modal-title').text("Give access to others");
	$('.useraccess-modal .modal-message').text("Enter the user you want to give access to \""+name+"\"");
	$('.useraccess-modal .modal-input').val('');

	var fetchUserList = function(){
		$.ajax({
			method: 'POST',
			url: '/who/access/'+id,
			success: function(users){
				var html = '';
				for(var i=0; i<users.length; i++){
					html += '<div class="user" title="'+user.email+'" id="'+user.id+'">'+user.name+'<img title="Delete" class="delete" src="/icons/delete.png"></div>';
				}
				$('.useraccess-modal .user-list').html(html);

				$('.useraccess-modal .user-list .delete').click(function(event){
					var target = event.currentTarget.parentElement;
					onDeleteUser(target.id);
				});
			},
			error: function(){
				alert('danger', 'Fail to get users.');
				$('.useraccess-modal').modal('hide');
			}
		})
	}

	var onSelectUser = function(userID){
		$.ajax({
			method: 'POST',
			url: '/give/access/'+id+'?userId='+userID,
			success: function(){
				alert('success', 'New user has been added to '+name);
				fetchUserList();
			},
			error: function(){
				alert('danger', 'Fail to add user to '+name);
				$('.useraccess-modal').modal('hide');
			}
		})
	}

	var onDeleteUser = function(userID){
		$.ajax({
			method: 'POST',
			url: '/cancel/access/'+id+'?userId='+userId,
			success: function(){
				alert('success', 'User has been deleted from '+name);
				fetchUserList();
			},
			error: function(){
				alert('danger', 'Fail to delete user from '+name);
				$('.useraccess-modal').modal('hide');
			}
		})
	}

	autocomplete($('.useraccess-modal .autocomplete input')[0], onSelectUser);

    $('.useraccess-modal').modal('show');
}

function autocomplete(inp, onSelect) {
  /*the autocomplete function takes two arguments,
  the text field element and an array of possible autocompleted values:*/
  var currentFocus;
  /*execute a function when someone writes in the text field:*/
  inp.addEventListener("input", function(e) {
      var a, b, i, val = this.value;
      /*close any already open lists of autocompleted values*/
      closeAllLists();
      if (!val) { return false;}
      currentFocus = -1;
      /*create a DIV element that will contain the items (values):*/
      a = document.createElement("DIV");
      a.setAttribute("id", this.id + "autocomplete-list");
      a.setAttribute("class", "autocomplete-items");
      /*append the DIV element as a child of the autocomplete container:*/
      this.parentNode.appendChild(a);

      // JS search
      /*for each item in the array...*/
     /* for (i = 0; i < arr.length; i++) {
        // check if the item starts with the same letters as the text field value:
        if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
          // create a DIV element for each matching element:
          b = document.createElement("DIV");
          // make the matching letters bold:
          b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
          b.innerHTML += arr[i].substr(val.length);
          // insert a input field that will hold the current array item's value:
          b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
          // execute a function when someone clicks on the item value (DIV element):
          b.addEventListener("click", function(e) {
              // insert the value for the autocomplete text field:
              inp.value = this.getElementsByTagName("input")[0].value;
              // close the list of autocompleted values, (or any other open lists of autocompleted values:
              closeAllLists();
          });
          a.appendChild(b);
        }
      }*/

      // Remote search
      var savedInput = inp.value;
      setTimeout(function(){
      	if(inp.value === savedInput) // if the user hasn't tap from 0,5 sec, fetch data
      		fetchResults();
      }, 500);

      var fetchResults = function(){
	      $.ajax({
	      	method: 'POST',
			url: '/search/'+inp.value,
			success: function(users){
				for(let i=0; i<users.length; i++){
				  // create a DIV element for each matching element:
		          b = document.createElement("DIV");
		          // make the matching letters bold:
		          b.innerHTML = "<strong>" + users[i].name.substr(0, inp.value.length) + "</strong>";
		          b.innerHTML += users[i].name.substr(inp.value.length);
		          // insert a input field that will hold the current array item's value:
		          b.innerHTML += "<input type='hidden' value='" + users[i].name + "'>";
		          // execute a function when someone clicks on the item value (DIV element):
		          b.addEventListener("click", function(e) {
		          	  // reset iput
		              inp.value = '';
		              // close the list of autocompleted values, (or any other open lists of autocompleted values:
		              closeAllLists();
		              // callback
		              onSelect(users[i].id);
		          });
		          a.appendChild(b);
		        }
			},
			error: function(){
				// Temporary code, waiting for the api to work

				var users = ['No users','enfin si peut etre','le truc c\'est que','y\a pas d\'api', 'lol'];
				for(var i=0; i<users.length; i++){
				  // create a DIV element for each matching element:
		          b = document.createElement("DIV");
		          // make the matching letters bold:
		          b.innerHTML = "<strong>" + users[i].substr(0, inp.value.length) + "</strong>";
		          b.innerHTML += users[i].substr(inp.value.length);
		          // insert a input field that will hold the current array item's value:
		          b.innerHTML += "<input type='hidden' value='" + users[i] + "'>";
		          // execute a function when someone clicks on the item value (DIV element):
		          b.addEventListener("click", function(e) {
		          	  // reset iput
		              inp.value = '';
		              // close the list of autocompleted values, (or any other open lists of autocompleted values:
		              closeAllLists();
		              // callback
		              onSelect(users[i]);
		          });
		          a.appendChild(b);
		        }
			}
	      })
	  }
  });
  /*execute a function presses a key on the keyboard:*/
  inp.addEventListener("keydown", function(e) {
      var x = document.getElementById(this.id + "autocomplete-list");
      if (x) x = x.getElementsByTagName("div");
      if (e.keyCode == 40) {
        /*If the arrow DOWN key is pressed,
        increase the currentFocus variable:*/
        currentFocus++;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 38) { //up
        /*If the arrow UP key is pressed,
        decrease the currentFocus variable:*/
        currentFocus--;
        /*and and make the current item more visible:*/
        addActive(x);
      } else if (e.keyCode == 13) {
        /*If the ENTER key is pressed, prevent the form from being submitted,*/
        e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (x) x[currentFocus].click();
        }
      }
  });
  function addActive(x) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
  }
  function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("autocomplete-active");
    }
  }
  function closeAllLists(elmnt) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
      if (elmnt != x[i] && elmnt != inp) {
        x[i].parentNode.removeChild(x[i]);
      }
    }
  }
  /*execute a function when someone clicks in the document:*/
  document.addEventListener("click", function (e) {
      closeAllLists(e.target);
  });
}
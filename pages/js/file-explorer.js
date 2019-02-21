var root = {
	type: 'root',
	children: [
		{
			type: 'folder',
			path: '/',
			name: 'Nouveau dossier',
			lastUpdate: '05/12/2017',
			lastUpdator: 'Jacques',
			creator: 'Toto'
		},
		{
			type: 'folder',
			path: '/',
			name: 'Nouveau dossier 2',
			lastUpdate: '06/12/2017',
			lastUpdator: 'Bob',
			creator: 'Toto',
		},
		{
			type: 'file',
			path: '/',
			name: 'notes.txt',
			lastUpdate: '09/11/2017',
			version: '1.8',
			lastUpdator: 'Manu',
			creator: 'Toto'
		},
		{
			type: 'file',
			path: '/',
			name: 'photo.png',
			lastUpdate: '02/10/2017',
			version: '1.5',
			lastUpdator: 'Tom',
			creator: 'Arthur'
		},
		{
			type: 'file',
			path: '/',
			name: 'rapport.pdf',
			lastUpdate: '30/01/2018',
			version: '0.2',
			lastUpdator: 'Gilbert',
			creator: 'Vincent'
		}
	]
}

function build(id){
	jQuery.ajax({
		method: 'POST',
		url: "/filebox/get/templates/"+id,
		dataType: "JSON",
		success: function(data){
			alert("test");
			var mainFolder = data;

			var html = '';
			for(var i=0; i<mainFolder.children.length; i++){
				var f = mainFolder.children[i];

				html += '<tr class="'+f.type+'" id="'+f.id+'" path="'+f.path+f.name+'">';
				html += '<th scope="row">'+ (i+1) +'</th>';
				html += '<td>'+ f.name +'</td>';
				html += '<td>'+ f.lastUpdate +'</td>';
				html += '<td>'+ (f.version ? f.version : '') +'</td>';
				html += '<td>'+ f.lastUpdator +'</td>';
				html += '<td>'+ f.creator +'</td>';
				html += '</tr>';
			}

			jQuery('.file-explorer tbody').html(html);

			jQuery('.file-explorer tr.folder').click(function(event){
				var id = event.currentTarget.getAttribute('id');
				build(id);
			})
		}
	}); // requete ajax pour recupérer les folders/fichiers
	
}

build(0);
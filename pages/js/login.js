jQuery(document).ready(function() {
	jQuery("form").validate({
		rules: {
			"login":{
				"required": true,
				"email": true
			},
			"password": {
				"required": true
			}
		}
	})
})
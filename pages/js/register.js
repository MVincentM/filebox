jQuery(document).ready(function() {
	jQuery("form").validate({
		rules: {
			"firstname":{
				"required": true
			},
			"lastname": {
				"required": true
			},
			"login":{
				"required": true,
				"email": true
			},
			"password": {
				"required": true
			},
			"confirmpassword": {
				"required": true
			}
		}
	})
})
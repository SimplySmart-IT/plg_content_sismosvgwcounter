(function () {
	'use strict';
	/* SimplySmart-IT - Martina Scholz */
	/* (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de> */

	function sismos_vgwimport() {

		const btnImport = document.getElementById('importbtnsismosvgw');
		const adminform = document.querySelector('form[name="adminForm"]');
		const importError = Joomla.JText._('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_GLOBAL_MSG');
		

		function importTask() {
			let importCheck = document.querySelector('input[name="import_sismosvgw"]');
			adminform.enctype="multipart/form-data";
			importCheck.value = '1';
			var formData = new FormData(adminform);

			let ajaxParams = {
				url:'index.php?option=com_ajax&plugin=sismosvgwcounter&group=content&format=raw',
				type: 'POST',
				processData: false,
				contentType: false,
				dataType: 'json',
				format: 'json',
				method: 'post',
				data: formData,
				cache: false,
				async: false,
				success: function (response) {
					if (response.success === true) {
						Joomla.renderMessages({"success":[response.message]});
					} else {
						Joomla.renderMessages({"error":[response.message]});
					}
					// console.log(response);
					
				},
				error: function (response) {
					console.log('error');
					console.log(response);
					Joomla.renderMessages({"error":[importError]});
				}
			};
			jQuery.ajax(ajaxParams);
		}

		btnImport.addEventListener('click', importTask);

		document.removeEventListener("DOMContentLoaded", sismos_vgwimport);
	}

	document.addEventListener("DOMContentLoaded", sismos_vgwimport);

})();
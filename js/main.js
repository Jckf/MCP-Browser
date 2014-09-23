function clearSelect(el) {
	document.getElementsByName(el)[0].value = "";
}

function submitForm(form) {
	if (typeof(form.onsubmit) != 'undefined') {
		form.onsubmit();
	}
	
	form.submit();
}

function disableEmpty(form) {
	var selects = form.getElementsByTagName('select');

	for (var i = 0; i < selects.length; i++) {
		if (selects[i].value == "") {
			selects[i].disabled = true;
		}
	}
}

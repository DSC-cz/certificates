const ico = document.querySelector("[name=c_findbyico]");
const ico_val = document.querySelector("[name=c_ico]");
const nazev = document.querySelector("[name=c_nazev]");
const obec = document.querySelector("[name=c_obec]");
const okres = document.querySelector("[name=c_okres]");
const adresa = document.querySelector("[name=c_adresa]");

ico.addEventListener("click", function(e){
	var response = [];
	const xhr = new XMLHttpRequest();
	xhr.open('GET', "/wp-content/plugins/certificates/ares.php?ico=" + ico_val.value);
	xhr.responseType = 'json';
	xhr.onload = function(e) {
	if (this.status == 200) {
		console.log(this.response);
		response = this.response;

		if(response["error"]){
			alert(response["error"]);
			e.preventDefault();
			return false;
		}
	
		nazev.value = response["Obchodni_firma"][0];
		obec.value = response["Obec"];
		okres.value = response["Okres"][0];
		adresa.value = response["Adresa"];	
	}
	};
	xhr.send();
	
	e.preventDefault();
});
let url = window.location.origin + '/Dashboard/updateOrder';
fetch(url).then(response => response.json()).then(result => console.log(result));

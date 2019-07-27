let url = window.location.origin + '/Dashboard/getOrders';
fetch(url).then(response => response.json()).then(result => console.log(result));

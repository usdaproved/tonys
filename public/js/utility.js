const postJSON = (url, json, token) => {
    // TODO(trystan): Handle the repsonse better here.
    // in case something goes wrong. Then return the results of
    // the response.
    url = window.location.origin + url;
    json["CSRFToken"] = token;
    const data = JSON.stringify(json);
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data
    });
};

export { postJSON }
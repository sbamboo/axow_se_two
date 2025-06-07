const BASE_API_ENDPOINT = "http://localhost:8080/proj/axow_se_two/backend/site/";

async function fetchApi(endpointPath, data, method, authToken=null, authType="Bearer") {
    // Endpoint URL = BASE_API_ENDPOINT + endpoint_path.notBeginningWithSlash()
    // if method is POST data is the body of the request, if method is GET data is the query parameters
    let url = BASE_API_ENDPOINT + endpointPath.replace(/^\//, "");
    const options = {
        method: method,
        headers: {
            "Content-Type": "application/json"
        }
    };
    if (authToken !== null) {
        options.headers["Authorization"] = `${authType} ${authToken}`;
    }

    if (method === "GET") {
        const queryParams = new URLSearchParams(data).toString();
        url = `${url}?${queryParams}`;
    } else {
        options.body = JSON.stringify(data);
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (error) {
        return [false, null, null, error.message];
    }
    let success = response.ok;
    let jsonResponse = null;
    try {
        jsonResponse = await response.json();
        if (jsonResponse.status === "failed") {
            success = false;
        }
    } catch (error) {
        return [false, null, response, error.message];
    }
    return [success, jsonResponse, response, null];
}

class Api {
    // Constructor that can store authResponse as null
    constructor() {
        this.authResponse = null;
    }

    // Method to check if we have a valid authResponse (i.e not null and authResponse.expires is greater than current time and if refresh_expires is not null it is greater then the current time)
    _validateAuth() {
        if (this.authResponse === null) {
            return false;
        }
        const currentTime = Date.now();
        if (this.authResponse.expires <= currentTime) {
            return false;
        }
        if (this.authResponse.refresh_expires !== null && this.authResponse.refresh_expires <= currentTime) {
            return false;
        }
        return true;
    }

    // Method for /auth endpoint posting username, password and token_type defaulted to "Pair"
    async auth(username, password, tokenType="pair") {
        const data = { "username": username, "password": password, "token_type": tokenType };
        const [success, jsonResponse, response, error] = await fetchApi("/auth", data, "GET");
        if (success) {
            this.authResponse = jsonResponse;
        }
        return [success, jsonResponse, response, error];
    }
}
export class ApiError extends Error {
    constructor(response, data) {
        super(data?.message || response.statusText || 'Request failed');
        this.name = 'ApiError';
        this.status = response.status;
        this.data = data;
    }
}

export async function apiFetch(path, options = {}) {
    const {
        method = 'GET',
        body,
        headers: customHeaders,
        ...rest
    } = options;

    const headers = new Headers(customHeaders);

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    let requestBody = body;

    if (body !== undefined && !(body instanceof FormData)) {
        if (!headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json');
        }

        requestBody = JSON.stringify(body);
    }

    const response = await fetch(path, {
        method,
        body: requestBody,
        headers,
        credentials: 'include',
        ...rest,
    });

    let data = null;
    const text = await response.text();

    if (text !== '') {
        try {
            data = JSON.parse(text);
        } catch (error) {
            data = null;
        }
    }

    if (!response.ok) {
        throw new ApiError(response, data);
    }

    return data;
}

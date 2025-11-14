// Common helpers for HAProxy UI
async function fetchAndExpectResponse(response) {
    const text = await response.text();
    try {
        const data = JSON.parse(text);
        if (data.success)
            alert(`Succes: ${data.message}`);
        else
            alert(`Erreur: ${data.message}`);
        return data;
    } catch (err) {
        alert('NON JSON response: ' + text);
        return { success: false, message: text };
    }
}

async function submitFormAndReload(form) {
    const formData = new FormData(form);
    const resp = await fetch(form.action, { method: 'POST', body: formData });
    const result = await fetchAndExpectResponse(resp);
    if (result && result.success) 
        location.reload();
}

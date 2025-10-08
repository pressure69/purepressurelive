// PurePressureLive Frontend Error Logger
window.addEventListener("error", function(event) {
    sendError({
        message: event.message,
        source: event.filename,
        line: event.lineno,
        column: event.colno,
        stack: event.error ? event.error.stack : null
    });
});

window.addEventListener("unhandledrejection", function(event) {
    sendError({
        message: event.reason ? event.reason.toString() : "Unhandled Promise Rejection",
        source: "Promise",
        line: 0,
        column: 0,
        stack: event.reason && event.reason.stack ? event.reason.stack : null
    });
});

function sendError(errorData) {
    fetch("/log_frontend_error.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(errorData)
    }).catch(() => { /* fail silently */ });
}

// src/js/camera.js

document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("video-feed");
    const canvas = document.getElementById("capture-canvas");
    const snapButton = document.getElementById("snap-btn");
    const overlaySelect = document.getElementById("overlay-select");
    const studioLog = document.getElementById("studio-log");

    // 1. Hook into user's hardware camera feed
    if (video) {
        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                studioLog.innerHTML = `<p class="error">Camera access denied: ${err.message}</p>`;
            });
    }

    // 2. Capture and transmit snapshots
    if (snapButton) {
        snapButton.addEventListener("click", () => {
            const context = canvas.getContext("2d");
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            // Draw current video frame to hidden canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const base64Data = canvas.toDataURL("image/png");
            const selectedOverlay = overlaySelect.value;

            studioLog.innerHTML = "<p>Processing composite image...</p>";

            // Post asynchronously to our routing entry point
            fetch("/studio", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=snap&image=${encodeURIComponent(base64Data)}&overlay=${encodeURIComponent(selectedOverlay)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    studioLog.innerHTML = `<p class="success">🎉 ${data.message}</p>`;
                    // Instantly append new photo to sidebar or refresh preview list
                    if (typeof window.refreshStudioGrid === "function") window.refreshStudioGrid();
                } else {
                    studioLog.innerHTML = `<p class="error">❌ ${data.message}</p>`;
                }
            })
            .catch(() => {
				studioLog.innerHTML = "<p class='error'>❌ Network error during snapshot transfer.</p>";            });
        });
    }
});

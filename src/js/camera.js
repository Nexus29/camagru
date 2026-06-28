// src/js/camera.js

document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("video-feed");
    const placeholder = document.getElementById("camera-placeholder");
    const captureButton = document.getElementById("capture-trigger");
    const statusFeedback = document.getElementById("studio-status-feedback");
    const liveOverlayPreview = document.getElementById("live-overlay-preview");
    const canvas = document.getElementById("hidden-processing-canvas");
    const fileUploadInput = document.getElementById("file-upload-input");
    const uploadPreview = document.getElementById("upload-preview");

    let activeOverlayFilename = "";

    // ========================================================
    // 1. INITIALIZE WEB CAMERA STREAM HARDWARE PIPELINE
    // ========================================================
    if (video) {
        navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: false })
            .then(stream => {
                // 💥 THE ACCURATE VISIBILITY SEQUENCE FIX:
                // Swap visibility flags FIRST so the browser engine can see the element layout bounding box
                placeholder.style.display = "none";
                video.style.display = "block";
                if (uploadPreview) uploadPreview.style.display = "none";
                
                // Assign the stream pipeline tracking
                video.srcObject = stream;
                
                // Now force the media engine to play the initialized track
                return video.play();
            })
            .then(() => {
                if (statusFeedback) {
                    statusFeedback.style.color = "#aaa";
                    statusFeedback.innerText = "📷 Camera active. Select a graphic overlay to enable snapshot capturing.";
                }
            })
            .catch(err => {
                console.error("Camera access denied or missing:", err);
                if (statusFeedback) {
                    statusFeedback.style.color = "#ef4444";
                    statusFeedback.innerText = `❌ Camera stream error: ${err.message}`;
                }
                
                // Update placeholder visually if permissions get rejected or drop out
                placeholder.innerHTML = `
                    <span style="font-size: 3rem; display: block; margin-bottom: 0.5rem;">❌</span>
                    Camera blocked or unavailable.<br>
                    <small style="color: #ef4444;">Please check browser permissions or use the manual file upload option below.</small>
                `;
            });
    }

    // ========================================================
    // 2. ALTERNATIVE FILE UPLOAD IMAGE SOURCE FALLBACK
    // ========================================================
    if (fileUploadInput) {
        fileUploadInput.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (uploadPreview) {
                        if (video) video.style.display = "none";
                        if (placeholder) placeholder.style.display = "none";
                        
                        uploadPreview.src = event.target.result;
                        uploadPreview.style.display = "block";
                        
                        if (statusFeedback) {
                            statusFeedback.style.color = "#aaa";
                            statusFeedback.innerText = "🖼️ Uploaded source file loaded into preview context.";
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ========================================================
    // 3. GRAPHIC OVERLAY COMPONENT SELECTION MANAGEMENT
    // ========================================================
    const thumbs = document.querySelectorAll(".overlay-mask-thumb");
    thumbs.forEach(thumb => {
        thumb.addEventListener("click", function() {
            thumbs.forEach(t => {
                t.style.borderColor = "#333";
                t.style.background = "#111";
            });

            this.style.borderColor = "#00adb5";
            this.style.background = "#1a3a3a";

            activeOverlayFilename = this.getAttribute("data-filename");
            
            if (liveOverlayPreview) {
                liveOverlayPreview.src = `/uploads/overlays/${activeOverlayFilename}`;
                liveOverlayPreview.style.display = "block";
            }

            if (captureButton) {
                captureButton.disabled = false;
                captureButton.style.background = "#00adb5";
                captureButton.style.color = "#fff";
                captureButton.style.cursor = "pointer";
            }
            
            if (statusFeedback) {
                statusFeedback.style.color = "#aaa";
                statusFeedback.innerText = `Ready to capture! Overlay active: "${activeOverlayFilename}"`;
            }
        });
    });

    // ========================================================
    // 4. CAPTURE RENDER AND TRANSMIT LOGIC
    // ========================================================
    if (captureButton) {
        captureButton.addEventListener("click", () => {
            if (!activeOverlayFilename) {
                if (statusFeedback) {
                    statusFeedback.style.color = "#ef4444";
                    statusFeedback.innerText = "❌ Select an overlay configuration before snapping.";
                }
                return;
            }

            const context = canvas.getContext("2d");
            canvas.width = 640;
            canvas.height = 480;

            if (uploadPreview && uploadPreview.style.display === "block") {
                context.drawImage(uploadPreview, 0, 0, canvas.width, canvas.height);
            } else if (video && video.style.display === "block") {
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
            } else {
                if (statusFeedback) {
                    statusFeedback.style.color = "#ef4444";
                    statusFeedback.innerText = "❌ No source media framework tracks found to composite.";
                }
                return;
            }

            const base64Data = canvas.toDataURL("image/png");

            if (statusFeedback) {
                statusFeedback.style.color = "#00adb5";
                statusFeedback.innerText = "Processing composite engine graphics layers...";
            }

            fetch("/studio", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=snap&image=${encodeURIComponent(base64Data)}&overlay=${encodeURIComponent(activeOverlayFilename)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (statusFeedback) {
                        statusFeedback.style.color = "#22c55e";
                        statusFeedback.innerText = `🎉 Snapshot published successfully!`;
                    }
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    if (statusFeedback) {
                        statusFeedback.style.color = "#ef4444";
                        statusFeedback.innerText = `❌ Error: ${data.message}`;
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (statusFeedback) {
                    statusFeedback.style.color = "#ef4444";
                    statusFeedback.innerText = "❌ Network transport connection pipeline severed.";
                }
            });
        });
    }
});

function deleteSnapshot(snapshotId) {
    if (!confirm("Are you certain you wish to completely drop this image composition?")) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/gallery';
    
    const act = document.createElement('input');
    act.type = 'hidden';
    act.name = 'action';
    act.value = 'delete';
    
    const id = document.createElement('input');
    id.type = 'hidden';
    id.name = 'snapshot_id';
    id.value = snapshotId;
    
    form.appendChild(act);
    form.appendChild(id);
    document.body.appendChild(form);
    form.submit();
}

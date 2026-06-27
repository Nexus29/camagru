function toggleLike(snapshotId, buttonElement) {
    fetch("/gallery", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=like&snapshot_id=${snapshotId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const countSpan = buttonElement.querySelector(".like-count");
            let currentLikes = parseInt(countSpan.textContent);
            countSpan.textContent = data.action === "liked" ? currentLikes + 1 : currentLikes - 1;
            buttonElement.classList.toggle("liked", data.action === "liked");
        }
    });
}

function submitComment(event, snapshotId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[name="comment_text"]');
    const commentText = input.value.trim();
    const targetList = document.getElementById(`comments-list-${snapshotId}`);

    if (!commentText) return;

    fetch("/gallery", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=comment&snapshot_id=${snapshotId}&comment_text=${encodeURIComponent(commentText)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const li = document.createElement("li");
            li.innerHTML = `<strong>You:</strong> ${escapeHTML(commentText)}`;
            targetList.appendChild(li);
            input.value = "";
        }
    });
}

function escapeHTML(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

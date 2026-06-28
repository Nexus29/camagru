<?php
if (!defined('CAMAGRU_RUNNING')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct script access is strictly prohibited.");
}
?>

<div class="gallery-container">
    <h1 class="gallery-title">Public Community Gallery</h1>
    <p class="gallery-subtitle">Explore composite snapshots designed by our community.</p>

    <?php if (empty($cards)): ?>
        <div class="empty-gallery-card">
            <p>No snapshots have been published to the relational matrix yet.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/studio" class="btn btn-primary">Visit the Studio to Post an Image!</a>
            <?php else: ?>
                <a href="/login" class="btn btn-primary">Sign in to start capturing!</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($cards as $item): ?>
                <div class="gallery-card" id="snapshot-card-<?php echo $item['id']; ?>">
                    
                    <div class="card-image-wrapper">
                        <img src="<?php echo htmlspecialchars($item['storage_path']); ?>" alt="Camagru Snapshot" class="main-snapshot-img">
                    </div>
                    
                    <div class="card-meta">
                        <span class="meta-author">By: <strong><?php echo htmlspecialchars($item['username']); ?></strong></span>
                        <span class="meta-date"><?php echo date("M d, Y", strtotime($item['created_at'])); ?></span>
                    </div>

                    <div class="card-actions">
                        <?php 
                        // Query interactions using our operational models directly
                        $likesCount = $interactionModel->getLikeCount($item['id']); 
                        $comments = $interactionModel->getCommentsForSnapshot($item['id']);
                        ?>
                        
                        <button class="like-btn" onclick="toggleLike(<?php echo $item['id']; ?>, this)">
                            ❤️ Likes (<span class="like-count"><?php echo $likesCount; ?></span>)
                        </button>
                    </div>

                    <div class="comments-section">
                        <ul class="comments-list" id="comments-list-<?php echo $item['id']; ?>">
                            <?php if (empty($comments)): ?>
                                <li class="empty-comment-placeholder" style="color: #666; font-style: italic; font-size: 0.85rem; padding: 4px 0;">No comments yet...</li>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <li>
                                        <strong class="comment-author"><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                        <span class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form class="comment-form" onsubmit="submitComment(event, <?php echo $item['id']; ?>)">
                                <input type="text" name="comment_text" placeholder="Write a comment..." maxlength="255" required>
                                <button type="submit">Post</button>
                            </form>
                        <?php else: ?>
                            <p class="login-prompt-notice"><a href="/login">Log in</a> to leave a like or comment.</p>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="/js/gallery.js"></script>

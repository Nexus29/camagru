<?php
if (!defined('CAMAGRU_RUNNING')) {
    define('CAMAGRU_RUNNING', true);
}
?>

<div class="gallery-container">
    <h1 style="text-align: center; margin-bottom: 2rem;">Public Exploration Feed</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; width: 100%;">
        
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="card" style="align-items: stretch; text-align: left;">
                <div style="background: #222; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; border-radius: 12px; overflow: hidden;">
                    <span style="color: #666;">📸 Simulated Captured Snapshot #<?php echo $i; ?></span>
                </div>
                
                <div style="padding: 1rem 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: #aaa;">By: @user_creator</span>
                    
                    <div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button style="background: none; border: none; color: #ff3b30; cursor: pointer; font-size: 1.2rem;">❤️ 12</button>
                        <?php else: ?>
                            <span style="color: #666; font-size: 0.9rem;">❤️ 12</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem;"><strong style="color: #fff;">@reviewer:</strong> Clean composition wrapper layer!</p>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <input type="text" placeholder="Write a comment..." style="flex: 1; background: #222; border: 1px solid #333; color: #fff; padding: 0.5rem; border-radius: 8px;">
                            <button style="background: #444; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">Post</button>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 0.8rem; color: #666; font-style: italic; margin-top: 0.5rem;">
                            <a href="/login" style="color: #d0d0d0; text-decoration: underline;">Sign in</a> to leave likes and comments.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>

    </div>

    <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 3rem;">
        <button disabled style="padding: 0.5rem 1rem; background: #222; color: #555; border: 1px solid #333; border-radius: 8px;">Previous</button>
        <span style="align-self: center; color: #aaa;">Page 1 of 3</span>
        <button style="padding: 0.5rem 1rem; background: #333; color: #fff; border: 1px solid #444; border-radius: 8px; cursor: pointer;">Next</button>
    </div>
</div>

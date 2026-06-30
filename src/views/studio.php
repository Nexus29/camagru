<?php
if (!defined('CAMAGRU_RUNNING')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct script access is strictly prohibited.");
}

// Redirect if session token context drops unexpectedly
if (!isset($_SESSION['user_id'])) {
	header("Location: https://" . $_SERVER['HTTP_HOST'] . "/login");
	exit();
}

// Fetch only the logged-in user's historic snapshots for the sidebar component
$userSnapshots = [];
if (method_exists($snapshotModel, 'getPaginated')) {
    $allCards = $snapshotModel->getPaginated(20, 0);
    foreach ($allCards as $card) {
        if ((int)$card['user_id'] === (int)$_SESSION['user_id']) {
            $userSnapshots[] = $card;
        }
    }
}

// Read raw available PNG overlay assets dynamically from disk
$overlayDir = __DIR__ . '/../uploads/overlays';
$availableOverlays = [];
if (is_dir($overlayDir)) {
    $files = scandir($overlayDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png') {
            $availableOverlays[] = $file;
        }
    }
}
?>

<div style="padding-top: 100px; box-sizing: border-box; width: 100%;">

    <h1 style="text-align: center; margin-bottom: 2rem; color: #fff;">Creative Editing Studio Workspace</h1>

    <main style="display: grid; grid-template-columns: 3fr 1fr; gap: 2rem; width: 95%; max-width: 1200px; margin: 0 auto;">
        
        <div>
            <div class="card" id="studio-viewport-container" style="padding: 0; margin-bottom: 1.5rem; background: #000; aspect-ratio: 4/3; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #333; border-radius: 12px;">
                
                <div id="camera-placeholder" style="color: #666; text-align: center; z-index: 2;">
                    <span style="font-size: 3rem; display: block; margin-bottom: 0.5rem;">📹</span>
                    Awaiting webcam stream hardware authorization...<br>
                    <small style="color: #555;">(Allow browser media capabilities prompts to proceed)</small>
                </div>

                <video id="video-feed" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; display:none; z-index: 1;" autoplay playsinline></video>
                
                <img id="upload-preview" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:contain; display:none; z-index: 1;" alt="Upload Preview">

                <img id="live-overlay-preview" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:contain; pointer-events: none; display:none; z-index: 3;" alt="Active Frame Mask">
            </div>

            <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; border: 2px dashed rgba(255,255,255,0.15); border-radius: 12px; background: #1e1e1e;">
                <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: #aaa;">Alternative: Upload a standard graphic master image source file</p>
                <input type="file" id="file-upload-input" accept="image/*" style="font-size: 0.85rem; color: #ccc;">
            </div>

            <div class="card" style="text-align: left; padding: 1.5rem; background: #1e1e1e; border-radius: 12px; border: 1px solid #333;">
                <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #fff;">1. Choose a Graphic Overlay Mask Component</h3>
                
                <div id="overlays-selector-row" style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
                    <?php if (empty($availableOverlays)): ?>
                        <p style="color: #666; font-size: 0.9rem; font-style: italic;">No overlay frames (.png) located in uploads/overlays/ directory.</p>
                    <?php else: ?>
                        <?php foreach ($availableOverlays as $index => $overlayName): ?>
                            <div class="sticker-item overlay-mask-thumb" 
                                 data-filename="<?php echo htmlspecialchars($overlayName); ?>"
                                 style="width: 80px; height: 80px; background: #111; border: 2px solid #333; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0; position: relative; overflow: hidden;">
                                <img src="/uploads/overlays/<?php echo htmlspecialchars($overlayName); ?>" style="max-width: 90%; max-height: 90%; object-fit: contain;" alt="Overlay #<?php echo $index; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div id="studio-status-feedback" style="font-size: 0.85rem; color: #aaa; font-weight: 500;"></div>
                    
                    <button id="capture-trigger" disabled style="padding: 0.8rem 2rem; background: #333; color: #666; border: none; border-radius: 12px; font-weight: 600; cursor: not-allowed; transition: all 0.2s;">
                        Take Snapshot
                    </button>
                </div>
            </div>
        </div>

        <div class="card" style="align-items: stretch; height: fit-content; max-height: 720px; overflow-y: auto; background: #1e1e1e; border-radius: 12px; border: 1px solid #333; padding: 1.5rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0.5rem; color: #fff;">Your Creations</h3>
            
            <div id="user-creations-sidebar-list" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php if (empty($userSnapshots)): ?>
                    <p id="empty-sidebar-notice" style="font-size: 0.85rem; color: #555; text-align: center; font-style: italic; margin-top: 1rem;">Your captures will populate here live.</p>
                <?php else: ?>
                    <?php foreach ($userSnapshots as $snap): ?>
                        <div class="sidebar-thumb-card" id="sidebar-snap-<?php echo $snap['id']; ?>" style="position: relative; background: #000; aspect-ratio: 4/3; border-radius: 8px; border: 1px solid #333; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="<?php echo htmlspecialchars($snap['storage_path']); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Personal Creation">
                            <button class="delete-creation-btn" 
                                    onclick="deleteSnapshot(<?php echo $snap['id']; ?>)"
                                    style="position: absolute; top: 6px; right: 6px; background: rgba(239,68,68,0.85); border: none; color: #fff; border-radius: 6px; padding: 4px 8px; font-size: 0.75rem; font-weight: bold; cursor: pointer; backdrop-filter: blur(4px); transition: background 0.2s;">
                                ✕
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<canvas id="hidden-processing-canvas" style="display: none;" width="640" height="480"></canvas>

<script src="/js/camera.js"></script>

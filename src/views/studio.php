<?php
if (!isset($_SESSION['user_id'])) {
	header("Location: https://" . $_SERVER['HTTP_HOST'] . "/login");
	exit();
}
?>

<h1 style="text-align: center; margin-bottom: 2rem;">Creative Editing Studio Workspace</h1>

<div style="display: grid; grid-template-columns: 3fr 1fr; gap: 2rem; width: 100%;">
	
	<div>
		<div class="card" style="padding: 1rem; margin-bottom: 1.5rem; background: #000; aspect-ratio: 4/3; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
			<div id="camera-placeholder" style="color: #666; text-align: center;">
				<span style="font-size: 3rem; display: block; margin-bottom: 0.5rem;">📹</span>
				Webcam stream hardware placeholder.<br>
				<small style="color: #444;">(getUserMedia constraints activate upon script initialization)</small>
			</div>
			<video id="video-feed" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; display:none;" autoplay playsinline></video>
		</div>

		<div class="card" style="padding: 1rem; margin-bottom: 1.5rem; border: 2px dashed rgba(255,255,255,0.15);">
			<p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: #aaa;">Alternative: Upload a standard graphic master image source file</p>
			<input type="file" accept="image/*" style="font-size: 0.85rem; color: #ccc;">
		</div>

		<div class="card" style="text-align: left;">
			<h3 style="font-size: 1rem; margin-bottom: 1rem; color: #fff;">1. Choose a Graphic Overlay Mask Component</h3>
			<div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
				<?php for ($s = 1; $s <= 4; $s++): ?>
					<div class="sticker-item" style="width: 70px; height: 70px; background: #222; border: 2px solid #333; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;">
						<span style="font-size: 1.5rem;">🎭</span>
					</div>
				<?php endfor; ?>
			</div>
			
			<div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
				<button id="capture-trigger" disabled style="padding: 0.8rem 2rem; background: linear-gradient(135deg, #555, #777); color: #888; border: none; border-radius: 12px; font-weight: 600; cursor: not-allowed;">
					Take Snapshot
				</button>
			</div>
		</div>
	</div>

	<div class="card" style="align-items: stretch; height: fit-content; max-height: 700px; overflow-y: auto;">
		<h3 style="font-size: 1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 0.5rem;">Your Creations</h3>
		
		<div style="display: flex; flex-direction: column; gap: 1rem;">
			<?php for ($t = 1; $t <= 3; $t++): ?>
				<div style="position: relative; background: #222; aspect-ratio: 4/3; border-radius: 8px; border: 1px solid #333; display: flex; align-items: center; justify-content: center;">
					<span style="font-size: 0.75rem; color: #555;">Thumb #<?php echo $t; ?></span>
					<button style="position: absolute; top: 4px; right: 4px; background: rgba(255,59,48,0.8); border: none; color: #fff; border-radius: 4px; padding: 2px 6px; font-size: 0.7rem; cursor: pointer;">✕</button>
				</div>
			<?php endfor; ?>
		</div>
	</div>

</div>

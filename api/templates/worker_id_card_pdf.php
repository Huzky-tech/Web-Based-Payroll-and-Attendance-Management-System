<?php
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18pt; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #374151; }
    .section-label { margin: 0 0 6pt; color: #6b7280; font-size: 6pt; font-weight: bold; }
    .card { width: 100%; position: relative; overflow: hidden; }
    .front { height: 154pt; background: #f59e0b; border-radius: 7pt; padding: 13pt 14pt 11pt; color: #fff; }
    .glow { position: absolute; width: 185pt; height: 185pt; border-radius: 93pt; background: #fbbf24; right: -38pt; top: -105pt; opacity: .75; }
    .top { width: 100%; font-size: 6.5pt; font-weight: bold; position: relative; z-index: 2; }
    .top td:last-child { text-align: right; }
    .identity { margin-top: 13pt; position: relative; z-index: 2; }
    .identity-table { width: 100%; border-collapse: collapse; }
    .photo-cell { width: 48pt; vertical-align: middle; }
    .photo { width: 40pt; height: 40pt; border-radius: 7pt; background: rgba(255,255,255,.3); overflow: hidden; text-align: center; line-height: 40pt; color: #374151; font-size: 16pt; font-weight: bold; }
    .photo img { width: 40pt; height: 40pt; object-fit: cover; }
    .name { font-size: 12pt; font-weight: bold; margin-bottom: 3pt; }
    .site { font-size: 7pt; font-weight: bold; margin-bottom: 3pt; }
    .location { font-size: 6.5pt; }
    .bottom { position: absolute; left: 14pt; right: 14pt; bottom: 10pt; border-top: .5pt solid rgba(255,255,255,.55); padding-top: 6pt; }
    .label { display: block; font-size: 5pt; opacity: .9; margin-bottom: 1pt; }
    .value { display: block; font-size: 6.5pt; font-weight: bold; }
    .back-label { margin-top: 10pt; }
    .back { height: 170pt; background: #fff; text-align: center; border: .8pt solid #dfe3e8; border-radius: 7pt; }
    .qr { width: 78pt; height: 78pt; margin: 25pt auto 0; }
    .qr img { width: 78pt; height: 78pt; }
    .qr-missing { border: 1pt dashed #d1d5db; color: #9ca3af; font-size: 7pt; line-height: 78pt; }
    .caption { margin-top: 10pt; font-size: 6.5pt; }
    .worker-id { margin-top: 3pt; color: #9ca3af; font-size: 5.5pt; }
</style>
</head>
<body>
<div class="section-label">FRONT</div>
<div class="card front">
    <div class="glow"></div>
    <table class="top"><tr><td><?= $e($cardData['company_name']) ?></td><td><?= $e($cardData['role_name']) ?></td></tr></table>
    <div class="identity">
        <table class="identity-table"><tr>
            <td class="photo-cell"><div class="photo"><?php if ($cardData['photo_data_uri']): ?><img src="<?= $e($cardData['photo_data_uri']) ?>"><?php else: ?><?= $e($cardData['initial']) ?><?php endif; ?></div></td>
            <td><div class="name"><?= $e($cardData['full_name']) ?></div><div class="site"><?= $e($cardData['site_name']) ?></div><div class="location">&#9679; <?= $e($cardData['location']) ?></div></td>
        </tr></table>
    </div>
    <div class="bottom"><span class="label">SCAN LOCATION</span><span class="value"><?= $e($cardData['location']) ?></span></div>
</div>
<div class="section-label back-label">BACK</div>
<div class="card back">
    <div class="qr"><?php if ($cardData['qr_data_uri']): ?><img src="<?= $e($cardData['qr_data_uri']) ?>"><?php else: ?><div class="qr-missing">QR unavailable</div><?php endif; ?></div>
    <div class="caption">Scan this QR code at the Timekeeper</div>
    <div class="worker-id">Worker ID: <?= $e($cardData['display_id']) ?></div>
</div>
</body>
</html>

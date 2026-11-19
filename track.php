<?php require 'includes/layout.php';need();$id=(int)($_GET['id']??0);top('Live Tracking','Overview'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<div class="eyebrow">Live delivery tracking</div><h1 class="page" id="t">Delivery man location</h1><p class="sub" id="s">Waiting for volunteer location…</p><div id="map" style="height:480px;border-radius:14px"></div>
<script>const m=L.map('map').setView([23.8103,90.4125],12);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(m);let mk;
async function go(){const r=await (await fetch('<?=BASE?>/api/location.php?id=<?=$id?>')).json();if(r.lat){const p=[r.lat,r.lng];if(!mk)mk=L.marker(p).addTo(m);mk.setLatLng(p).bindPopup('🛵 '+r.vname);m.setView(p,15);s.textContent='Status: '+r.status+' · updated '+r.t}else if(r.status)s.textContent='Status: '+r.status+' · volunteer has not shared location yet'}
go();setInterval(go,5000)</script><?php bottom();

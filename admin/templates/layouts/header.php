<!doctype html>
<html>
<head>
	<title>Wheeliams Dashboard <?= perch_pages_title() ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
	<link href="/assets/css/style.css?v=<?= rand() ?>" rel="stylesheet">
	
	<link rel="manifest" href="/manifest.json" />
	<!-- ios support -->
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-72x72.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-96x96.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-128x128.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-144x144.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-152x152.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-192x192.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-384x384.png" />
	<link rel="apple-touch-icon" href="/assets/images/icons/icon-512x512.png" />
	<meta name="apple-mobile-web-app-status-bar" content="#000000" />
	<meta name="theme-color" content="#f5f1e9" />
	
	<link rel="stylesheet" href="/assets/redactor/redactor.min.css">
	
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"
			  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
			  crossorigin="anonymous"></script>
			  
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/luxon/3.4.4/luxon.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-adapter-luxon/1.3.1/chartjs-adapter-luxon.umd.min.js"></script>
	
	<link rel="stylesheet" href="//cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
	<script src="//cdn.datatables.net/2.3.8/js/dataTables.min.js" type="text/javascript"></script>
	
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	
	<script>
		// ── Part file manager ───────────────────────────────────────────────
		// Renders a part's Drive documents grouped by area, with upload-into-area,
		// delete (which archives, recoverably), restore, and an overwrite prompt on
		// name clashes. The areas come from opts.categories (KITs use the fixed
		// numbered folders; other parts use a single General area). Kits also get a
		// "Component Drawings" list linking to each child part's own file area.
		function wheeliamsInitFileManager(mountId, code, type, opts) {
			opts = opts || {};
			var canEdit  = opts.canEdit !== false;
			var kitLinks = opts.kitLinks || [];
			var mount = document.getElementById(mountId);
			if (!mount) return;
			var CATS = (opts.categories && opts.categories.length) ? opts.categories : ['General'];
			var BLOCKED = ['sldprt', 'sldasm', 'slddrw', 'slddrt', 'sldlfp'];

			mount.innerHTML = '';
			var root = document.createElement('div');
			root.className = 'file-manager flow';
			mount.appendChild(root);

			// Component-drawing links (kits only).
			if (kitLinks.length) {
				var cd = document.createElement('section');
				cd.className = 'fm-section flow';
				var ch = '<h3>Component Drawings</h3><ul class="fm-links">';
				kitLinks.forEach(function (k) {
					ch += '<li><a href="' + k.url + '">' + escapeHtml(k.partCode) + '</a>'
						+ (k.description ? ' <span class="muted">' + escapeHtml(k.description) + '</span>' : '') + '</li>';
				});
				ch += '</ul>';
				cd.innerHTML = ch;
				root.appendChild(cd);
			}

			// Existing files first, then the upload control below them.
			var listBox = document.createElement('div');
			listBox.className = 'fm-list';
			listBox.innerHTML = 'Loading files&hellip;';
			root.appendChild(listBox);

			// Upload control. The area picker only appears when there's a real
			// choice (kits); single-area parts upload straight into that area.
			if (canEdit) {
				var up = document.createElement('div');
				up.className = 'fm-upload';
				var picker = '';
				if (CATS.length > 1) {
					var options = CATS.map(function (c, i) { return '<option value="' + escapeHtml(c) + '"' + (i === 0 ? ' selected' : '') + '>' + escapeHtml(c) + '</option>'; }).join('');
					picker = '<label class="fm-cat-label">Upload to area: <select class="fm-category">' + options + '</select></label>';
				} else {
					picker = '<input type="hidden" class="fm-category" value="' + escapeHtml(CATS[0]) + '">';
				}
				up.innerHTML =
					'<h3>Attach a File</h3>'
					+ picker
					+ '<div class="drag-and-drop fm-drop"><label>Drop file here</label></div>'
					+ '<div class="fm-browse-row"><label class="button small subtle">Browse for a file&hellip;<input type="file" class="fm-browse" hidden></label> <span class="fm-status muted"></span></div>';
				root.appendChild(up);
			}

			var arc = document.createElement('div');
			arc.className = 'fm-archived';
			arc.innerHTML = '<button type="button" class="button small subtle fm-show-archived">Show archived</button><div class="fm-archived-list" hidden></div>';
			root.appendChild(arc);

			// Icon-only action button (Material Symbols). The title/aria-label keep
			// it clear for mouse hover and screen readers.
			function fmIcon(name) { return '<span class="material-symbols-outlined" aria-hidden="true">' + name + '</span>'; }

			function fileRow(f, archived) {
				var id = encodeURIComponent(f.id);
				var icon = getFileIcon(f.mimeType || '');
				var actions = '';
				if (/\.pdf$/i.test(f.name)) {
					actions += '<a href="/drive_download.php?id=' + id + '&inline=1" target="_blank" rel="noopener" class="button small subtle fm-icon-btn" title="View" aria-label="View">' + fmIcon('visibility') + '</a> ';
				}
				actions += '<a href="/drive_download.php?id=' + id + '" download="' + escapeHtml(f.name) + '" class="button small subtle fm-icon-btn" title="Download" aria-label="Download">' + fmIcon('download') + '</a>';
				if (canEdit) {
					actions += archived
						? ' <button type="button" class="button small subtle fm-icon-btn fm-restore" data-id="' + escapeHtml(f.id) + '" title="Restore" aria-label="Restore">' + fmIcon('restore_from_trash') + '</button>'
						: ' <button type="button" class="button small danger fm-icon-btn fm-archive" data-id="' + escapeHtml(f.id) + '" data-name="' + escapeHtml(f.name) + '" title="Delete (archive)" aria-label="Delete">' + fmIcon('delete') + '</button>';
				}
				return '<li><span class="file-icon">' + icon + '</span> ' + escapeHtml(f.name) + ' <span class="fm-actions">' + actions + '</span></li>';
			}

			function renderList(files) {
				if (!files || !files.length) { listBox.innerHTML = '<p class="no-files">No files attached yet.</p>'; return; }
				var groups = {};
				files.forEach(function (f) { var c = f.category || 'General'; (groups[c] = groups[c] || []).push(f); });
				// Canonical areas first (01, 02, 03 … in their defined order), then any
				// other folders found, with 'General' always pushed to the very end.
				var order = CATS.slice();
				var extras = Object.keys(groups).filter(function (c) { return order.indexOf(c) === -1 && c !== 'General'; }).sort();
				order = order.concat(extras);
				if (order.indexOf('General') === -1) order.push('General');
				var html = '';
				order.forEach(function (c) {
					if (!groups[c]) return;
					html += '<section class="fm-section flow"><h3>' + escapeHtml(c) + '</h3><ul class="file-list">';
					groups[c].forEach(function (f) { html += fileRow(f, false); });
					html += '</ul></section>';
				});
				listBox.innerHTML = html;
			}

			function loadFiles() {
				listBox.innerHTML = 'Loading files&hellip;';
				fetch('/drive_files.php?productCode=' + encodeURIComponent(code) + '&type=' + encodeURIComponent(type))
					.then(function (r) { return r.json(); })
					.then(function (d) { if (d.error) { listBox.innerHTML = '<p class="error">' + escapeHtml(d.error) + '</p>'; return; } renderList(d.files); })
					.catch(function () { listBox.innerHTML = '<p class="error">Error loading files.</p>'; });
			}

			function loadArchived() {
				var box = root.querySelector('.fm-archived-list');
				if (!box || box.hidden) return;
				box.innerHTML = 'Loading&hellip;';
				fetch('/drive_files.php?scope=archived&productCode=' + encodeURIComponent(code) + '&type=' + encodeURIComponent(type))
					.then(function (r) { return r.json(); })
					.then(function (d) {
						if (d.error) { box.innerHTML = '<p class="error">' + escapeHtml(d.error) + '</p>'; return; }
						if (!d.files || !d.files.length) { box.innerHTML = '<p class="no-files">No archived files.</p>'; return; }
						var html = '<ul class="file-list">';
						d.files.forEach(function (f) { html += fileRow(f, true); });
						html += '</ul>';
						box.innerHTML = html;
					})
					.catch(function () { box.innerHTML = '<p class="error">Error loading archived files.</p>'; });
			}

			function doUpload(file, overwrite) {
				if (!file) return;
				var statusEl = root.querySelector('.fm-status');
				var ext = (file.name.split('.').pop() || '').toLowerCase();
				if (BLOCKED.indexOf(ext) !== -1) {
					alert('Native SolidWorks files (.' + ext + ') can’t be attached here. Please attach a PDF, DXF, STEP, image or document.');
					return;
				}
				var cat = (root.querySelector('.fm-category') || {}).value || 'General';
				if (statusEl) statusEl.textContent = 'Uploading ' + file.name + '…';
				var fd = new FormData();
				fd.append('file', file); fd.append('type', type); fd.append('partCode', code); fd.append('category', cat);
				if (overwrite) fd.append('overwrite', '1');
				fetch('/upload_to_drive.php', { method: 'POST', body: fd })
					.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; }); })
					.then(function (res) {
						if (res.status === 409 && res.body.collision) {
							if (confirm('“' + res.body.fileName + '” already exists in ' + res.body.category + '. Replace it? The existing file will be archived.')) {
								doUpload(file, true);
							} else if (statusEl) { statusEl.textContent = ''; }
							return;
						}
						if (!res.ok || res.body.error) { throw new Error(res.body.error || 'Upload failed'); }
						if (statusEl) statusEl.textContent = '✓ Uploaded ' + res.body.fileName;
						loadFiles();
					})
					.catch(function (err) { if (statusEl) statusEl.textContent = '✗ ' + err.message; });
			}

			function fileAction(action, id) {
				var fd = new FormData();
				fd.append('action', action); fd.append('id', id); fd.append('type', type); fd.append('partCode', code);
				return fetch('/drive_file_action.php', { method: 'POST', body: fd }).then(function (r) { return r.json(); });
			}

			if (canEdit) {
				var drop = root.querySelector('.fm-drop'), browse = root.querySelector('.fm-browse');
				if (drop) {
					drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('drag-over'); });
					drop.addEventListener('dragleave', function () { drop.classList.remove('drag-over'); });
					drop.addEventListener('drop', function (e) { e.preventDefault(); drop.classList.remove('drag-over'); doUpload(e.dataTransfer.files[0], false); });
				}
				if (browse) { browse.addEventListener('change', function () { doUpload(browse.files[0], false); browse.value = ''; }); }
			}

			root.addEventListener('click', function (e) {
				// closest() so a click on the icon <span> inside a button still matches.
				var archiveBtn = e.target.closest ? e.target.closest('.fm-archive') : null;
				var restoreBtn = e.target.closest ? e.target.closest('.fm-restore') : null;
				var showBtn    = e.target.closest ? e.target.closest('.fm-show-archived') : null;
				if (archiveBtn) {
					if (!confirm('Archive “' + archiveBtn.getAttribute('data-name') + '”? It will be hidden from the list but can be restored.')) return;
					archiveBtn.disabled = true;
					fileAction('archive', archiveBtn.getAttribute('data-id')).then(function (d) { if (d.error) { alert(d.error); archiveBtn.disabled = false; return; } loadFiles(); loadArchived(); });
				} else if (restoreBtn) {
					restoreBtn.disabled = true;
					fileAction('restore', restoreBtn.getAttribute('data-id')).then(function (d) { if (d.error) { alert(d.error); restoreBtn.disabled = false; return; } loadFiles(); loadArchived(); });
				} else if (showBtn) {
					var box = root.querySelector('.fm-archived-list');
					if (box.hidden) { box.hidden = false; showBtn.textContent = 'Hide archived'; loadArchived(); }
					else { box.hidden = true; showBtn.textContent = 'Show archived'; }
				}
			});

			loadFiles();
		}

		function getFileIcon(mimeType) {
		  if (mimeType.includes('pdf'))        return '📄';
		  if (mimeType.includes('image'))      return '🖼️';
		  if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return '📊';
		  if (mimeType.includes('document')   || mimeType.includes('word'))   return '📝';
		  if (mimeType.includes('video'))      return '🎬';
		  if (mimeType.includes('zip')        || mimeType.includes('compressed')) return '🗜️';
		  return '📎';
		}
		
		function escapeHtml(str) {
		  return str.replace(/[&<>"']/g, c => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
		  })[c]);
		}
	</script>
</head>
<body>
	<header class="site-header">
		<?php
			if(perch_member_logged_in()){
		?>
		<button class="menu">
			<span class="material-symbols-outlined">
				menu
			</span>
		</button>
		<?php
			}
		?>
			
		<h2><span>Wheeliams</span></h2>
		<nav>
			<?php
				if(perch_member_logged_in()){
			?>
			<div class="main-nav-container">
				<?php
				perch_pages_navigation();
  				?>
			</div>
			<button id="account-nav">
				<span class="material-symbols-outlined">
				account_circle
				</span>
			</button>
			<ul class="account-nav">
			<?php 
				if(perch_member_logged_in()){
					if(staff_status()){
						echo '<li><a href="/clock-out/"><span class="material-symbols-outlined">timer</span>Clock Out</a></li>';
					}else{
						echo '<li><a href="/clock-in/"><span class="material-symbols-outlined">timer</span>Clock In</a></li>';
					}	
				}
				perch_pages_navigation(array(
					'navgroup' => 'account',
					'template' => 'account.html'
  				));
				}
				
			?>
			</ul>
		</nav>
	</header>
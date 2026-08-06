<?php if (!defined('PERCH_RUNWAY')) include($_SERVER['DOCUMENT_ROOT'].'/admin/runtime.php'); ?>
<?php
// Job Details — read-only multilevel BOM + documents. Viewable by all levels (L1+).
if(!perch_member_logged_in()){
	header('location:/');
	exit;
}
wheeliams_require_level('view');

$id        = (int)($_GET['id'] ?? 0);
$component = component($id);

perch_layout('header');
?>
<main class="full">
<?php
if(!$component){
	echo '<h1>Job Details</h1><p>Job not found.</p>';
}else{
	$dyn       = json_decode($component['dynamicFields'], true) ?: array();
	$code      = $component['partCode'];
	$driveType = wheeliams_drive_type_for($code, $component['type']);

	echo '<p class="no-print"><a href="/" class="button back">&larr; Dashboard</a> ';
	echo '</p>';

	echo '<h1>'.htmlspecialchars($code).' &mdash; '.htmlspecialchars($dyn['part_description'] ?? '').'</h1>';

	echo '<h2>Bill of Materials</h2>';
	wheeliams_bom_explosion_table($id, false, null, true); // read-only, with per-row Files

	echo '<h2>Documents</h2>';
	echo '<div id="job-docs" data-code="'.htmlspecialchars($code, ENT_QUOTES).'" data-type="'.htmlspecialchars($driveType, ENT_QUOTES).'">Loading documents&hellip;</div>';
	
	if(perch_member_has_tag('admin')){
		$bt = wheeliams_bom_type_for_partcode($code);
		if($bt){ echo '<a class="button" href="/boms/?type='.$bt.'&edit=1&id='.$id.'">Edit BOM</a>'; }
	}

	echo <<<'JS'
<style>
.files-modal{ position:fixed; inset:0; z-index:2000; display:none; align-items:center; justify-content:center; }
.files-modal.open{ display:flex; }
.files-modal-backdrop{ position:absolute; inset:0; background:rgba(0,0,0,.55); }
.files-modal-box{ position:relative; background:#fff; max-width:600px; width:90%; max-height:80vh; overflow:auto; padding:1.5rem 1.75rem; border-radius:6px; box-shadow:0 12px 44px rgba(0,0,0,.3); }
.files-modal-close{ position:absolute; top:.35rem; right:.6rem; border:0; background:none; font-size:1.7rem; line-height:1; cursor:pointer; }
.files-modal-box h4{ margin:1rem 0 .25rem; }
</style>
<div id="filesModal" class="files-modal" aria-hidden="true">
	<div class="files-modal-backdrop" data-close></div>
	<div class="files-modal-box">
		<button type="button" class="files-modal-close" data-close aria-label="Close">&times;</button>
		<h3 id="filesModalTitle">Files</h3>
		<div id="filesModalBody">Loading&hellip;</div>
	</div>
</div>
<script>
// Top-level Documents section (loaded into the page).
(function(){
	function render(container, files){
		if(!files || !files.length){ container.innerHTML = '<p>No documents found.</p>'; return; }
		var groups = {};
		files.forEach(function(f){
			var cat = f.path || 'General Documents';
			(groups[cat] = groups[cat] || []).push(f);
		});
		var html = '';
		Object.keys(groups).sort().forEach(function(cat){
			html += '<section class="doc-group flow"><h3>' + cat + '</h3><ul>';
			groups[cat].forEach(function(f){
				html += '<li><a href="' + f.webViewLink + '" target="_blank" rel="noopener">' + f.name + '</a></li>';
			});
			html += '</ul></section>';
		});
		container.innerHTML = html;
	}
	var docs = document.getElementById('job-docs');
	if(docs){
		var url = '/drive_files.php?productCode=' + encodeURIComponent(docs.dataset.code) + '&type=' + encodeURIComponent(docs.dataset.type);
		fetch(url).then(function(r){ return r.json(); })
			.then(function(d){ if(d.error){ docs.innerHTML = d.error; } else { render(docs, d.files); } })
			.catch(function(){ docs.innerHTML = 'Error loading documents.'; });
	}
})();

function wheeliamsFilesModalClose(){ document.getElementById('filesModal').classList.remove('open'); }

// Per-BOM-row "Files" button — opens the drawings in a modal.
function wheeliamsJobFiles(code, type){
	var modal = document.getElementById('filesModal');
	document.getElementById('filesModalTitle').textContent = code + ' — Files';
	var body = document.getElementById('filesModalBody');
	body.textContent = 'Loading…';
	modal.classList.add('open');
	fetch('/drive_files.php?productCode=' + encodeURIComponent(code) + '&type=' + encodeURIComponent(type))
		.then(function(r){ return r.json(); })
		.then(function(d){
			if(d.error){ body.textContent = d.error; return; }
			if(!d.files || !d.files.length){ body.textContent = 'No files found.'; return; }
			var groups = {};
			d.files.forEach(function(f){ var cat = f.path || 'Files'; (groups[cat] = groups[cat] || []).push(f); });
			var html = '';
			Object.keys(groups).sort().forEach(function(cat){
				html += '<h4>' + cat + '</h4><ul>';
				groups[cat].forEach(function(f){ html += '<li><a href="' + f.webViewLink + '" target="_blank" rel="noopener">' + f.name + '</a></li>'; });
				html += '</ul>';
			});
			body.innerHTML = html;
		})
		.catch(function(){ body.textContent = 'Error loading files.'; });
}

document.addEventListener('click', function(e){ if(e.target.hasAttribute('data-close')) wheeliamsFilesModalClose(); });
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') wheeliamsFilesModalClose(); });
</script>
JS;
}
?>
</main>
<?php
perch_layout('footer');
?>

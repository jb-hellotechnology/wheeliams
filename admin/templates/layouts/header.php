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
		async function loadProductFiles(productCode, type) {
			const container = document.getElementById('product-files-list');
			container.innerHTML = 'Loading files…';
			const params = new URLSearchParams({ productCode, type });
		
			try {
				const response = await fetch(`/organise_drive_files.php?${params}`);
				const data = await response.json();
				if (data.error) throw new Error(data.error);
		
				if (!data.files.length) {
					container.innerHTML = '<p class="no-files">No files found for this product code.</p>';
					return;
				}
		
				// Group files by their path
				const groups = {};
				data.files.forEach(file => {
					const key = file.path || '';
					if (!groups[key]) groups[key] = [];
					groups[key].push(file);
				});
		
				// Sort groups so root ('') comes first, then alphabetically
				const sortedPaths = Object.keys(groups).sort((a, b) => {
					if (a === '') return -1;
					if (b === '') return 1;
					return a.localeCompare(b);
				});
		
				const wrapper = document.createDocumentFragment();
		
				sortedPaths.forEach(path => {
					if (path) {
						const heading = document.createElement('p');
						heading.className = 'file-folder-path';
						heading.textContent = '📁 ' + path;
						wrapper.appendChild(heading);
					}
		
					const ul = document.createElement('ul');
					ul.className = 'file-list';
		
					groups[path].forEach(file => {
						const li = document.createElement('li');
						const icon = getFileIcon(file.mimeType);
						li.innerHTML = `
							<span class="file-icon">${icon}</span>
							<a href="${file.webViewLink}" target="_blank" rel="noopener noreferrer">
								${escapeHtml(file.name)}
							</a>
						`;
						ul.appendChild(li);
					});
		
					wrapper.appendChild(ul);
				});
		
				container.innerHTML = '';
				container.appendChild(wrapper);
		
			} catch (err) {
				container.innerHTML = `<p class="error">Error: ${err.message}</p>`;
			}
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
	<p id="notification"></p>

	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.0/fullcalendar.min.css" />
	<link rel="stylesheet" href="/admin/addons/apps/wheeliams/assets/fullcalendar-scheduler/scheduler.css" />
			  
	<script type="text/javascript" src="/admin/addons/apps/wheeliams/assets/moment.js"></script>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.0/fullcalendar.min.js"></script>
	<script src='/admin/addons/apps/wheeliams/assets/fullcalendar-scheduler/scheduler.js'></script>
	
	<script src="/assets/js/scripts.js?v=<?= rand() ?>"></script>
	<script src="/assets/redactor/redactor.min.js"></script>
	<script>
	$(document).ready( function () {
		$('.datatable').DataTable({
			aLengthMenu: [
				[10, 25, 50, 100, 200, -1],
				[10, 25, 50, 100, 200, "All"]
			]
		});
	} );
	$R('.redactor');
	</script>
	<script>
	// In your Javascript (external .js resource or <script> tag)
	$(document).ready(function() {
		$('.select-2').select2();
	});
	document.addEventListener('DOMContentLoaded', () => {
		const forms = document.querySelectorAll('.ajax-form');
	
		forms.forEach(form => {
			form.addEventListener('focusout', async (e) => {
				if (!e.target.matches('input, select, textarea')) return;
		
				const formData = new FormData(form);
		
				try {
					const response = await fetch(form.action, {
						method: 'POST',
						body: formData,
						headers: { 'X-Requested-With': 'XMLHttpRequest' }
					});
		
					if (response.ok) {
						console.log('Form submitted successfully');
						alert('Saved');
					} else {
						console.error('Server returned an error', response.status);
					}
				} catch (error) {
					console.error('Request failed', error);
				}
			});
			
			form.addEventListener('change', async (e) => {
				if (!e.target.matches('input, select, textarea')) return;
			
				const formData = new FormData(form);
			
				try {
					const response = await fetch(form.action, {
						method: 'POST',
						body: formData,
						headers: { 'X-Requested-With': 'XMLHttpRequest' }
					});
			
					if (response.ok) {
						console.log('Form submitted successfully');
					} else {
						console.error('Server returned an error', response.status);
					}
				} catch (error) {
					console.error('Request failed', error);
				}
			});
		});
		
		document.querySelectorAll('button.danger, input[type="submit"].danger').forEach(btn => {
			btn.addEventListener('click', (e) => {
				if (!confirm('Are you sure?')) {
					e.preventDefault();
				}
			});
		});
	});
	</script>
	<?php PerchUtil::output_debug(); ?>
	</body>
</html>
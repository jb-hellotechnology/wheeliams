$('button.menu').click(function(){
	$('.main-nav-container').toggleClass('show');
});

$('#account-nav').click(function(){
	$('.account-nav').toggleClass('show');
});
	
$('.hide').click(function(){
	var pOrder = $(this).data('order-id');
	var pItem = $(this).data('item-id');
	$.post( "hide.php", { order: pOrder, item: pItem }, function(){
		location.reload();
	});
});

$('.complete').click(function(){
	var pOrder = $(this).data('order-id');
	var pItem = $(this).data('item-id');
	var pSku = $(this).data('sku');
	var pQty = $(this).data('qty');
	$.post( "update.php", { order: pOrder, item: pItem, sku: pSku, qty: pQty } );
	if($(this).is(':checked')){
		$(this).parent().parent().addClass('strike');
	}else{
		$(this).parent().parent().removeClass('strike');
	}
});

$('.break').change(function(){
	let pDate = $(this).data('date');
	let pBreakLength = $(this).val();
	$('#notification').show().text('Saving...');
	$.post( "/break/", { date: pDate, breakLength: pBreakLength }).done(function( data ) {
		$('#notification').text('Saved!').fadeOut('slow');
	});
});

$('h2.full').click(function(){
	$(this).parent().next('article').toggleClass('show');
});

/* HOLIDAY REQUESTS */
$('.delete-request').click(function(){
	var requestID = $(this).data('requestid');
	var $element = $(this);
	$.post( "/ajax/delete-request/index.php", { id: requestID }, function(data){
		$element.closest('li').remove();
	} );
});

$('.decline-request').click(function(){
	var requestID = $(this).data('requestid');
	var $element = $(this);
	$.post( "/ajax/decline-request/index.php", { id: requestID }, function(data){
		$element.closest('tr').remove();
	} );
});

$('.approve-request').click(function(){
	var requestID = $(this).data('requestid');
	var $element = $(this);
	$.post( "/ajax/approve-request/index.php", { id: requestID }, function(data){
		$element.closest('tr').remove();
	} );
});

$('.delete-holiday').click(function(){
	var holiday = $(this).data('holiday');
	var $element = $(this);
	$.post( "/ajax/delete-holiday/index.php", { id: holiday, type: 'holiday' }, function(data){
		window.location.href = "/staff/holidays/";
	} );
});

$('.delete-holiday-day').click(function(){
	var holiday = $(this).data('holiday');
	var $element = $(this);
	$.post( "/ajax/delete-holiday/index.php", { id: holiday, type: 'day' }, function(data){
		window.location.href = "/staff/holidays/";
	} );
});

/* TIME */
$(document).on('change', '.time', function(){
	var id = $(this).data('staff');
	var t = $(this).data('type');
	var d = $(this).data('date');
	var ti = $(this).val();
	console.log(id + ' ' + t + ' ' + d + ' ' + ti);
	if(ti=='00:00'){
		$(this).val("");
	}
	$.post("/ajax/update-time/index.php", { staffID: id, type: t, date: d, time: ti }, function(data){
		var $cell = $('td[data-date="' + d + '"][data-staff="' + id + '"]');
		$cell.html(data);
		$cell.css('background-color', '#4CAF50');
		setTimeout(function(){
			$cell.css('background-color', '');
		}, 1000);
	});
});

function addFormConfirmation(formSelector, message) {
  const forms = document.querySelectorAll(formSelector);

  if (!forms.length) {
	console.warn(`No forms found for selector "${formSelector}".`);
	return;
  }

  forms.forEach(function (form) {
	form.addEventListener("submit", function (event) {
	  const confirmed = window.confirm(message || "Are you sure you want to submit?");
	  if (!confirmed) {
		event.preventDefault();
	  }
	});
  });
}

addFormConfirmation(".confirm", "Are you sure?");


(function () {
  // Warn about unsaved changes — but ONLY when the values actually differ from
  // how the form loaded. We snapshot each form after everything has settled
  // (Perch pre-fill, Select2, autofill) and compare values on the way out, so
  // programmatic/auto-population events never count as "the user made changes".
  const forms = Array.from(document.querySelectorAll('form'));
  if (!forms.length) return;

  let submitting = false;
  const snapshots = new WeakMap();

  function serialize(form) {
	const parts = [];
	Array.from(form.elements).forEach((el) => {
	  if (!el.name || el.disabled) return;
	  const t = (el.type || '').toLowerCase();
	  // Ignore buttons, files, and hidden system fields (component IDs, type, etc.)
	  if (t === 'submit' || t === 'button' || t === 'reset' || t === 'file' || t === 'hidden') return;
	  if (t === 'checkbox' || t === 'radio') {
		parts.push(el.name + '=' + (el.checked ? '1' : '0'));
	  } else if (el.multiple) {
		parts.push(el.name + '=' + Array.from(el.selectedOptions).map((o) => o.value).join(','));
	  } else {
		parts.push(el.name + '=' + el.value);
	  }
	});
	return parts.join('&');
  }

  function snapshotAll() {
	forms.forEach((f) => snapshots.set(f, serialize(f)));
  }

  function anyDirty() {
	if (submitting) return false;
	return forms.some((f) => serialize(f) !== snapshots.get(f));
  }

  // Take the baseline once the page (and its widgets/autofill) has settled.
  snapshotAll(); // best-effort immediately
  window.addEventListener('load', () => setTimeout(snapshotAll, 300));

  // A real submit is an intentional save — never warn on that navigation.
  forms.forEach((f) => f.addEventListener('submit', () => { submitting = true; }));

  // Covers tab close, refresh, back button, in-app links and address-bar nav.
  window.addEventListener('beforeunload', (e) => {
	if (anyDirty()) {
	  e.preventDefault();
	  e.returnValue = '';
	}
  });
})();
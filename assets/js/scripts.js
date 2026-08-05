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
  const form = document.querySelector('form'); // or getElementById('myForm')
  let isDirty = false;

  // Mark dirty on any change
  form.addEventListener('input', () => { isDirty = true; });
  form.addEventListener('change', () => { isDirty = true; });

  // Clear flag when the form is actually submitted/saved
  form.addEventListener('submit', () => { isDirty = false; });

  // Browser back button, tab close, refresh, address bar navigation
  window.addEventListener('beforeunload', (e) => {
	if (isDirty) {
	  e.preventDefault();
	  e.returnValue = ''; // required by some browsers
	}
  });

  // Links on the page
  document.addEventListener('click', (e) => {
	const link = e.target.closest('a[href]');
	if (link && isDirty) {
	  if (!confirm('You have unsaved changes. Leave without saving?')) {
		e.preventDefault();
	  }
	}
  });
})();
$(document).ready(function(){
	rate_init();
});


function rate_init() {
	if (!$('.rate-container').length) return;
	$('.rate-container').rate_init();
}

$.fn.rate_init=function() {
	var r = /^rate-nid-([\d]+)$/;
	var m = r.exec($(this).attr('id'));
	if (!m) return;
	
	var nid=m[1];
	rate_build_container($(this),nid);
}

function rate_build_container(elem,nid) {
	$.post(
		site+'/index.php?location=vote',
		{
			nid: nid
		},
		rate_build_container_callback,
		'json'
		);
}

function rate_build_container_callback(response) {
	if (!response) return;
	if (!$('#rate-nid-'+response.nid).length) return;
	$('#rate-nid-'+response.nid).html('');
	if (response.allow) {
		var stars=Math.round(response.rate);
		for(var i=1;i<=5;i++) {
			if (i<=stars) {
				$('#rate-nid-'+response.nid).append('<div class="rate-star rate-star-yellow" id="rate-star-'+response.nid+'-'+i+'"></div>');
			} else {
				$('#rate-nid-'+response.nid).append('<div class="rate-star rate-star-grey" id="rate-star-'+response.nid+'-'+i+'"></div>');
			}
		}
	}
	$('#rate-nid-'+response.nid).append('<div class="rate-info-container"><div class="rate-info"><label>'+top_rating_str+':</label>'+response.rate+'</div><div class="rate-info"><label>'+top_voted_str+':</label>'+response.total+'</div></div>');

	rate_container_init($('#rate-nid-'+response.nid));
}

function rate_container_init(elem) {
	$(elem).find('.rate-star').hover(
		function() {
			var r = /^rate-star-([\d]+)-([\d]+)$/;
			var m = r.exec($(this).attr('id'));
			if (m) {
				for (var i=1;i<=5;i++) {
					if (i<=m[2]) {
						$('#rate-star-'+m[1]+'-'+i).addClass('rate-yellowed');
					} else {
						$('#rate-star-'+m[1]+'-'+i).addClass('rate-greyed');
					}
				}
			}
		},
		function() {
			var r = /^rate-star-([\d]+)-([\d]+)$/;
			var m = r.exec($(this).attr('id'));
			if (m) {
				for (var i=1;i<=5;i++) {
					$('#rate-star-'+m[1]+'-'+i).removeClass('rate-yellowed');
					$('#rate-star-'+m[1]+'-'+i).removeClass('rate-greyed');
				}
			}
		}
	);
	$(elem).find('.rate-star').click(function(){
		var r = /^rate-star-([\d]+)-([\d]+)$/;
		var m = r.exec($(this).attr('id'));
		if (m) {
			$.post(
				site+'/index.php?location=vote',
				{
					nid: m[1],
					rate: m[2]
				},
				rate_build_container_callback,
				'json'
				);
		}
	});
}

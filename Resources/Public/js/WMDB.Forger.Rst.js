$(document).ready(function () {
	var $ticketType = $('.js-changetypes a'),
		$ticketField = $('#ticketid'),
		$titleField = $('#title'),
		$generateButton = $('#generate'),
		$modal = $('#resultModal'),
		$activeChangeType = null,
		$lastActiveTextarea = null;

	$('textarea').on('focus', function () {
		$lastActiveTextarea = $(this);
	});

	// build text-to-tickettype tree
	var typeTextTree = {},
		$ticketTypeRelatedFields = $('[data-tickettypes]');

	$ticketTypeRelatedFields.each(function () {
		var $textfield = $(this),
			types = $textfield.data('tickettypes').split(' ');

		$.each(types, function (_, type) {
			if (typeof typeTextTree[type] === 'undefined') {
				typeTextTree[type] = [];
			}
			typeTextTree[type].push($textfield);
		});
	});

	$ticketType.on('click', function (e) {
		e.preventDefault();

		var $me = $(this),
			value = $me.data('value');

		$activeChangeType = $me;
		$ticketType.removeClass('warning');
		$me.addClass('warning');
		$ticketTypeRelatedFields.addClass('hide');

		if (typeof typeTextTree[value] !== 'undefined') {
			$generateButton.removeClass('hide');
			$.each(typeTextTree[value], function (_, $affectedTicketTypeField) {
				$affectedTicketTypeField.removeClass('hide');
			});
		} else {
			$generateButton.addClass('hide');
		}
	});

	$('[data-action=load-ticket]').on('click', function () {
		var $trigger = $(this),
			$icon = $trigger.find('.fa'),
			ticketId = parseInt($ticketField.val().replace(/\D/g, ''));

		if (isNaN(ticketId)) {
			return;
		}

		$.ajax({
			url: 'standard/getissuejson',
			data: {
				issueId: ticketId
			},
			dataType: 'json',
			beforeSend: function() {
				$ticketField.attr('disabled', 'disabled');
				$trigger.attr('disabled', 'disabled');
				$icon.addClass('fa-spin');
			},
			success: function(issue) {
				$titleField.val(issue.subject);
				$('#description').val(issue.description);
			},
			complete: function() {
				$ticketField.removeAttr('disabled');
				$trigger.removeAttr('disabled');
				$icon.removeClass('fa-spin');
			}
		});
	});

	$('[data-action=paste-code]').on('click', function () {
		if ($lastActiveTextarea === null) {
			alert('Please activate a textarea first');
			return;
		}
		var snippet = $(this).next().text();
		if (document.selection) {
			$lastActiveTextarea.focus();
			var sel = document.selection.createRange();
			sel.text = snippet;
		} else if ($lastActiveTextarea.prop('selectionStart') || $lastActiveTextarea.prop('selectionStart') === 0) {
			var startPos = $lastActiveTextarea.prop('selectionStart'),
				endPos = $lastActiveTextarea.prop('selectionEnd'),
				fieldValue = $lastActiveTextarea.val();

			$lastActiveTextarea.val(fieldValue.substring(0, startPos) + snippet + fieldValue.substring(endPos, fieldValue.length));
		} else {
			$lastActiveTextarea.val($lastActiveTextarea.val() + snippet);
		}
	});

	// Do the serious stuff here!
	$generateButton.on('click', function () {
		var ticketId = $ticketField.val().replace(/\D/g, ''),
			title = $.trim($titleField.val()),
			ticketType = $activeChangeType.text(),
			headline = sprintf('%s - #%d: %s', ticketType, ticketId, title),
			filename = sprintf('%s-%d-%s.rst', ticketType, ticketId, title.toUpperCamelCase().replace(/[^0-9a-z-_.]/gi, ''));

		var restContent = sprintf('%s\n%s\n%s', '='.repeat(headline.length), headline, '='.repeat(headline.length)),
			restBody = '';

		$('textarea:visible').each(function () {
			var $me = $(this),
				$label = $me.prev(),
				value = $.trim($me.val());

			if (value.length > 0) {
				var labelText = $.trim($label.text());
				restBody += sprintf('\n\n\n%s\n%s\n\n%s', labelText, '='.repeat(labelText.length), value);
			}
		});
		restContent += restBody.replace('\n', '');

		$modal.find('h4').text(filename);
		$modal.find('textarea').val(restContent);

		$modal.foundation('reveal', 'open');
	});

	$(document).on('opened.fndtn.reveal', '[data-reveal]', function () {
		var $modal = $(this);
		$modal.find('textarea').focus().select();
	});
});

String.prototype.toUpperCamelCase = function () {
	return this.replace(/\w\S*/g, function (txt) {
		return txt.charAt(0).toUpperCase() + txt.substr(1);
	}).replace(/\s/g, '');
};

String.prototype.repeat = function (num) {
	return new Array(num + 1).join(this);
};

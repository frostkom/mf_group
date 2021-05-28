jQuery(function($)
{
	var dom_show_and_hide_fields = $("#strGroupAPI, #strGroupAcceptanceEmail, #strGroupAcceptanceSubject, #strGroupAllowRegistration, #strGroupVerifyAddress");

	function show_and_hide_fields()
	{
		dom_show_and_hide_fields.each(function()
		{
			var dom_obj = $(this),
				dom_obj_id = dom_obj.attr('id'),
				dom_obj_val = dom_obj.val();

			switch(dom_obj_id)
			{
				case 'strGroupAPI':
					if(dom_obj_val != '')
					{
						$("#strGroupAPIFilter").parent(".form_textarea").removeClass('hide');

						$("#strGroupAllowRegistration").val('no').parent(".form_select").addClass('hide');
					}

					else
					{
						$("#strGroupAPIFilter").parent(".form_textarea").addClass('hide');

						$("#strGroupAllowRegistration").parent(".form_select").removeClass('hide');
					}
				break;

				case 'strGroupAcceptanceEmail':
					if(dom_obj_val == 'yes')
					{
						$(".display_acceptance_message").removeClass('hide');
					}

					else
					{
						$(".display_acceptance_message").addClass('hide');
					}
				break;

				case 'strGroupAcceptanceSubject':
					if(dom_obj_val != '')
					{
						$(".display_reminder_message").removeClass('hide');
					}

					else
					{
						$(".display_reminder_message").addClass('hide');
					}
				break;

				case 'strGroupAllowRegistration':
					if(dom_obj_val == 'yes')
					{
						$(".display_registration_fields").removeClass('hide');
					}

					else
					{
						$(".display_registration_fields").addClass('hide');
					}
				break;

				case 'strGroupVerifyAddress':
					if(dom_obj_val == 'yes')
					{
						$("#intGroupContactPage").parent(".form_select").removeClass('hide');
					}

					else
					{
						$("#intGroupContactPage").val('').parent(".form_select").addClass('hide');
					}
				break;
			}
		});
	}

	show_and_hide_fields();

	dom_show_and_hide_fields.on('keyup change', function()
	{
		show_and_hide_fields();
	});
});
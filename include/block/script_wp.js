(function()
{
	var __ = wp.i18n.__,
		el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		MediaUpload = wp.blockEditor.MediaUpload,
	    Button = wp.components.Button,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck;

	registerBlockType('mf/group',
	{
		title: __("Group", 'lang_group'),
		description: __("Display a Group", 'lang_group'),
		icon: 'groups', /* https://developer.wordpress.org/resource/dashicons/ */
		category: 'widgets', /* common, formatting, layout, widgets, embed */
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'group_heading':
			{
                'type': 'string',
                'default': ''
            },
			'group_text':
			{
                'type': 'string',
                'default': ''
            },
			'group_id':
			{
                'type': 'string',
                'default': ''
            },
			'group_label_type':
			{
                'type': 'string',
                'default': ''
            },
			'group_display_consent':
			{
                'type': 'string',
                'default': ''
            },
			'group_button_text':
			{
                'type': 'string',
                'default': ''
            },
			'group_button_icon':
			{
                'type': 'string',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			}
		},
		edit: function(props)
		{
			var arr_out = [];
			
			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Heading", 'lang_group'),
						type: 'text',
						value: props.attributes.group_heading,
						/*help: __("Description...", 'lang_group'),*/
						onChange: function(value)
						{
							props.setAttributes({group_heading: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Text", 'lang_group'),
						type: 'text',
						value: props.attributes.group_text,
						/*help: __("Description...", 'lang_group'),*/
						onChange: function(value)
						{
							props.setAttributes({group_text: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_group_block_wp.group_id, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Link", 'lang_group'),
						value: props.attributes.group_id,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({group_id: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_group_block_wp.group_label_type, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Input Label as", 'lang_group'),
						value: props.attributes.group_label_type,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({group_label_type: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_group_block_wp.group_display_consent, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Consent", 'lang_group'),
						value: props.attributes.group_display_consent,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({group_display_consent: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Button Text", 'lang_group'),
						type: 'text',
						value: props.attributes.group_button_text,
						/*help: __("Description...", 'lang_group'),*/
						placeholder: __("Join", 'lang_group'),
						onChange: function(value)
						{
							props.setAttributes({group_button_text: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_group_block_wp.group_button_icon, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Button Icon", 'lang_group'),
						value: props.attributes.group_button_icon,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({group_button_icon: value});
						}
					}
				)
			));
			/* ################### */

			return arr_out;
		},

		save: function()
		{
			return null;
		}
	});
})();
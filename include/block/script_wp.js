(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		MediaUpload = wp.blockEditor.MediaUpload,
	    Button = wp.components.Button,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/group',
	{
		title: script_group_block_wp.block_title,
		description: script_group_block_wp.block_description,
		icon: 'groups',
		category: 'widgets',
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
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						el(
							TextControl,
							{
								label: script_group_block_wp.group_heading_label,
								type: 'text',
								value: props.attributes.group_heading,
								onChange: function(value)
								{
									props.setAttributes({group_heading: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_group_block_wp.group_text_label,
								type: 'text',
								value: props.attributes.group_text,
								onChange: function(value)
								{
									props.setAttributes({group_text: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_group_block_wp.group_id_label,
								value: props.attributes.group_id,
								options: convert_php_array_to_block_js(script_group_block_wp.group_id),
								onChange: function(value)
								{
									props.setAttributes({group_id: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_group_block_wp.group_label_type_label,
								value: props.attributes.group_label_type,
								options: convert_php_array_to_block_js(script_group_block_wp.group_label_type),
								onChange: function(value)
								{
									props.setAttributes({group_label_type: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_group_block_wp.group_display_consent_label,
								value: props.attributes.group_display_consent,
								options: convert_php_array_to_block_js(script_group_block_wp.group_display_consent),
								onChange: function(value)
								{
									props.setAttributes({group_display_consent: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_group_block_wp.group_button_text_label,
								type: 'text',
								value: props.attributes.group_button_text,
								placeholder: script_group_block_wp.group_button_text_placeholder,
								onChange: function(value)
								{
									props.setAttributes({group_button_text: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_group_block_wp.group_button_icon_label,
								value: props.attributes.group_button_icon,
								options: convert_php_array_to_block_js(script_group_block_wp.group_button_icon),
								onChange: function(value)
								{
									props.setAttributes({group_button_icon: value});
								}
							}
						)
					),
					el(
						'strong',
						{className: props.className},
						script_group_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();
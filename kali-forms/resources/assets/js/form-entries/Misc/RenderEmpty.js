import React from 'react'
import { Tooltip } from 'antd'
import { __ } from '@wordpress/i18n';
import InfoCircleOutlined from '@ant-design/icons/InfoCircleOutlined';
export default function RenderEmpty() {
	return (
		<div>
			<p>{__('No entry found ', 'kali-forms')}
				<Tooltip title={__('Most likely, user did not complete this field', 'kali-forms')}>
					<InfoCircleOutlined />
				</Tooltip>
			</p>
		</div>
	)
}

import React, { useContext } from 'react'
import { ExportContext } from './../Context/ExportContext';
import { Card, Typography } from 'antd';
import { __ } from '@wordpress/i18n';
export default function ExportDetails() {
	const [exportOptions, setExportOptions] = useContext(ExportContext);

	return (
		<Card loading={exportOptions.loading}>
			<Typography.Paragraph>
				{__('Selected fields: ', 'kali-forms')} {exportOptions.fields.join(',')}
			</Typography.Paragraph>
			<Typography.Paragraph>
				{__('Format: ', 'kali-forms')} {exportOptions.fileFormat}
			</Typography.Paragraph>
		</Card>
	)
}

import React, { useContext, useState } from 'react'
import { ExportContext } from './../Context/ExportContext';
import { AppPropsContext } from './../Context/AppPropsContext';
import { Select, Typography, Switch, Space } from 'antd';

import { __ } from '@wordpress/i18n';

export default function FormSelect() {
	const [exportOptions, setExportOptions] = useContext(ExportContext);
	const AppProps = useContext(AppPropsContext);

	const onSelectChange = value => {
		setExportOptions(prevOptions => { return { ...prevOptions, form: value } })
	}

	const onSelectMultipleChange = value => {
		setExportOptions(prevOptions => { return { ...prevOptions, forms: value } })
	}

	const onMultipleToggle = checked => {
		setExportOptions(prevOptions => {
			return {
				...prevOptions,
				multiple: checked,
				form: null,
				forms: []
			}
		})
	}

	return (
		<div style={{ textAlign: 'center' }}>
			<Typography.Title level={4} style={{ marginBottom: 24 }}>
				{__('Select a form from which you want to export entries', 'kaliforms')}
			</Typography.Title>

			<Space direction="vertical" size="middle" style={{ display: 'flex', alignItems: 'center' }}>
				<Space align="center">
					<Typography.Text>{__('Enable multiple form selection', 'kaliforms')}</Typography.Text>
					<Switch
						checked={exportOptions.multiple}
						onChange={onMultipleToggle}
					/>
				</Space>

				{exportOptions.multiple
					? <Select
						mode="multiple"
						allowClear
						onChange={onSelectMultipleChange}
						placeholder={__('Please select the forms', 'kaliforms')}
						style={{ width: 250, textAlign: 'left' }}
						value={exportOptions.forms}
					>
						{AppProps.allForms.map(form => <Select.Option key={form.id} value={form.id}>{form.name}</Select.Option>)}
					</Select>
					: <Select
						onChange={onSelectChange}
						placeholder={__('Please select a form', 'kaliforms')}
						style={{ width: 250, textAlign: 'left' }}
						value={exportOptions.form}
					>
						{AppProps.allForms.map(form => <Select.Option key={form.id} value={form.id}>{form.name}</Select.Option>)}
					</Select>
				}
			</Space>
		</div>
	)
}

import React, { useContext, useState, useEffect } from 'react'
import { ExportContext } from './../Context/ExportContext';
import { AppPropsContext } from '../Context/AppPropsContext';
import { Transfer, Typography, Form, Select, DatePicker } from 'antd';
import Api from './../utils/Api';
import DataFormatter from './../utils/DataFormatter';
import GSheetTree from './GSheetStuff/GSheetTree';

import { __ } from '@wordpress/i18n';

export default function ExportOptions() {
	const [exportOptions, setExportOptions] = useContext(ExportContext);
	const appProps = useContext(AppPropsContext);
	const [columns, setColumns] = useState([]);

	useEffect(() => {
		if (exportOptions.multiple) {
			if (!exportOptions.forms || !exportOptions.forms.length) return;

			Api.getFormEntriesMultiple(exportOptions.forms)
				.then(res => {
					if (res.data) {
						const allColumns = [];
						const processedFields = new Set();

						// Process each form's data through DataFormatter
						Object.entries(res.data).forEach(([formId, formData]) => {
							const { columns } = DataFormatter([formData]);
							columns.slice(0, -1).forEach(column => {
								// Create a unique key combining field name and form ID
								const uniqueKey = `${column.key}_${formId}`;

								// Only add if not already present
								if (!processedFields.has(uniqueKey)) {
									allColumns.push({
										...column,
										originalKey: column.key, // Store original key for data mapping
										key: uniqueKey, // Use unique key
										formId: formId
									});
									processedFields.add(uniqueKey);
								}
							});
						});

						setColumns(allColumns);
						setExportOptions(prev => ({
							...prev,
							availableFields: allColumns
						}));
					}
				});
		} else {
			if (!exportOptions.form) return;

			Api.getFormEntries(exportOptions.form, 1, 1, false)
				.then(res => {
					if (res.data.length) {
						const { columns } = DataFormatter(res.data);
						const columnsWithFormId = columns.slice(0, -1).map(column => ({
							...column,
							originalKey: column.key,
							key: `${column.key}_${exportOptions.form}`,
							formId: exportOptions.form
						}));
						setColumns(columnsWithFormId);
						setExportOptions(prev => ({
							...prev,
							availableFields: columnsWithFormId
						}));
					}
				});
		}

		return () => {
			setColumns([]);
		};
	}, [exportOptions.form, exportOptions.forms, exportOptions.multiple]);

	const handleChange = fields => {
		let formattedFields = [];
		fields.map(el => {
			const column = columns.find(col => col.key === el);
			formattedFields.push({
				key: column ? column.originalKey : el, // Use original key for data mapping
				uniqueKey: el, // Store the unique key
				newName: column ? column.title : el,
				formId: column ? column.formId : exportOptions.form
			});
		});
		setExportOptions(prevOptions => ({
			...prevOptions,
			fields,
			formattedFields
		}));
	};

	const gSheetChanged = (value) => {
		setExportOptions(prevState => ({ ...prevState, googleSheet: value }));
	};

	const valuesChanged = (key, value) => {
		switch (key) {
			case 'fileFormat':
				setExportOptions(prevState => ({ ...prevState, fileFormat: value }));
				break;
			case 'dateFilter':
				if (!value) {
					return setExportOptions(prevState => ({ ...prevState, filters: [] }));
				}

				let from = value[0].format('D-M-YYYY');
				let to = value.length > 1 ? value[1].format('D-M-YYYY') : null;
				let filter = to !== null
					? { after: from, before: to }
					: { after: from };

				return setExportOptions(prevState => ({
					...prevState,
					filters: [{ date: filter }]
				}));
				break;
		}
	};

	return (
		<div>
			<Typography.Title level={4} style={{ textAlign: 'center', marginBottom: 24 }}>
				{__('Select which fields you want to export', 'kaliforms')}
			</Typography.Title>
			<Transfer
				style={{ justifyContent: 'center' }}
				dataSource={columns}
				targetKeys={exportOptions.fields}
				onChange={handleChange}
				listStyle={{
					width: 350
				}}
				render={item => `${item.title} (#${item.formId})`}
			/>
			<Form autoComplete={'false'}
				name="exporter-options"
				onValuesChange={valuesChanged}
				style={{ textAlign: 'center' }}
			>
				<Typography.Title level={4} style={{ textAlign: 'center', marginTop: 24, marginBottom: 24 }}>
					{__('Do you wish to export from a certain range?', 'kaliforms')}
				</Typography.Title>
				<DatePicker.RangePicker name="dateFilter" format={'DD-MM-YYYY'} style={{ width: 350 }} onChange={val => valuesChanged('dateFilter', val)} />
				<Typography.Title level={4} style={{ textAlign: 'center', marginTop: 24, marginBottom: 24 }}>
					{__('Exported file type', 'kaliforms')}
				</Typography.Title>
				<Select name={'fileFormat'} defaultValue={exportOptions.fileFormat} style={{ width: 350, textAlign: 'left' }} onChange={val => valuesChanged('fileFormat', val)}>
					<Select.Option value="csv">{__('CSV', 'kaliforms')}</Select.Option>
					<Select.Option value="xls">{__('Microsoft Excel™', 'kaliforms')}</Select.Option>
					<Select.Option value="xlsx">{__('Microsoft Excel™ 2007', 'kaliforms')}</Select.Option>
					<Select.Option disabled={appProps.plugins.googleSheets ? false : true} value="gsheet">
						{__('Google sheet', 'kaliforms')}
						<If condition={!appProps.plugins.googleSheets}>
							{__(' (requires the google sheets plugin)', 'kaliforms')}
						</If>
					</Select.Option>
				</Select>

				<If condition={exportOptions.fileFormat === 'gsheet'}>
					<Typography.Title level={4} style={{ textAlign: 'center', marginTop: 24, marginBottom: 24 }}>
						{__('Google sheets export options', 'kaliforms')}
					</Typography.Title>
					<GSheetTree onChange={gSheetChanged} />
				</If>
			</Form>
		</div>
	);
}

import React, { useContext, useState, useEffect } from 'react'
import { ExportContext } from './../Context/ExportContext';
import { Result, Button, Spin, message } from 'antd';
import Api from './../utils/Api';

import { __ } from '@wordpress/i18n';

export default function StartExport() {
	const [exportOptions, setExportOptions] = useContext(ExportContext);
	const [downloadUrl, setDownloadUrl] = useState('');

	const labels = {
		idle: {
			title: __('Click the button below to start the export process!', 'kali-forms'),
			subTitle: '',
			button: __('Start export', 'kali-forms'),
		},
		processing: {
			title: __('We are currently processing the export file', 'kali-forms'),
			subTitle: __('Please be patient, this process might take a few minutes to complete', 'kali-forms'),
			button: __('Processing', 'kali-forms')
		},
		success: {
			title: __('Processing complete!', 'kali-forms'),
			subTitle: __('Click the button below to start the download of your file', 'kali-forms'),
			button: __('Download file', 'kali-forms'),
		},
		successGoogle: {
			title: __('Processing complete!', 'kali-forms'),
			subTitle: __('A new file was created to your Google Drive account.', 'kali-forms'),
			button: __('Take me to Google Drive', 'kali-forms'),
		},
		error: {
			title: __('Something went wrong!', 'kali-forms'),
			subTitle: __('Something went wrong, please try exporting your data again. If you still encounter issues, please contact us directly so we can provide assistance.', 'kali-forms'),
			button: __('Go back', 'kali-forms'),
		}
	}

	const props = {
		title: labels[exportOptions.status].title,
		subTitle: labels[exportOptions.status].subTitle,
		status: (exportOptions.status === 'success' || exportOptions.status === 'error')
			? exportOptions.status
			: (exportOptions.status === 'successGoogle') ? 'success' : 'info',
		extra: (
			<Button type="primary" loading={exportOptions.loading} onClick={e => handleClick()}>
				{labels[exportOptions.status].button}
			</Button>
		)
	}

	if (exportOptions.status === 'processing') {
		props.icon = (<Spin size="large" />)
	}

	const handleClick = () => {
		switch (exportOptions.status) {
			case 'idle':
				startExport();
				break;
			case 'success':
				startDownload();
				break;
			case 'successGoogle':
				goToGoogleDrive();
				break;
			case 'error':
				resetExporter();
				break;
		}
	}

	const resetExporter = () => {
		setExportOptions(prevState => {
			return {
				loading: false,
				status: 'idle',
				form: null,
				multiple: false,
				forms: [],
				fields: [],
				formattedFields: [],
				filters: [],
				fileFormat: 'csv',
				currentStep: 0,
				googleSheet: 'new',
			}
		})
	}

	const startDownload = () => {
		if (!downloadUrl) {
			message.error(__('Download URL is not available. Please try again.', 'kali-forms'));
			return;
		}

		// Convert HTTP URL to HTTPS if needed
		const secureUrl = downloadUrl.replace(/^http:/, 'https:');

		// Try to fetch the file directly
		fetch(secureUrl)
			.then(response => {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				return response.blob();
			})
			.then(blob => {
				const url = window.URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.href = url;
				// Get filename from URL or use default
				const filename = secureUrl.split('/').pop() || `export.${exportOptions.fileFormat}`;
				link.download = filename;
				document.body.appendChild(link);
				link.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(link);
			})
			.catch(err => {
				console.error('Download error:', err);
				message.error(__('Download failed. Please ensure you are using HTTPS or contact support.', 'kali-forms'));

				// Try direct navigation as fallback
				const securePageUrl = window.location.href.replace(/^http:/, 'https:');
				if (window.location.href !== securePageUrl) {
					window.location.href = securePageUrl;
				} else {
					window.location.href = secureUrl;
				}
			});
	}

	const goToGoogleDrive = () => {
		window.open('https://drive.google.com', '_blank');
	}

	const startExport = () => {
		let data = {
			fields: exportOptions.formattedFields,
			filters: exportOptions.filters,
			form: exportOptions.multiple ? null : exportOptions.form,
			forms: exportOptions.multiple ? exportOptions.forms : null,
			multiple: exportOptions.multiple,
			fileType: exportOptions.fileFormat,
			googleSheet: exportOptions.googleSheet
		}
		setExportOptions(prevState => {
			return {
				...prevState,
				loading: true,
				status: 'processing'
			}
		})

		Api.startExport(data).then(res => {
			if (!res.data?.status || !res.data?.url) {
				message.error(__('Export failed. Please try again.', 'kali-forms'));
				return setExportOptions(prevState => {
					return {
						...prevState,
						loading: false,
						status: 'error'
					}
				})
			}

			if (exportOptions.fileFormat !== 'gsheet') {
				// Ensure the URL uses HTTPS
				const secureUrl = res.data.url.replace(/^http:/, 'https:');
				setDownloadUrl(secureUrl);
				return setExportOptions(prevState => {
					return {
						...prevState,
						loading: false,
						status: 'success'
					}
				})
			}

			return setExportOptions(prevState => {
				return {
					...prevState,
					loading: false,
					status: 'successGoogle'
				}
			})
		}).catch(err => {
			message.error(__('Export failed. Please try again.', 'kali-forms'));
			setExportOptions(prevState => {
				return {
					...prevState,
					loading: false,
					status: 'error'
				}
			})
		})
	}

	return (
		<div>
			<Result {...props} />
		</div>
	)
}

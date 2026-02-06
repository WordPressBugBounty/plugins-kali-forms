import React, { useState, createContext, useMemo } from 'react'
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
export const UiContext = createContext();
export const UiProvider = props => {
	let currentPath = useLocation();
	let navbar = [
		{ label: __('Forms', 'kali-forms'), key: 'forms', path: '/' },
		{ label: __('Form entries', 'kali-forms'), key: 'form-entries', path: `/form-entries` },
		{ label: __('Exporter', 'kali-forms'), key: 'exporter', path: '/exporter' }
	]

	const [ui, setUi] = useState({
		navbar,
		selectedNavbar: [currentPath.pathname !== '/' ? currentPath.pathname.substring(1) : 'forms'],
	})


	return (<UiContext.Provider value={[ui, setUi]}>{props.children}</UiContext.Provider>)
}

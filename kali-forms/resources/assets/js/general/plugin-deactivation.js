import './plugin-deactivation.scss';
import UninstallFeedback from './uninstall-feedback';
const { __ } = wp.i18n;
jQuery(document).ready(_ => {
	const uninstallScript = UninstallFeedback;

	uninstallScript.slug = 'kali-forms';
	uninstallScript.template = KaliFormsPluginDeactivationObject.modalHtml;
	uninstallScript.form = 'kaliforms-deactivate-form';
	uninstallScript.deactivateUrl = jQuery('#kaliforms-deactivate-link-kaliforms').attr('href');
	uninstallScript.deactivate = false;

	uninstallScript.translations = {
		'setup': __('What was the dificult part ?', 'kali-forms'),
		'docs': __('What can we describe more ?', 'kali-forms'),
		'features': __('How could we improve ?', 'kali-forms'),
		'better-plugin': __('Can you mention it ?', 'kali-forms'),
		'incompatibility': __('With what plugin or theme is incompatible ?', 'kali-forms'),
		'maintenance': __('Please specify', 'kali-forms'),
	};

	uninstallScript.nonce = KaliFormsPluginDeactivationObject.ajax_nonce;
	uninstallScript.init(jQuery('#kaliforms-deactivate-link-kaliforms'));
});

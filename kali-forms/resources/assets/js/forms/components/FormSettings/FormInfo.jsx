import FormGroup from "@material-ui/core/FormGroup";
import Grid from "@material-ui/core/Grid";
import React from "react";
import PlaceholderDialogOpener from "./../PlaceholderDialog/PlaceholderDialogOpener";
import { observer } from "mobx-react";
import { store } from "./../../store/store";
import BootstrapInput from "./../BootstrapInput";
import InputLabel from "@material-ui/core/InputLabel";
import FormControl from "@material-ui/core/FormControl";
import Container from "./../LayoutComponents/Container";
import SectionTitle from "./../Misc/SectionTitle";
import Checkbox from "./../Misc/Checkbox";
import FormControlLabel from "./../Misc/FormControlLabel";
import ConditionalThankYouMessage from "./../Misc/ConditionalThankYouMessage";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import FormHelperText from "@material-ui/core/FormHelperText";
import FieldComponentSelect from "./../HubSpotIntegration/FieldComponentSelect";
const { __ } = wp.i18n;
const FormInfo = observer((props) => {
	/**
	 * Returns a boolean, if we have the plugin installed it should be true
	 */
	// const saveFormSubmissionsInstalled = () => KaliFormsObject.hasOwnProperty('submissionViewPage');
	const saveFormSubmissionsInstalled = () => true;

	return (
		<React.Fragment>
			<Container maxWidth="md">
				<SectionTitle title={__("General settings", "kali-forms")} />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__("Required field mark", "kali-forms")}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.requiredFieldMark}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({
										requiredFieldMark: e.target.value,
									})
								}
								fullWidth={true}
								variant="filled"
								placeholder="(*)"
								inputProps={{ maxLength: 5 }}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__("Multiple selection separator", "kali-forms")}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.multipleSelectionsSeparator}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({
										multipleSelectionsSeparator: e.target.value,
									})
								}
								fullWidth={true}
								variant="filled"
								placeholder={__(", or . or - or whatyouneed", "kali-forms")}
								inputProps={{ maxLength: 5 }}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={12}>
						<FormControl>
							<InputLabel shrink>
								{__("Global error message", "kali-forms")}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.globalErrorMessage}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({
										globalErrorMessage: e.target.value,
									})
								}
								fullWidth={true}
								variant="filled"
								placeholder={__("Something went wrong...", "kali-forms")}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={9}>
						<FormControl>
							<InputLabel shrink>{__("Form action", "kali-forms")}</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.formAction}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({ formAction: e.target.value })
								}
								fullWidth={true}
								variant="filled"
								placeholder={__("Form action", "kali-forms")}
							/>
							<FormHelperText>
								{__(
									"The form action controls where the collected information will be submitted to (this is optional, and overrides the default form submission process).",
									"kali-forms"
								)}
							</FormHelperText>
						</FormControl>
					</Grid>
					<Grid item xs={3}>
						<FormControl>
							<InputLabel shrink>{__("Form method", "kali-forms")}</InputLabel>
							<Select
								value={store._FORM_INFO_.formMethod}
								multiple={false}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({ formMethod: e.target.value })
								}
								input={<BootstrapInput />}
							>
								<MenuItem value="GET">GET</MenuItem>
								<MenuItem value="POST">POST</MenuItem>
							</Select>
						</FormControl>
					</Grid>
				</Grid>

				<Grid container direction="row" spacing={3}>
					<Grid item xs={12}>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.hideFormName === "1"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												hideFormName: e.target.checked ? "1" : "0",
											})
										}
									/>
								}
								label={__("Hide form name", "kali-forms")}
							/>
						</FormGroup>
					</Grid>
				</Grid>

				<Grid container direction="row" spacing={3}>
					<Grid item xs={12}>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.saveIpAddress === "1"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												saveIpAddress: e.target.checked ? "1" : "0",
											})
										}
									/>
								}
								label={__("Save submitter IP Address", "kali-forms")}
							/>
						</FormGroup>
					</Grid>
				</Grid>
				<If
					condition={
						typeof KaliFormsObject.conditionalLogic !== "undefined" &&
						store._FIELD_COMPONENTS_.getFieldsById("pageBreak").length > 0
					}
				>
					<Grid container direction="row" spacing={3}>
						<Grid item xs={12}>
							<FormGroup>
								<FormControl>
									<InputLabel shrink>
										{__("Pagebreak progress complete label", "kali-forms")}
									</InputLabel>
									<BootstrapInput
										value={store._FORM_INFO_.pagebreakCompleteLabel}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												pagebreakCompleteLabel: e.target.value,
											})
										}
										fullWidth={true}
										variant="filled"
										placeholder={__("Complete", "kali-forms")}
									/>
								</FormControl>
							</FormGroup>
						</Grid>
					</Grid>
				</If>

				<SectionTitle title={__("After form submit", "kali-forms")} />
				<Grid container direction="row" spacing={3}>
					<Choose>
						<When
							condition={
								typeof KaliFormsObject.conditionalLogic !== "undefined"
							}
						>
							<ConditionalThankYouMessage />
						</When>
						<Otherwise>
							<Grid item>
								<FormGroup>
									<FormControlLabel
										control={
											<Checkbox
												checked={store._FORM_INFO_.showThankYouMessage === "1"}
												onChange={(e) =>
													store._FORM_INFO_.setFormInfo({
														showThankYouMessage: e.target.checked ? "1" : "0",
													})
												}
											/>
										}
										label={__("Show thank you message", "kali-forms")}
									/>
								</FormGroup>
							</Grid>
							<If condition={store._FORM_INFO_.showThankYouMessage === "1"}>
								<Grid item xs={12}>
									<FormControl>
										<InputLabel shrink>
											{__("Thank you message", "kali-forms")}
										</InputLabel>
										<BootstrapInput
											value={store._FORM_INFO_.thankYouMessage}
											onChange={(e) =>
												store._FORM_INFO_.setFormInfo({
													thankYouMessage: e.target.value,
												})
											}
											// multiline={true}
											variant="filled"
											fullWidth={true}
											style={{ whiteSpace: "pre" }}
											endAdornment={
												<PlaceholderDialogOpener
													adornment={true}
												></PlaceholderDialogOpener>
											}
										/>
									</FormControl>
								</Grid>
								<Grid item>
									<FormGroup>
										<FormControlLabel
											control={
												<Checkbox
													checked={store._FORM_INFO_.scrollToThankYou === "1"}
													onChange={(e) =>
														store._FORM_INFO_.setFormInfo({
															scrollToThankYou: e.target.checked ? "1" : "0",
														})
													}
												/>
											}
											label={__("Scroll to thank you message", "kali-forms")}
										/>
									</FormGroup>
								</Grid>
							</If>
						</Otherwise>
					</Choose>
				</Grid>
				<Grid container direction="row" spacing={3}>
					<Grid item>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.saveFormSubmissions === "1"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												saveFormSubmissions: e.target.checked ? "1" : "0",
											})
										}
									/>
								}
								label={__("Save form submissions", "kali-forms")}
							/>
						</FormGroup>
					</Grid>
					<If condition={store._FORM_INFO_.saveFormSubmissions === "1"}>
						<Grid item xs={12}>
							<FormControl>
								<InputLabel shrink>
									{__(
										"Entries view page (page with [kaliform-submission] shortcode)",
										"kali-forms"
									)}
								</InputLabel>
								<Select
									value={store._FORM_INFO_.submissionViewPage}
									multiple={false}
									onChange={(e) =>
										store._FORM_INFO_.setFormInfo({
											submissionViewPage: e.target.value,
										})
									}
									input={<BootstrapInput />}
								>
									<MenuItem value={0}>
										{__("-- Select a page --", "kali-forms")}
									</MenuItem>
									{KaliFormsObject.websitePages.map((e) => (
										<MenuItem key={e.id} value={e.id}>
											{e.title}
										</MenuItem>
									))}
								</Select>
							</FormControl>
						</Grid>
					</If>
					<If condition={store._FORM_INFO_.saveFormSubmissions === "1"}>
						<Grid item xs={12}>
							<FormControl>
								<InputLabel shrink>
									{__(
										"Delete entries after a certain amount of time (in days). Use 0 to never delete entries.",
										"kali-forms"
									)}
								</InputLabel>
								<BootstrapInput
									value={store._FORM_INFO_.deleteEntriesAfter}
									type="number"
									onChange={(e) =>
										store._FORM_INFO_.setFormInfo({
											deleteEntriesAfter: e.target.value,
										})
									}
									fullWidth={true}
									variant="filled"
									placeholder="10"
								/>
							</FormControl>
						</Grid>
					</If>
					<If
						condition={
							typeof KaliFormsObject.conditionalLogic !== "undefined" &&
							store._FORM_INFO_.saveFormSubmissions === "1"
						}
					>
						<Grid item xs={12}>
							<FormGroup>
								<FormControlLabel
									control={
										<Checkbox
											checked={
												store._FORM_INFO_.preventMultipleEntriesFromSameUser ===
												"1"
											}
											onChange={(e) =>
												store._FORM_INFO_.setFormInfo({
													preventMultipleEntriesFromSameUser: e.target.checked
														? "1"
														: "0",
												})
											}
										/>
									}
									label={__(
										"Prevent multiple entries from same user",
										"kali-forms"
									)}
								/>
							</FormGroup>
						</Grid>
						<If
							condition={
								store._FORM_INFO_.preventMultipleEntriesFromSameUser === "1"
							}
						>
							<Grid item xs={4}>
								<FormControl>
									<InputLabel shrink>{__("Invalidate by")}</InputLabel>
									<Select
										value={
											store._FORM_INFO_.multipleEntriesInvalidate || "userid"
										}
										multiple={false}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												multipleEntriesInvalidate: e.target.value,
											})
										}
										input={<BootstrapInput />}
									>
										<MenuItem value="field">
											{__("Field", "kali-forms")}
										</MenuItem>
										<MenuItem value="userid">
											{__("User Id", "kali-forms")}
										</MenuItem>
									</Select>
								</FormControl>
							</Grid>
							<If
								condition={
									store._FORM_INFO_.multipleEntriesInvalidate === "field"
								}
							>
								<Grid item xs={8}>
									<FieldComponentSelect
										label={__("Unique field", "kali-forms")}
										selectedValue={
											store._FORM_INFO_.multipleEntriesInvalidateField || ""
										}
										field={"multipleEntriesInvalidateField"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												multipleEntriesInvalidateField: e.value,
											})
										}
									/>
								</Grid>
							</If>
							<Grid item xs={12}>
								<FormControl>
									<InputLabel shrink>
										{__(
											"Message displayed for the users that already submitted the form",
											"kali-forms"
										)}
									</InputLabel>
									<BootstrapInput
										value={store._FORM_INFO_.multipleEntriesError}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												multipleEntriesError: e.target.value,
											})
										}
										fullWidth={true}
										variant="filled"
										placeholder={__(
											"You already completed this form ... only one entry is allowed",
											"kali-forms"
										)}
									/>
									<FormHelperText>
										{__(
											"If you select to invalidate by user id, the form will only be accessible to logged in users.",
											"kali-forms"
										)}
									</FormHelperText>
								</FormControl>
							</Grid>
						</If>
					</If>
					<Grid item xs={12}>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.resetFormAfterSubmit === "1"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												resetFormAfterSubmit: e.target.checked ? "1" : "0",
											})
										}
									/>
								}
								label={__("Reset form after submit", "kali-forms")}
							/>
						</FormGroup>
					</Grid>
					<Grid item xs={8}>
						<FormControl>
							<InputLabel shrink>{__("Redirect URL", "kali-forms")}</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.redirectUrl}
								type="url"
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({ redirectUrl: e.target.value })
								}
								fullWidth={true}
								variant="filled"
								placeholder="www.google.com"
							/>
						</FormControl>
					</Grid>
					<Grid item xs={4}>
						<FormControl>
							<InputLabel shrink>
								{__("Redirect timeout (in seconds)", "kali-forms")}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.redirectTimeout}
								type="number"
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({
										redirectTimeout: e.target.value,
									})
								}
								fullWidth={true}
								variant="filled"
								placeholder="5"
							/>
						</FormControl>
					</Grid>
				</Grid>

				<SectionTitle title={__("Form class and id", "kali-forms")} />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>{__("CSS Id", "kali-forms")}</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.cssId}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({ cssId: e.target.value })
								}
								fullWidth={true}
								variant="filled"
							/>
						</FormControl>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>{__("CSS Class", "kali-forms")}</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.cssClass}
								onChange={(e) =>
									store._FORM_INFO_.setFormInfo({ cssClass: e.target.value })
								}
								fullWidth={true}
								variant="filled"
							/>
						</FormControl>
					</Grid>
					<Grid item>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.disableBootstrapGrid === "1"}
										onChange={(e) =>
											store._FORM_INFO_.setFormInfo({
												disableBootstrapGrid: e.target.checked ? "1" : "0",
											})
										}
									/>
								}
								label={__("Disable bootstrap grid", "kali-forms")}
							/>
						</FormGroup>
					</Grid>
				</Grid>
			</Container>
		</React.Fragment>
	);
});
export default FormInfo;

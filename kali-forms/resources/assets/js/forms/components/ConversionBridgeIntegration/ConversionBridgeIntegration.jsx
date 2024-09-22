import React from "react";
import Grid from "@material-ui/core/Grid";
import Typography from "@material-ui/core/Typography";
import Container from "./../LayoutComponents/Container";
import SectionTitle from "./../Misc/SectionTitle";
import { store } from "../../store/store";
import FormGroup from "@material-ui/core/FormGroup";
import Checkbox from "./../Misc/Checkbox";
import FormControlLabel from "./../Misc/FormControlLabel";
import { observer } from "mobx-react";
import InputLabel from "@material-ui/core/InputLabel";
import BootstrapInput from "../BootstrapInput";
import FormControl from "@material-ui/core/FormControl";
const { __ } = wp.i18n;

const ConversionBridgeIntegration = observer((props) => {
	return (
		<React.Fragment>
			<Container maxWidth="md">
				<SectionTitle title={"Conversion Bridge WP"} />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={8}>
						<Choose>
							<When
								condition={store._FORM_INFO_.conversionBridgeInstalled !== "1"}
							>
								<Typography>
									{__(
										"Please install and activate Conversion Bridge WP to use this integration",
										"kaliforms"
									)}
								</Typography>
							</When>
							<Otherwise>
								<>
									<FormGroup row>
										<FormControlLabel
											control={
												<Checkbox
													checked={
														store._FORM_INFO_.enableConversionTracking === "1"
													}
													onChange={(e) =>
														store._FORM_INFO_.setFormInfo({
															enableConversionTracking: e.target.checked
																? "1"
																: "0",
														})
													}
												/>
											}
											label={__(
												"Enable Conversion Bridge integration",
												"kaliforms"
											)}
										/>
									</FormGroup>
									<If
										condition={
											store._FORM_INFO_.enableConversionTracking === "1"
										}
									>
										<>
											<FormGroup row style={{ marginTop: 20 }}>
												<FormControl>
													<InputLabel shrink>
														{__("Custom label", "kaliforms")}
													</InputLabel>
													<BootstrapInput
														value={store._FORM_INFO_.conversionLabel}
														onChange={(e) =>
															store._FORM_INFO_.setFormInfo({
																conversionLabel: e.target.value,
															})
														}
														fullWidth={true}
														placeholder={__("KF Submission Label", "kaliforms")}
														variant="filled"
													/>
												</FormControl>
											</FormGroup>
											<FormGroup row style={{ marginTop: 20 }}>
												<FormControl>
													<InputLabel shrink>
														{__("Custom value", "kaliforms")}
													</InputLabel>
													<BootstrapInput
														value={store._FORM_INFO_.conversionCustomValue}
														onChange={(e) =>
															store._FORM_INFO_.setFormInfo({
																conversionCustomValue: e.target.value,
															})
														}
														fullWidth={true}
														placeholder={__("XXXXX", "kaliforms")}
														variant="filled"
													/>
												</FormControl>
											</FormGroup>
										</>
									</If>
								</>
							</Otherwise>
						</Choose>
					</Grid>
				</Grid>
			</Container>
		</React.Fragment>
	);
});

export default ConversionBridgeIntegration;

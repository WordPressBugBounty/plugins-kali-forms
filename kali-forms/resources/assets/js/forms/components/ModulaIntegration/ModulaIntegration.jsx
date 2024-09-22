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
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import BootstrapInput from "../BootstrapInput";
import FormControl from "@material-ui/core/FormControl";
import PlaceholderDialogOpener from "../PlaceholderDialog/PlaceholderDialogOpener";
const { __ } = wp.i18n;

const ModulaIntegration = observer((props) => {
	const modulaGalleries = store._FORM_INFO_.modulaGalleries;
	return (
		<React.Fragment>
			<Container maxWidth="md">
				<SectionTitle title={"Modula"} />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<Choose>
							<When condition={store._FORM_INFO_.modulaInstalled !== "1"}>
								<Typography>
									{__(
										"Please install and activate Modula to use this integration",
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
													checked={store._FORM_INFO_.modulaIntegration === "1"}
													onChange={(e) =>
														store._FORM_INFO_.setFormInfo({
															modulaIntegration: e.target.checked ? "1" : "0",
														})
													}
												/>
											}
											label={__(
												"Enable Modula gallery integration",
												"kaliforms"
											)}
										/>
									</FormGroup>
									<If condition={store._FORM_INFO_.modulaIntegration === "1"}>
										<>
											<FormGroup row style={{ marginTop: 20 }}>
												<FormControl>
													<InputLabel shrink>
														{__("Form action", "kaliforms")}
													</InputLabel>
													<Select
														value={store._FORM_INFO_.modulaAction || "new"}
														multiple={false}
														onChange={(e) =>
															store._FORM_INFO_.setFormInfo({
																modulaAction: e.target.value,
															})
														}
														input={<BootstrapInput />}
													>
														<MenuItem value="new">
															{__("Create new gallery", "kaliforms")}
														</MenuItem>
														<MenuItem value="existing">
															{__("Add to existing gallery", "kaliforms")}
														</MenuItem>
													</Select>
												</FormControl>
											</FormGroup>
											<If
												condition={
													store._FORM_INFO_.modulaAction === "existing"
												}
											>
												<FormGroup row style={{ marginTop: 20 }}>
													<FormControl>
														<InputLabel shrink>
															{__("Select existing gallery", "kaliforms")}
														</InputLabel>
														<Select
															value={store._FORM_INFO_.modulaGallery || "0"}
															multiple={false}
															onChange={(e) =>
																store._FORM_INFO_.setFormInfo({
																	modulaGallery: e.target.value,
																})
															}
															input={<BootstrapInput />}
														>
															<MenuItem value="0">
																{__("Select a gallery", "kaliforms")}
															</MenuItem>
															{store._FORM_INFO_.modulaGalleries.map(
																(gallery) => (
																	<MenuItem value={gallery.id} key={gallery.id}>
																		{gallery.title}
																	</MenuItem>
																)
															)}
														</Select>
													</FormControl>
												</FormGroup>
											</If>
											<If condition={store._FORM_INFO_.modulaAction === "new"}>
												<FormGroup row style={{ marginTop: 20 }}>
													<FormControl>
														<InputLabel shrink>
															{__("Gallery name prefix", "kaliforms")}
														</InputLabel>
														<BootstrapInput
															value={store._FORM_INFO_.modulaGalleryName}
															onChange={(e) =>
																store._FORM_INFO_.setFormInfo({
																	modulaGalleryName: e.target.value,
																})
															}
															fullWidth={true}
															placeholder={__("{email} - gallery", "kaliforms")}
															variant="filled"
															endAdornment={
																<PlaceholderDialogOpener adornment={true} />
															}
														/>
													</FormControl>
												</FormGroup>
												{store._FORM_INFO_.modulaDefaultsInstalled === "1" && (
													<FormGroup row style={{ marginTop: 20 }}>
														<FormControl>
															<InputLabel shrink>
																{__(
																	"Select existing gallery defaults",
																	"kaliforms"
																)}
															</InputLabel>
															<Select
																value={store._FORM_INFO_.modulaDefaultsId}
																multiple={false}
																onChange={(e) =>
																	store._FORM_INFO_.setFormInfo({
																		modulaDefaultsId: e.target.value,
																	})
																}
																input={<BootstrapInput />}
															>
																<MenuItem value="0">
																	{__(
																		"Select a gallery default entry",
																		"kaliforms"
																	)}
																</MenuItem>
																{store._FORM_INFO_.modulaDefaults.map(
																	(defaultPost) => (
																		<MenuItem
																			value={defaultPost.id}
																			key={defaultPost.id}
																		>
																			{defaultPost.title}
																		</MenuItem>
																	)
																)}
															</Select>
														</FormControl>
													</FormGroup>
												)}
											</If>
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

export default ModulaIntegration;

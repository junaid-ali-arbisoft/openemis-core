<?php
if ($data !== false) {
	if (empty($data)) {
		?>
		<div class="alert none" type="2"><?php echo __("Your search returns no result."); ?></div>
		<?php
	} else {
		foreach ($data as $obj) {
			$id = $obj['Student']['id'];
			$gender = $this->Utility->formatGender($obj['Student']['gender']);
			$id_no = $obj['Student']['identification_no'];
			$firstName = $obj['Student']['first_name'];
			$middleName = $obj['Student']['middle_name'];
			$lastName = $obj['Student']['last_name'];
			$preferredName = $obj['Student']['preferred_name'];
			?>
			<tr class="table_row" row-id="<?php echo $id; ?>" id-no="<?php echo $id_no; ?>" first-name="<?php echo $firstName; ?>" middle-name="<?php echo $middleName; ?>" last-name="<?php echo $lastName; ?>" preferred-name="<?php echo $preferredName; ?>" gender="<?php echo $gender; ?>" onclick="InstitutionSiteStudents.addStudent(this)">
				<td class="table_cell cell_id_no"><?php echo $this->Utility->highlight($search, $id_no); ?></td>
				<td class="table_cell first_name"><?php echo $this->Utility->highlight($search, $firstName); ?></td>
				<td class="table_cell middle_name"><?php echo $this->Utility->highlight($search, $middleName); ?></td>
				<td class="table_cell"><?php echo $this->Utility->highlight($search, $lastName); ?></td>
			</tr>
			<?php
		}
	}
} else {
	?>
	<div class="alert none" type="0"><?php echo __("Your search returned too many results. Please refine your search criteria."); ?></div>
<?php } ?>
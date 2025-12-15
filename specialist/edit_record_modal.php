<?php
// Get medications, treatments, and lab tests for this record
$medications = [];
$treatments = [];
$lab_tests = [];

// Get medications
$med_sql = "SELECT * FROM medications WHERE record_id = ?";
if ($med_stmt = mysqli_prepare($conn, $med_sql)) {
    mysqli_stmt_bind_param($med_stmt, "i", $row['record_id']);
    if (mysqli_stmt_execute($med_stmt)) {
        $med_result = mysqli_stmt_get_result($med_stmt);
        while ($med_row = mysqli_fetch_assoc($med_result)) {
            $medications[] = $med_row;
        }
    }
    mysqli_stmt_close($med_stmt);
}

// Get treatments
$treat_sql = "SELECT * FROM treatments WHERE record_id = ?";
if ($treat_stmt = mysqli_prepare($conn, $treat_sql)) {
    mysqli_stmt_bind_param($treat_stmt, "i", $row['record_id']);
    if (mysqli_stmt_execute($treat_stmt)) {
        $treat_result = mysqli_stmt_get_result($treat_stmt);
        while ($treat_row = mysqli_fetch_assoc($treat_result)) {
            $treatments[] = $treat_row;
        }
    }
    mysqli_stmt_close($treat_stmt);
}

// Get lab tests
$test_sql = "SELECT * FROM lab_tests WHERE record_id = ?";
if ($test_stmt = mysqli_prepare($conn, $test_sql)) {
    mysqli_stmt_bind_param($test_stmt, "i", $row['record_id']);
    if (mysqli_stmt_execute($test_stmt)) {
        $test_result = mysqli_stmt_get_result($test_stmt);
        while ($test_row = mysqli_fetch_assoc($test_result)) {
            $lab_tests[] = $test_row;
        }
    }
    mysqli_stmt_close($test_stmt);
}
?>

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="editRecordModalLabel<?php echo $row['record_id']; ?>">
                Edit Patient Record
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="record_id" value="<?php echo $row['record_id']; ?>">

                <div class="form-group">
                    <label for="diagnosis_edit<?php echo $row['record_id']; ?>">Diagnosis</label>
                    <textarea class="form-control" id="diagnosis_edit<?php echo $row['record_id']; ?>" name="diagnosis"
                        rows="3" required><?php echo htmlspecialchars($row['diagnosis']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="notes_edit<?php echo $row['record_id']; ?>">Notes</label>
                    <textarea class="form-control" id="notes_edit<?php echo $row['record_id']; ?>" name="notes"
                        rows="3"><?php echo htmlspecialchars($row['notes']); ?></textarea>
                </div>

                <ul class="nav nav-tabs" id="recordTabsEdit<?php echo $row['record_id']; ?>" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="medications-tab-edit<?php echo $row['record_id']; ?>"
                            data-toggle="tab" href="#medications-edit<?php echo $row['record_id']; ?>"
                            role="tab">Medications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="treatments-tab-edit<?php echo $row['record_id']; ?>" data-toggle="tab"
                            href="#treatments-edit<?php echo $row['record_id']; ?>" role="tab">Treatments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tests-tab-edit<?php echo $row['record_id']; ?>" data-toggle="tab"
                            href="#tests-edit<?php echo $row['record_id']; ?>" role="tab">Lab Tests</a>
                    </li>
                </ul>

                <div class="tab-content pt-3" id="recordTabContentEdit<?php echo $row['record_id']; ?>">
                    <!-- Medications Tab -->
                    <div class="tab-pane fade show active" id="medications-edit<?php echo $row['record_id']; ?>"
                        role="tabpanel">
                        <div id="medications-container-edit<?php echo $row['record_id']; ?>">
                            <?php if (count($medications) > 0): ?>
                                <?php foreach ($medications as $med): ?>
                                    <div class="dynamic-form-row">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Medication Name</label>
                                                <input type="text" class="form-control" name="medications[]"
                                                    value="<?php echo htmlspecialchars($med['medication_name']); ?>"
                                                    placeholder="Medication name">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Dosage</label>
                                                <input type="text" class="form-control" name="dosages[]"
                                                    value="<?php echo htmlspecialchars($med['dosage']); ?>"
                                                    placeholder="Dosage">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Instructions</label>
                                                <input type="text" class="form-control" name="med_instructions[]"
                                                    value="<?php echo htmlspecialchars($med['instructions']); ?>"
                                                    placeholder="Instructions">
                                            </div>
                                            <div class="form-group col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="removeFormRow(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dynamic-form-row">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Medication Name</label>
                                            <input type="text" class="form-control" name="medications[]"
                                                placeholder="Medication name">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Dosage</label>
                                            <input type="text" class="form-control" name="dosages[]" placeholder="Dosage">
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label>Instructions</label>
                                            <input type="text" class="form-control" name="med_instructions[]"
                                                placeholder="Instructions">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-add-field mt-2"
                            onclick="addMedicationEdit(<?php echo $row['record_id']; ?>)">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    </div>

                    <!-- Treatments Tab -->
                    <div class="tab-pane fade" id="treatments-edit<?php echo $row['record_id']; ?>" role="tabpanel">
                        <div id="treatments-container-edit<?php echo $row['record_id']; ?>">
                            <?php if (count($treatments) > 0): ?>
                                <?php foreach ($treatments as $treatment): ?>
                                    <div class="dynamic-form-row">
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label>Treatment Name</label>
                                                <input type="text" class="form-control" name="treatments[]"
                                                    value="<?php echo htmlspecialchars($treatment['treatment_name']); ?>"
                                                    placeholder="Treatment name">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Description</label>
                                                <input type="text" class="form-control" name="treatment_descriptions[]"
                                                    value="<?php echo htmlspecialchars($treatment['description']); ?>"
                                                    placeholder="Description">
                                            </div>
                                            <div class="form-group col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="removeFormRow(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dynamic-form-row">
                                    <div class="form-row">
                                        <div class="form-group col-md-5">
                                            <label>Treatment Name</label>
                                            <input type="text" class="form-control" name="treatments[]"
                                                placeholder="Treatment name">
                                        </div>
                                        <div class="form-group col-md-7">
                                            <label>Description</label>
                                            <input type="text" class="form-control" name="treatment_descriptions[]"
                                                placeholder="Description">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-add-field mt-2"
                            onclick="addTreatmentEdit(<?php echo $row['record_id']; ?>)">
                            <i class="fas fa-plus"></i> Add Treatment
                        </button>
                    </div>

                    <!-- Lab Tests Tab -->
                    <div class="tab-pane fade" id="tests-edit<?php echo $row['record_id']; ?>" role="tabpanel">
                        <div id="tests-container-edit<?php echo $row['record_id']; ?>">
                            <?php if (count($lab_tests) > 0): ?>
                                <?php foreach ($lab_tests as $test): ?>
                                    <div class="dynamic-form-row">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Test Name</label>
                                                <input type="text" class="form-control" name="tests[]"
                                                    value="<?php echo htmlspecialchars($test['test_name']); ?>"
                                                    placeholder="Test name">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Results</label>
                                                <input type="text" class="form-control" name="results[]"
                                                    value="<?php echo htmlspecialchars($test['results']); ?>"
                                                    placeholder="Results">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Test Date</label>
                                                <input type="date" class="form-control" name="test_dates[]"
                                                    value="<?php echo htmlspecialchars($test['test_date']); ?>">
                                            </div>
                                            <div class="form-group col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="removeFormRow(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dynamic-form-row">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Test Name</label>
                                            <input type="text" class="form-control" name="tests[]" placeholder="Test name">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Results</label>
                                            <input type="text" class="form-control" name="results[]" placeholder="Results">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Test Date</label>
                                            <input type="date" class="form-control" name="test_dates[]">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-add-field mt-2"
                            onclick="addLabTestEdit(<?php echo $row['record_id']; ?>)">
                            <i class="fas fa-plus"></i> Add Lab Test
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Record</button>
            </div>
        </form>
    </div>
</div>
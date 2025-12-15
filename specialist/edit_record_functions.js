// Dynamic form field functions for the edit modal
function addMedicationEdit(id) {
    let container = document.getElementById('medications-container-edit' + id);
    let newRow = document.createElement('div');
    newRow.className = 'dynamic-form-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Medication Name</label>
                <input type="text" class="form-control" name="medications[]" placeholder="Medication name">
            </div>
            <div class="form-group col-md-3">
                <label>Dosage</label>
                <input type="text" class="form-control" name="dosages[]" placeholder="Dosage">
            </div>
            <div class="form-group col-md-4">
                <label>Instructions</label>
                <input type="text" class="form-control" name="med_instructions[]" placeholder="Instructions">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFormRow(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

function addTreatmentEdit(id) {
    let container = document.getElementById('treatments-container-edit' + id);
    let newRow = document.createElement('div');
    newRow.className = 'dynamic-form-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group col-md-5">
                <label>Treatment Name</label>
                <input type="text" class="form-control" name="treatments[]" placeholder="Treatment name">
            </div>
            <div class="form-group col-md-6">
                <label>Description</label>
                <input type="text" class="form-control" name="treatment_descriptions[]" placeholder="Description">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFormRow(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

function addLabTestEdit(id) {
    let container = document.getElementById('tests-container-edit' + id);
    let newRow = document.createElement('div');
    newRow.className = 'dynamic-form-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Test Name</label>
                <input type="text" class="form-control" name="tests[]" placeholder="Test name">
            </div>
            <div class="form-group col-md-3">
                <label>Results</label>
                <input type="text" class="form-control" name="results[]" placeholder="Results">
            </div>
            <div class="form-group col-md-4">
                <label>Test Date</label>
                <input type="date" class="form-control" name="test_dates[]">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFormRow(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}
<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true" style="width: 700px;">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content rounded-3 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Preview Student Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="accordion" id="previewAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingPersonal">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#personalSection">
                Personal Details
              </button>
            </h2>
            <div id="personalSection" class="accordion-collapse collapse show" data-bs-parent="#previewAccordion">
              <div class="accordion-body">
                <table class="table table-bordered">
                  <tbody id="previewPersonal"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingContact">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contactSection">
                Contact / Adress
              </button>
            </h2>
            <div id="contactSection" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
              <div class="accordion-body">
                <table class="table table-bordered">
                  <tbody id="previewContact"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingGuardian">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guardianSection">
                Guardian Details
              </button>
            </h2>
            <div id="guardianSection" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
              <div class="accordion-body">
                <table class="table table-bordered">
                  <tbody id="previewGuardian"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingEmergency">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#emergencySection">
                Emergency Contact Details
              </button>
            </h2>
            <div id="emergencySection" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
              <div class="accordion-body">
                <table class="table table-bordered">
                  <tbody id="previewEmergency"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="headingAdmission">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#admissionSection">
                Admission Details
              </button>
            </h2>
            <div id="admissionSection" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
              <div class="accordion-body">
                <table class="table table-bordered">
                  <tbody id="previewAdmission"></tbody>
                </table>
              </div>
            </div>
          </div>


        </div> <!-- end accordion -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="confirmSubmit" class="btn btn-primary">Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

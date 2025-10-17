<style>
  .offcanvas-custom {
    position: fixed;
    top: 10%;
    left: 45%;
    transform: translate(50%, 50%);
    padding: 10px;
    width: 400px; /* Set your desired width */
    max-height: 80%; /* Optional: limit height */
    overflow-y: auto; /* Optional: enable scrolling */
}

.students-container {
    display: flex;
    justify-content: center;

}
.create-student {
    width: 40%;
    display: block;
    margin-right: 5px;
}

.list-student {

}
</style>



        <div class="list-student">
            <!--Alerts offcanvas-->
            <div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
            <div class="offcanvas-body">
                <div id="alert-box" class="alert">
                    <button id="btn-close" type="button" class="btn-close bg-white close-alert float-end"  data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    <div class="alert-message" id="alert-message"></div>
                </div>
            </div>
            </div>

            <!--Import Student form-->
            <div class="offcanvas offcanvas-custom" data-bs-backdrop="static" tabindex="-1" id="staticBackdrop" aria-labelledby="staticBackdropLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasExampleLabel"><h3>Import Students</h3></h5>
                    
                    <button type="button" class="btn-close text-reset" onclick="resetUpload()" data-bs-dismiss="offcanvas" aria-label="Close" id="close-upload"></button>
                </div>
                <div class="offcanvas-body">
                <div id="importResult"></div>

                <div id="previewArea"  style="margin-top:20px; display:none;">
                        <h4 id="preview-header">Preview</h4>
                        <table border="2" cellpadding="5" id="previewTable" class="table table-bordered"></table>
                        
                    </div>

                <form id="importForm" enctype="multipart/form-data">
                    <input type="file" name="csv_file" id="choose_file" accept=".csv" class="btn btn-sm p-0" required>
                    <button type="submit" id="btn-preview" class="btn btn-success btn-sm p-0 px-1" onclick="uploadPreview()">Preview Data</button>
                    <div class="w-100 d-flex justify-content-center">
                            <button id="confirmImport" class="btn btn-success btn-sm p-0 px-1" onclick="importStudents()">Upload</button>
                            <button class="btn btn-reset btn-sm p-0 px-1" onclick="resetUpload()" id="btn-reset">Reset</button>
                    </div>
                    
                </form>
                
                </div>
            </div>

            
            
            <div class="d-flex justify-content-end mt-3">
                <!--Select rows per page-->
            <div>
                <label for="rowsPerPage" class="me-2">Rows per page:</label>
                <select id="rowsPerPage" class="form-select d-inline-block" style="width:auto;">
                    <option value="5">5</option>
                    <option value="7" selected>7</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div>
                <label for="studentStatus" class="me-2">Status</label>
                <select id="studentStatus" class="form-select d-inline-block" style="width:auto;">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="graduated">Graduated</option>

                    <option value="suspended">Suspended</option>
                    <option value="transferred">Transferred</option>
                    <option value="stopped">Stopped</option>
                </select>
            </div>
            
            <!--Search box-->
                <div class="search-student">
                    <div class="mb-3 d-flex justify-content-start">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by ID, name, or email...">
                    </div>  
                </div>
            <a href="/api/students/download-template?apiKey=devKey123" class="p-0 px-1 text-success text-bold text-decoration-none">Download template</a>
            <a href="/web/students/create" class="btn btn-primary p-1 mb-3 d-flex justify-content-end" style="font-size: 0.8em;"><?=icon('person-plus-fill')?></a>
            <a href="/web/students/download" class="btn btn-success p-1 mb-3 d-flex justify-content-end ms-1" onclick="exportStudents(event)" style="font-size: 0.8em;" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling"><?=icon('cloud-arrow-down-fill')?></a>
            <a href="/web/students/updload" class="btn btn-danger p-1 mb-3 d-flex justify-content-end ms-1"  style="font-size: 0.8em;" data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop" aria-controls="staticBackdrop"><?=icon('cloud-arrow-up-fill')?></a>
            </div>
            <div id="exportResult"></div>
            <table class="table table-bordered">
            <thead>
                <tr><td colspan="7" class="m-0 p-0"><h4 class="d-flex justify-content-center">Student List</h1></td></tr>
                <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Class</th>
                <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbody">
            <tr class="content-row">

            </tr>

            </tbody>
            </table>
        </div>
        
    </div>
    <?php  layout('spinner') ?>
</div>


<?php require_once('list-student-ajax.php');?>
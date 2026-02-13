<div class="container mt-4">

    <!--Alerts offcanvas-->
<div class="offcanvas offcanvas-end fade" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
  <div class="offcanvas-body">
    <div id="alert-box" class="alert">
        <button id="btn-close" type="button" class="btn-close bg-white close-alert float-end"  data-bs-dismiss="offcanvas" aria-label="Close"></button>
        <div class="alert-message" id="alert-message"></div>
    </div>
  </div>
</div>


    <h3>Create Student</h3>

    <form id="createStudentForm">
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="studentTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#personal">Personal</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#contact">Contact / address</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#guardian">Parents Details</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#emergency">Emergency Contact</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#system">Admission Info</a></li>
        </ul>

        <div class="tab-content border p-3 mt-2">
            <!-- Personal Tab -->
            <div class="tab-pane fade show active" id="personal">
                <div class="row mb-2 ">
                    <div class="col-md-4 me-0"><label>Student No</label><input type="text" name="student_no" value="<?=$student_no ?? ''?>" class="form-control me-0" readonly></div>
                    <div class="col-md-2 mt-4"><a href="#Search student Globally" class="btn ms-0" data-bs-toggle="tooltip" data-bs-title="Search student globally" data-bs-placement="top" id="btnSearch"  style="background-color: lightgreen;color:white;font-weight:bolder"><?=icon('search')?></a></div>   
                    
                    <div class="col-md-6"><label>NHIS Number <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="nhis_no" class="form-control" ></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><label>First Name <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="first_name" class="form-control" ></div>
                    <div class="col-md-4"><label>Last Name <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="last_name" class="form-control" ></div>
                    <div class="col-md-4"><label>Other Name</label><input type="text" name="other_name" class="form-control"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><label>DOB <span style="color: red; font-weight: bolder">*</span></label><input type="date" id="dob" name="dob" class="form-control" ></div>
                    <div class="col-md-4">  
                        <label>Age</label>
                        <input type="text" id="age" class="form-control" readonly>                        
                    </div>
                    <div class="col-md-4">
                        <label>Gender <span style="color: red; font-weight: bolder">*</span></label>
                        <select name="gender" class="form-control" >
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    
                    
                </div>
                <!-- <a type="submit" id="btn-personal-details" class="btn btn-success mt-3">Next</a> -->
                
            </div>

            <!-- Contact Tab -->
            <div class="tab-pane fade" id="contact">
                <div class="row mb-2">
                    <div class="col-md-6"><label>Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-6"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><label>Country <span style="color: red; font-weight: bolder">*</span></label>
                        <select name="country_id" class="form-control" >
                            <?php foreach ($countries as $country) :?>
                                <option value="<?=$country['country_id']?>" <?=$country['name'] == 'Ghana' ? 'selected' : ''?>><?=esc($country['name'])?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label>City</label><input type="text" name="city" class="form-control"></div>
                    <div class="col-md-4"><label>Hometown</label><input type="text" name="hometown" class="form-control"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><label>Residence <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="residence" class="form-control" ></div>
                    <div class="col-md-3"><label>House No <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="house_no" class="form-control" ></div>
                    <div class="col-md-3"><label>GPS No <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="gps_no" class="form-control" ></div>
                </div>
            </div>

            <!-- Guardian Tab -->
            <div class="tab-pane fade" id="guardian">
                <div class="row mb-2">
                    <div class="col-md-12"><h5 class="text-decoration-underline">Father's Details</h5></div>
                </div>
               <div class="row mb-2">
                    <div class="col-md-6"><label>Name <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="father_name" class="form-control" ></div>
                    <div class="col-md-6"><label>Phone <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="father_phone" class="form-control" ></div>
                    
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><label>Email</label><input type="text" name="father_email" class="form-control"></div>
                    
                </div>

                <div class="row mb-2">
                    <div class="col-md-12"><h5 class="text-decoration-underline">Mother's Details</h5></div>
                </div>
               <div class="row mb-2">
                    <div class="col-md-6"><label>Name <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="mother_name" class="form-control" ></div>
                    <div class="col-md-6"><label>Phone <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="mother_phone" class="form-control" ></div>
                    
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><label>Email</label><input type="text" name="mother_email" class="form-control"></div>
                </div>
            </div>

             <!-- Emergenct contact Tab -->
            <div class="tab-pane fade" id="emergency">
               <div class="row mb-2">
                    <div class="col-md-6"><label>Name <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="emergency_name" class="form-control" ></div>
                    <div class="col-md-6"><label>Phone <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="emergency_phone" class="form-control" ></div>
                    
                </div>
                <div class="row mb-2">
                    <div class="col-md-6"><label>Email</label><input type="text" name="emergency_email" class="form-control"></div>
                    <div class="col-md-6"><label>Relationship <span style="color: red; font-weight: bolder">*</span></label>
                        <select name="emergency_relationship" class="form-control" >
                            <option value="">Select</option>
                            <option value="father">Father</option>
                            <option value="mother">Mother</option>
                            <option value="sister">Sister</option>
                            <option value="brother">Brother</option>
                            <option value="uncle">Uncle</option>
                            <option value="auntie">Auntie</option>
                            <option value="auntie">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" disabled hidden><label>Specify (If Other) <span style="color: red; font-weight: bolder">*</span></label><input type="text" name="emergency_relationship_other" class="form-control"></div>
            </div>


            <!-- Admission Tab -->
            <div class="tab-pane fade" id="system">
                <div class="row mb-2">
                    <div class="col-md-6"><label>Admission No</label><input type="text" name="admission_no" class="form-control" ></div>
                    <div class="col-md-4"><label>Class <span style="color: red; font-weight: bolder">*</span></label>
                        <select name="class_id" class="form-control" >
                            <?php foreach ($classes as $class) :?>
                                <option value="<?=$class['class_id']?>"><?=esc($class['class_name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="row mb-2">
                    <div class="col-md-6"><label>Enrollment Date <span style="color: red; font-weight: bolder">*</span></label><input type="date" name="enrollment_date" class="form-control" ></div>
                    <div class="col-md-6">
                        <label>Admission Status <span style="color: red; font-weight: bolder">*</span></label>
                        <select name="status" class="form-control" >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="inactive">Pending</option>
                            <option value="inactive">Suspended</option>
                            <option value="inactive">Transferred</option>
                            <option value="inactive">Stopped</option>
                        </select>
                    </div>
                </div>
                    
                </div>
            </div>
        </div>
        
        <div class="w-100 d-flex justify-content-center"><button type="submit" id="save-student" class="btn btn-success mt-3">Save Student</button></div>
    </form>
    <?php layout('spinner');  require_once('preview.view.php')?>
    <?php ?>
</div>

<?php require_once('create-ajax.php') ?>
